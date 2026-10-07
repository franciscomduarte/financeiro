<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Actions\Financeiro\BaixarTransacaoAction;
use App\Actions\Financeiro\EstornarBaixaAction;
use App\Actions\Financeiro\LiquidarRecebiveisCartaoAction;
use App\Actions\Financeiro\TitulosContasFixasAction;
use App\Actions\LancarFaturaAction;
use App\Actions\LancarGuiaFiscalAction;
use App\Actions\Pacotes\CancelarPacoteAction;
use App\Actions\PagarContratoAction;
use App\Actions\PagarFaturaAction;
use App\Enums\StatusFatura;
use App\Enums\StatusLancamentoFiscal;
use App\Enums\StatusTransacao;
use App\Enums\TipoAlertaVencimento;
use App\Enums\TipoContaFinanceira;
use App\Models\Cobranca;
use App\Models\ContaConsumo;
use App\Models\ContaFinanceira;
use App\Models\Contrato;
use App\Models\ContratoPagamento;
use App\Models\ObrigacaoFiscal;
use App\Models\Paciente;
use App\Models\Pacote;
use App\Models\RecebivelCartao;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\User;
use App\Services\AlertasVencimentoService;
use App\Services\FluxoCaixaService;
use App\Services\SaldoContasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Financeiro, fase 3: recebíveis de cartão, contas fixas no contas a pagar, Asaas e alertas. */
class FinanceiroFase3Test extends TestCase
{
    use RefreshDatabase;

    private ContaFinanceira $banco;
    private ContaFinanceira $maquininha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        ContaFinanceira::garantirPadroes();
        $this->banco      = ContaFinanceira::query()->where('tipo', 'banco')->firstOrFail();
        $this->maquininha = ContaFinanceira::query()->where('tipo', 'maquininha')->firstOrFail();
        TaxaCartao::query()->create(['modalidade' => 'credito_3x', 'percentual' => 5, 'ativo' => true]);
    }

    private function venda(array $dados = []): Transacao
    {
        return app(CreateTransacaoAction::class)->execute(array_merge([
            'tipo' => 'entrada', 'fase' => 'operacao', 'categoria' => 'Massagem', 'descricao' => 'Pacote Ana',
            'valor_bruto' => 900, 'forma_pagamento' => 'credito_3x', 'data_competencia' => today()->toDateString(),
            'status' => 'pago', 'data_pagamento' => today()->toDateString(),
        ], $dados));
    }

    public function test_venda_parcelada_vira_tres_recebiveis_e_libera_no_banco_na_data(): void
    {
        $t = $this->venda();

        $r = RecebivelCartao::query()->orderBy('parcela')->get();
        $this->assertCount(3, $r);                                   // 900 − 5% = 855 → 285 × 3
        $this->assertSame(['285.00', '285.00', '285.00'], $r->pluck('valor_liquido')->all());
        $this->assertSame(today()->addDays(30)->toDateString(), $r[0]->data_prevista->toDateString());
        $this->assertSame(today()->addDays(90)->toDateString(), $r[2]->data_prevista->toDateString());
        $this->assertSame($this->maquininha->id, $t->baixas()->first()->conta_financeira_id);

        // Projeção: disponível hoje não conta o que está na maquininha; cada parcela entra na data
        $p = app(FluxoCaixaService::class)->projetado(90);
        $this->assertSame(0.0, $p['saldo_hoje']);
        $this->assertSame(855.0, $p['a_liberar_cartao']);
        $this->assertSame(855.0, $p['entradas']);

        // Na data da 1ª parcela, ela passa para o banco
        $this->travel(30)->days();
        $this->assertSame(1, app(LiquidarRecebiveisCartaoAction::class)->execute());
        $saldos = app(SaldoContasService::class)->saldos();
        $this->assertSame(285.0, $saldos[$this->banco->id]);
        $this->assertSame(570.0, $saldos[$this->maquininha->id]);
        $this->assertSame(0, app(LiquidarRecebiveisCartaoAction::class)->execute()); // não repete

        // Já liberado: não dá para desfazer o recebimento sem desfazer a transferência
        $this->expectExceptionMessage('já foi liberada para o banco');
        app(EstornarBaixaAction::class)->execute($t->baixas()->first());
    }

    public function test_venda_antecipada_desconta_a_taxa_por_mes_e_lanca_a_despesa(): void
    {
        $this->maquininha->update(['taxa_antecipacao_mes' => 2, 'antecipacao_dias' => 1]);
        $this->venda(['antecipar_cartao' => true]);

        $r = RecebivelCartao::query()->sole();
        // 285 × 2% × (29/30 + 59/30 + 89/30 meses) = 5,51 + 11,21 + 16,91
        $this->assertTrue($r->antecipado);
        $this->assertSame('33.63', $r->taxa_antecipacao);
        $this->assertSame('821.37', $r->valor_liquido);
        $this->assertSame(today()->addDay()->toDateString(), $r->data_prevista->toDateString());

        $this->travel(1)->days();
        app(LiquidarRecebiveisCartaoAction::class)->execute();
        $saldos = app(SaldoContasService::class)->saldos();
        $this->assertSame(821.37, $saldos[$this->banco->id]);
        $this->assertSame(0.0, $saldos[$this->maquininha->id]);
        $despesa = Transacao::query()->findOrFail($r->fresh()->despesa_id);
        $this->assertSame('Taxa de antecipação', $despesa->categoria);
        $this->assertSame('despesas_financeiras', $despesa->planoConta->grupo->value);
    }

    public function test_recebimento_no_cartao_pelo_contas_a_receber_respeita_a_escolha(): void
    {
        $t = $this->venda(['status' => 'pendente', 'data_pagamento' => null]);
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 900, 'data' => today()->toDateString(),
            'conta_financeira_id' => $this->maquininha->id, 'forma_pagamento' => 'credito_3x', 'antecipar' => false]);

        $this->assertSame(3, RecebivelCartao::query()->count());

        // Estornar antes de liberar apaga os recebíveis
        app(EstornarBaixaAction::class)->execute($t->baixas()->first());
        $this->assertSame(0, RecebivelCartao::query()->count());
        $this->assertSame(StatusTransacao::Pendente, $t->fresh()->status);
    }

    public function test_fatura_e_guia_entram_no_contas_a_pagar_e_pagamento_sincroniza(): void
    {
        $conta  = ContaConsumo::create(['tipo' => 'luz', 'descricao' => 'Energia', 'dia_vencimento' => 12]);
        $fatura = app(LancarFaturaAction::class)->execute($conta, ['competencia' => '2026-09', 'data_vencimento' => '2026-10-12', 'valor' => 300]);

        $titulo = Transacao::query()->findOrFail($fatura->transacao_id);
        $this->assertSame(StatusTransacao::Pendente, $titulo->status);
        $this->assertSame('2026-10-12', $titulo->data_vencimento->toDateString());
        $this->assertSame('Energia Elétrica', $titulo->categoria);

        // Pagar pelo contas a pagar marca a fatura como paga; desfazer volta
        $baixa = app(BaixarTransacaoAction::class)->execute($titulo, ['valor' => 300, 'data' => '2026-10-10', 'conta_financeira_id' => $this->banco->id]);
        $this->assertSame(StatusFatura::Paga, $fatura->fresh()->status);
        app(EstornarBaixaAction::class)->execute($baixa);
        $this->assertSame(StatusFatura::Recebida, $fatura->fresh()->status);

        // Pagar pela tela de faturas quita o mesmo lançamento (sem criar outro)
        app(PagarFaturaAction::class)->execute($fatura->fresh(), ['forma_pagamento' => 'pix', 'data_pagamento' => '2026-10-11']);
        $this->assertSame(StatusTransacao::Pago, $titulo->fresh()->status);
        $this->assertSame(1, Transacao::query()->count());

        $das  = ObrigacaoFiscal::create(['tipo_tributo' => 'das_simples', 'descricao' => 'DAS', 'periodicidade' => 'mensal']);
        $guia = app(LancarGuiaFiscalAction::class)->execute($das, ['competencia' => '2026-09', 'data_vencimento' => '2026-10-20', 'valor_principal' => 500]);
        $tg = Transacao::query()->findOrFail($guia->transacao_id);
        $this->assertSame('Simples Nacional', $tg->categoria);
        app(BaixarTransacaoAction::class)->execute($tg, ['valor' => 500, 'data' => '2026-10-19', 'conta_financeira_id' => $this->banco->id]);
        $this->assertSame(StatusLancamentoFiscal::Pago, $guia->fresh()->status);
    }

    public function test_contrato_gera_a_conta_do_mes_e_pagar_registra_o_pagamento(): void
    {
        $contrato = Contrato::factory()->create(['status' => 'ativo', 'data_inicio' => '2025-01-01', 'data_fim' => null, 'dia_vencimento' => 10, 'valor_mensal' => 3000]);

        $this->assertSame(2, app(TitulosContasFixasAction::class)->execute()); // mês atual e próximo
        $this->assertSame(0, app(TitulosContasFixasAction::class)->execute()); // idempotente

        $mes = today()->startOfMonth();
        $titulo = Transacao::query()->where('contrato_id', $contrato->id)->where('data_competencia', $mes->toDateString())->firstOrFail();
        $this->assertSame($mes->day(10)->toDateString(), $titulo->data_vencimento->toDateString());

        app(PagarContratoAction::class)->execute($contrato, ['competencia' => $mes->format('Y-m'), 'forma_pagamento' => 'pix', 'data_pagamento' => $mes->day(9)->toDateString(), 'observacoes' => '']);

        $this->assertSame(StatusTransacao::Pago, $titulo->fresh()->status);
        $this->assertSame(1, ContratoPagamento::query()->where('contrato_id', $contrato->id)->count());
        $this->assertSame(2, Transacao::query()->where('contrato_id', $contrato->id)->count());

        // Pagamento do mês seguinte pelo contas a pagar também registra no contrato
        $proximo = Transacao::query()->where('contrato_id', $contrato->id)->where('status', 'pendente')->firstOrFail();
        app(BaixarTransacaoAction::class)->execute($proximo, ['valor' => 3000, 'data' => today()->toDateString(), 'conta_financeira_id' => $this->banco->id]);
        $this->assertSame(2, ContratoPagamento::query()->where('contrato_id', $contrato->id)->count());
    }

    public function test_alertas_sem_duplicar_e_com_recebimentos_atrasados(): void
    {
        $conta = ContaConsumo::create(['tipo' => 'luz', 'descricao' => 'Energia', 'dia_vencimento' => 12]);
        app(LancarFaturaAction::class)->execute($conta, ['competencia' => today()->format('Y-m'), 'data_vencimento' => today()->addDay()->toDateString(), 'valor' => 300]);
        $this->venda(['status' => 'pendente', 'data_pagamento' => null, 'data_competencia' => today()->subDays(5)->toDateString(), 'paciente_id' => Paciente::create(['nome' => 'Bia'])->id]);

        $alertas = app(AlertasVencimentoService::class)->levantar();
        $tipos = $alertas['vencidos']->concat($alertas['a_vencer'])->map(fn ($a) => $a->tipo)->all();

        $this->assertSame(1, collect($tipos)->filter(fn ($t) => in_array($t, [TipoAlertaVencimento::Fatura, TipoAlertaVencimento::Despesa], true))->count());
        $this->assertContains(TipoAlertaVencimento::Recebimento, $tipos);
        $this->assertStringContainsString('Bia', $alertas['vencidos']->firstWhere('tipo', TipoAlertaVencimento::Recebimento)->titulo);
    }

    public function test_cobranca_do_asaas_vira_conta_a_receber_e_o_pagamento_da_baixa(): void
    {
        $paciente = Paciente::create(['nome' => 'Carla']);
        $c = Cobranca::create(['paciente_id' => $paciente->id, 'asaas_id' => 'pay_1', 'valor' => 250, 'vencimento' => today()->addDays(5)->toDateString(),
            'mes_referencia' => today()->format('Y-m'), 'status' => 'PENDING']);

        $t = Transacao::query()->findOrFail($c->fresh()->transacao_id);
        $this->assertSame('Mensalidades', $t->categoria);
        $this->assertSame(StatusTransacao::Pendente, $t->status);

        $c->update(['status' => 'RECEIVED', 'pago_em' => now()]);
        $t->refresh();
        $this->assertSame(StatusTransacao::Pago, $t->status);
        $this->assertSame($this->banco->id, $t->baixas()->first()->conta_financeira_id);

        $c->update(['status' => 'REFUNDED']);
        $this->assertSame(StatusTransacao::Cancelado, $t->fresh()->status);
        $this->assertSame(0, $t->baixas()->count());

        $c2 = Cobranca::create(['paciente_id' => $paciente->id, 'asaas_id' => 'pay_2', 'valor' => 250, 'vencimento' => today()->toDateString(),
            'mes_referencia' => today()->addMonth()->format('Y-m'), 'status' => 'PENDING']);
        $c2->update(['status' => 'DELETED']);
        $this->assertSame(StatusTransacao::Cancelado, Transacao::query()->findOrFail($c2->fresh()->transacao_id)->status);
    }

    public function test_pacote_cancelado_cancela_a_venda_ainda_nao_paga(): void
    {
        $paciente = Paciente::create(['nome' => 'Duda']);
        $venda = $this->venda(['status' => 'pendente', 'data_pagamento' => null, 'paciente_id' => $paciente->id, 'forma_pagamento' => 'pix']);
        $pacote = Pacote::query()->create(['paciente_id' => $paciente->id, 'nome' => 'Massagem 5x', 'sessoes_total' => 5, 'sessoes_usadas' => 0,
            'valor_total' => 900, 'status' => 'ativo', 'transacao_id' => $venda->id]);

        app(CancelarPacoteAction::class)->execute($pacote->id);

        $this->assertSame(StatusTransacao::Cancelado, $venda->fresh()->status);
    }
}
