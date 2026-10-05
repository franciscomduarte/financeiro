<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CriarLancamentoRecorrenteAction;
use App\Actions\GerarLancamentosRecorrentesAction;
use App\Actions\GerenciarRecorrenciaAction;
use App\Enums\StatusClinica;
use App\Livewire\RecorrenciaIndex;
use App\Livewire\TransacaoIndex;
use App\Models\Recorrencia;
use App\Models\Transacao;
use App\Models\User;
use App\Support\ClinicaAtual;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Lançamentos recorrentes: modelo + geração mensal automática. */
class LancamentosRecorrentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-05 10:00'));
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'Infraestrutura', 'descricao' => 'Aluguel',
            'valor_bruto' => 3000, 'forma_pagamento' => 'pix', 'data_competencia' => '2026-10-10',
            'status' => 'pendente', 'recorrencia' => 'mensal',
        ];
    }

    private function criar(array $extra = [], ?string $ate = null, bool $pago = false): Transacao
    {
        return app(CriarLancamentoRecorrenteAction::class)->execute($this->dados($extra), $ate, $pago);
    }

    private function datas(): array
    {
        return Transacao::orderBy('data_competencia')->pluck('data_competencia')->map->toDateString()->all();
    }

    private function gerarEm(string $data): int
    {
        $this->travelTo(CarbonImmutable::parse($data . ' 06:00'));

        return app(GerarLancamentosRecorrentesAction::class)->execute();
    }

    public function test_cria_o_primeiro_e_gera_um_por_mes_no_inicio_de_cada_mes(): void
    {
        $this->criar();
        $this->assertSame(['2026-10-10'], $this->datas());

        $this->assertSame(1, $this->gerarEm('2026-11-01'));
        $this->assertSame(0, $this->gerarEm('2026-11-02')); // idempotente
        $this->gerarEm('2026-12-01');

        $this->assertSame(['2026-10-10', '2026-11-10', '2026-12-10'], $this->datas());
        $this->assertTrue(Transacao::where('data_competencia', '2026-12-10')->first()->status->value === 'pendente');
    }

    public function test_dia_31_cai_no_ultimo_dia_dos_meses_curtos(): void
    {
        $this->criar(['data_competencia' => '2026-10-31']);
        $this->gerarEm('2026-11-01');
        $this->gerarEm('2026-12-01');
        $this->gerarEm('2027-02-01');

        $this->assertSame(['2026-10-31', '2026-11-30', '2026-12-31', '2027-01-31', '2027-02-28'], $this->datas());
    }

    public function test_data_passada_nao_cria_meses_atrasados(): void
    {
        $this->criar(['data_competencia' => '2026-08-15']);

        // Agosto (o que a pessoa lançou) + outubro (mês atual); setembro não é criado
        $this->assertSame(['2026-08-15', '2026-10-15'], $this->datas());
    }

    public function test_frequencias_trimestral_e_anual(): void
    {
        $this->criar(['descricao' => 'Contador', 'recorrencia' => 'trimestral', 'data_competencia' => '2026-10-20']);
        $this->criar(['descricao' => 'Anuidade', 'recorrencia' => 'anual', 'data_competencia' => '2026-10-25']);
        foreach (['2026-11-01', '2026-12-01', '2027-01-01', '2027-02-01', '2027-03-01', '2027-04-01'] as $d) {
            $this->gerarEm($d);
        }

        $this->assertSame(['2026-10-20', '2027-01-20', '2027-04-20'], Transacao::where('descricao', 'Contador')->orderBy('data_competencia')->pluck('data_competencia')->map->toDateString()->all());
        $this->assertSame(1, Transacao::where('descricao', 'Anuidade')->count());
    }

    public function test_termina_na_data_final(): void
    {
        $this->criar([], '2026-12-15');
        foreach (['2026-11-01', '2026-12-01', '2027-01-01'] as $d) {
            $this->gerarEm($d);
        }

        $this->assertSame(['2026-10-10', '2026-11-10', '2026-12-10'], $this->datas());
        $this->assertTrue(Recorrencia::first()->encerrada());
    }

    public function test_lancar_como_pago_para_debito_automatico(): void
    {
        $this->criar([], null, pago: true);
        $this->gerarEm('2026-11-01');

        $novo = Transacao::where('data_competencia', '2026-11-10')->first();
        $this->assertSame('pago', $novo->status->value);
        $this->assertSame('2026-11-10', $novo->data_pagamento->toDateString());
    }

    public function test_editar_vale_dos_proximos_em_diante(): void
    {
        $this->criar();
        $r = Recorrencia::first();

        app(GerenciarRecorrenciaAction::class)->atualizar($r, [
            'descricao' => 'Aluguel reajustado', 'categoria' => 'Infraestrutura', 'valor_bruto' => 3300.0,
            'forma_pagamento' => 'pix', 'data_fim' => null, 'lancar_como_pago' => false, 'observacoes' => null,
        ]);
        $this->gerarEm('2026-11-01');

        $this->assertSame('3000.00', Transacao::where('data_competencia', '2026-10-10')->first()->valor_bruto);
        $novo = Transacao::where('data_competencia', '2026-11-10')->first();
        $this->assertSame('3300.00', $novo->valor_bruto);
        $this->assertSame('Aluguel reajustado', $novo->descricao);
    }

    public function test_pausar_e_retomar_nao_cria_os_meses_pausados(): void
    {
        $this->criar();
        $acao = app(GerenciarRecorrenciaAction::class);

        $acao->pausar(Recorrencia::first());
        $this->gerarEm('2026-11-01');
        $this->gerarEm('2026-12-01');
        $this->assertSame(['2026-10-10'], $this->datas());

        $this->travelTo(CarbonImmutable::parse('2027-01-03'));
        $acao->retomar(Recorrencia::first());

        $this->assertSame(['2026-10-10', '2027-01-10'], $this->datas());
    }

    public function test_encerrar_para_de_gerar(): void
    {
        $this->criar();
        app(GerenciarRecorrenciaAction::class)->encerrar(Recorrencia::first());
        $this->gerarEm('2026-11-01');

        $this->assertSame(1, Transacao::count());
    }

    public function test_parcelado_no_cartao_nao_pode_repetir(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->criar(['forma_pagamento' => 'credito_3x']);
    }

    public function test_geracao_respeita_cada_clinica(): void
    {
        $this->criar();
        $outra = $this->novaClinica('Outra');
        app(ClinicaAtual::class)->executarComo($outra, fn () => $this->criar(['descricao' => 'Aluguel da outra']));

        $this->travelTo(CarbonImmutable::parse('2026-11-01 06:00'));
        app(ClinicaAtual::class)->paraCadaClinica(fn () => app(GerarLancamentosRecorrentesAction::class)->execute());

        $this->assertSame(['Aluguel', 'Aluguel'], Transacao::pluck('descricao')->all());
        $this->assertSame(2, app(ClinicaAtual::class)->executarComo($outra, fn () => Transacao::count()));
    }

    public function test_clinica_com_teste_encerrado_nao_gera(): void
    {
        $this->criar();
        $this->clinica->update(['status' => StatusClinica::Teste, 'teste_ate' => '2026-10-20']);

        $this->travelTo(CarbonImmutable::parse('2026-11-01 06:00'));
        app(ClinicaAtual::class)->paraCadaClinica(fn () => app(GerarLancamentosRecorrentesAction::class)->execute());

        $this->assertSame(1, Transacao::count());
    }

    // ─── Telas ────────────────────────────────────────────────────

    public function test_formulario_cria_lancamento_que_repete(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TransacaoIndex::class)
            ->call('abrirModalCriar')
            ->set('tipo', 'saida')->set('categoria', 'Infraestrutura')->set('descricao', 'Internet')
            ->set('valorBruto', '120')->set('formaPagamento', 'pix')->set('dataCompetencia', '2026-10-15')
            ->set('recorrencia', 'mensal')->set('recorrenciaAte', '2027-03-31')->set('recorrenciaPago', true)
            ->call('salvarNova')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', fn ($m) => str_contains((string) $m, 'todo mês'));

        $r = Recorrencia::firstOrFail();
        $this->assertSame('Internet', $r->descricao);
        $this->assertSame('2027-03-31', $r->data_fim->toDateString());
        $this->assertTrue($r->lancar_como_pago);
        $this->assertSame(1, $r->lancamentos()->count());
    }

    public function test_tela_de_recorrencias_lista_pausa_e_edita(): void
    {
        $this->actingAs(User::factory()->create());
        $this->criar();
        $id = Recorrencia::first()->id;

        Livewire::test(RecorrenciaIndex::class)
            ->assertSee('Aluguel')
            ->assertViewHas('saidasMensais', 3000.0)
            ->call('pausar', $id)
            ->assertSet('flashSucesso', fn ($m) => str_contains((string) $m, 'pausada'))
            ->call('editar', $id)
            ->set('valorBruto', '3.200,50')
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertFalse(Recorrencia::first()->ativa);
        $this->assertSame('3200.50', Recorrencia::first()->valor_bruto);

        $this->get('/transacoes/recorrencias')->assertOk()->assertSee('Recorrências');
    }

    public function test_tela_vazia_convida_a_cadastrar(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(RecorrenciaIndex::class)->assertSee('Nenhuma conta fixa ainda');
    }
}
