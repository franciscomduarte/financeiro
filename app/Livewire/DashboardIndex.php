<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Contrato;
use App\Models\ContaConsumoFatura;
use App\Models\ObrigacaoFiscalLancamento;
use App\Models\Transacao;
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

        // ─── KPIs ───────────────────────────────────────────────
        $receitaMes         = (float) Transacao::where('tipo', 'entrada')->where('status', 'pago')->whereBetween('data_pagamento', [$inicioM, $fimM])->sum('valor_bruto');
        $despesaMes         = (float) Transacao::where('tipo', 'saida')->where('status', 'pago')->whereBetween('data_pagamento', [$inicioM, $fimM])->sum('valor_bruto');
        $receitaMesAnterior = (float) Transacao::where('tipo', 'entrada')->where('status', 'pago')->whereBetween('data_pagamento', [$inicioMA, $fimMA])->sum('valor_bruto');
        $despesaMesAnterior = (float) Transacao::where('tipo', 'saida')->where('status', 'pago')->whereBetween('data_pagamento', [$inicioMA, $fimMA])->sum('valor_bruto');

        $saldoMes         = $receitaMes - $despesaMes;
        $saldoMesAnterior = $receitaMesAnterior - $despesaMesAnterior;

        // ─── Gráfico fluxo — últimos 6 meses ───────────────────
        $fluxo = collect(range(5, 0))->map(function (int $i) use ($hoje): array {
            $ref    = $hoje->copy()->subMonths($i);
            $inicio = $ref->copy()->startOfMonth()->toDateString();
            $fim    = $ref->copy()->endOfMonth()->toDateString();

            return [
                'label'   => $ref->isoFormat('MMM/YY'),
                'receita' => (float) Transacao::where('tipo', 'entrada')->where('status', 'pago')->whereBetween('data_pagamento', [$inicio, $fim])->sum('valor_bruto'),
                'despesa' => (float) Transacao::where('tipo', 'saida')->where('status', 'pago')->whereBetween('data_pagamento', [$inicio, $fim])->sum('valor_bruto'),
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

        return view('livewire.dashboard-index', [
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
