<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GrupoPlanoContas as G;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DRE gerencial por competência (o mês a que a receita ou despesa pertence), montada a partir do
 * plano de contas. Juros, multas e descontos das baixas entram nas linhas financeiras pela data
 * em que foram pagos. Aportes, investimentos e retiradas aparecem à parte: mexem no caixa, não no lucro.
 */
class DreService
{
    /**
     * @return array{
     *   mes: CarbonImmutable,
     *   linhas: array<string, array{rotulo: string, valor: float, contas: array<string, float>}>,
     *   receita_bruta: float, deducoes: float, receita_liquida: float, custos_variaveis: float,
     *   margem: float, margem_pct: ?float, despesas_fixas: float, resultado_operacional: float,
     *   financeiro: float, resultado: float, resultado_pct: ?float, imposto_estimado: float,
     *   fora: array<string, array{rotulo: string, valor: float, contas: array<string, float>}>
     * }
     */
    public function mes(CarbonImmutable $mes): array
    {
        $mes = $mes->startOfMonth();

        return $this->montar($mes, $this->dados($mes, $mes->endOfMonth())[$mes->format('Y-m')] ?? $this->vazio());
    }

    /**
     * Resumo dos últimos $meses meses até $ate (inclusive): receita, custos, despesas e resultado.
     *
     * @return array<int, array{mes: CarbonImmutable, receita: float, despesas: float, resultado: float}>
     */
    public function serie(CarbonImmutable $ate, int $meses = 12): array
    {
        $inicio = $ate->startOfMonth()->subMonths($meses - 1);
        $dados  = $this->dados($inicio, $ate->endOfMonth());

        $serie = [];
        for ($m = $inicio; $m->lte($ate); $m = $m->addMonth()) {
            $d = $this->montar($m, $dados[$m->format('Y-m')] ?? $this->vazio());
            $serie[] = [
                'mes'       => $m,
                'receita'   => $d['receita_bruta'],
                'despesas'  => round($d['receita_bruta'] - $d['resultado'], 2),
                'resultado' => $d['resultado'],
            ];
        }

        return $serie;
    }

    /** @return array{contas: array<string, array<string, float>>, taxas: float, imposto: float, enc: array<string, float>} */
    private function vazio(): array
    {
        return ['contas' => [], 'taxas' => 0.0, 'imposto' => 0.0, 'enc' => ['juros_recebidos' => 0.0, 'descontos_obtidos' => 0.0, 'juros_pagos' => 0.0, 'descontos_concedidos' => 0.0]];
    }

    /**
     * Somas por mês: grupo → conta → valor; taxas de cartão; imposto estimado; encargos das baixas.
     *
     * @return array<string, array{contas: array<string, array<string, float>>, taxas: float, imposto: float, enc: array<string, float>}>
     */
    private function dados(CarbonImmutable $inicio, CarbonImmutable $fim): array
    {
        $periodo = [$inicio->toDateString(), $fim->toDateString()];
        $out     = [];

        $linhas = Transacao::query()
            ->leftJoin('plano_contas as pc', 'pc.id', '=', 'transacoes.categoria_id')
            ->where('transacoes.status', '!=', StatusTransacao::Cancelado->value)
            ->whereBetween('transacoes.data_competencia', $periodo)
            ->groupBy(DB::raw("to_char(transacoes.data_competencia, 'YYYY-MM')"), 'transacoes.tipo', 'pc.grupo', DB::raw('COALESCE(pc.nome, transacoes.categoria)'))
            ->selectRaw("to_char(transacoes.data_competencia, 'YYYY-MM') AS mes, transacoes.tipo AS tipo_raw, pc.grupo AS grupo,
                COALESCE(pc.nome, transacoes.categoria) AS conta, SUM(transacoes.valor_bruto) AS total,
                SUM(CASE WHEN transacoes.tipo = 'entrada' THEN transacoes.taxa_operacional ELSE 0 END) AS taxas,
                SUM(CASE WHEN transacoes.tipo = 'entrada' THEN transacoes.imposto_estimado ELSE 0 END) AS imposto")
            ->toBase()
            ->get();

        foreach ($linhas as $l) {
            $out[$l->mes] ??= $this->vazio();
            $grupo = $l->grupo ?? ($l->tipo_raw === TipoTransacao::Entrada->value ? G::OutrasReceitas->value : G::Administrativas->value);
            $out[$l->mes]['contas'][$grupo][$l->conta] = ($out[$l->mes]['contas'][$grupo][$l->conta] ?? 0) + (float) $l->total;
            $out[$l->mes]['taxas']   += (float) $l->taxas;
            $out[$l->mes]['imposto'] += (float) $l->imposto;
        }

        $encargos = TransacaoBaixa::query()
            ->whereBetween('data', $periodo)
            ->groupBy(DB::raw("to_char(data, 'YYYY-MM')"), 'tipo')
            ->selectRaw("to_char(data, 'YYYY-MM') AS mes, tipo AS tipo_raw, SUM(juros + multa) AS jm, SUM(desconto) AS desconto")
            ->toBase()
            ->get();

        foreach ($encargos as $e) {
            $out[$e->mes] ??= $this->vazio();
            if ($e->tipo_raw === TipoTransacao::Entrada->value) {
                $out[$e->mes]['enc']['juros_recebidos']      += (float) $e->jm;
                $out[$e->mes]['enc']['descontos_concedidos'] += (float) $e->desconto;
            } else {
                $out[$e->mes]['enc']['juros_pagos']       += (float) $e->jm;
                $out[$e->mes]['enc']['descontos_obtidos'] += (float) $e->desconto;
            }
        }

        return $out;
    }

    /** @param  array{contas: array<string, array<string, float>>, taxas: float, imposto: float, enc: array<string, float>}  $d */
    private function montar(CarbonImmutable $mes, array $d): array
    {
        $grupo = function (G $g, array $extra = []) use ($d): array {
            $contas = $d['contas'][$g->value] ?? [];
            foreach ($extra as $nome => $v) {
                $contas[$nome] = ($contas[$nome] ?? 0) + $v;
            }
            $contas = array_filter($contas, fn ($v) => abs($v) >= 0.005);
            arsort($contas);

            return ['rotulo' => $g->label(), 'valor' => round(array_sum($contas), 2), 'contas' => array_map(fn ($v) => round($v, 2), $contas)];
        };

        $linhas = [
            'receita_servicos' => $grupo(G::ReceitaServicos),
            'receita_produtos' => $grupo(G::ReceitaProdutos),
            'outras_receitas'  => $grupo(G::OutrasReceitas),
            'deducoes'         => $grupo(G::Deducoes),
            'custos_variaveis' => $grupo(G::CustosVariaveis, $d['taxas'] > 0 ? ['Taxas de cartão' => $d['taxas']] : []),
            'pessoal'          => $grupo(G::Pessoal),
            'ocupacao'         => $grupo(G::Ocupacao),
            'administrativas'  => $grupo(G::Administrativas),
            'marketing'        => $grupo(G::Marketing),
            'receitas_financeiras' => $grupo(G::ReceitasFinanceiras, array_filter([
                'Juros e multas recebidos' => $d['enc']['juros_recebidos'], 'Descontos obtidos' => $d['enc']['descontos_obtidos'],
            ])),
            'despesas_financeiras' => $grupo(G::DespesasFinanceiras, array_filter([
                'Juros e multas pagos' => $d['enc']['juros_pagos'], 'Descontos concedidos' => $d['enc']['descontos_concedidos'],
            ])),
        ];

        $receitaBruta  = $linhas['receita_servicos']['valor'] + $linhas['receita_produtos']['valor'] + $linhas['outras_receitas']['valor'];
        $receitaLiq    = $receitaBruta - $linhas['deducoes']['valor'];
        $margem        = $receitaLiq - $linhas['custos_variaveis']['valor'];
        $fixas         = $linhas['pessoal']['valor'] + $linhas['ocupacao']['valor'] + $linhas['administrativas']['valor'] + $linhas['marketing']['valor'];
        $operacional   = $margem - $fixas;
        $financeiro    = $linhas['receitas_financeiras']['valor'] - $linhas['despesas_financeiras']['valor'];
        $resultado     = $operacional + $financeiro;
        $pct           = fn (float $v) => $receitaBruta > 0 ? round($v / $receitaBruta * 100, 1) : null;

        return [
            'mes'                   => $mes,
            'linhas'                => $linhas,
            'receita_bruta'         => round($receitaBruta, 2),
            'deducoes'              => $linhas['deducoes']['valor'],
            'receita_liquida'       => round($receitaLiq, 2),
            'custos_variaveis'      => $linhas['custos_variaveis']['valor'],
            'margem'                => round($margem, 2),
            'margem_pct'            => $pct($margem),
            'despesas_fixas'        => round($fixas, 2),
            'resultado_operacional' => round($operacional, 2),
            'financeiro'            => round($financeiro, 2),
            'resultado'             => round($resultado, 2),
            'resultado_pct'         => $pct($resultado),
            'imposto_estimado'      => round($d['imposto'], 2),
            'fora'                  => [
                'aportes'       => $grupo(G::Aportes),
                'investimentos' => $grupo(G::Investimentos),
                'retiradas'     => $grupo(G::Retiradas),
            ],
        ];
    }

    /** @return Collection<int, array{0: string, 1: string}> linhas para exportar (rótulo, valor formatado sem R$) */
    public function paraCsv(array $dre): Collection
    {
        $l = $dre['linhas'];
        $linhas = collect();
        $add = function (string $rotulo, float $valor) use ($linhas): void {
            $linhas->push([$rotulo, number_format($valor, 2, ',', '')]);
        };
        $grupo = function (string $chave, string $sinal) use ($l, $add): void {
            $add("{$sinal} {$l[$chave]['rotulo']}", $l[$chave]['valor']);
            foreach ($l[$chave]['contas'] as $nome => $v) {
                $add('    ' . trim($nome), $v);
            }
        };

        $grupo('receita_servicos', '(+)');
        $grupo('receita_produtos', '(+)');
        $grupo('outras_receitas', '(+)');
        $add('= Receita bruta', $dre['receita_bruta']);
        $grupo('deducoes', '(-)');
        $add('= Receita líquida', $dre['receita_liquida']);
        $grupo('custos_variaveis', '(-)');
        $add('= Margem de contribuição', $dre['margem']);
        foreach (['pessoal', 'ocupacao', 'administrativas', 'marketing'] as $g) {
            $grupo($g, '(-)');
        }
        $add('= Resultado operacional', $dre['resultado_operacional']);
        $grupo('receitas_financeiras', '(+)');
        $grupo('despesas_financeiras', '(-)');
        $add('= Resultado do mês', $dre['resultado']);
        foreach ($dre['fora'] as $f) {
            $add("Fora do resultado: {$f['rotulo']}", $f['valor']);
        }

        return $linhas;
    }
}
