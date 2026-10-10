<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EtapaLead;
use App\Enums\OrigemLead;
use App\Enums\StatusTransacao;
use App\Enums\TipoTransacao;
use App\Models\Lead;
use App\Models\Transacao;
use Carbon\CarbonInterface;

/** Números do funil de leads no período: por origem, por etapa, perdas, tempo de resposta e faturamento. */
class RelatorioLeadsService
{
    /** @return array{total: int, convertidos: int, taxa: float, horas_primeiro_contato: ?float, origens: array<int, array<string, mixed>>, etapas: array<string, int>, perdas: array<string, int>} */
    public function gerar(CarbonInterface $de, CarbonInterface $ate): array
    {
        $doPeriodo = fn () => Lead::query()->whereBetween('created_at', [$de->copy()->startOfDay(), $ate->copy()->endOfDay()]);

        // Quem já era paciente não entra na conversão (não era um lead de verdade)
        $porOrigem = $doPeriodo()->where('etapa', '!=', EtapaLead::JaPaciente)->selectRaw("origem, count(*) as total, count(*) filter (where etapa = 'fechado') as convertidos")
            ->groupBy('origem')->get()->keyBy(fn ($r) => $r->origem instanceof OrigemLead ? $r->origem->value : (string) $r->origem);

        // Faturamento dos pacientes que vieram de leads do período (receitas pagas depois da conversão)
        $faturamento = Transacao::query()
            ->join('leads', fn ($j) => $j->on('leads.paciente_id', '=', 'transacoes.paciente_id')->on('leads.tenant_id', '=', 'transacoes.tenant_id'))
            ->whereBetween('leads.created_at', [$de->copy()->startOfDay(), $ate->copy()->endOfDay()])
            ->whereNotNull('leads.convertido_em')
            ->whereColumn('transacoes.data_competencia', '>=', \Illuminate\Support\Facades\DB::raw('leads.convertido_em::date'))
            ->where('transacoes.tipo', TipoTransacao::Entrada)->where('transacoes.status', StatusTransacao::Pago)
            ->selectRaw('leads.origem as origem, sum(transacoes.valor_bruto) as total')
            ->groupBy('leads.origem')->pluck('total', 'origem');

        $origens = collect(OrigemLead::cases())->map(function (OrigemLead $o) use ($porOrigem, $faturamento) {
            $linha = $porOrigem->get($o->value);
            $total = (int) ($linha->total ?? 0);
            $conv  = (int) ($linha->convertidos ?? 0);

            return ['origem' => $o, 'total' => $total, 'convertidos' => $conv, 'taxa' => $total ? round($conv * 100 / $total, 1) : 0.0,
                'faturamento' => (float) ($faturamento[$o->value] ?? 0)];
        })->filter(fn ($l) => $l['total'] > 0)->sortByDesc('total')->values()->all();

        $total       = (int) $porOrigem->sum('total');
        $convertidos = (int) $porOrigem->sum('convertidos');

        $horas = $doPeriodo()->whereNotNull('primeiro_contato_em')
            ->selectRaw('avg(extract(epoch from (primeiro_contato_em - created_at)) / 3600) as horas')->value('horas');

        return [
            'total'                  => $total,
            'convertidos'            => $convertidos,
            'taxa'                   => $total ? round($convertidos * 100 / $total, 1) : 0.0,
            'horas_primeiro_contato' => $horas !== null ? round((float) $horas, 1) : null,
            'origens'                => $origens,
            'etapas'                 => $doPeriodo()->selectRaw('etapa, count(*) as total')->groupBy('etapa')->pluck('total', 'etapa')
                ->mapWithKeys(fn ($t, $e) => [($e instanceof EtapaLead ? $e->value : (string) $e) => (int) $t])->all(),
            'perdas'                 => $doPeriodo()->where('etapa', EtapaLead::Perdido)->whereNotNull('motivo_perda')
                ->selectRaw('motivo_perda, count(*) as total')->groupBy('motivo_perda')->orderByDesc('total')->limit(10)
                ->pluck('total', 'motivo_perda')->map(fn ($t) => (int) $t)->all(),
        ];
    }
}
