<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateTransacaoAction;
use App\Actions\Financeiro\BaixarTransacaoAction;
use App\Actions\Financeiro\TransferirEntreContasAction;
use App\Enums\GrupoPlanoContas;
use App\Enums\TipoContaFinanceira;
use App\Livewire\DreIndex;
use App\Livewire\FluxoCaixaIndex;
use App\Livewire\PlanoContasIndex;
use App\Models\ContaFinanceira;
use App\Models\PlanoConta;
use App\Models\Recorrencia;
use App\Models\TaxaCartao;
use App\Models\Transacao;
use App\Models\User;
use App\Services\DreService;
use App\Services\FluxoCaixaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Financeiro, fase 2: plano de contas, DRE, fluxo de caixa (realizado e projetado), exportação e painel. */
class FinanceiroFase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->clinica->update(['aliquota_imposto' => 6]);
    }

    private function lancar(array $dados): Transacao
    {
        return app(CreateTransacaoAction::class)->execute(array_merge([
            'tipo' => 'saida', 'fase' => 'operacao', 'descricao' => 'Teste', 'valor_bruto' => 100,
            'forma_pagamento' => 'pix', 'data_competencia' => today()->startOfMonth()->toDateString(), 'status' => 'pendente',
        ], $dados));
    }

    private function conta(TipoContaFinanceira $tipo): ContaFinanceira
    {
        ContaFinanceira::garantirPadroes();

        return ContaFinanceira::query()->where('tipo', $tipo->value)->firstOrFail();
    }

    public function test_plano_padrao_e_lancamentos_ligados_ao_plano(): void
    {
        $this->assertSame(GrupoPlanoContas::CustosVariaveis, PlanoConta::resolver('saida', 'Insumos')->grupo);
        $this->assertSame('Outros', PlanoConta::resolver('saida', 'Coisa que não existe')->nome);
        $this->assertSame('Comissões', PlanoConta::resolver('saida', 'Pessoal', 'Comissões')->nome);
        $this->assertSame('Serviços de terceiros', PlanoConta::resolver('saida', 'servicos')->nome);
        $this->assertContains('Massagem', PlanoConta::nomes('entrada'));
        $this->assertNotContains('Massagem', PlanoConta::nomes('saida'));

        $t = $this->lancar(['categoria' => 'Energia Elétrica']);
        $this->assertSame('Energia Elétrica', $t->planoConta->nome);
        $this->assertSame(GrupoPlanoContas::Ocupacao, $t->planoConta->grupo);

        // Escolher pela conta do plano grava também o nome
        $marketing = PlanoConta::query()->where('tipo', 'saida')->where('nome', 'Marketing')->firstOrFail();
        $t->update(['categoria_id' => $marketing->id]);
        $this->assertSame('Marketing', $t->fresh()->categoria);
    }

    public function test_tela_do_plano_cria_renomeia_e_protege_contas_usadas(): void
    {
        $t = $this->lancar(['categoria' => 'Insumos']);
        $insumos = $t->planoConta;

        Livewire::test(PlanoContasIndex::class)
            ->assertSee('Custos variáveis')->assertSee('Insumos')
            ->call('nova', 'marketing')->set('nome', 'Instagram Ads')->call('salvar')->assertHasNoErrors()
            ->call('editar', $insumos->id)->set('nome', 'Insumos e descartáveis')->call('salvar')->assertHasNoErrors()
            ->call('excluir', $insumos->id)->assertSet('flashErro', 'Esta categoria tem lançamentos. Desative em vez de excluir.');

        $this->assertSame('Insumos e descartáveis', $t->fresh()->categoria);
        $this->assertTrue(PlanoConta::query()->where('nome', 'Instagram Ads')->where('grupo', 'marketing')->exists());
        $this->assertContains('Instagram Ads', PlanoConta::nomes('saida'));
    }

    public function test_dre_do_mes(): void
    {
        TaxaCartao::query()->create(['modalidade' => 'credito_1x', 'percentual' => 4, 'ativo' => true]);
        $mes = today()->startOfMonth()->toDateString();

        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 1000, 'forma_pagamento' => 'credito_1x']);
        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Produto Vendido', 'valor_bruto' => 200]);
        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Aporte dos sócios', 'valor_bruto' => 5000]);
        $this->lancar(['categoria' => 'Simples Nacional', 'valor_bruto' => 72]);
        $this->lancar(['categoria' => 'Insumos', 'valor_bruto' => 200]);
        $this->lancar(['categoria' => 'Pessoal', 'subcategoria' => 'Comissões', 'valor_bruto' => 100]);
        $this->lancar(['categoria' => 'Pessoal', 'valor_bruto' => 300]);
        $aluguel = $this->lancar(['categoria' => 'Aluguel e condomínio', 'valor_bruto' => 400]);
        $this->lancar(['categoria' => 'Equipamentos', 'valor_bruto' => 900]);
        $this->lancar(['categoria' => 'Marketing', 'valor_bruto' => 999, 'status' => 'cancelado']);
        $this->lancar(['categoria' => 'Insumos', 'valor_bruto' => 50, 'data_competencia' => today()->subMonth()->startOfMonth()->toDateString()]);
        app(BaixarTransacaoAction::class)->execute($aluguel, ['valor' => 400, 'data' => today()->toDateString(), 'conta_financeira_id' => $this->conta(TipoContaFinanceira::Banco)->id, 'juros' => 8, 'multa' => 2]);

        $dre = app(DreService::class)->mes(CarbonImmutable::parse($mes));

        $this->assertSame(1200.0, $dre['receita_bruta']);
        $this->assertSame(72.0, $dre['deducoes']);
        $this->assertSame(1128.0, $dre['receita_liquida']);
        $this->assertSame(['Insumos' => 200.0, 'Comissões' => 100.0, 'Taxas de cartão' => 40.0], $dre['linhas']['custos_variaveis']['contas']);
        $this->assertSame(788.0, $dre['margem']);
        $this->assertSame(700.0, $dre['despesas_fixas']);
        $this->assertSame(88.0, $dre['resultado_operacional']);
        $this->assertSame(['Juros e multas pagos' => 10.0], $dre['linhas']['despesas_financeiras']['contas']);
        $this->assertSame(78.0, $dre['resultado']);
        $this->assertSame(5000.0, $dre['fora']['aportes']['valor']);
        $this->assertSame(900.0, $dre['fora']['investimentos']['valor']);

        $serie = app(DreService::class)->serie(CarbonImmutable::parse($mes), 3);
        $this->assertCount(3, $serie);
        $this->assertSame(-50.0, $serie[1]['resultado']);
        $this->assertSame(78.0, $serie[2]['resultado']);

        Livewire::test(DreIndex::class)->assertSee('Receita bruta')->assertSee('Margem de contribuição')->assertSee('R$ 78,00');
    }

    public function test_fluxo_realizado_por_mes_e_por_dia_sem_transferencias(): void
    {
        $banco = $this->conta(TipoContaFinanceira::Banco);
        $caixa = $this->conta(TipoContaFinanceira::Caixa);
        $inicio = today()->startOfMonth();

        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 500, 'status' => 'pago', 'data_pagamento' => $inicio->toDateString(), 'forma_pagamento' => 'dinheiro']);
        $this->lancar(['categoria' => 'Insumos', 'valor_bruto' => 120, 'status' => 'pago', 'data_pagamento' => $inicio->toDateString()]);
        app(TransferirEntreContasAction::class)->execute(['conta_origem_id' => $caixa->id, 'conta_destino_id' => $banco->id, 'data' => $inicio->toDateString(), 'valor' => 300]);

        $fluxo = app(FluxoCaixaService::class);
        $ano = $fluxo->realizado(CarbonImmutable::create((int) $inicio->format('Y'), 1, 1), CarbonImmutable::create((int) $inicio->format('Y'), 12, 31));
        $this->assertCount(12, $ano['periodos']);
        $this->assertSame(500.0, $ano['entradas']);
        $this->assertSame(120.0, $ano['saidas']);
        $this->assertSame(380.0, $ano['saldo_final']);
        $this->assertSame(500.0, $ano['grupos']['receita_servicos']['total']);

        $mes = $fluxo->realizado(CarbonImmutable::parse($inicio), CarbonImmutable::parse($inicio)->endOfMonth(), 'dia');
        $this->assertSame(380.0, $mes['periodos'][0]['saldo']);

        Livewire::test(FluxoCaixaIndex::class)->set('aba', 'realizado')->assertSee('R$ 500,00')->assertSee('Receita de serviços');
    }

    public function test_fluxo_projetado_com_vencimentos_recorrencias_e_alerta_de_saldo_negativo(): void
    {
        $banco = $this->conta(TipoContaFinanceira::Banco);
        app(\App\Actions\Financeiro\AjustarSaldoContaAction::class)->execute($banco, 1000, today()->subDay()->toDateString());

        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 300, 'data_competencia' => today()->addDays(3)->toDateString()]);
        $this->lancar(['categoria' => 'Aluguel e condomínio', 'valor_bruto' => 1500, 'data_competencia' => today()->addDays(10)->toDateString()]);
        $this->lancar(['categoria' => 'Insumos', 'valor_bruto' => 80, 'data_competencia' => today()->subDays(4)->toDateString()]);
        Recorrencia::query()->create([
            'tipo' => 'saida', 'fase' => 'operacao', 'categoria' => 'Internet', 'descricao' => 'Internet', 'valor_bruto' => 100,
            'forma_pagamento' => 'pix', 'frequencia' => 'mensal', 'dia_vencimento' => (int) today()->addDays(5)->format('d'),
            'proxima_data' => today()->addDays(5)->toDateString(), 'lancar_como_pago' => false, 'ativa' => true,
        ]);

        $p = app(FluxoCaixaService::class)->projetado(30);

        $this->assertSame(1000.0, $p['saldo_hoje']);
        $this->assertSame(80.0, $p['vencidos_pagar']);
        $this->assertSame(300.0, $p['entradas']);
        $this->assertSame(1600.0, $p['saidas']); // aluguel + internet (1 ocorrência nos 30 dias)
        $this->assertSame(-300.0, $p['saldo_final']);
        $this->assertSame(-300.0, $p['menor_saldo']);
        $this->assertSame(today()->addDays(10)->toDateString(), $p['menor_saldo_em']->toDateString());
        $this->assertCount(31, $p['diario']);

        Livewire::test(FluxoCaixaIndex::class)->set('dias', 30)->assertSee('o caixa fica negativo');
    }

    public function test_exportacoes_e_painel(): void
    {
        $this->lancar(['tipo' => 'entrada', 'categoria' => 'Massagem', 'valor_bruto' => 250, 'status' => 'pago', 'data_pagamento' => today()->toDateString(), 'data_competencia' => today()->toDateString()]);

        $csv = $this->get(route('financeiro.exportar', ['relatorio' => 'dre', 'formato' => 'csv', 'mes' => today()->format('Y-m')]));
        $csv->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('"= Receita bruta";"250,00"', $csv->getContent());

        $this->get(route('financeiro.exportar', ['relatorio' => 'dre', 'formato' => 'pdf']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('financeiro.exportar', ['relatorio' => 'fluxo-projetado', 'formato' => 'csv', 'dias' => 60]))->assertOk();
        $this->get(route('financeiro.exportar', ['relatorio' => 'fluxo-realizado', 'formato' => 'pdf', 'periodo' => today()->format('Y-m')]))->assertOk();
        $this->get('/financeiro/exportar/outro.csv')->assertNotFound();

        $this->get(route('dashboard'))->assertOk()->assertSee('Saldo em contas')->assertSee('Resultado do mês (DRE)');
        foreach (['financeiro.dre', 'financeiro.fluxo', 'financeiro.plano'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
    }

    public function test_acesso_por_perfil(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'recepcao']));
        $this->get(route('financeiro.dre'))->assertRedirect()->assertSessionHas('error');
        $this->get(route('financeiro.exportar', ['relatorio' => 'dre', 'formato' => 'csv']))->assertRedirect();
        $this->get(route('financeiro.pagar'))->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => 'financeiro']));
        $this->get(route('financeiro.dre'))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertSee('Saldo em contas');
    }
}
