<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Contrato;
use App\Models\ContaConsumoFatura;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use App\Services\AlertasVencimentoService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardIndex extends Component
{
    public function render(): View
    {
        $hoje     = now();
        $inicioM  = $hoje->copy()->startOfMonth()->toDateString();
        $fimM     = $hoje->copy()->endOfMonth()->toDateString();
        $inicioMA = $hoje->copy()->subMonth()->startOfMonth()->toDateString();
        $fimMA    = $hoje->copy()->subMonth()->endOfMonth()->toDateString();

        // ─── KPIs: o que entrou e saiu de fato (baixas, já com juros, descontos e taxa da maquininha) ──
        $movimentado = fn (string $tipo, string $de, string $ate): float => (float) TransacaoBaixa::query()
            ->where('tipo', $tipo)->whereBetween('data', [$de, $ate])->sum('valor_movimentado');
        $receitaMes         = $movimentado('entrada', $inicioM, $fimM);
        $despesaMes         = $movimentado('saida', $inicioM, $fimM);
        $receitaMesAnterior = $movimentado('entrada', $inicioMA, $fimMA);
        $despesaMesAnterior = $movimentado('saida', $inicioMA, $fimMA);

        $saldoMes         = $receitaMes - $despesaMes;
        $saldoMesAnterior = $receitaMesAnterior - $despesaMesAnterior;

        // ─── Gráfico fluxo — últimos 6 meses ───────────────────
        $porMes = TransacaoBaixa::query()
            ->whereBetween('data', [$hoje->copy()->subMonths(5)->startOfMonth()->toDateString(), $fimM])
            ->groupBy(\Illuminate\Support\Facades\DB::raw("to_char(data, 'YYYY-MM')"), 'tipo')
            ->selectRaw("to_char(data, 'YYYY-MM') AS mes, tipo AS tipo_raw, SUM(valor_movimentado) AS total")
            ->toBase()->get()
            ->groupBy('mes');
        $fluxo = collect(range(5, 0))->map(function (int $i) use ($hoje, $porMes): array {
            $ref   = $hoje->copy()->subMonths($i);
            $linha = $porMes->get($ref->format('Y-m'), collect());

            return [
                'label'   => $ref->isoFormat('MMM/YY'),
                'receita' => (float) ($linha->firstWhere('tipo_raw', 'entrada')->total ?? 0),
                'despesa' => (float) ($linha->firstWhere('tipo_raw', 'saida')->total ?? 0),
            ];
        });

        // ─── Gráfico donut — despesas por categoria (mês atual) ─
        $despesasCat = Transacao::where('tipo', 'saida')
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicioM, $fimM])
            ->selectRaw('categoria, SUM(valor_bruto) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->get();

        // ─── Alertas (mesma fonte do resumo diário por e-mail/WhatsApp) ──
        ['vencidos' => $alertasVencidos, 'a_vencer' => $alertasAVencer] = app(AlertasVencimentoService::class)->levantar();

        $custoMinimo = $this->custoMinimoMensal();

        $financeiro = auth()->user()?->pode(\App\Enums\Modulo::Lancamentos)
            ? app(\App\Services\PainelFinanceiroService::class)->resumo()
            : null;

        return view('livewire.dashboard-index', [
            'financeiro'         => $financeiro,
            // KPIs
            'receitaMes'         => $receitaMes,
            'despesaMes'         => $despesaMes,
            'saldoMes'           => $saldoMes,
            'receitaMesAnterior' => $receitaMesAnterior,
            'despesaMesAnterior' => $despesaMesAnterior,
            'saldoMesAnterior'   => $saldoMesAnterior,
            // Gráficos
            'fluxo'              => $fluxo,
            'despesasCat'        => $despesasCat,
            // Alertas
            'alertasVencidos' => $alertasVencidos,
            'alertasAVencer'  => $alertasAVencer,
            'totalAlertas'    => $alertasVencidos->count() + $alertasAVencer->count(),
            'hojeAlertas'     => \Carbon\CarbonImmutable::today(),
            // Custo mínimo
            'custoMinimo'      => $custoMinimo,
            // Meta
            'mesLabel'         => ucfirst($hoje->isoFormat('MMMM [de] YYYY')),
            'mesAnteriorLabel' => ucfirst($hoje->copy()->subMonth()->isoFormat('MMMM')),
        ])->layout('layouts.app', ['title' => 'Início']);
    }

    private function custoMinimoMensal(): array
    {
        $hoje           = now();
        $mesesHistorico = 6;

        // ── Contratos fixos — valor exato dos contratos ativos ────────
        $contratosAtivos = Contrato::where('status', 'ativo')
            ->get(['valor_mensal', 'data_inicio', 'data_fim']);

        $custoContratos = (float) $contratosAtivos->sum(
            fn ($c) => (float) $c->getRawOriginal('valor_mensal')
        );

        $numContratos = $contratosAtivos->count();

        // ── Contas de consumo — média dos últimos N meses completos ──
        $somaConsumo    = 0.0;
        $mesesConsumo   = 0;

        for ($i = 1; $i <= $mesesHistorico; $i++) {
            $comp = $hoje->copy()->subMonths($i)->format('Y-m');
            $soma = (float) ContaConsumoFatura::where('competencia', $comp)
                ->whereNotIn('status', ['cancelada'])
                ->sum('valor');
            if ($soma > 0.0) {
                $somaConsumo += $soma;
                $mesesConsumo++;
            }
        }

        $mediaConsumo = $mesesConsumo > 0 ? round($somaConsumo / $mesesConsumo, 2) : 0.0;

        // ── Fiscal — média dos últimos N meses completos ──────────────
        $somaFiscal  = 0.0;
        $mesesFiscal = 0;

        for ($i = 1; $i <= $mesesHistorico; $i++) {
            $comp = $hoje->copy()->subMonths($i)->format('Y-m');
            $soma = (float) ObrigacaoFiscalLancamento::where('competencia', $comp)
                ->whereNotIn('status', ['cancelado'])
                ->selectRaw('COALESCE(SUM(valor_principal + COALESCE(valor_multa,0) + COALESCE(valor_juros,0)),0) as total')
                ->value('total');
            if ($soma > 0.0) {
                $somaFiscal += $soma;
                $mesesFiscal++;
            }
        }

        $mediaFiscal = $mesesFiscal > 0 ? round($somaFiscal / $mesesFiscal, 2) : 0.0;

        // ── Total e pesos ─────────────────────────────────────────────
        $totalMinimo = $custoContratos + $mediaConsumo + $mediaFiscal;

        return [
            'custoContratos'  => $custoContratos,
            'numContratos'    => $numContratos,
            'mediaConsumo'    => $mediaConsumo,
            'mesesConsumo'    => $mesesConsumo,
            'mediaFiscal'     => $mediaFiscal,
            'mesesFiscal'     => $mesesFiscal,
            'totalMinimo'     => $totalMinimo,
            'mesesHistorico'  => $mesesHistorico,
        ];
    }

    private function variacao(float $atual, float $anterior): ?float
    {
        if ($anterior == 0) {
            return null;
        }

        return round((($atual - $anterior) / $anterior) * 100, 1);
    }
}
