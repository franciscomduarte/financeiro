<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cadastro e remoção de bloqueios da agenda (férias, folgas, feriados, compromissos).
 */
class BloqueioAgendaService
{
    /** Teto de agendamentos listados como conflito após criar um bloqueio. */
    public const LIMITE_CONFLITOS = 100;

    /**
     * Cria um bloqueio para cada profissional informado. Quando há mais de um,
     * todos compartilham o mesmo grupo_id para serem listados e removidos juntos.
     *
     * @param  list<string>  $profissionalIds
     * @return array{bloqueios: Collection<int, BloqueioAgenda>, conflitos: Collection<int, Agendamento>}
     */
    public function criar(
        array $profissionalIds,
        CarbonImmutable $inicio,
        CarbonImmutable $fim,
        bool $diaInteiro,
        ?string $motivo,
    ): array {
        $grupoId = count($profissionalIds) > 1 ? (string) Str::uuid() : null;

        $bloqueios = DB::transaction(fn () => collect($profissionalIds)->map(
            fn (string $profissionalId) => BloqueioAgenda::create([
                'profissional_id' => $profissionalId,
                'grupo_id'        => $grupoId,
                'inicio_em'       => $inicio,
                'fim_em'          => $fim,
                'dia_inteiro'     => $diaInteiro,
                'motivo'          => $motivo,
            ]),
        ));

        Log::info('[BloqueioAgenda] bloqueio criado', [
            'user_id'          => auth()->id(),
            'grupo_id'         => $grupoId,
            'profissional_ids' => $profissionalIds,
            'inicio_em'        => $inicio->toDateTimeString(),
            'fim_em'           => $fim->toDateTimeString(),
        ]);

        return [
            'bloqueios' => $bloqueios,
            'conflitos' => $this->agendamentosConflitantes($profissionalIds, $inicio, $fim),
        ];
    }

    /**
     * Agendamentos ainda pendentes (agendado/confirmado) que caem dentro do período.
     *
     * @param  list<string>  $profissionalIds
     * @return Collection<int, Agendamento>
     */
    public function agendamentosConflitantes(array $profissionalIds, CarbonImmutable $inicio, CarbonImmutable $fim): Collection
    {
        return Agendamento::with(['paciente:id,nome', 'profissional:id,nome'])
            ->select(['id', 'paciente_id', 'profissional_id', 'inicio_em', 'fim_em', 'status'])
            ->whereIn('profissional_id', $profissionalIds)
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->where('inicio_em', '<', $fim)
            ->where('fim_em', '>', $inicio)
            ->orderBy('inicio_em')
            ->limit(self::LIMITE_CONFLITOS)
            ->get();
    }

    /** Remove o bloqueio e, se fizer parte de um grupo, todos os do mesmo grupo. */
    public function remover(BloqueioAgenda $bloqueio): int
    {
        app(\App\Support\ClinicaAtual::class)->garantirEscrita(); // delete em massa não dispara eventos do Model

        $removidos = DB::transaction(fn () => $bloqueio->grupo_id
            ? BloqueioAgenda::where('grupo_id', $bloqueio->grupo_id)->delete()
            : (int) $bloqueio->delete());

        Log::info('[BloqueioAgenda] bloqueio removido', [
            'user_id'   => auth()->id(),
            'id'        => $bloqueio->id,
            'grupo_id'  => $bloqueio->grupo_id,
            'removidos' => $removidos,
        ]);

        return $removidos;
    }

    /**
     * Junta os bloqueios de um mesmo grupo numa única entrada para exibição.
     *
     * @param  Collection<int, BloqueioAgenda>  $bloqueios  com a relação profissional carregada
     * @return Collection<int, array{id: int, inicio_em: CarbonImmutable, fim_em: CarbonImmutable, dia_inteiro: bool, motivo: ?string, profissionais: list<string>, cor: string, rotulo: string}>
     */
    public function agrupar(Collection $bloqueios): Collection
    {
        return $bloqueios
            ->groupBy(fn (BloqueioAgenda $b) => $b->grupo_id ?? 'id-' . $b->id)
            ->map(function (Collection $grupo) {
                /** @var BloqueioAgenda $primeiro */
                $primeiro = $grupo->first();
                $nomes    = $grupo->map(fn (BloqueioAgenda $b) => $b->profissional?->nome ?? '—')->sort()->values()->all();

                return [
                    'id'            => $primeiro->id,
                    'inicio_em'     => $primeiro->inicio_em->toImmutable(),
                    'fim_em'        => $primeiro->fim_em->toImmutable(),
                    'dia_inteiro'   => (bool) $primeiro->dia_inteiro,
                    'motivo'        => $primeiro->motivo,
                    'profissionais' => $nomes,
                    'cor'           => count($nomes) === 1
                        ? AgendaCalendarioService::corSegura($primeiro->profissional?->cor_agenda)
                        : '#78716c',
                    'rotulo'        => $primeiro->grupo_id ? 'Todos os profissionais' : $nomes[0],
                ];
            })
            ->sortBy('inicio_em')
            ->values();
    }

    /** Texto do período, ex.: "10/12 a 20/12/2026 (dia inteiro)" ou "05/10/2026, 14:00–16:00". */
    public static function descreverPeriodo(CarbonImmutable $inicio, CarbonImmutable $fim, bool $diaInteiro): string
    {
        if ($diaInteiro) {
            $ultimoDia = $fim->subDay();

            return $ultimoDia->isSameDay($inicio)
                ? $inicio->format('d/m/Y') . ' (dia inteiro)'
                : $inicio->format('d/m') . ' a ' . $ultimoDia->format('d/m/Y') . ' (dias inteiros)';
        }

        return $inicio->isSameDay($fim)
            ? $inicio->format('d/m/Y, H:i') . '–' . $fim->format('H:i')
            : $inicio->format('d/m/Y H:i') . ' a ' . $fim->format('d/m/Y H:i');
    }
}
