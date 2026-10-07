<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Actions\Financeiro\AjustarSaldoContaAction;
use App\Actions\Financeiro\BaixarTransacaoAction;
use App\Actions\Financeiro\EstornarBaixaAction;
use App\Actions\Financeiro\TransferirEntreContasAction;
use App\Actions\UpdateTransacaoAction;
use App\Enums\StatusTransacao;
use App\Enums\TipoContaFinanceira;
use App\Livewire\ContasFinanceirasIndex;
use App\Livewire\ContasPagarReceber;
use App\Models\ContaFinanceira;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use App\Models\User;
use App\Services\SaldoContasService;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/** Financeiro, fase 1: contas financeiras, vencimento, baixas (parciais, encargos), transferências e saldos. */
class FinanceiroFase1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function conta(TipoContaFinanceira $tipo): ContaFinanceira
    {
        ContaFinanceira::garantirPadroes();

        return ContaFinanceira::query()->where('tipo', $tipo->value)->firstOrFail();
    }

    private function lancamento(array $dados = []): Transacao
    {
        return app(CreateTransacaoAction::class)->execute(array_merge([
            'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'Insumos', 'descricao' => 'Fornecedor de ácido',
            'valor_bruto' => 1000, 'forma_pagamento' => 'boleto', 'data_competencia' => '2026-10-01', 'status' => 'pendente',
        ], $dados));
    }

    public function test_clinica_tem_contas_padrao_e_vencimento_vem_da_competencia(): void
    {
        $this->assertSame(['banco', 'caixa', 'maquininha'], ContaFinanceira::query()->orderBy('tipo')->pluck('tipo')->map->value->all());
        $this->assertTrue($this->conta(TipoContaFinanceira::Banco)->padrao);

        $t = $this->lancamento();
        $this->assertSame('2026-10-01', $t->data_vencimento->toDateString());

        $t2 = $this->lancamento(['data_vencimento' => '2026-10-15']);
        $this->assertSame('2026-10-15', $t2->data_vencimento->toDateString());
    }

    public function test_baixa_parcial_com_juros_e_depois_quitacao_com_desconto(): void
    {
        $t     = $this->lancamento();
        $banco = $this->conta(TipoContaFinanceira::Banco);
        $baixar = app(BaixarTransacaoAction::class);

        $baixar->execute($t, ['valor' => 400, 'data' => today()->subDays(5)->toDateString(), 'conta_financeira_id' => $banco->id, 'juros' => 10, 'multa' => 20]);
        $t->refresh();
        $this->assertSame(StatusTransacao::Parcial, $t->status);
        $this->assertSame(600.0, $t->valorAberto());
        $this->assertNull($t->data_pagamento);
        $this->assertSame('430.00', TransacaoBaixa::query()->first()->valor_movimentado);

        $baixar->execute($t, ['valor' => '600', 'data' => today()->toDateString(), 'conta_financeira_id' => $banco->id, 'desconto' => 50]);
        $t->refresh();
        $this->assertSame(StatusTransacao::Pago, $t->status);
        $this->assertSame(0.0, $t->valorAberto());
        $this->assertSame(today()->toDateString(), $t->data_pagamento->toDateString());

        // 430 + 550 saíram do banco
        $this->assertSame(-980.0, app(SaldoContasService::class)->saldo($banco));

        $this->expectException(RuntimeException::class);
        $baixar->execute($t, ['valor' => 1, 'data' => '2026-10-10', 'conta_financeira_id' => $banco->id]);
    }

    public function test_nao_aceita_baixa_maior_que_o_aberto(): void
    {
        $t = $this->lancamento(['valor_bruto' => 100]);

        $this->expectExceptionMessage('no máximo R$ 100,00');
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 150, 'data' => '2026-10-05', 'conta_financeira_id' => $this->conta(TipoContaFinanceira::Banco)->id]);
    }

    public function test_estornar_baixa_volta_o_valor_para_o_aberto(): void
    {
        $t = $this->lancamento();
        $b = app(BaixarTransacaoAction::class)->execute($t, ['valor' => 1000, 'data' => '2026-10-05', 'conta_financeira_id' => $this->conta(TipoContaFinanceira::Banco)->id]);
        $this->assertSame(StatusTransacao::Pago, $t->fresh()->status);

        app(EstornarBaixaAction::class)->execute($b);

        $t->refresh();
        $this->assertSame(StatusTransacao::Pendente, $t->status);
        $this->assertSame(1000.0, $t->valorAberto());
        $this->assertNull($t->data_pagamento);
        $this->assertSame(0, TransacaoBaixa::query()->count());
    }

    public function test_receita_no_cartao_desconta_a_taxa_e_cai_na_maquininha(): void
    {
        TaxaCartao::query()->create(['modalidade' => 'credito_1x', 'percentual' => 4, 'ativo' => true]);

        $t = $this->lancamento(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 500, 'forma_pagamento' => 'credito_1x', 'status' => 'pago', 'data_pagamento' => '2026-10-02']);

        $baixa = $t->baixas()->firstOrFail();
        $this->assertSame($this->conta(TipoContaFinanceira::Maquininha)->id, $baixa->conta_financeira_id);
        $this->assertSame('20.00', $baixa->taxa);
        $this->assertSame('480.00', $baixa->valor_movimentado);
        $this->assertSame('500.00', $t->fresh()->valor_pago);
    }

    public function test_fluxos_antigos_que_mudam_o_status_mantem_as_baixas(): void
    {
        $t = $this->lancamento(['forma_pagamento' => 'dinheiro', 'valor_bruto' => 200]);
        $atualizar = app(UpdateTransacaoAction::class);

        // Marcar como pago (lista de lançamentos, contratos, faturas, guias...) gera a baixa no caixa
        $atualizar->execute($t, ['status' => 'pago', 'data_pagamento' => '2026-10-03']);
        $this->assertSame(1, $t->baixas()->count());
        $this->assertSame($this->conta(TipoContaFinanceira::Caixa)->id, $t->baixas()->first()->conta_financeira_id);

        // Editar o valor de um lançamento pago ajusta a baixa
        $atualizar->execute($t->fresh(), ['valor_bruto' => 250]);
        $this->assertSame('250.00', $t->baixas()->first()->valor);
        $this->assertSame('250.00', $t->fresh()->valor_pago);

        // Voltar para pendente ou cancelar estorna
        $atualizar->execute($t->fresh(), ['status' => 'cancelado']);
        $this->assertSame(0, $t->baixas()->count());
        $this->assertSame(0.0, $t->fresh()->valorAberto());
    }

    public function test_marcar_como_pago_um_parcial_baixa_so_o_restante(): void
    {
        $t = $this->lancamento();
        $banco = $this->conta(TipoContaFinanceira::Banco);
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 300, 'data' => '2026-10-05', 'conta_financeira_id' => $banco->id]);

        app(UpdateTransacaoAction::class)->execute($t->fresh(), ['status' => 'pago', 'data_pagamento' => '2026-10-08']);

        $this->assertSame([300.0, 700.0], $t->baixas()->orderBy('valor')->pluck('valor')->map(fn ($v) => (float) $v)->all());
        $this->assertSame('1000.00', $t->fresh()->valor_pago);
    }

    public function test_transferencia_e_ajuste_de_saldo(): void
    {
        $banco = $this->conta(TipoContaFinanceira::Banco);
        $maq   = $this->conta(TipoContaFinanceira::Maquininha);
        $saldos = app(SaldoContasService::class);

        app(AjustarSaldoContaAction::class)->execute($maq, 1000, today()->subDays(10)->toDateString());
        // Movimento anterior ao ajuste não conta; posterior conta
        $t = $this->lancamento(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 300, 'forma_pagamento' => 'debito']);
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 100, 'data' => today()->subDays(20)->toDateString(), 'conta_financeira_id' => $maq->id]);
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 200, 'data' => today()->subDays(2)->toDateString(), 'conta_financeira_id' => $maq->id]);
        $this->assertSame(1200.0, $saldos->saldo($maq));

        app(TransferirEntreContasAction::class)->execute(['conta_origem_id' => $maq->id, 'conta_destino_id' => $banco->id, 'data' => today()->toDateString(), 'valor' => 700.5]);
        $this->assertSame(499.5, $saldos->saldo($maq));
        $this->assertSame(700.5, $saldos->saldo($banco));

        $this->expectException(RuntimeException::class);
        app(TransferirEntreContasAction::class)->execute(['conta_origem_id' => $banco->id, 'conta_destino_id' => $banco->id, 'data' => today()->toDateString(), 'valor' => 10]);
    }

    public function test_tela_de_contas_a_pagar_lista_por_vencimento_e_registra_pagamento(): void
    {
        $vencida = $this->lancamento(['descricao' => 'Aluguel', 'data_competencia' => today()->subDays(5)->toDateString()]);
        $this->lancamento(['descricao' => 'Luz', 'data_competencia' => today()->toDateString(), 'valor_bruto' => 300]);
        $this->lancamento(['descricao' => 'Venda', 'tipo' => 'entrada', 'categoria' => 'Massagem', 'data_competencia' => today()->toDateString()]);

        $tela = Livewire::test(ContasPagarReceber::class, ['tipo' => 'saida'])
            ->assertSee('Aluguel')->assertSee('Luz')->assertDontSee('Venda')
            ->assertSee('venceu há 5 dias');
        $this->assertSame([1, 1000.0], $tela->instance()->resumo['vencidos']);
        $this->assertSame([1, 300.0], $tela->instance()->resumo['hoje']);

        $tela->set('filtro', 'vencidos')->assertSee('Aluguel')->assertDontSee('Luz')
            ->call('abrirBaixa', $vencida->id)
            ->assertSet('baixaValor', '1000,00')
            ->assertSet('baixaConta', $this->conta(TipoContaFinanceira::Banco)->id)
            ->set('baixaValor', '400,00')
            ->call('confirmarBaixa')
            ->assertHasNoErrors()
            ->assertSet('baixaId', null)
            ->assertSee('Ainda falta R$ 600,00');

        $this->assertSame(StatusTransacao::Parcial, $vencida->fresh()->status);

        $tela->set('filtro', 'quitados')->assertSee('Aluguel');
        Livewire::test(ContasPagarReceber::class, ['tipo' => 'entrada'])->assertSee('Venda')->assertDontSee('Aluguel');
    }

    public function test_tela_caixa_e_bancos_mostra_saldo_extrato_e_transfere(): void
    {
        $banco = $this->conta(TipoContaFinanceira::Banco);
        $caixa = $this->conta(TipoContaFinanceira::Caixa);
        $this->lancamento(['tipo' => 'entrada', 'categoria' => 'Massagem', 'descricao' => 'Massagem Ana', 'valor_bruto' => 150, 'forma_pagamento' => 'dinheiro', 'status' => 'pago', 'data_competencia' => today()->toDateString()]);

        Livewire::test(ContasFinanceirasIndex::class)
            ->assertSee('Caixa')->assertSee('R$ 150,00')
            ->call('verExtrato', $caixa->id)->assertSee('Massagem Ana')
            ->call('novaTransferencia', $caixa->id)
            ->set('trDestino', $banco->id)->set('trValor', '100')
            ->call('confirmarTransferencia')->assertHasNoErrors()
            ->assertSee('Transferência para Conta bancária');

        $saldos = app(SaldoContasService::class)->saldos();
        $this->assertSame(50.0, $saldos[$caixa->id]);
        $this->assertSame(100.0, $saldos[$banco->id]);
    }

    public function test_contas_e_baixas_sao_isoladas_por_clinica(): void
    {
        $t = $this->lancamento();
        app(BaixarTransacaoAction::class)->execute($t, ['valor' => 100, 'data' => '2026-10-05', 'conta_financeira_id' => $this->conta(TipoContaFinanceira::Banco)->id]);

        $outra = $this->novaClinica();
        app(ClinicaAtual::class)->executarComo($outra, function () use ($t): void {
            $this->assertSame(0, ContaFinanceira::query()->count());
            $this->assertSame(0, TransacaoBaixa::query()->count());
            $this->assertSame([], app(SaldoContasService::class)->saldos()->all());

            ContaFinanceira::garantirPadroes();
            $contaOutra = ContaFinanceira::query()->firstOrFail();
            $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
            app(BaixarTransacaoAction::class)->execute($t, ['valor' => 1, 'data' => '2026-10-05', 'conta_financeira_id' => $contaOutra->id]);
        });
    }
}
