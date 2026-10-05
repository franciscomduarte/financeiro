<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\PagarContratoAction;
use App\Actions\PagarFaturaAction;
use App\Actions\PagarGuiaFiscalAction;
use App\Enums\StatusContrato;
use App\Enums\StatusFatura;
use App\Enums\StatusLancamentoFiscal;
use App\Models\ContaConsumoFatura;
use App\Models\Contrato;
use App\Models\ContratoPagamento;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Throwable;

class RelatorioIndex extends Component
{
    public string $competencia;
    public string $tab = 'gerencial';

    // ── Modal de pagamento ───────────────────────────────────────
    public bool    $modalPagar         = false;
    public string  $modalPagarTipo     = '';   // 'contrato' | 'fatura' | 'fiscal'
    public string  $pagarNome          = '';
    public ?string $pagarId            = null;
    public string  $pagarCompetencia   = '';
    public string  $pagarValor         = '';
    public string  $pagarValorMulta    = '';
    public string  $pagarValorJuros    = '';
    public string  $pagarDataPagamento = '';
    public string  $pagarFormaPagamento = 'pix';
    public string  $pagarNumAutenticacao = '';
    public string  $pagarObservacoes   = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $this->competencia = now()->format('Y-m');
    }

    public function render(): View
    {
        $mesLabel = ucfirst(
            Carbon::createFromFormat('Y-m', $this->competencia)->startOfMonth()->isoFormat('MMMM [de] YYYY')
        );

        $data = $this->tab === 'contabil'
            ? $this->dadosContabil()
            : $this->dadosGerencial();

        return view('livewire.relatorio-index', array_merge($data, [
            'tab'      => $this->tab,
            'mesLabel' => $mesLabel,
        ]))->layout('layouts.app', ['title' => 'Relatórios']);
    }

    // ── Pagamento de contratos ───────────────────────────────────

    public function abrirPagarContrato(string $id): void
    {
        $contrato = Contrato::with('fornecedor')->findOrFail($id);

        $this->pagarId             = $id;
        $this->modalPagarTipo      = 'contrato';
        $this->pagarNome           = $contrato->fornecedor?->nome_fantasia ?? 'Contrato';
        $this->pagarCompetencia    = $this->competencia;
        $this->pagarValor          = (string) $contrato->getRawOriginal('valor_mensal');
        $this->pagarDataPagamento  = now()->toDateString();
        $this->pagarFormaPagamento = 'pix';
        $this->pagarObservacoes    = '';
        $this->flashErro           = null;
        $this->modalPagar          = true;
    }

    // ── Pagamento de faturas de consumo ──────────────────────────

    public function abrirPagarFatura(string $id): void
    {
        $fatura = ContaConsumoFatura::with('contaConsumo')->findOrFail($id);

        $this->pagarId             = $id;
        $this->modalPagarTipo      = 'fatura';
        $this->pagarNome           = $fatura->contaConsumo?->descricao ?? 'Fatura';
        $this->pagarValor          = $fatura->valor ? (string) $fatura->getRawOriginal('valor') : '';
        $this->pagarDataPagamento  = now()->toDateString();
        $this->pagarFormaPagamento = 'pix';
        $this->flashErro           = null;
        $this->modalPagar          = true;
    }

    // ── Pagamento de guias fiscais ───────────────────────────────

    public function abrirPagarFiscal(string $id): void
    {
        $lancamento = ObrigacaoFiscalLancamento::with('obrigacaoFiscal')->findOrFail($id);

        $this->pagarId               = $id;
        $this->modalPagarTipo        = 'fiscal';
        $this->pagarNome             = $lancamento->obrigacaoFiscal?->descricao
            ?? ($lancamento->obrigacaoFiscal?->tipo_tributo->label() ?? 'Obrigação Fiscal');
        $this->pagarValor            = (string) $lancamento->getRawOriginal('valor_principal');
        $this->pagarValorMulta       = (string) ($lancamento->getRawOriginal('valor_multa') ?? '0');
        $this->pagarValorJuros       = (string) ($lancamento->getRawOriginal('valor_juros') ?? '0');
        $this->pagarNumAutenticacao  = '';
        $this->pagarDataPagamento    = now()->toDateString();
        $this->pagarFormaPagamento   = 'pix';
        $this->flashErro             = null;
        $this->modalPagar            = true;
    }

    // ── Confirmar pagamento (genérico) ───────────────────────────

    public function confirmarPagamento(
        PagarContratoAction  $pagarContrato,
        PagarFaturaAction    $pagarFatura,
        PagarGuiaFiscalAction $pagarFiscal,
    ): void {
        $rules = [
            'pagarDataPagamento'  => ['required', 'date'],
            'pagarFormaPagamento' => ['required', 'string'],
        ];

        if ($this->modalPagarTipo === 'contrato') {
            $rules['pagarCompetencia'] = ['required', 'regex:/^\d{4}-\d{2}$/'];
            $rules['pagarValor']       = ['required', 'numeric', 'min:0.01'];
        }
        if ($this->modalPagarTipo === 'fatura') {
            $rules['pagarValor'] = ['required', 'numeric', 'min:0.01'];
        }

        $this->validate($rules);

        try {
            match ($this->modalPagarTipo) {
                'contrato' => $pagarContrato->execute(
                    Contrato::findOrFail($this->pagarId),
                    [
                        'competencia'     => $this->pagarCompetencia,
                        'valor'           => (float) $this->pagarValor,
                        'data_pagamento'  => $this->pagarDataPagamento,
                        'forma_pagamento' => $this->pagarFormaPagamento,
                        'observacoes'     => $this->pagarObservacoes ?: null,
                    ]
                ),
                'fatura' => $pagarFatura->execute(
                    ContaConsumoFatura::findOrFail($this->pagarId),
                    [
                        'valor'           => (float) $this->pagarValor,
                        'data_pagamento'  => $this->pagarDataPagamento,
                        'forma_pagamento' => $this->pagarFormaPagamento,
                    ]
                ),
                'fiscal' => $pagarFiscal->execute(
                    ObrigacaoFiscalLancamento::findOrFail($this->pagarId),
                    [
                        'data_pagamento'      => $this->pagarDataPagamento,
                        'forma_pagamento'     => $this->pagarFormaPagamento,
                        'valor_multa'         => $this->pagarValorMulta ? (float) $this->pagarValorMulta : null,
                        'valor_juros'         => $this->pagarValorJuros ? (float) $this->pagarValorJuros : null,
                        'numero_autenticacao' => $this->pagarNumAutenticacao ?: null,
                    ]
                ),
            };

            $this->modalPagar   = false;
            $this->flashSucesso = 'Pagamento registrado.';
            $this->resetPagamento();
        } catch (Throwable $e) {
            $this->flashErro = 'Não foi possível registrar o pagamento: ' . $e->getMessage();
        }
    }

    public function fecharModalPagamento(): void
    {
        $this->modalPagar = false;
        $this->resetPagamento();
    }

    private function resetPagamento(): void
    {
        $this->pagarId              = null;
        $this->modalPagarTipo       = '';
        $this->pagarNome            = '';
        $this->pagarCompetencia     = '';
        $this->pagarValor           = '';
        $this->pagarValorMulta      = '';
        $this->pagarValorJuros      = '';
        $this->pagarNumAutenticacao = '';
        $this->pagarDataPagamento   = '';
        $this->pagarFormaPagamento  = 'pix';
        $this->pagarObservacoes     = '';
        $this->flashErro            = null;
    }

    public function exportarCsv(): Response
    {
        $transacoes = $this->calcTransacoesContabil();
        $bom        = "\xEF\xBB\xBF"; // UTF-8 BOM para compatibilidade com Excel

        $linhas = ['Data;Tipo;Classificacao;Categoria;Descricao;Valor'];
        foreach ($transacoes as $t) {
            $linhas[] = implode(';', [
                $t['data'],
                $t['tipo'],
                $t['classificacao'],
                $t['categoria'],
                '"' . str_replace('"', '""', $t['descricao']) . '"',
                number_format($t['valor'], 2, ',', '.'),
            ]);
        }

        $csv      = $bom . implode("\r\n", $linhas);
        $filename = "relatorio_contabil_{$this->competencia}.csv";

        return response()->streamDownload(
            fn () => print($csv),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    // ── Aba Gerencial ────────────────────────────────────────────

    private function dadosGerencial(): array
    {
        $mes    = Carbon::createFromFormat('Y-m', $this->competencia)->startOfMonth();
        $inicio = $mes->copy()->startOfMonth()->toDateString();
        $fim    = $mes->copy()->endOfMonth()->toDateString();
        $comp   = $this->competencia;

        // ── Receitas ──────────────────────────────────────────────
        $receitaRows = Transacao::where('tipo', 'entrada')
            ->where('status', 'pago')
            ->whereBetween('data_competencia', [$inicio, $fim])
            ->selectRaw('categoria, SUM(valor_bruto) as total, COUNT(*) as qtd')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get();

        $totalReceita = (float) $receitaRows->sum('total');

        // ── Satélites: soma total de pagamentos no período ─────────
        // PagarContratoAction/PagarFaturaAction/PagarGuiaFiscalAction sempre
        // criam uma transação vinculada (transacao_id). Por isso somamos tudo
        // (não filtramos por transacao_id IS NULL) e excluímos as transacoes
        // vinculadas do loop geral para evitar duplicidade.

        $pgtoContratos = (float) ContratoPagamento::where('competencia', $comp)
            ->sum('valor');

        $pgtoFaturas = (float) ContaConsumoFatura::where('competencia', $comp)
            ->where('status', StatusFatura::Paga->value)
            ->sum('valor');

        $pgtoFiscal = (float) ObrigacaoFiscalLancamento::where('competencia', $comp)
            ->where('status', StatusLancamentoFiscal::Pago->value)
            ->selectRaw('COALESCE(SUM(valor_principal + COALESCE(valor_multa,0) + COALESCE(valor_juros,0)),0) as total')
            ->value('total');

        // ── IDs das transacoes originadas pelos satélites ──────────
        $idsDosSatelites = collect()
            ->merge(ContratoPagamento::where('competencia', $comp)
                ->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->merge(ContaConsumoFatura::where('competencia', $comp)
                ->where('status', StatusFatura::Paga->value)
                ->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->merge(ObrigacaoFiscalLancamento::where('competencia', $comp)
                ->where('status', StatusLancamentoFiscal::Pago->value)
                ->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->unique();

        // ── Despesas avulsas: transacoes não vinculadas a satélites ─
        $despesasTransacao = Transacao::where('tipo', 'saida')
            ->where('status', 'pago')
            ->whereBetween('data_competencia', [$inicio, $fim])
            ->when($idsDosSatelites->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $idsDosSatelites))
            ->selectRaw('categoria, SUM(valor_bruto) as total, COUNT(*) as qtd')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get();

        // ── Consolidar categorias de despesa ───────────────────────
        $categoriasDespesa = collect();

        foreach ($despesasTransacao as $row) {
            $cat = $this->classificarCategoria($row->categoria);
            $categoriasDespesa[$cat] = ($categoriasDespesa[$cat] ?? 0.0) + (float) $row->total;
        }

        if ($pgtoContratos > 0.0) {
            $categoriasDespesa['Contratos'] = ($categoriasDespesa['Contratos'] ?? 0.0) + $pgtoContratos;
        }
        if ($pgtoFaturas > 0.0) {
            $categoriasDespesa['Contas de Consumo'] = ($categoriasDespesa['Contas de Consumo'] ?? 0.0) + $pgtoFaturas;
        }
        if ($pgtoFiscal > 0.0) {
            $categoriasDespesa['Tributos'] = ($categoriasDespesa['Tributos'] ?? 0.0) + $pgtoFiscal;
        }

        $categoriasDespesa = $categoriasDespesa->sortByDesc(fn ($v) => $v);

        $totalDespesa = $categoriasDespesa->sum();
        $saldo        = $totalReceita - $totalDespesa;
        $margem       = $totalReceita > 0 ? round(($saldo / $totalReceita) * 100, 1) : 0.0;

        $topFornecedores = Transacao::where('tipo', 'saida')
            ->where('status', 'pago')
            ->whereBetween('data_competencia', [$inicio, $fim])
            ->whereNotNull('fornecedor_id')
            ->with('fornecedor:id,nome_fantasia')
            ->selectRaw('fornecedor_id, SUM(valor_bruto) as total')
            ->groupBy('fornecedor_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Contratos pendentes = ativos sem ContratoPagamento registrado na competência
        // e cujo período de vigência inclui o mês consultado (evita listar contratos futuros)
        $contratosPagosIds  = ContratoPagamento::where('competencia', $comp)->pluck('contrato_id');
        $contratosPendentes = Contrato::where('status', StatusContrato::Ativo->value)
            ->whereNotIn('id', $contratosPagosIds)
            ->where(fn ($q) => $q->whereNull('data_inicio')->orWhere('data_inicio', '<=', $fim))
            ->where(fn ($q) => $q->whereNull('data_fim')->orWhere('data_fim', '>=', $inicio))
            ->with('fornecedor:id,nome_fantasia')
            ->get();

        $faturasPendentes = ContaConsumoFatura::where('competencia', $comp)
            ->whereNotIn('status', [StatusFatura::Paga->value, StatusFatura::Cancelada->value])
            ->with('contaConsumo:id,descricao,tipo')
            ->get();

        $fiscaisPendentes = ObrigacaoFiscalLancamento::where('competencia', $comp)
            ->whereNotIn('status', [StatusLancamentoFiscal::Pago->value, StatusLancamentoFiscal::Cancelado->value])
            ->with('obrigacaoFiscal:id,descricao,tipo_tributo')
            ->orderBy('data_vencimento')
            ->get();

        return [
            'totalReceita'       => $totalReceita,
            'totalDespesa'       => $totalDespesa,
            'saldo'              => $saldo,
            'margem'             => $margem,
            'receitaRows'        => $receitaRows,
            'categoriasDespesa'  => $categoriasDespesa,
            'topFornecedores'    => $topFornecedores,
            'contratosPendentes' => $contratosPendentes,
            'faturasPendentes'   => $faturasPendentes,
            'fiscaisPendentes'   => $fiscaisPendentes,
            'insights'           => $this->gerarInsights($totalReceita, $totalDespesa, $saldo, $margem, $categoriasDespesa),
            'previsao'           => $this->previsaoGastos(),
            'transacoesContabil' => collect(),
            'totaisContabil'     => [],
        ];
    }

    // ── Previsão de Gastos ───────────────────────────────────────

    private function previsaoGastos(): array
    {
        $comp   = $this->competencia;
        $mes    = Carbon::createFromFormat('Y-m', $comp)->startOfMonth();
        $inicio = $mes->copy()->startOfMonth()->toDateString();
        $fim    = $mes->copy()->endOfMonth()->toDateString();

        // ── Contratos ativos no período ───────────────────────────
        $previstoContratos = (float) Contrato::where('status', StatusContrato::Ativo->value)
            ->where(fn ($q) => $q->whereNull('data_inicio')->orWhere('data_inicio', '<=', $fim))
            ->where(fn ($q) => $q->whereNull('data_fim')->orWhere('data_fim', '>=', $inicio))
            ->sum('valor_mensal');

        $pagoContratos = (float) ContratoPagamento::where('competencia', $comp)->sum('valor');

        // ── Contas de Consumo — todas as faturas do período ───────
        $todasFaturas    = ContaConsumoFatura::where('competencia', $comp)
            ->whereNotIn('status', ['cancelada'])
            ->get(['valor', 'status']);
        $previstoFaturas = (float) $todasFaturas->sum(fn ($f) => (float) ($f->getRawOriginal('valor') ?? 0));
        $pagoFaturas     = (float) $todasFaturas
            ->filter(fn ($f) => $f->status === StatusFatura::Paga)
            ->sum(fn ($f) => (float) ($f->getRawOriginal('valor') ?? 0));

        // ── Fiscal — todos os lançamentos do período ──────────────
        $todosLanc = ObrigacaoFiscalLancamento::where('competencia', $comp)
            ->whereNotIn('status', [StatusLancamentoFiscal::Cancelado->value])
            ->get(['valor_principal', 'valor_multa', 'valor_juros', 'status']);

        $calcLanc       = fn ($l) => (float) $l->getRawOriginal('valor_principal')
            + (float) ($l->getRawOriginal('valor_multa') ?? 0)
            + (float) ($l->getRawOriginal('valor_juros') ?? 0);
        $previstoFiscal = (float) $todosLanc->sum($calcLanc);
        $pagoFiscal     = (float) $todosLanc
            ->filter(fn ($l) => $l->status === StatusLancamentoFiscal::Pago)
            ->sum($calcLanc);

        // ── Transações avulsas pendentes (não vinculadas a satélites) ─
        $idsSatelites = collect()
            ->merge(ContratoPagamento::where('competencia', $comp)->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->merge(ContaConsumoFatura::where('competencia', $comp)->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->merge(ObrigacaoFiscalLancamento::where('competencia', $comp)->whereNotNull('transacao_id')->pluck('transacao_id'))
            ->unique();

        $pendentesAvulsos = (float) Transacao::where('tipo', 'saida')
            ->whereNotIn('status', ['pago', 'cancelado'])
            ->whereBetween('data_competencia', [$inicio, $fim])
            ->when($idsSatelites->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $idsSatelites))
            ->sum('valor_bruto');

        // ── Totais gerais ─────────────────────────────────────────
        $totalPrevisto  = $previstoContratos + $previstoFaturas + $previstoFiscal + $pendentesAvulsos;
        $totalRealizado = $pagoContratos + $pagoFaturas + $pagoFiscal;
        $totalPendente  = max(0.0, $previstoContratos - $pagoContratos)
            + max(0.0, $previstoFaturas - $pagoFaturas)
            + max(0.0, $previstoFiscal - $pagoFiscal)
            + $pendentesAvulsos;
        $pctExecutado   = $totalPrevisto > 0
            ? round(($totalRealizado / $totalPrevisto) * 100, 1)
            : 0.0;

        return compact(
            'previstoContratos', 'pagoContratos',
            'previstoFaturas',   'pagoFaturas',
            'previstoFiscal',    'pagoFiscal',
            'pendentesAvulsos',
            'totalPrevisto',     'totalRealizado',
            'totalPendente',     'pctExecutado',
        );
    }

    // ── Aba Contábil ─────────────────────────────────────────────

    private function dadosContabil(): array
    {
        $transacoes = $this->calcTransacoesContabil();
        $totais     = $this->calcTotaisContabil($transacoes);

        return [
            'totalReceita'       => $totais['total_receitas'],
            'totalDespesa'       => $totais['total_saidas'],
            'saldo'              => $totais['resultado'],
            'margem'             => $totais['total_receitas'] > 0
                ? round(($totais['resultado'] / $totais['total_receitas']) * 100, 1)
                : 0.0,
            'receitaRows'        => collect(),
            'categoriasDespesa'  => collect(),
            'topFornecedores'    => collect(),
            'contratosPendentes' => collect(),
            'faturasPendentes'   => collect(),
            'fiscaisPendentes'   => collect(),
            'insights'           => [],
            'previsao'           => [],
            'transacoesContabil' => $transacoes,
            'totaisContabil'     => $totais,
        ];
    }

    private function calcTransacoesContabil(): Collection
    {
        $mes    = Carbon::createFromFormat('Y-m', $this->competencia)->startOfMonth();
        $inicio = $mes->copy()->startOfMonth()->toDateString();
        $fim    = $mes->copy()->endOfMonth()->toDateString();

        return Transacao::where('status', 'pago')
            ->whereBetween('data_competencia', [$inicio, $fim])
            ->select('data_competencia', 'tipo', 'categoria', 'descricao', 'valor_bruto')
            ->orderBy('data_competencia')
            ->orderByRaw("CASE tipo WHEN 'entrada' THEN 1 ELSE 2 END")
            ->orderBy('categoria')
            ->get()
            ->map(function ($t) {
                $tipoStr = $t->tipo instanceof \App\Enums\TipoTransacao ? $t->tipo->value : (string) $t->tipo;
                return [
                    'data'          => $t->data_competencia->format('d/m/Y'),
                    'tipo'          => $tipoStr === 'entrada' ? 'Entrada' : 'Saída',
                    'classificacao' => $this->classificarContabil($tipoStr, $t->categoria),
                    'categoria'     => $t->categoria,
                    'descricao'     => $t->descricao,
                    'valor'         => (float) $t->valor_bruto,
                ];
            });
    }

    private function calcTotaisContabil(Collection $transacoes): array
    {
        $totalReceitas = (float) $transacoes->where('classificacao', 'Receita')->sum('valor');
        $totalDespesas = (float) $transacoes->where('classificacao', 'Despesa')->sum('valor');
        $totalTributos = (float) $transacoes->where('classificacao', 'Tributo')->sum('valor');
        $totalTaxas    = (float) $transacoes->where('classificacao', 'Taxa')->sum('valor');
        $totalSaidas   = $totalDespesas + $totalTributos + $totalTaxas;
        $resultado     = $totalReceitas - $totalSaidas;

        $somaLinhas    = (float) $transacoes->sum('valor');
        $somaAgregados = $totalReceitas + $totalSaidas;

        return [
            'total_receitas'  => $totalReceitas,
            'total_despesas'  => $totalDespesas,
            'total_tributos'  => $totalTributos,
            'total_taxas'     => $totalTaxas,
            'total_saidas'    => $totalSaidas,
            'resultado'       => $resultado,
            'qtd_lancamentos' => $transacoes->count(),
            'integridade_ok'  => abs($somaLinhas - $somaAgregados) < 0.01,
            'soma_linhas'     => $somaLinhas,
            'soma_agregados'  => $somaAgregados,
        ];
    }

    private function classificarContabil(string $tipo, string $categoria): string
    {
        if ($tipo === 'entrada') {
            return 'Receita';
        }

        $c = mb_strtolower($categoria);

        if (preg_match('/imposto|tribut|issqn|irpj|csll|pis|cofins|simples|das|inss|fgts/', $c)) {
            return 'Tributo';
        }
        if (preg_match('/taxa|cartão|cartao|bancari|iof|tarifa|financ/', $c)) {
            return 'Taxa';
        }

        return 'Despesa';
    }

    private function classificarCategoria(string $categoria): string
    {
        $c = mb_strtolower($categoria);

        if (preg_match('/imposto|tribut|issqn|irpj|csll|pis|cofins|simples|das|inss|fgts|tax/', $c)) {
            return 'Tributos';
        }
        if (preg_match('/aluguel|água|agua|luz|internet|energia|telecom|telefon|condomin|infra/', $c)) {
            return 'Infraestrutura';
        }
        if (preg_match('/taxa|cartão|cartao|banc[aá]ri|iof|tarifa|financ/', $c)) {
            return 'Taxas Financeiras';
        }
        if (preg_match('/folha|salário|salario|funcionári|funcionari|pessoal|férias|ferias|13|rescis/', $c)) {
            return 'Pessoal';
        }
        if (preg_match('/material|produto|estoque|insumo|compra|higiene|beleza/', $c)) {
            return 'Insumos';
        }

        return 'Operacional';
    }

    private function gerarInsights(
        float $totalReceita,
        float $totalDespesa,
        float $saldo,
        float $margem,
        Collection $categoriasDespesa,
    ): array {
        $insights = [];

        if ($totalReceita === 0.0 && $totalDespesa === 0.0) {
            $insights[] = ['tipo' => 'info', 'msg' => 'Ainda não há movimentações neste mês.'];
            return $insights;
        }

        if ($margem >= 20.0) {
            $insights[] = ['tipo' => 'positivo', 'msg' => sprintf('Margem líquida de %.1f%%. Resultado saudável.', $margem)];
        } elseif ($margem >= 0.0) {
            $insights[] = ['tipo' => 'atencao', 'msg' => sprintf('Margem líquida de %.1f%%, abaixo do ideal. Vale revisar as despesas operacionais.', $margem)];
        } else {
            $insights[] = ['tipo' => 'critico', 'msg' => sprintf('Resultado negativo de R$ %s. Revise os custos o quanto antes.', number_format(abs($saldo), 2, ',', '.'))];
        }

        if ($totalReceita === 0.0) {
            $insights[] = ['tipo' => 'atencao', 'msg' => 'Nenhuma receita registrada neste mês.'];
        }

        $maiorCat   = $categoriasDespesa->keys()->first();
        $maiorValor = (float) ($categoriasDespesa->first() ?? 0);
        if ($maiorCat && $totalDespesa > 0) {
            $pct = ($maiorValor / $totalDespesa) * 100;
            if ($pct > 40) {
                $insights[] = ['tipo' => 'atencao', 'msg' => sprintf('"%s" representa %.0f%% das despesas. Vale ficar de olho nessa concentração.', $maiorCat, $pct)];
            }
        }

        return $insights;
    }
}
