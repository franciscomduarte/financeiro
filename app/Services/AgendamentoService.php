<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Enums\TipoNotificacaoAgendamento;
use App\Jobs\NotificacaoAgendamentoJob;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\GradeHorario;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AgendamentoService
{
    public function __construct(
        private readonly GoogleCalendarService $googleCalendar,
    ) {}

    public function criar(array $dados): Agendamento
    {
        return DB::transaction(function () use ($dados): Agendamento {
            $procedimentoId = $dados['procedimento_id'];
            $procedimento   = \App\Models\Procedimento::findOrFail($procedimentoId);

            $inicioEm = Carbon::parse($dados['inicio_em'])->timezone(config('app.timezone'));
            $fimEm    = $inicioEm->copy()->addMinutes($procedimento->duracao_minutos);

            $agendamento = Agendamento::create([
                'paciente_id'           => $dados['paciente_id'],
                'profissional_id'       => $dados['profissional_id'],
                'procedimento_id'       => $procedimentoId,
                'inicio_em'             => $inicioEm,
                'fim_em'                => $fimEm,
                'status'                => StatusAgendamento::Agendado->value,
                'observacoes'           => $dados['observacoes'] ?? null,
                'agendamento_origem_id' => null,
            ]);

            $agendamento->load(['paciente', 'profissional', 'procedimento']);

            try {
                $eventId = $this->googleCalendar->criarEvento($agendamento);
                if ($eventId) {
                    $agendamento->update(['google_event_id' => $eventId]);
                    $agendamento->google_event_id = $eventId;
                }
            } catch (Throwable $e) {
                Log::warning('AgendamentoService: Google Calendar falhou ao criar', ['error' => $e->getMessage()]);
            }

            NotificacaoAgendamentoJob::dispatch($agendamento->id, TipoNotificacaoAgendamento::Confirmacao)
                ->onQueue('default');

            return $agendamento;
        });
    }

    public function cancelar(Agendamento $agendamento, string $motivo): Agendamento
    {
        if (! $agendamento->status->isPendente()) {
            throw new RuntimeException("Agendamento com status '{$agendamento->status->label()}' não pode ser cancelado.");
        }

        return DB::transaction(function () use ($agendamento, $motivo): Agendamento {
            $agendamento->update([
                'status'               => StatusAgendamento::Cancelado->value,
                'motivo_cancelamento'  => $motivo,
            ]);
            $agendamento->refresh();
            $agendamento->load(['paciente', 'profissional', 'procedimento']);

            if ($agendamento->google_event_id && $agendamento->profissional?->google_calendar_id) {
                try {
                    $this->googleCalendar->deletarEvento(
                        $agendamento->google_event_id,
                        $agendamento->profissional->google_calendar_id,
                    );
                } catch (Throwable $e) {
                    Log::warning('AgendamentoService: Google Calendar falhou ao deletar', ['error' => $e->getMessage()]);
                }
            }

            NotificacaoAgendamentoJob::dispatch($agendamento->id, TipoNotificacaoAgendamento::Cancelamento)
                ->onQueue('default');

            return $agendamento;
        });
    }

    public function reagendar(Agendamento $agendamento, string $novaData, string $novoHorario): Agendamento
    {
        if (! $agendamento->status->isPendente()) {
            throw new RuntimeException("Agendamento com status '{$agendamento->status->label()}' não pode ser reagendado.");
        }

        return DB::transaction(function () use ($agendamento, $novaData, $novoHorario): Agendamento {
            $agendamento->load(['paciente', 'profissional', 'procedimento']);
            $procedimento = $agendamento->procedimento;

            // Marca o original como reagendado e deleta o evento Google
            $eventIdOriginal   = $agendamento->google_event_id;
            $calendarIdOriginal = $agendamento->profissional?->google_calendar_id;

            $agendamento->update(['status' => StatusAgendamento::Reagendado->value]);

            if ($eventIdOriginal && $calendarIdOriginal) {
                try {
                    $this->googleCalendar->deletarEvento($eventIdOriginal, $calendarIdOriginal);
                } catch (Throwable $e) {
                    Log::warning('AgendamentoService: Google Calendar falhou ao deletar (reagendamento)', ['error' => $e->getMessage()]);
                }
            }

            // Cria o novo agendamento
            $inicioEm = Carbon::parse("{$novaData} {$novoHorario}")->timezone(config('app.timezone'));
            $fimEm    = $inicioEm->copy()->addMinutes($procedimento->duracao_minutos);

            $novo = Agendamento::create([
                'paciente_id'           => $agendamento->paciente_id,
                'profissional_id'       => $agendamento->profissional_id,
                'procedimento_id'       => $agendamento->procedimento_id,
                'inicio_em'             => $inicioEm,
                'fim_em'                => $fimEm,
                'status'                => StatusAgendamento::Agendado->value,
                'observacoes'           => $agendamento->observacoes,
                'agendamento_origem_id' => $agendamento->id,
            ]);

            $novo->load(['paciente', 'profissional', 'procedimento']);

            try {
                $eventId = $this->googleCalendar->criarEvento($novo);
                if ($eventId) {
                    $novo->update(['google_event_id' => $eventId]);
                    $novo->google_event_id = $eventId;
                }
            } catch (Throwable $e) {
                Log::warning('AgendamentoService: Google Calendar falhou ao criar (reagendamento)', ['error' => $e->getMessage()]);
            }

            NotificacaoAgendamentoJob::dispatch($novo->id, TipoNotificacaoAgendamento::Reagendamento)
                ->onQueue('default');

            return $novo;
        });
    }

    public function marcarRealizado(Agendamento $agendamento): Agendamento
    {
        $agendamento->update(['status' => StatusAgendamento::Realizado->value]);
        $agendamento->refresh();
        $agendamento->load(['paciente', 'profissional', 'procedimento']);

        try {
            $this->googleCalendar->atualizarEvento($agendamento);
        } catch (Throwable $e) {
            Log::warning('AgendamentoService: Google Calendar falhou ao atualizar (realizado)', ['error' => $e->getMessage()]);
        }

        return $agendamento;
    }

    public function marcarFalta(Agendamento $agendamento): Agendamento
    {
        $agendamento->update(['status' => StatusAgendamento::Falta->value]);
        $agendamento->refresh();
        $agendamento->load(['paciente', 'profissional', 'procedimento']);

        try {
            $this->googleCalendar->atualizarEvento($agendamento);
        } catch (Throwable $e) {
            Log::warning('AgendamentoService: Google Calendar falhou ao atualizar (falta)', ['error' => $e->getMessage()]);
        }

        return $agendamento;
    }

    /**
     * @return list<string> ex: ["09:00", "10:00", "14:00"]
     */
    public function slotsDisponiveis(string $profissionalId, string $data, int $duracaoMinutos): array
    {
        $dataCarbon = Carbon::parse($data);
        $diaSemana  = (int) $dataCarbon->dayOfWeek; // 0=dom, 6=sab

        $grade = GradeHorario::where('profissional_id', $profissionalId)
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();

        if (! $grade) {
            return [];
        }

        // Gera todos os slots possíveis da grade
        $inicio = Carbon::parse("{$data} {$grade->hora_inicio}");
        $fim    = Carbon::parse("{$data} {$grade->hora_fim}");
        $slots  = [];

        $cursor = $inicio->copy();
        while ($cursor->copy()->addMinutes($duracaoMinutos)->lte($fim)) {
            $slots[] = $cursor->copy();
            $cursor->addMinutes($duracaoMinutos);
        }

        if (empty($slots)) {
            return [];
        }

        // Busca agendamentos ativos no dia
        $agendamentosOcupados = Agendamento::where('profissional_id', $profissionalId)
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->whereDate('inicio_em', $data)
            ->get(['inicio_em', 'fim_em']);

        // Busca bloqueios no dia
        $bloqueios = BloqueioAgenda::where('profissional_id', $profissionalId)
            ->where('inicio_em', '<', Carbon::parse("{$data} {$grade->hora_fim}"))
            ->where('fim_em', '>', Carbon::parse("{$data} {$grade->hora_inicio}"))
            ->get(['inicio_em', 'fim_em']);

        $ocupados = $agendamentosOcupados->concat($bloqueios);

        return array_values(
            array_map(
                fn (Carbon $s) => $s->format('H:i'),
                array_filter($slots, function (Carbon $slot) use ($ocupados, $duracaoMinutos): bool {
                    $slotFim = $slot->copy()->addMinutes($duracaoMinutos);
                    foreach ($ocupados as $ocupado) {
                        $ocInicio = Carbon::parse($ocupado->inicio_em);
                        $ocFim    = Carbon::parse($ocupado->fim_em);
                        // Colisão: slot.inicio < ocupado.fim AND slot.fim > ocupado.inicio
                        if ($slot->lt($ocFim) && $slotFim->gt($ocInicio)) {
                            return false;
                        }
                    }
                    return true;
                }),
            )
        );
    }
}
