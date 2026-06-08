<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\StatusAgendamento;
use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Services\AgendamentoService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

class AgendamentoIndex extends Component
{
    use WithPagination;

    // ─── Filtros ──────────────────────────────────────────────────
    #[Url(history: true)]
    public string $filtroData = '';

    #[Url(history: true)]
    public string $filtroProfissionalId = '';

    #[Url(history: true)]
    public string $filtroStatus = '';

    // ─── Flash ────────────────────────────────────────────────────
    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    // ─── Modal Criar ──────────────────────────────────────────────
    public bool   $modalCriar          = false;
    public string $criarPacienteId     = '';
    public string $criarPacienteNome   = '';
    public string $criarPacienteBusca  = '';
    public string $criarProfissionalId = '';
    public string $criarProcedimentoId = '';
    public string $criarData           = '';
    public string $criarSlot           = '';
    public string $criarObservacoes    = '';
    /** @var array<int, array{id: string, nome: string}> */
    public array $sugestoesPaciente = [];
    public bool  $mostrarSugestoes  = false;

    // ─── Modal Cancelar ───────────────────────────────────────────
    public bool   $modalCancelar   = false;
    public string $cancelarId      = '';
    public string $cancelarMotivo  = '';

    // ─── Modal Reagendar ──────────────────────────────────────────
    public bool   $modalReagendar  = false;
    public string $reagendarId     = '';
    public string $reagendarData   = '';
    public string $reagendarSlot   = '';

    // ─── Detalhe ──────────────────────────────────────────────────
    public bool    $modalDetalhe    = false;
    public ?string $detalheId       = null;

    // ─── Boot ─────────────────────────────────────────────────────
    public function mount(): void
    {
        if (empty($this->filtroData)) {
            $this->filtroData = now()->toDateString();
        }
        $this->criarData    = now()->toDateString();
        $this->reagendarData = now()->toDateString();
    }

    // ─── Slots (criar) ────────────────────────────────────────────
    #[Computed]
    public function slots(): array
    {
        if (! $this->criarProfissionalId || ! $this->criarProcedimentoId || ! $this->criarData) {
            return [];
        }

        $procedimento = Procedimento::find((int) $this->criarProcedimentoId);
        if (! $procedimento) {
            return [];
        }

        /** @var AgendamentoService $service */
        $service = app(AgendamentoService::class);

        return $service->slotsDisponiveis($this->criarProfissionalId, $this->criarData, $procedimento->duracao_minutos);
    }

    #[Computed]
    public function slotsReagendar(): array
    {
        if (! $this->reagendarId || ! $this->reagendarData) {
            return [];
        }

        $agendamento = Agendamento::with('procedimento')->find($this->reagendarId);
        if (! $agendamento) {
            return [];
        }

        /** @var AgendamentoService $service */
        $service = app(AgendamentoService::class);

        return $service->slotsDisponiveis(
            $agendamento->profissional_id,
            $this->reagendarData,
            $agendamento->procedimento->duracao_minutos,
        );
    }

    #[Computed]
    public function profissionais(): Collection
    {
        return Profissional::where('ativo', true)->orderBy('nome')->get(['id', 'nome']);
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'duracao_minutos', 'valor']);
    }

    #[Computed]
    public function statusOpcoes(): array
    {
        return StatusAgendamento::cases();
    }

    // ─── Busca de paciente ────────────────────────────────────────
    public function updatedCriarPacienteBusca(string $valor): void
    {
        if (strlen($valor) < 2) {
            $this->sugestoesPaciente = [];
            $this->mostrarSugestoes  = false;
            return;
        }

        $this->sugestoesPaciente = Paciente::where('nome', 'ilike', "%{$valor}%")
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome'])
            ->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome])
            ->toArray();

        $this->mostrarSugestoes = true;
    }

    public function selecionarPaciente(string $id, string $nome): void
    {
        $this->criarPacienteId    = $id;
        $this->criarPacienteNome  = $nome;
        $this->criarPacienteBusca = $nome;
        $this->mostrarSugestoes   = false;
        $this->sugestoesPaciente  = [];
    }

    public function limparPaciente(): void
    {
        $this->criarPacienteId    = '';
        $this->criarPacienteNome  = '';
        $this->criarPacienteBusca = '';
        $this->mostrarSugestoes   = false;
    }

    // ─── Slots: reload quando muda profissional, procedimento ou data ──
    public function updatedCriarProfissionalId(): void
    {
        $this->criarSlot = '';
        unset($this->slots);
    }

    public function updatedCriarProcedimentoId(): void
    {
        $this->criarSlot = '';
        unset($this->slots);
    }

    public function updatedCriarData(): void
    {
        $this->criarSlot = '';
        unset($this->slots);
    }

    public function updatedReagendarData(): void
    {
        $this->reagendarSlot = '';
        unset($this->slotsReagendar);
    }

    // ─── Modal Criar ──────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetCriarForm();
        $this->modalCriar = true;
    }

    public function fecharModalCriar(): void
    {
        $this->modalCriar = false;
        $this->resetCriarForm();
    }

    public function salvarAgendamento(AgendamentoService $service): void
    {
        $this->validate([
            'criarPacienteId'     => 'required|uuid|exists:pacientes,id',
            'criarProfissionalId' => 'required|uuid|exists:profissionais,id',
            'criarProcedimentoId' => 'required|integer|exists:procedimentos,id',
            'criarData'           => 'required|date_format:Y-m-d',
            'criarSlot'           => 'required|date_format:H:i',
        ], [
            'criarPacienteId.required'     => 'Selecione um paciente.',
            'criarProfissionalId.required' => 'Selecione um profissional.',
            'criarProcedimentoId.required' => 'Selecione um procedimento.',
            'criarData.required'           => 'Informe a data.',
            'criarSlot.required'           => 'Selecione um horário disponível.',
        ]);

        try {
            $service->criar([
                'paciente_id'     => $this->criarPacienteId,
                'profissional_id' => $this->criarProfissionalId,
                'procedimento_id' => (int) $this->criarProcedimentoId,
                'inicio_em'       => "{$this->criarData} {$this->criarSlot}",
                'observacoes'     => $this->criarObservacoes ?: null,
            ]);

            $this->flashSucesso = 'Agendamento criado com sucesso.';
            $this->modalCriar   = false;
            $this->resetCriarForm();
            $this->resetPage();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao criar agendamento: ' . $e->getMessage();
        }
    }

    private function resetCriarForm(): void
    {
        $this->criarPacienteId     = '';
        $this->criarPacienteNome   = '';
        $this->criarPacienteBusca  = '';
        $this->criarProfissionalId = '';
        $this->criarProcedimentoId = '';
        $this->criarData           = now()->toDateString();
        $this->criarSlot           = '';
        $this->criarObservacoes    = '';
        $this->sugestoesPaciente   = [];
        $this->mostrarSugestoes    = false;
        $this->resetErrorBag();
        unset($this->slots);
    }

    // ─── Cancelar ─────────────────────────────────────────────────
    public function abrirModalCancelar(string $id): void
    {
        $this->cancelarId     = $id;
        $this->cancelarMotivo = '';
        $this->modalCancelar  = true;
    }

    public function fecharModalCancelar(): void
    {
        $this->modalCancelar  = false;
        $this->cancelarId     = '';
        $this->cancelarMotivo = '';
        $this->resetErrorBag();
    }

    public function confirmarCancelamento(AgendamentoService $service): void
    {
        $this->validate(['cancelarMotivo' => 'required|string|min:5|max:500'], [
            'cancelarMotivo.required' => 'Informe o motivo do cancelamento.',
            'cancelarMotivo.min'      => 'Mínimo 5 caracteres.',
        ]);

        try {
            $agendamento = Agendamento::findOrFail($this->cancelarId);
            $service->cancelar($agendamento, $this->cancelarMotivo);
            $this->flashSucesso = 'Agendamento cancelado.';
            $this->fecharModalCancelar();
        } catch (RuntimeException $e) {
            $this->flashErro = $e->getMessage();
            $this->fecharModalCancelar();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao cancelar: ' . $e->getMessage();
            $this->fecharModalCancelar();
        }
    }

    // ─── Reagendar ────────────────────────────────────────────────
    public function abrirModalReagendar(string $id): void
    {
        $this->reagendarId   = $id;
        $this->reagendarData = now()->addDay()->toDateString();
        $this->reagendarSlot = '';
        $this->modalReagendar = true;
        unset($this->slotsReagendar);
    }

    public function fecharModalReagendar(): void
    {
        $this->modalReagendar = false;
        $this->reagendarId    = '';
        $this->reagendarData  = '';
        $this->reagendarSlot  = '';
        $this->resetErrorBag();
        unset($this->slotsReagendar);
    }

    public function confirmarReagendamento(AgendamentoService $service): void
    {
        $this->validate([
            'reagendarData' => 'required|date_format:Y-m-d',
            'reagendarSlot' => 'required|date_format:H:i',
        ], [
            'reagendarData.required' => 'Informe a nova data.',
            'reagendarSlot.required' => 'Selecione o novo horário.',
        ]);

        try {
            $agendamento = Agendamento::findOrFail($this->reagendarId);
            $service->reagendar($agendamento, $this->reagendarData, $this->reagendarSlot);
            $this->flashSucesso = 'Agendamento reagendado com sucesso.';
            $this->fecharModalReagendar();
        } catch (RuntimeException $e) {
            $this->flashErro = $e->getMessage();
            $this->fecharModalReagendar();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao reagendar: ' . $e->getMessage();
            $this->fecharModalReagendar();
        }
    }

    // ─── Ações rápidas ────────────────────────────────────────────
    public function marcarRealizado(string $id, AgendamentoService $service): void
    {
        try {
            $service->marcarRealizado(Agendamento::findOrFail($id));
            $this->flashSucesso = 'Marcado como realizado.';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
    }

    public function marcarFalta(string $id, AgendamentoService $service): void
    {
        try {
            $service->marcarFalta(Agendamento::findOrFail($id));
            $this->flashSucesso = 'Marcado como falta.';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
    }

    public function confirmarAgendamento(string $id): void
    {
        try {
            $agendamento = Agendamento::findOrFail($id);
            if ($agendamento->status !== StatusAgendamento::Agendado) {
                $this->flashErro = 'Apenas agendamentos com status "Agendado" podem ser confirmados.';
                return;
            }
            $agendamento->update(['status' => StatusAgendamento::Confirmado->value]);
            $this->flashSucesso = 'Agendamento confirmado.';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
    }

    // ─── Detalhe ──────────────────────────────────────────────────
    public function abrirDetalhe(string $id): void
    {
        $this->detalheId    = $id;
        $this->modalDetalhe = true;
    }

    public function fecharDetalhe(): void
    {
        $this->modalDetalhe = false;
        $this->detalheId    = null;
    }

    // ─── Filtros ──────────────────────────────────────────────────
    public function limparFiltros(): void
    {
        $this->filtroData          = now()->toDateString();
        $this->filtroProfissionalId = '';
        $this->filtroStatus        = '';
        $this->resetPage();
    }

    public function updatedFiltroData(): void    { $this->resetPage(); }
    public function updatedFiltroProfissionalId(): void { $this->resetPage(); }
    public function updatedFiltroStatus(): void  { $this->resetPage(); }

    // ─── Render ───────────────────────────────────────────────────
    public function render(): View
    {
        $query = Agendamento::with(['paciente:id,nome,telefone', 'profissional:id,nome,cor_agenda', 'procedimento:id,nome,duracao_minutos'])
            ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'inicio_em', 'fim_em', 'status', 'observacoes', 'motivo_cancelamento', 'google_event_id'])
            ->orderBy('inicio_em', 'asc');

        if ($this->filtroData) {
            $query->whereDate('inicio_em', $this->filtroData);
        }
        if ($this->filtroProfissionalId) {
            $query->where('profissional_id', $this->filtroProfissionalId);
        }
        if ($this->filtroStatus) {
            $query->where('status', $this->filtroStatus);
        }

        $agendamentos = $query->paginate(20);

        $agendamentoDetalhe = $this->detalheId
            ? Agendamento::with(['paciente', 'profissional', 'procedimento', 'agendamentoOrigem'])->find($this->detalheId)
            : null;

        return view('livewire.agendamento-index', compact('agendamentos', 'agendamentoDetalhe'))
            ->layout('layouts.app', ['title' => 'Agenda']);
    }
}
