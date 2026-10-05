<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Comissoes\CalcularComissoesAction;
use App\Actions\Comissoes\FecharComissaoAction;
use App\Actions\ConcluirAtendimentoAction;
use App\Actions\CreateTransacaoAction;
use App\Enums\StatusOrcamento;
use App\Enums\StatusPacote;
use App\Livewire\AgendamentoIndex;
use App\Livewire\ComissaoIndex;
use App\Livewire\OrcamentoIndex;
use App\Livewire\PacoteIndex;
use App\Models\Agendamento;
use App\Models\ComissaoFechamento;
use App\Models\Orcamento;
use App\Models\Pacote;
use App\Models\PacoteSessao;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\User;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** Pacotes de sessões, orçamentos e comissões dos profissionais. */
class PacotesOrcamentosComissoesTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;
    private Profissional $ana;
    private Procedimento $laser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->paciente = Paciente::create(['nome' => 'Maria Silva', 'telefone' => '(61) 99999-0000'])->fresh();
        $this->ana      = $this->profissional('Ana Souza', 30);
        $this->laser    = Procedimento::create(['nome' => 'Laser', 'duracao_minutos' => 30, 'valor' => 200, 'ativo' => true]);
    }

    private function profissional(string $nome, float $comissao = 0): Profissional
    {
        $p     = new Profissional(['nome' => $nome, 'email' => Str::slug($nome) . '@x.com', 'ativo' => true, 'comissao_percentual' => $comissao]);
        $p->id = (string) Str::uuid();
        $p->save();

        return $p;
    }

    private function agendar(string $quando = '2026-09-10 10:00', ?Paciente $paciente = null, ?Profissional $prof = null): Agendamento
    {
        return Agendamento::create([
            'paciente_id'     => ($paciente ?? $this->paciente)->id,
            'profissional_id' => ($prof ?? $this->ana)->id,
            'procedimento_id' => $this->laser->id,
            'inicio_em'       => $quando,
            'fim_em'          => date('Y-m-d H:i', strtotime($quando) + 1800),
            'status'          => 'confirmado',
        ]);
    }

    private function venderPacote(int $sessoes = 3, float $valor = 600, bool $pago = true): Pacote
    {
        Livewire::test(PacoteIndex::class)
            ->call('novo')
            ->call('escolherPaciente', $this->paciente->id)
            ->set('procedimentoId', (string) $this->laser->id)
            ->set('sessoes', (string) $sessoes)
            ->set('valorTotal', (string) $valor)
            ->set('categoria', 'Outros')
            ->set('pago', $pago)
            ->call('vender')
            ->assertHasNoErrors()
            ->assertSet('flashSucesso', 'Pacote vendido. A receita já está em Lançamentos.');

        return Pacote::latest()->firstOrFail();
    }

    // ─── Pacotes ────────────────────────────────────────────────

    public function test_vender_pacote_lanca_a_receita_uma_vez_e_cria_o_saldo(): void
    {
        $pacote = $this->venderPacote(3, 600);

        $this->assertSame("3 sessões de Laser", $pacote->nome);
        $this->assertSame(3, $pacote->saldo());
        $receita = Transacao::findOrFail($pacote->transacao_id);
        $this->assertSame('entrada', $receita->tipo->value);
        $this->assertSame('600.00', $receita->valor_bruto);
        $this->assertSame($this->paciente->id, $receita->paciente_id);
    }

    public function test_concluir_atendimento_com_pacote_desconta_sessao_sem_nova_receita(): void
    {
        $pacote = $this->venderPacote(2, 400);
        $a1     = $this->agendar('2026-09-10 10:00');
        $a2     = $this->agendar('2026-09-17 10:00');

        // A agenda já sugere o pacote do mesmo procedimento
        Livewire::test(AgendamentoIndex::class)
            ->call('abrirModalConcluir', $a1->id)
            ->assertSet('concluirPacoteId', $pacote->id)
            ->assertSet('concluirLancarReceita', false)
            ->call('confirmarConclusao')
            ->assertSet('flashSucesso', 'Atendimento concluído e sessão descontada do pacote.');

        $this->assertSame(1, Transacao::count());
        $this->assertSame(1, $pacote->fresh()->sessoes_usadas);
        $this->assertSame('realizado', $a1->fresh()->status->value);

        app(ConcluirAtendimentoAction::class)->execute($a2->id, null, $pacote->id);
        $this->assertSame(StatusPacote::Concluido, $pacote->fresh()->status);
        $this->assertSame(0, Pacote::utilizaveis()->count());
    }

    public function test_pacote_de_outro_paciente_ou_sem_saldo_nao_e_aceito(): void
    {
        $pacote = $this->venderPacote(1, 200);
        $outro  = Paciente::create(['nome' => 'João'])->fresh();

        try {
            app(ConcluirAtendimentoAction::class)->execute($this->agendar('2026-09-10 10:00', $outro)->id, null, $pacote->id);
            $this->fail('Pacote de outro paciente não deveria valer');
        } catch (RuntimeException $e) {
            $this->assertSame('Esse pacote é de outro paciente.', $e->getMessage());
        }

        app(ConcluirAtendimentoAction::class)->execute($this->agendar('2026-09-11 10:00')->id, null, $pacote->id);

        $this->expectExceptionMessage('Esse pacote não tem sessões disponíveis.');
        app(ConcluirAtendimentoAction::class)->execute($this->agendar('2026-09-12 10:00')->id, null, $pacote->id);
    }

    public function test_cancelar_pacote_zera_o_uso_e_mantem_a_receita(): void
    {
        $pacote = $this->venderPacote();

        Livewire::test(PacoteIndex::class)->call('cancelar', $pacote->id)->assertSet('flashErro', null);

        $this->assertSame(StatusPacote::Cancelado, $pacote->fresh()->status);
        $this->assertSame(1, Transacao::count());
    }

    // ─── Orçamentos ─────────────────────────────────────────────

    private function criarOrcamento(): Orcamento
    {
        Livewire::test(OrcamentoIndex::class)
            ->call('novo')
            ->call('escolherPaciente', $this->paciente->id)
            ->set('itens.0.procedimento_id', (string) $this->laser->id)
            ->assertSet('itens.0.descricao', 'Laser')
            ->assertSet('itens.0.valor_unitario', '200.00')
            ->set('itens.0.quantidade', '5')
            ->call('adicionarItem')
            ->set('itens.1.descricao', 'Avaliação')
            ->set('itens.1.quantidade', '1')
            ->set('itens.1.valor_unitario', '100')
            ->set('desconto', '110')
            ->call('salvar')
            ->assertHasNoErrors();

        return Orcamento::latest()->firstOrFail();
    }

    public function test_orcamento_calcula_total_e_numera_por_clinica(): void
    {
        $o = $this->criarOrcamento();

        $this->assertSame(1, $o->numero);
        $this->assertSame('1100.00', $o->subtotal);
        $this->assertSame('990.00', $o->total);
        $this->assertCount(2, $o->itens);
        $this->assertSame(2, $this->criarOrcamento()->numero);
    }

    public function test_aprovar_orcamento_gera_receita_e_pacotes_com_desconto_rateado(): void
    {
        $o = $this->criarOrcamento();

        Livewire::test(OrcamentoIndex::class)
            ->call('abrirAprovacao', $o->id)
            ->set('aprovarCategoria', 'Outros')
            ->set('aprovarPago', false)
            ->call('aprovar')
            ->assertHasNoErrors();

        $o->refresh();
        $this->assertSame(StatusOrcamento::Aprovado, $o->status);
        $receita = Transacao::findOrFail($o->transacao_id);
        $this->assertSame('990.00', $receita->valor_bruto);
        $this->assertSame('pendente', $receita->status->value);

        $pacotes = Pacote::where('orcamento_id', $o->id)->orderBy('sessoes_total', 'desc')->get();
        $this->assertCount(2, $pacotes);
        $this->assertSame(5, $pacotes[0]->sessoes_total);
        $this->assertSame('900.00', $pacotes[0]->valor_total); // 1000 × 0,9
        $this->assertSame('90.00', $pacotes[1]->valor_total);
        $this->assertSame(990.0, (float) $pacotes->sum('valor_total'));

        // Não aprova duas vezes nem edita depois de aprovado
        Livewire::test(OrcamentoIndex::class)
            ->call('abrirAprovacao', $o->id)->set('aprovarCategoria', 'Outros')->call('aprovar')
            ->assertSet('flashErro', 'Este orçamento já foi aprovado.')
            ->call('editar', $o->id)
            ->assertSet('flashErro', 'Só orçamentos em aberto podem ser alterados.');
        $this->assertSame(1, Transacao::count());
    }

    public function test_orcamento_por_whatsapp_e_pdf(): void
    {
        config(['evolution.url' => 'https://evo.exemplo.com']);
        $this->clinica->update(['evolution_instance' => 'lc', 'evolution_api_key' => 'chave-lc']);
        app(ClinicaAtual::class)->definir($this->clinica->fresh());
        Http::fake(['*' => Http::response(['ok' => true])]);
        $o = $this->criarOrcamento();

        Livewire::test(OrcamentoIndex::class)->call('enviarWhatsapp', $o->id)
            ->assertSet('flashSucesso', 'Orçamento enviado por WhatsApp.');

        Http::assertSent(fn ($r) => str_contains($r['textMessage']['text'] ?? '', 'Total: R$ 990,00'));
        $this->assertNotNull($o->fresh()->enviado_em);

        $pdf = $this->get(route('orcamentos.pdf', $o->id));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_desconto_maior_que_os_itens_e_recusado(): void
    {
        Livewire::test(OrcamentoIndex::class)
            ->call('novo')
            ->call('escolherPaciente', $this->paciente->id)
            ->set('itens.0.descricao', 'Avaliação')
            ->set('itens.0.valor_unitario', '100')
            ->set('desconto', '150')
            ->call('salvar')
            ->assertSet('flashErro', 'O desconto não pode passar do valor dos itens.');

        $this->assertSame(0, Orcamento::count());
    }

    // ─── Comissões ──────────────────────────────────────────────

    public function test_comissao_sobre_o_recebido_sem_taxa_inclui_sessoes_de_pacote_pagas(): void
    {
        TaxaCartao::create(['modalidade' => 'credito_1x', 'percentual' => 5, 'ativo' => true]);
        $bia = $this->profissional('Bia Lima', 40);

        // Atendimento avulso pago no cartão em setembro: base 500 − 5% = 475
        $a = $this->agendar('2026-09-05 10:00');
        app(ConcluirAtendimentoAction::class)->execute($a->id, ['valor_bruto' => 500, 'categoria' => 'Outros', 'forma_pagamento' => 'credito_1x', 'pago' => true]);
        Transacao::where('agendamento_id', $a->id)->update(['data_pagamento' => '2026-09-05']);

        // A receber não conta
        $b = $this->agendar('2026-09-06 10:00');
        app(ConcluirAtendimentoAction::class)->execute($b->id, ['valor_bruto' => 300, 'categoria' => 'Outros', 'forma_pagamento' => 'pix', 'pago' => false]);

        // Pacote pago (4 × 100 no pix): sessão feita pela Bia vale 100
        $pacote = $this->venderPacote(4, 400);
        app(ConcluirAtendimentoAction::class)->execute($this->agendar('2026-09-20 10:00', null, $bia)->id, null, $pacote->id);

        // Pacote a receber: sessão aparece como pendente, sem base
        $naoPago = $this->venderPacote(2, 400, false);
        app(ConcluirAtendimentoAction::class)->execute($this->agendar('2026-09-21 10:00', null, $bia)->id, null, $naoPago->id);

        $linhas = app(CalcularComissoesAction::class)->execute('2026-09')->keyBy('nome');

        $this->assertSame(475.0, $linhas['Ana Souza']['base']);
        $this->assertSame(1, $linhas['Ana Souza']['qtd_atendimentos']);
        $this->assertSame(142.5, $linhas['Ana Souza']['valor']);     // 30%
        $this->assertSame(100.0, $linhas['Bia Lima']['base_pacotes']);
        $this->assertSame(1, $linhas['Bia Lima']['sessoes_a_receber']);
        $this->assertSame(40.0, $linhas['Bia Lima']['valor']);       // 40%
    }

    public function test_fechar_comissao_lanca_despesa_uma_vez_so(): void
    {
        $a = $this->agendar('2026-09-05 10:00');
        app(ConcluirAtendimentoAction::class)->execute($a->id, ['valor_bruto' => 1000, 'categoria' => 'Outros', 'forma_pagamento' => 'pix', 'pago' => true]);
        Transacao::where('agendamento_id', $a->id)->update(['data_pagamento' => '2026-09-05']);

        Livewire::test(ComissaoIndex::class, ['competencia' => '2026-09'])
            ->set('competencia', '2026-09')
            ->call('fechar', $this->ana->id)
            ->assertSet('flashSucesso', 'Comissão fechada: R$ 300,00 lançados como despesa a pagar.');

        $f = ComissaoFechamento::sole();
        $despesa = Transacao::findOrFail($f->transacao_id);
        $this->assertSame('saida', $despesa->tipo->value);
        $this->assertSame('300.00', $despesa->valor_bruto);
        $this->assertSame('2026-09-30', $despesa->data_competencia->toDateString());

        // Mudar o percentual não altera o mês fechado; fechar de novo é bloqueado
        $this->ana->update(['comissao_percentual' => 50]);
        $this->assertSame(300.0, app(CalcularComissoesAction::class)->execute('2026-09')->firstWhere('nome', 'Ana Souza')['valor']);

        $this->expectExceptionMessage('A comissão deste mês já foi fechada.');
        app(FecharComissaoAction::class)->execute($this->ana->id, '2026-09');
    }

    public function test_mes_em_andamento_nao_fecha(): void
    {
        $this->expectExceptionMessage('Feche a comissão depois que o mês terminar.');
        app(FecharComissaoAction::class)->execute($this->ana->id, now()->format('Y-m'));
    }

    public function test_acesso_por_perfil(): void
    {
        $recepcao   = User::factory()->create(['role' => 'recepcao']);
        $financeiro = User::factory()->create(['role' => 'financeiro']);
        $prof       = User::factory()->create(['role' => 'profissional']);

        $this->actingAs($recepcao)->get('/orcamentos')->assertOk();
        $this->actingAs($recepcao)->get('/pacotes')->assertOk();
        $this->actingAs($recepcao)->get('/comissoes')->assertRedirect();
        $this->actingAs($financeiro)->get('/comissoes')->assertOk()->assertSee('Ana Souza');
        $this->actingAs($prof)->get('/orcamentos')->assertRedirect();
    }
}
