<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GrupoPlanoContas;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\ContaFinanceira;
use App\Models\Recorrencia;
use App\Models\Transacao;
use App\Models\TransacaoBaixa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Fluxo de caixa: o realizado (o dinheiro que de fato entrou e saiu, pelas baixas) e o projetado
 * (saldo de hoje + contas a receber − contas a pagar por vencimento + recorrências ainda não geradas).
 * Transferências entre contas da clínica não entram: não mudam o total.
 */
class FluxoCaixaService
{
    public function __construct(private readonly SaldoContasService $saldos) {}

    /** Saldo somado das contas ativas ao fim do dia. */
    public function saldoTotal(?CarbonImmutable $ate = null): float
    {
        $ativas = ContaFinanceira::query()->ativas()->pluck('id');
        $saldos = $this->saldos->saldos($ate);

        return round((float) $ativas->sum(fn ($id) => $saldos[$id] ?? 0), 2);
    }

    /**
     * Entradas e saídas realizadas por período (dia ou mês), com o detalhe por grupo do plano de contas.
     *
     * @return array{periodos: array<int, array{chave: string, rotulo: string, entradas: float, saidas: float, liquido: float, saldo: float}>,
     *               grupos: array<string, array{rotulo: string, tipo: string, valores: array<string, float>, total: float}>,
     *               saldo_inicial: float, entradas: float, saidas: float, saldo_final: float}
     */
    public function realizado(CarbonImmutable $inicio, CarbonImmutable $fim, string $por = 'mes'): array
    {
        $fmt     = $por === 'dia' ? 'YYYY-MM-DD' : 'YYYY-MM';
        $periodo = [$inicio->toDateString(), $fim->toDateString()];

        $linhas = TransacaoBaixa::query()
            ->join('transacoes as t', 't.id', '=', 'transacao_baixas.transacao_id')
            ->leftJoin('plano_contas as pc', 'pc.id', '=', 't.categoria_id')
            ->whereBetween('transacao_baixas.data', $periodo)
            ->groupBy(DB::raw("to_char(transacao_baixas.data, '{$fmt}')"), 'transacao_baixas.tipo', 'pc.grupo')
            ->selectRaw("to_char(transacao_baixas.data, '{$fmt}') AS chave, transacao_baixas.tipo AS tipo_raw, pc.grupo AS grupo, SUM(transacao_baixas.valor_movimentado) AS total")
            ->toBase()
            ->get();

        $chaves = [];
        for ($d = $inicio; $d->lte($fim); $d = $por === 'dia' ? $d->addDay() : $d->addMonth()) {
            $chaves[$por === 'dia' ? $d->toDateString() : $d->format('Y-m')] = $d;
        }

        $grupos = [];
        $porChave = array_fill_keys(array_keys($chaves), ['entradas' => 0.0, 'saidas' => 0.0]);
        foreach ($linhas as $l) {
            $entrada = $l->tipo_raw === TipoTransacao::Entrada->value;
            $g = GrupoPlanoContas::tryFrom((string) $l->grupo) ?? ($entrada ? GrupoPlanoContas::OutrasReceitas : GrupoPlanoContas::Administrativas);
            $grupos[$g->value] ??= ['rotulo' => $g->label(), 'tipo' => $g->tipo()->value, 'valores' => [], 'total' => 0.0];
            $grupos[$g->value]['valores'][$l->chave] = ($grupos[$g->value]['valores'][$l->chave] ?? 0) + (float) $l->total;
            $grupos[$g->value]['total'] += (float) $l->total;
            if (isset($porChave[$l->chave])) {
                $porChave[$l->chave][$entrada ? 'entradas' : 'saidas'] += (float) $l->total;
            }
        }

        // Grupos na ordem do plano (entradas primeiro)
        $ordem  = array_flip(array_map(fn (GrupoPlanoContas $g) => $g->value, GrupoPlanoContas::cases()));
        uksort($grupos, fn ($a, $b) => $ordem[$a] <=> $ordem[$b]);

        $saldoInicial = $this->saldoTotal($inicio->subDay());
        $saldo = $saldoInicial;
        $periodos = [];
        foreach ($chaves as $chave => $data) {
            $e = round($porChave[$chave]['entradas'], 2);
            $s = round($porChave[$chave]['saidas'], 2);
            $saldo += $e - $s;
            $periodos[] = [
                'chave'    => $chave,
                'rotulo'   => $por === 'dia' ? $data->format('d/m') : ucfirst($data->translatedFormat('M/y')),
                'entradas' => $e,
                'saidas'   => $s,
                'liquido'  => round($e - $s, 2),
                'saldo'    => round($saldo, 2),
            ];
        }

        return [
            'periodos'      => $periodos,
            'grupos'        => array_map(fn ($g) => $g + ['total' => round($g['total'], 2)], $grupos),
            'saldo_inicial' => $saldoInicial,
            'entradas'      => round(array_sum(array_column($periodos, 'entradas')), 2),
            'saidas'        => round(array_sum(array_column($periodos, 'saidas')), 2),
            'saldo_final'   => round($saldo, 2),
        ];
    }

    /**
     * Saldo projetado dos próximos $dias dias, semana a semana, e a curva diária.
     * Contas vencidas aparecem à parte (não entram na curva: não se sabe quando serão pagas).
     *
     * @return array{saldo_hoje: float, semanas: array<int, array{inicio: CarbonImmutable, fim: CarbonImmutable, entradas: float, saidas: float, saldo: float}>,
     *               diario: array<int, array{data: CarbonImmutable, saldo: float}>, vencidos_receber: float, vencidos_pagar: float,
     *               entradas: float, saidas: float, saldo_final: float, menor_saldo: float, menor_saldo_em: ?CarbonImmutable}
     */
    public function projetado(int $dias = 90): array
    {
        $hoje = CarbonImmutable::today();
        $fim  = $hoje->addDays($dias);
        $mov  = []; // data => [entradas, saidas]

        $aberto = 'GREATEST(valor_bruto - valor_pago, 0)';
        // Entradas: o que deve cair na conta (sem a taxa da maquininha, proporcional ao que falta)
        $liquido = "CASE WHEN tipo = 'entrada' AND valor_bruto > 0 THEN {$aberto} * (1 - COALESCE(taxa_operacional, 0) / valor_bruto) ELSE {$aberto} END";

        $abertos = Transacao::query()
            ->whereIn('status', StatusTransacao::abertos())
            ->where('data_vencimento', '<=', $fim->toDateString())
            ->groupBy('data_vencimento', 'tipo')
            ->selectRaw("data_vencimento AS data, tipo AS tipo_raw, SUM({$liquido}) AS total")
            ->toBase()
            ->get();

        $vencidosReceber = 0.0;
        $vencidosPagar   = 0.0;
        foreach ($abertos as $a) {
            $data = CarbonImmutable::parse($a->data);
            $entrada = $a->tipo_raw === TipoTransacao::Entrada->value;
            if ($data->lt($hoje)) {
                $entrada ? $vencidosReceber += (float) $a->total : $vencidosPagar += (float) $a->total;
                continue;
            }
            $mov[$data->toDateString()][$entrada ? 0 : 1] = ($mov[$data->toDateString()][$entrada ? 0 : 1] ?? 0) + (float) $a->total;
        }

        // Recorrências: ocorrências futuras que ainda não viraram lançamento
        Recorrencia::query()
            ->where('ativa', true)->whereNull('encerrada_em')
            ->where('proxima_data', '<=', $fim->toDateString())
            ->select(['id', 'tipo', 'valor_bruto', 'frequencia', 'dia_vencimento', 'proxima_data', 'data_fim'])
            ->limit(500)
            ->get()
            ->each(function (Recorrencia $r) use (&$mov, $hoje, $fim): void {
                for ($d = $r->proxima_data, $i = 0; $d !== null && $d->lte($fim) && $i < 400; $d = $r->dataSeguinte($d), $i++) {
                    if ($r->data_fim !== null && $d->gt($r->data_fim)) {
                        break;
                    }
                    if ($d->lt($hoje)) {
                        continue;
                    }
                    $k = $r->tipo === TipoTransacao::Entrada ? 0 : 1;
                    $mov[$d->toDateString()][$k] = ($mov[$d->toDateString()][$k] ?? 0) + (float) $r->valor_bruto;
                }
            });

        $saldoHoje = $this->saldoTotal($hoje); // saldo atual; o que vence hoje e ainda está em aberto entra na curva
        $saldo = $saldoHoje;
        $diario = [];
        $semanas = [];
        $menor = $saldo;
        $menorEm = null;
        $totE = 0.0;
        $totS = 0.0;

        for ($d = $hoje, $i = 0; $d->lte($fim); $d = $d->addDay(), $i++) {
            [$e, $s] = [round($mov[$d->toDateString()][0] ?? 0, 2), round($mov[$d->toDateString()][1] ?? 0, 2)];
            $saldo += $e - $s;
            $totE += $e;
            $totS += $s;
            $diario[] = ['data' => $d, 'saldo' => round($saldo, 2)];
            if ($saldo < $menor) {
                $menor = $saldo;
                $menorEm = $d;
            }

            $w = intdiv($i, 7);
            $semanas[$w] ??= ['inicio' => $d, 'fim' => $d, 'entradas' => 0.0, 'saidas' => 0.0, 'saldo' => 0.0];
            $semanas[$w]['fim'] = $d;
            $semanas[$w]['entradas'] = round($semanas[$w]['entradas'] + $e, 2);
            $semanas[$w]['saidas']   = round($semanas[$w]['saidas'] + $s, 2);
            $semanas[$w]['saldo']    = round($saldo, 2);
        }

        return [
            'saldo_hoje'       => round($saldoHoje, 2),
            'semanas'          => array_values($semanas),
            'diario'           => $diario,
            'vencidos_receber' => round($vencidosReceber, 2),
            'vencidos_pagar'   => round($vencidosPagar, 2),
            'entradas'         => round($totE, 2),
            'saidas'           => round($totS, 2),
            'saldo_final'      => round($saldo, 2),
            'menor_saldo'      => round($menor, 2),
            'menor_saldo_em'   => $menorEm,
        ];
    }
}
