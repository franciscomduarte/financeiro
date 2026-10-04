<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Actions\EnviarResumoVencimentosAction;
use App\DTOs\AlertaVencimento;
use App\Enums\TipoAlertaVencimento;
use App\Mail\ResumoVencimentosMail;
use App\Models\ContaConsumo;
use App\Models\ContaConsumoFatura;
use App\Models\Contrato;
use App\Models\ContratoPagamento;
use App\Models\Documento;
use App\Models\DocumentoCategoria;
use App\Models\ObrigacaoFiscal;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\User;
use App\Services\AlertasVencimentoService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class AlertasVencimentoTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $hoje;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoje = CarbonImmutable::parse('2026-10-10');
        $this->travelTo($this->hoje->setTime(8, 0));
        $this->clinica->update(['whatsapp_numero' => '+55 (61) 99999-0000']);
        Cache::flush();
    }

    /** @return array{vencidos: list<string>, a_vencer: list<string>} títulos por grupo */
    private function titulos(): array
    {
        $r = app(AlertasVencimentoService::class)->levantar($this->hoje);

        return [
            'vencidos' => $r['vencidos']->map(fn (AlertaVencimento $a) => $a->titulo)->all(),
            'a_vencer' => $r['a_vencer']->map(fn (AlertaVencimento $a) => $a->titulo)->all(),
        ];
    }

    private function despesa(string $descricao, string $data, string $status = 'pendente'): void
    {
        app(CreateTransacaoAction::class)->execute([
            'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'Insumos', 'descricao' => $descricao,
            'valor_bruto' => 100, 'forma_pagamento' => 'pix', 'data_competencia' => $data, 'status' => $status,
        ]);
    }

    // ─── Fontes ──────────────────────────────────────────────────

    public function test_despesas_pendentes_ate_3_dias_e_vencidas(): void
    {
        $this->despesa('Vence em 3 dias', '2026-10-13');
        $this->despesa('Vence em 4 dias', '2026-10-14');
        $this->despesa('Venceu ontem', '2026-10-09');
        $this->despesa('Já paga', '2026-10-11', 'pago');

        $this->assertSame(['vencidos' => ['Venceu ontem'], 'a_vencer' => ['Vence em 3 dias']], $this->titulos());
    }

    public function test_faturas_e_guias_nao_pagas(): void
    {
        $conta = ContaConsumo::create(['tipo' => 'luz', 'descricao' => 'Energia', 'dia_vencimento' => 12]);
        ContaConsumoFatura::create(['conta_consumo_id' => $conta->id, 'competencia' => '2026-09', 'data_vencimento' => '2026-10-12', 'valor' => 300, 'status' => 'pendente']);
        ContaConsumoFatura::create(['conta_consumo_id' => $conta->id, 'competencia' => '2026-08', 'data_vencimento' => '2026-09-12', 'valor' => 280, 'status' => 'paga']);

        $das = ObrigacaoFiscal::create(['tipo_tributo' => 'das_simples', 'descricao' => 'DAS', 'periodicidade' => 'mensal']);
        ObrigacaoFiscalLancamento::create(['obrigacao_fiscal_id' => $das->id, 'competencia' => '2026-09', 'data_vencimento' => '2026-10-08', 'valor_principal' => 500, 'status' => 'pendente']);

        $this->assertSame(['vencidos' => ['DAS — ' . ObrigacaoFiscalLancamento::first()->competenciaFormatada()], 'a_vencer' => ['Energia — Set/2026']], $this->titulos());
    }

    public function test_contrato_perto_do_fim(): void
    {
        Contrato::factory()->create(['data_inicio' => '2025-01-01', 'data_fim' => '2026-11-30', 'dia_vencimento' => null]); // 51 dias
        Contrato::factory()->create(['data_inicio' => '2025-01-01', 'data_fim' => '2027-03-01', 'dia_vencimento' => null]); // longe
        Contrato::factory()->create(['data_inicio' => '2025-01-01', 'data_fim' => '2026-10-20', 'dia_vencimento' => null, 'status' => 'encerrado']);

        $r = app(AlertasVencimentoService::class)->levantar($this->hoje);

        $this->assertCount(1, $r['a_vencer']);
        $this->assertSame(TipoAlertaVencimento::Contrato, $r['a_vencer'][0]->tipo);
    }

    public function test_mensalidade_de_contrato_ignora_mes_ja_pago(): void
    {
        $aluguel = Contrato::factory()->create(['data_inicio' => '2025-01-01', 'data_fim' => null, 'dia_vencimento' => 12, 'valor_mensal' => 3000]);
        $outro   = Contrato::factory()->create(['data_inicio' => '2025-01-01', 'data_fim' => null, 'dia_vencimento' => 11, 'valor_mensal' => 900]);
        ContratoPagamento::create(['contrato_id' => $outro->id, 'competencia' => '2026-10', 'valor' => 900, 'data_pagamento' => '2026-10-05', 'forma_pagamento' => 'pix']);

        $r = app(AlertasVencimentoService::class)->levantar($this->hoje);

        $pagamentos = $r['a_vencer']->filter(fn ($a) => $a->tipo === TipoAlertaVencimento::PagamentoContrato)->values();
        $this->assertCount(1, $pagamentos);
        $this->assertSame('2026-10-12', $pagamentos[0]->data->toDateString());
        $this->assertSame(3000.0, $pagamentos[0]->valor);
        $this->assertStringContainsString('10/2026', $pagamentos[0]->titulo);
        $this->assertNotNull($aluguel);
    }

    public function test_documentos_respeitam_prazo_de_alerta_de_cada_um(): void
    {
        $cat = DocumentoCategoria::create(['nome' => 'Alvarás', 'alerta_dias_antes' => 30]);
        Documento::create(['categoria_id' => $cat->id, 'titulo' => 'Alvará (25 dias)', 'data_validade' => '2026-11-04', 'status' => 'vigente']);
        Documento::create(['categoria_id' => $cat->id, 'titulo' => 'Licença (20 dias, alerta 10)', 'data_validade' => '2026-10-30', 'alerta_dias_antes' => 10, 'status' => 'vigente']);
        Documento::create(['categoria_id' => $cat->id, 'titulo' => 'Vencido', 'data_validade' => '2026-10-01', 'status' => 'vigente']);
        Documento::create(['categoria_id' => $cat->id, 'titulo' => 'Arquivado', 'data_validade' => '2026-10-11', 'status' => 'arquivado']);

        $this->assertSame(['vencidos' => ['Vencido'], 'a_vencer' => ['Alvará (25 dias)']], $this->titulos());
    }

    // ─── Envio ───────────────────────────────────────────────────

    public function test_envia_email_aos_admins_e_whatsapp(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['ok' => true])]);
        User::factory()->create(['email' => 'gestora@clinica.com', 'role' => 'admin', 'active' => true]);
        User::factory()->create(['email' => 'recepcao@clinica.com', 'role' => 'user', 'active' => true]);
        $this->despesa('Aluguel', '2026-10-11');

        $r = app(EnviarResumoVencimentosAction::class)->execute($this->hoje);

        $this->assertSame(['itens' => 1, 'email' => true, 'whatsapp' => true], $r);
        Mail::assertSent(ResumoVencimentosMail::class, fn ($m) => $m->hasTo('gestora@clinica.com') && ! $m->hasTo('recepcao@clinica.com'));
        Http::assertSent(fn ($req) => $req['number'] === '5561999990000'
            && str_contains($req['textMessage']['text'], 'Aluguel')
            && str_contains($req['textMessage']['text'], 'vence amanhã'));
    }

    public function test_sem_vencimentos_nao_envia_nada(): void
    {
        Mail::fake();
        Http::fake();

        $r = app(EnviarResumoVencimentosAction::class)->execute($this->hoje);

        $this->assertSame(0, $r['itens']);
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_nao_reenvia_no_mesmo_dia(): void
    {
        Mail::fake();
        Http::fake(['*' => Http::response(['ok' => true])]);
        User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->despesa('Aluguel', '2026-10-11');

        app(EnviarResumoVencimentosAction::class)->execute($this->hoje);
        $segunda = app(EnviarResumoVencimentosAction::class)->execute($this->hoje);

        $this->assertSame(['itens' => 1, 'email' => null, 'whatsapp' => null], $segunda);
        Mail::assertSentCount(1);
        Http::assertSentCount(1);
    }

    public function test_falha_no_whatsapp_tenta_de_novo_sem_reenviar_email(): void
    {
        Mail::fake();
        Http::fakeSequence()->push('erro', 500)->push(['ok' => true]);
        User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->despesa('Aluguel', '2026-10-11');

        try {
            app(EnviarResumoVencimentosAction::class)->execute($this->hoje);
            $this->fail('Deveria lançar exceção para o Job tentar de novo');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('whatsapp', $e->getMessage());
        }

        $retry = app(EnviarResumoVencimentosAction::class)->execute($this->hoje);

        $this->assertSame(['itens' => 1, 'email' => null, 'whatsapp' => true], $retry);
        Mail::assertSentCount(1);
    }

    public function test_dashboard_mostra_os_vencimentos(): void
    {
        $this->despesa('Conta de teste vencida', '2026-10-05');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Conta de teste vencida')
            ->assertSee('Venceu há 5 dias');
    }
}
