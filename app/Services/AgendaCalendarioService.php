<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Enums\VisaoAgenda;
use App\Models\Agendamento;
use App\Models\GradeHorario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Monta os dados das visões de calendário (dia / semana / mês) da agenda.
 */
class AgendaCalendarioService
{
    /** Teto de agendamentos carregados por período (evita get() sem limite). */
    public const LIMITE_POR_PERIODO = 500;

    /** Granularidade da grade de horários, em minutos. */
    public const MINUTOS_POR_SLOT = 30;

    /** Faixa exibida quando não há grade cadastrada: 08:00–19:00. */
    private const FAIXA_PADRAO = [8 * 60, 19 * 60];

    private const COR_PADRAO = '#8b5cf6';

    /**
     * Período [inicio, fim) coberto pela visão a partir da data de referência.
     * Semanas começam na segunda-feira; o mês ocupa semanas completas.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodo(VisaoAgenda $visao, CarbonImmutable $referencia): array
    {
        $ref = $referencia->startOfDay();

        return match ($visao) {
            VisaoAgenda::Dia, VisaoAgenda::Lista => [$ref, $ref->addDay()],
            VisaoAgenda::Semana => [
                $ref->startOfWeek(CarbonImmutable::MONDAY),
                $ref->startOfWeek(CarbonImmutable::MONDAY)->addWeek(),
            ],
            VisaoAgenda::Mes => [
                $ref->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY),
                $ref->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)->addDay()->startOfDay(),
            ],
        };
    }

    /** Data de referência após avançar (+1) ou voltar (-1) um período. */
    public function navegar(VisaoAgenda $visao, CarbonImmutable $referencia, int $direcao): CarbonImmutable
    {
        $passo = $direcao >= 0 ? 1 : -1;

        return match ($visao) {
            VisaoAgenda::Dia, VisaoAgenda::Lista => $referencia->addDays($passo),
            VisaoAgenda::Semana                  => $referencia->addWeeks($passo),
            VisaoAgenda::Mes                     => $referencia->startOfMonth()->addMonthsNoOverflow($passo),
        };
    }

    /**
     * Agendamentos do período, já com as relações usadas pelo calendário.
     * Sem filtro de status, cancelados e reagendados ficam de fora para não poluir a grade.
     */
    public function agendamentosDoPeriodo(
        CarbonImmutable $inicio,
        CarbonImmutable $fim,
        string $profissionalId = '',
        string $status = '',
    ): Collection {
        $agendamentos = Agendamento::with([
            'paciente:id,nome,telefone',
            'profissional:id,nome,cor_agenda',
            'procedimento:id,nome,duracao_minutos',
        ])
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em', 'fim_em', 'status'])
            ->where('inicio_em', '>=', $inicio)
            ->where('inicio_em', '<', $fim)
            ->when($profissionalId, fn ($q) => $q->where('profissional_id', $profissionalId))
            ->when(
                $status,
                fn ($q) => $q->where('status', $status),
                fn ($q) => $q->whereNotIn('status', [
                    StatusAgendamento::Cancelado->value,
                    StatusAgendamento::Reagendado->value,
                ]),
            )
            ->orderBy('inicio_em')
            ->limit(self::LIMITE_POR_PERIODO)
            ->get();

        if ($agendamentos->count() >= self::LIMITE_POR_PERIODO) {
            Log::warning('[AgendaCalendario] limite de agendamentos por período atingido', [
                'inicio'          => $inicio->toDateString(),
                'fim'             => $fim->toDateString(),
                'profissional_id' => $profissionalId ?: null,
            ]);
        }

        return $agendamentos;
    }

    /**
     * Menor início e maior fim das grades ativas, em minutos desde 00:00.
     *
     * @return array{0: int, 1: int}|null
     */
    public function limitesGrade(string $profissionalId = ''): ?array
    {
        $limites = GradeHorario::query()
            ->where('ativo', true)
            ->when($profissionalId, fn ($q) => $q->where('profissional_id', $profissionalId))
            ->selectRaw('MIN(hora_inicio) as inicio, MAX(hora_fim) as fim')
            ->first();

        if (! $limites?->inicio || ! $limites?->fim) {
            return null;
        }

        return [self::paraMinutos((string) $limites->inicio), self::paraMinutos((string) $limites->fim)];
    }

    /**
     * Faixa horária exibida na grade, em minutos desde 00:00, alinhada a horas cheias.
     * Parte da grade cadastrada (ou 08–19h) e se expande para caber todos os agendamentos.
     *
     * @param  array{0: int, 1: int}|null  $limitesGrade
     * @return array{0: int, 1: int}
     */
    public function faixaHoraria(?array $limitesGrade, Collection $agendamentos): array
    {
        [$inicio, $fim] = $limitesGrade ?? self::FAIXA_PADRAO;

        foreach ($agendamentos as $ag) {
            $inicio = min($inicio, $ag->inicio_em->hour * 60 + $ag->inicio_em->minute);
            $fimAg  = $ag->fim_em->isSameDay($ag->inicio_em)
                ? $ag->fim_em->hour * 60 + $ag->fim_em->minute
                : 24 * 60;
            $fim = max($fim, $fimAg);
        }

        $inicio = intdiv($inicio, 60) * 60;
        $fim    = min(24 * 60, (int) ceil($fim / 60) * 60);

        if ($fim <= $inicio) {
            $fim = min(24 * 60, $inicio + 60);
        }

        return [$inicio, $fim];
    }

    /**
     * Distribui agendamentos sobrepostos de um mesmo dia em colunas lado a lado.
     * Cada grupo de eventos que se sobrepõem divide a largura igualmente.
     *
     * @return list<array{agendamento: Agendamento, coluna: int, colunas: int}>
     */
    public function layoutDia(Collection $agendamentos): array
    {
        $ordenados = $agendamentos->sortBy([
            fn ($a, $b) => $a->inicio_em <=> $b->inicio_em,
            fn ($a, $b) => $b->fim_em <=> $a->fim_em,
        ])->values();

        $resultado = [];
        $grupo     = [];   // índices em $resultado do grupo atual
        $colunas   = [];   // fim (timestamp) do último evento em cada coluna
        $fimGrupo  = null;

        $fecharGrupo = function () use (&$resultado, &$grupo, &$colunas): void {
            foreach ($grupo as $i) {
                $resultado[$i]['colunas'] = count($colunas);
            }
            $grupo   = [];
            $colunas = [];
        };

        foreach ($ordenados as $ag) {
            $inicio = $ag->inicio_em->getTimestamp();
            $fim    = max($ag->fim_em->getTimestamp(), $inicio + 60);

            if ($fimGrupo !== null && $inicio >= $fimGrupo) {
                $fecharGrupo();
                $fimGrupo = null;
            }

            $coluna = null;
            foreach ($colunas as $i => $fimColuna) {
                if ($fimColuna <= $inicio) {
                    $coluna = $i;
                    break;
                }
            }
            $coluna ??= count($colunas);
            $colunas[$coluna] = $fim;

            $resultado[] = ['agendamento' => $ag, 'coluna' => $coluna, 'colunas' => 1];
            $grupo[]     = array_key_last($resultado);
            $fimGrupo    = max($fimGrupo ?? $fim, $fim);
        }

        $fecharGrupo();

        return $resultado;
    }

    /** Cor da agenda do profissional, apenas se for um hex válido (vai para atributo style). */
    public static function corSegura(?string $cor): string
    {
        return $cor !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $cor) === 1 ? $cor : self::COR_PADRAO;
    }

    private static function paraMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', array_pad(explode(':', $hora), 2, '0'));

        return $h * 60 + $m;
    }
}
