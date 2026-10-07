<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusAgendamento;
use App\Enums\TipoNotificacao;
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
        private readonly NotificacaoService $notificacoes,
    ) {}

    public function criar(array $dados): Agendamento
    {
        // Perfil Profissional só agenda para si mesmo (telas e API)
        if ($proprio = app(\App\Support\EscopoProfissional::class)->profissionalId()) {
            $dados['profissional_id'] = $proprio;
        }

        return DB::transaction(function () use ($dados): Agendamento {
            // Suporta array de procedimentos ou ID único (retrocompatibilidade)
            $procedimentoIds = $dados['procedimentos_ids'] ?? [$dados['procedimento_id']];
            $procedimentoIdPrincipal = $procedimentoIds[0];

            $duracaoTotal = \App\Models\Procedimento::whereIn('id', $procedimentoIds)
                ->sum('duracao_minutos');

            $inicioEm = Carbon::parse($dados['inicio_em'])->timezone(config('app.timezone'));
            $fimEm    = $inicioEm->copy()->addMinutes(max(1, $duracaoTotal));

            $agendamento = Agendamento::create([
                'paciente_id'           => $dados['paciente_id'],
                'profissional_id'       => $dados['profissional_id'],
                'procedimento_id'       => $procedimentoIdPrincipal,
                'procedimentos_ids'     => $procedimentoIds,
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

            $this->notificacoes->avisarAgendamento($agendamento, TipoNotificacao::AgendamentoConfirmado);

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

            $this->notificacoes->avisarAgendamento($agendamento, TipoNotificacao::AgendamentoCancelado);

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
            $duracao = self::duracaoMinutos($agendamento);

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
            $fimEm    = $inicioEm->copy()->addMinutes($duracao);

            $novo = Agendamento::create([
                'paciente_id'           => $agendamento->paciente_id,
                'profissional_id'       => $agendamento->profissional_id,
                'procedimento_id'       => $agendamento->procedimento_id,
                'procedimentos_ids'     => $agendamento->procedimentos_ids,
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

            $this->notificacoes->avisarAgendamento($novo, TipoNotificacao::AgendamentoRemarcado);

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

    /** Duração do atendimento: a marcada no agendamento (fim − início) ou a soma dos procedimentos. */
    public static function duracaoMinutos(Agendamento $agendamento): int
    {
        if ($agendamento->inicio_em && $agendamento->fim_em && $agendamento->fim_em->gt($agendamento->inicio_em)) {
            return (int) $agendamento->inicio_em->diffInMinutes($agendamento->fim_em);
        }
        $ids = $agendamento->procedimentos_ids ?: [$agendamento->procedimento_id];

        return max(1, (int) \App\Models\Procedimento::whereIn('id', $ids)->sum('duracao_minutos'));
    }

    /**
     * Por que o horário não está livre (fora do expediente, intervalo, outro paciente, bloqueio); null se estiver livre.
     * Usado para avisar no encaixe: a equipe pode marcar mesmo assim.
     */
    public function conflito(string $profissionalId, string $data, string $hora, int $duracaoMinutos, ?string $ignorarAgendamentoId = null): ?string
    {
        $inicio = Carbon::parse("{$data} {$hora}");
        $fim    = $inicio->copy()->addMinutes($duracaoMinutos);

        $grade = GradeHorario::where('profissional_id', $profissionalId)
            ->where('dia_semana', (int) $inicio->dayOfWeek)->where('ativo', true)->first();
        if (! $grade) {
            return 'O profissional não atende neste dia da semana.';
        }
        if ($inicio->lt(Carbon::parse("{$data} {$grade->hora_inicio}")) || $fim->gt(Carbon::parse("{$data} {$grade->hora_fim}"))) {
            return 'Fica fora do horário de atendimento do profissional (' . substr((string) $grade->hora_inicio, 0, 5) . '–' . substr((string) $grade->hora_fim, 0, 5) . ').';
        }
        if (($intervalo = $grade->intervalo()) && $inicio->lt(Carbon::parse("{$data} {$intervalo[1]}")) && $fim->gt(Carbon::parse("{$data} {$intervalo[0]}"))) {
            return 'Cai no intervalo do profissional (' . $intervalo[0] . '–' . $intervalo[1] . ').';
        }

        $outro = Agendamento::query()->with('paciente:id,nome')
            ->select(['id', 'paciente_id', 'inicio_em', 'fim_em'])
            ->where('profissional_id', $profissionalId)
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->when($ignorarAgendamentoId, fn ($q) => $q->whereKeyNot($ignorarAgendamentoId))
            ->where('inicio_em', '<', $fim)->where('fim_em', '>', $inicio)
            ->orderBy('inicio_em')->first();
        if ($outro) {
            $fuso = config('clinica.fuso_horario');

            return 'Já tem ' . ($outro->paciente?->nome ?? 'outro paciente') . ' das ' . $outro->inicio_em->timezone($fuso)->format('H:i')
                . ' às ' . $outro->fim_em->timezone($fuso)->format('H:i') . '.';
        }

        $bloqueado = BloqueioAgenda::where('profissional_id', $profissionalId)
            ->where('inicio_em', '<', $fim)->where('fim_em', '>', $inicio)->exists();

        return $bloqueado ? 'A agenda do profissional está bloqueada neste horário.' : null;
    }

    /**
     * @return list<string> ex: ["09:00", "10:00", "14:00"]
     */
    public function slotsDisponiveis(string $profissionalId, string $data, int $duracaoMinutos, ?string $ignorarAgendamentoId = null): array
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
            $cursor->addMinutes(30);
        }

        if (empty($slots)) {
            return [];
        }

        // Busca agendamentos ativos no dia
        $agendamentosOcupados = Agendamento::where('profissional_id', $profissionalId)
            ->whereIn('status', [StatusAgendamento::Agendado->value, StatusAgendamento::Confirmado->value])
            ->whereDate('inicio_em', $data)
            ->when($ignorarAgendamentoId, fn ($q) => $q->whereKeyNot($ignorarAgendamentoId)) // ao reagendar, o próprio horário fica livre
            ->get(['inicio_em', 'fim_em']);

        // Busca bloqueios no dia
        $bloqueios = BloqueioAgenda::where('profissional_id', $profissionalId)
            ->where('inicio_em', '<', Carbon::parse("{$data} {$grade->hora_fim}"))
            ->where('fim_em', '>', Carbon::parse("{$data} {$grade->hora_inicio}"))
            ->get(['inicio_em', 'fim_em']);

        $ocupados = $agendamentosOcupados->concat($bloqueios);

        // Intervalo da grade (ex.: almoço) também ocupa a agenda
        if ($intervalo = $grade->intervalo()) {
            $ocupados->push((object) [
                'inicio_em' => Carbon::parse("{$data} {$intervalo[0]}"),
                'fim_em'    => Carbon::parse("{$data} {$intervalo[1]}"),
            ]);
        }

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
