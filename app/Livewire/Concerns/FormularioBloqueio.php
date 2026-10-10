<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\BloqueioAgenda;
use App\Models\Profissional;
use App\Services\BloqueioAgendaService;
use App\Support\EscopoProfissional;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Formulário de bloqueio da agenda (férias, folgas, compromissos), usado na Agenda e em
 * "Profissionais e horários". Perfil Profissional só bloqueia e remove a própria agenda.
 * O componente precisa das propriedades $flashSucesso / $flashErro e do trait MensagemDeErro.
 */
trait FormularioBloqueio
{
    public const BLOQUEIO_TODOS = 'todos';

    public bool   $modalBloqueio       = false;
    public string $bloqProfissionalId  = self::BLOQUEIO_TODOS;
    public bool   $bloqDiaInteiro      = true;
    public string $bloqDataInicio      = '';
    public string $bloqDataFim         = '';
    public string $bloqHoraInicio      = '08:00';
    public string $bloqHoraFim         = '12:00';
    public string $bloqMotivo          = '';

    /**
     * Agendamentos pendentes dentro do último bloqueio criado (aviso para reagendar/cancelar).
     *
     * @var list<array{data: string, horario: string, paciente: string, profissional: string, profissional_id: string}>
     */
    public array  $bloqConflitos       = [];
    public string $bloqConflitosResumo = '';

    /** Profissional logado (perfil Profissional): só a própria agenda. */
    private function bloqueioSoDoProfissional(): ?string
    {
        return app(EscopoProfissional::class)->profissionalId();
    }

    /** Profissionais que aparecem no formulário. @return Collection<int, Profissional> */
    public function profissionaisParaBloqueio(): Collection
    {
        return Profissional::query()->select(['id', 'nome'])->where('ativo', true)
            ->when($this->bloqueioSoDoProfissional(), fn ($q, string $id) => $q->whereKey($id))
            ->orderBy('nome')->limit(200)->get();
    }

    /**
     * Abre o formulário. Com data e hora (horário clicado na agenda), já vem como "Algumas horas"
     * de 1 hora a partir dali; com profissional, já vem com ele escolhido.
     */
    public function abrirModalNovoBloqueio(?string $data = null, ?string $hora = null, ?string $profissionalId = null): void
    {
        $this->resetBloqueioForm();

        $dia = $data !== null ? CarbonImmutable::createFromFormat('!Y-m-d', $data) : false;
        if ($dia !== false && $dia->format('Y-m-d') === $data) {
            $this->bloqDataInicio = $this->bloqDataFim = $data;
        }
        if ($hora !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            $inicio = CarbonImmutable::createFromFormat('H:i', $hora);
            $this->bloqDiaInteiro = false;
            $this->bloqHoraInicio = $hora;
            $this->bloqHoraFim    = $inicio->addHour()->isSameDay($inicio) ? $inicio->addHour()->format('H:i') : '23:59';
        }
        if (filled($profissionalId) && Profissional::query()->whereKey($profissionalId)->exists()) {
            $this->bloqProfissionalId = $profissionalId;
        }
        $this->bloqProfissionalId = $this->bloqueioSoDoProfissional() ?? $this->bloqProfissionalId;
        $this->modalBloqueio = true;
    }

    public function fecharModalBloqueio(): void
    {
        $this->modalBloqueio = false;
        $this->resetBloqueioForm();
    }

    public function salvarBloqueio(BloqueioAgendaService $service): void
    {
        $this->bloqProfissionalId = $this->bloqueioSoDoProfissional() ?? $this->bloqProfissionalId;

        $regras = [
            'bloqProfissionalId' => ['required', function (string $attr, mixed $valor, Closure $falha): void {
                if ($valor !== self::BLOQUEIO_TODOS && ! Profissional::whereKey($valor)->exists()) {
                    $falha('Selecione um profissional válido.');
                }
            }],
            'bloqDataInicio' => 'required|date_format:Y-m-d',
            'bloqMotivo'     => 'nullable|string|max:500',
        ];
        $regras += $this->bloqDiaInteiro
            ? ['bloqDataFim' => 'required|date_format:Y-m-d|after_or_equal:bloqDataInicio']
            : ['bloqHoraInicio' => 'required|date_format:H:i', 'bloqHoraFim' => 'required|date_format:H:i|after:bloqHoraInicio'];

        $this->validate($regras, [
            'bloqDataInicio.required'    => 'Informe a data.',
            'bloqDataFim.required'       => 'Informe a data final.',
            'bloqDataFim.after_or_equal' => 'A data final deve ser igual ou posterior à inicial.',
            'bloqHoraFim.after'          => 'O horário final deve ser depois do inicial.',
        ]);

        $profissionalIds = $this->bloqProfissionalId === self::BLOQUEIO_TODOS
            ? Profissional::where('ativo', true)->pluck('id')->all()
            : [$this->bloqProfissionalId];

        if ($profissionalIds === []) {
            $this->addError('bloqProfissionalId', 'Nenhum profissional ativo. Cadastre ou ative um profissional antes de bloquear.');
            return;
        }

        [$inicio, $fim] = $this->bloqDiaInteiro
            ? [CarbonImmutable::parse($this->bloqDataInicio)->startOfDay(), CarbonImmutable::parse($this->bloqDataFim)->startOfDay()->addDay()]
            : [CarbonImmutable::parse("{$this->bloqDataInicio} {$this->bloqHoraInicio}"), CarbonImmutable::parse("{$this->bloqDataInicio} {$this->bloqHoraFim}")];

        try {
            $resultado = $service->criar($profissionalIds, $inicio, $fim, $this->bloqDiaInteiro, $this->bloqMotivo ?: null);
        } catch (Throwable $e) {
            Log::error('[Bloqueio] salvarBloqueio erro', ['message' => $e->getMessage()]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível salvar o bloqueio');
            return;
        }

        $conflitos = $resultado['conflitos'];
        $this->bloqConflitos = $conflitos->map(fn ($ag) => [
            'data'            => $ag->inicio_em->toDateString(),
            'horario'         => $ag->inicio_em->format('d/m H:i'),
            'paciente'        => $ag->paciente?->nome ?? '—',
            'profissional'    => $ag->profissional?->nome ?? '—',
            'profissional_id' => (string) $ag->profissional_id,
        ])->all();
        $this->bloqConflitosResumo = BloqueioAgendaService::descreverPeriodo($inicio, $fim, $this->bloqDiaInteiro);

        $this->flashSucesso = $conflitos->isEmpty()
            ? 'Bloqueio criado.'
            : 'Bloqueio criado. ' . ($conflitos->count() === 1 ? '1 agendamento continua' : $conflitos->count() . ' agendamentos continuam')
              . ' nesse período: reagende ou cancele.';
        $this->modalBloqueio = false;
        $this->resetBloqueioForm();
    }

    public function removerBloqueio(int $id, BloqueioAgendaService $service): void
    {
        try {
            $bloqueio = BloqueioAgenda::findOrFail($id);
            $proprio  = $this->bloqueioSoDoProfissional();
            if ($proprio !== null && ($bloqueio->profissional_id !== $proprio || $bloqueio->grupo_id !== null)) {
                $this->flashErro = 'Você só pode excluir bloqueios da sua própria agenda. Bloqueios de todos os profissionais são excluídos pela administração.';
                return;
            }
            $service->remover($bloqueio);
            $this->flashSucesso = 'Bloqueio excluído.';
        } catch (Throwable $e) {
            Log::error('[Bloqueio] removerBloqueio erro', ['id' => $id, 'message' => $e->getMessage()]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível excluir o bloqueio');
        }
    }

    public function fecharConflitos(): void
    {
        $this->bloqConflitos       = [];
        $this->bloqConflitosResumo = '';
    }

    public function updatedBloqDataInicio(): void
    {
        if ($this->bloqDataFim < $this->bloqDataInicio) {
            $this->bloqDataFim = $this->bloqDataInicio;
        }
    }

    private function resetBloqueioForm(): void
    {
        $this->bloqProfissionalId = $this->bloqueioSoDoProfissional() ?? self::BLOQUEIO_TODOS;
        $this->bloqDiaInteiro     = true;
        $this->bloqDataInicio     = now()->toDateString();
        $this->bloqDataFim        = now()->toDateString();
        $this->bloqHoraInicio     = '08:00';
        $this->bloqHoraFim        = '12:00';
        $this->bloqMotivo         = '';
        $this->resetErrorBag();
    }
}
