<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\ConcluirAtendimentoAction;
use App\Enums\FormaPagamento;
use App\Enums\StatusAgendamento;
use App\Enums\VisaoAgenda;
use App\Models\Agendamento;
use App\Models\Pacote;
use App\Models\Paciente;
use App\Models\Procedimento;
use App\Models\Profissional;
use App\Models\Transacao;
use App\Services\AgendaCalendarioService;
use App\Services\AgendamentoService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AgendamentoIndex extends Component
{
    use Concerns\FormularioBloqueio;
    use Concerns\MensagemDeErro;
    use WithPagination;

    // ─── Visão (calendário dia/semana/mês ou lista) ────────────────
    #[Url(history: true)]
    public string $visao = 'semana';

    // ─── Filtros ──────────────────────────────────────────────────
    // Na lista filtra o dia; no calendário é a data de referência do período.
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
    public string $criarProfissionalId = '';
    /** @var array<int, int> */
    public array  $criarProcedimentoIds = [];
    public string $criarData           = '';
    public string $criarSlot           = '';
    public string $criarObservacoes    = '';
    // Horário clicado no calendário; aplicado assim que aparecer entre os disponíveis.
    public string $criarSlotSugerido   = '';

    // ─── Modal Cancelar ───────────────────────────────────────────
    public bool   $modalCancelar  = false;
    public string $cancelarId     = '';
    public string $cancelarMotivo = '';

    // ─── Modal Reagendar ──────────────────────────────────────────
    public bool   $modalReagendar = false;
    public string $reagendarId    = '';
    public string $reagendarData  = '';
    public string $reagendarSlot  = '';

    // ─── Modal Concluir atendimento (Realizado + receita) ─────────
    public bool   $modalConcluir           = false;
    public string $concluirId              = '';
    public bool   $concluirLancarReceita   = true;
    public string $concluirValor           = '';
    public string $concluirCategoria       = '';
    public string $concluirFormaPagamento  = 'pix';
    public bool   $concluirPago            = true;
    /** Pacote do paciente usado neste atendimento ('' = não usa pacote) */
    public string $concluirPacoteId        = '';

    // ─── Detalhe ──────────────────────────────────────────────────
    public bool    $modalDetalhe = false;
    public ?string $detalheId    = null;

    // ─── Boot ─────────────────────────────────────────────────────
    /** Perfil Profissional: a agenda fica presa ao próprio profissional. */
    private function profissionalFixo(): ?string
    {
        return app(\App\Support\EscopoProfissional::class)->profissionalId();
    }

    public function mount(): void
    {
        $this->filtroProfissionalId = $this->profissionalFixo() ?? $this->filtroProfissionalId;
        if (empty($this->filtroData)) {
            $this->filtroData = now()->toDateString();
        }
        $this->criarData     = now()->toDateString();
        $this->reagendarData = now()->toDateString();

        // Vindo de um lead convertido: abre o agendamento já com o paciente escolhido
        $pacienteId = request()->query('paciente');
        if (is_string($pacienteId) && \Illuminate\Support\Str::isUuid($pacienteId)
            && ($paciente = \App\Models\Paciente::query()->select(['id', 'nome'])->find($pacienteId))) {
            $this->abrirModalCriar();
            $this->selecionarPaciente($paciente->id, $paciente->nome);
        }

        // Vindo do atendimento finalizado: abre a conclusão (receita/pacote) do horário
        $concluir = request()->query('concluir');
        if (is_string($concluir) && \Illuminate\Support\Str::isUuid($concluir)
            && Agendamento::query()->whereKey($concluir)->whereIn('status', [StatusAgendamento::Agendado, StatusAgendamento::Confirmado])->exists()) {
            $this->abrirModalConcluir($concluir);
        }
    }

    /** Abre (ou retoma) o atendimento clínico deste horário. */
    public function iniciarAtendimento(string $id, \App\Actions\Atendimento\IniciarAtendimentoAction $iniciar): mixed
    {
        try {
            $agendamento = Agendamento::query()->select(['id', 'paciente_id'])->findOrFail($id);
            $atendimento = $iniciar->execute((string) $agendamento->paciente_id, $agendamento->id);
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível iniciar o atendimento');

            return null;
        }

        return $this->redirectRoute('atendimentos.show', ['id' => $atendimento->id], navigate: true);
    }

    // ─── Computed ─────────────────────────────────────────────────
    #[Computed]
    public function pacientes(): Collection
    {
        return Paciente::orderBy('nome')->get(['id', 'nome']);
    }

    #[Computed]
    public function profissionais(): Collection
    {
        return Profissional::where('ativo', true)
            ->when($this->profissionalFixo(), fn ($q, string $id) => $q->whereKey($id))
            ->orderBy('nome')->get(['id', 'nome', 'cor_agenda']);
    }

    #[Computed]
    public function procedimentos(): Collection
    {
        return Procedimento::where('ativo', true)->orderBy('nome')
            ->get(['id', 'nome', 'duracao_minutos', 'valor']);
    }

    #[Computed]
    public function statusOpcoes(): array
    {
        return StatusAgendamento::cases();
    }

    #[Computed]
    public function duracaoTotal(): int
    {
        if (empty($this->criarProcedimentoIds)) {
            return 0;
        }
        return (int) Procedimento::whereIn('id', $this->criarProcedimentoIds)
            ->sum('duracao_minutos');
    }

    #[Computed]
    public function horariosDisponiveis(): array
    {
        if (! $this->criarProfissionalId || empty($this->criarProcedimentoIds) || ! $this->criarData) {
            return [];
        }

        $duracao = $this->duracaoTotal;
        if ($duracao === 0) {
            return [];
        }

        /** @var AgendamentoService $service */
        $service = app(AgendamentoService::class);
        return $service->slotsDisponiveis($this->criarProfissionalId, $this->criarData, $duracao);
    }

    /** Horário digitado fora da lista de livres no agendamento novo (encaixe): o motivo do aviso, ou null. */
    #[Computed]
    public function conflitoCriar(): ?string
    {
        if (! self::horaValida($this->criarSlot) || in_array($this->criarSlot, $this->horariosDisponiveis, true)
            || ! $this->criarProfissionalId || ! $this->criarData || $this->duracaoTotal === 0) {
            return null;
        }

        return app(AgendamentoService::class)->conflito($this->criarProfissionalId, $this->criarData, $this->criarSlot, $this->duracaoTotal);
    }

    private static function horaValida(string $hora): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora);
    }

    #[Computed]
    public function horariosReagendar(): array
    {
        if (! $this->reagendarId || ! $this->reagendarData) {
            return [];
        }

        $agendamento = $this->agendamentoReagendar();
        if (! $agendamento) {
            return [];
        }

        // O próprio agendamento não ocupa o horário que vai deixar
        return app(AgendamentoService::class)->slotsDisponiveis(
            $agendamento->profissional_id,
            $this->reagendarData,
            AgendamentoService::duracaoMinutos($agendamento),
            $agendamento->id,
        );
    }

    /** Horário digitado fora da lista de livres (encaixe): o motivo do aviso, ou null. */
    #[Computed]
    public function conflitoReagendar(): ?string
    {
        if (! self::horaValida($this->reagendarSlot) || in_array($this->reagendarSlot, $this->horariosReagendar, true)) {
            return null;
        }
        $agendamento = $this->agendamentoReagendar();
        if (! $agendamento || ! $this->reagendarData) {
            return null;
        }

        return app(AgendamentoService::class)->conflito(
            $agendamento->profissional_id, $this->reagendarData, $this->reagendarSlot,
            AgendamentoService::duracaoMinutos($agendamento), $agendamento->id,
        );
    }

    private function agendamentoReagendar(): ?Agendamento
    {
        return $this->reagendarId
            ? Agendamento::query()->select(['id', 'profissional_id', 'procedimento_id', 'procedimentos_ids', 'inicio_em', 'fim_em'])->find($this->reagendarId)
            : null;
    }

    public function updatedReagendarSlot(): void
    {
        unset($this->conflitoReagendar);
    }

    // ─── Paciente (seleção via combobox Alpine) ───────────────────
    public function selecionarPaciente(string $id, string $nome): void
    {
        $this->criarPacienteId   = $id;
        $this->criarPacienteNome = $nome;
    }

    public function limparPaciente(): void
    {
        $this->criarPacienteId   = '';
        $this->criarPacienteNome = '';
    }

    // ─── Reload de slots ao mudar campos do formulário ────────────
    public function updatedCriarProfissionalId(): void
    {
        $this->criarSlot = '';
        unset($this->horariosDisponiveis);
        $this->aplicarSlotSugerido();
    }

    public function updatedCriarProcedimentoIds(): void
    {
        $this->criarSlot = '';
        unset($this->horariosDisponiveis, $this->duracaoTotal);
        $this->aplicarSlotSugerido();
    }

    public function updatedCriarData(): void
    {
        $this->criarSlot         = '';
        $this->criarSlotSugerido = '';
        unset($this->horariosDisponiveis);
    }

    private function aplicarSlotSugerido(): void
    {
        if ($this->criarSlotSugerido !== '' && in_array($this->criarSlotSugerido, $this->horariosDisponiveis, true)) {
            $this->criarSlot = $this->criarSlotSugerido;
        }
    }

    public function updatedReagendarData(): void
    {
        $this->reagendarSlot = '';
        unset($this->horariosReagendar, $this->conflitoReagendar);
    }

    // ─── Modal Criar ──────────────────────────────────────────────
    public function abrirModalCriar(): void
    {
        $this->resetCriarForm();
        $this->modalCriar = true;
    }

    public function novoNoHorario(string $data, string $hora): void
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data} {$hora}");
        if ($inicio === false || $inicio->format('Y-m-d H:i') !== "{$data} {$hora}") {
            return;
        }

        $this->resetCriarForm();
        $this->criarData           = $data;
        $this->criarSlotSugerido   = $hora;
        $this->criarProfissionalId = $this->filtroProfissionalId;
        $this->modalCriar          = true;
    }

    /** "Bloquear este horário" no modal aberto a partir do calendário. */
    public function bloquearNoHorario(): void
    {
        [$data, $hora, $profissional] = [$this->criarData, $this->criarSlotSugerido, $this->criarProfissionalId ?: $this->filtroProfissionalId];
        $this->fecharModalCriar();
        $this->abrirModalNovoBloqueio($data, $hora ?: null, $profissional ?: null);
    }

    public function fecharModalCriar(): void
    {
        $this->modalCriar = false;
        $this->resetCriarForm();
    }

    public function salvarAgendamento(AgendamentoService $service): void
    {
        $this->criarProfissionalId = $this->profissionalFixo() ?? $this->criarProfissionalId;
        Log::info('[AgendamentoIndex] salvarAgendamento chamado', [
            'pacienteId'       => $this->criarPacienteId,
            'profissionalId'   => $this->criarProfissionalId,
            'procedimentoIds'  => $this->criarProcedimentoIds,
            'data'             => $this->criarData,
            'slot'             => $this->criarSlot,
        ]);

        // Garante inteiros antes do exists (PostgreSQL bigint vs string PDO binding)
        $this->criarProcedimentoIds = array_map('intval', $this->criarProcedimentoIds);

        try {
            $this->validate([
                'criarPacienteId'        => 'required|uuid|exists:pacientes,id',
                'criarProfissionalId'    => 'required|uuid|exists:profissionais,id',
                'criarProcedimentoIds'   => 'required|array|min:1',
                'criarProcedimentoIds.*' => 'integer|exists:procedimentos,id',
                'criarData'              => 'required|date_format:Y-m-d',
                'criarSlot'              => 'required|date_format:H:i',
            ], [
                'criarPacienteId.required'      => 'Selecione um paciente.',
                'criarProfissionalId.required'  => 'Selecione um profissional.',
                'criarProcedimentoIds.required' => 'Selecione ao menos um procedimento.',
                'criarProcedimentoIds.min'      => 'Selecione ao menos um procedimento.',
                'criarData.required'            => 'Informe a data.',
                'criarSlot.required'            => 'Selecione um horário disponível.',
            ]);

            $service->criar([
                'paciente_id'       => $this->criarPacienteId,
                'profissional_id'   => $this->criarProfissionalId,
                'procedimentos_ids' => $this->criarProcedimentoIds,
                'procedimento_id'   => $this->criarProcedimentoIds[0],
                'inicio_em'         => "{$this->criarData} {$this->criarSlot}",
                'observacoes'       => $this->criarObservacoes ?: null,
            ]);

            $this->flashSucesso = 'Agendamento criado.';
            $this->modalCriar   = false;
            $this->resetCriarForm();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e; // deixa o Livewire tratar e popular o error bag
        } catch (Throwable $e) {
            Log::error('[AgendamentoIndex] salvarAgendamento erro', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível criar o agendamento');
        }
    }

    private function resetCriarForm(): void
    {
        $this->criarPacienteId      = '';
        $this->criarPacienteNome    = '';
        $this->criarProfissionalId  = '';
        $this->criarProcedimentoIds = [];
        $this->criarData            = now()->toDateString();
        $this->criarSlot            = '';
        $this->criarObservacoes     = '';
        $this->criarSlotSugerido    = '';
        $this->resetErrorBag();
        unset($this->horariosDisponiveis, $this->duracaoTotal);
    }

    // ─── Cancelar ─────────────────────────────────────────────────
    public function abrirModalCancelar(string $id): void
    {
        $this->fecharDetalhe();
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
            'cancelarMotivo.min'      => 'Escreva o motivo com pelo menos 5 caracteres.',
        ]);

        try {
            $agendamento = Agendamento::findOrFail($this->cancelarId);
            $service->cancelar($agendamento, $this->cancelarMotivo);
            $this->flashSucesso = 'Agendamento cancelado.';
            $this->fecharModalCancelar();
        } catch (RuntimeException $e) {
            $this->flashErro = $this->mensagemDeErro($e);
            $this->fecharModalCancelar();
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível cancelar');
            $this->fecharModalCancelar();
        }
    }

    // ─── Reagendar ────────────────────────────────────────────────
    public function abrirModalReagendar(string $id): void
    {
        $this->fecharDetalhe();
        // Sugere a data do próprio agendamento; se já passou, amanhã.
        $inicio = Agendamento::whereKey($id)->value('inicio_em');
        $data   = $inicio ? CarbonImmutable::parse($inicio)->startOfDay() : null;

        $this->reagendarId    = $id;
        $this->reagendarData  = ($data && $data->gte(CarbonImmutable::today()) ? $data : CarbonImmutable::tomorrow())->toDateString();
        $this->reagendarSlot  = '';
        $this->modalReagendar = true;
        unset($this->horariosReagendar);
    }

    public function fecharModalReagendar(): void
    {
        $this->modalReagendar = false;
        $this->reagendarId    = '';
        $this->reagendarData  = '';
        $this->reagendarSlot  = '';
        $this->resetErrorBag();
        unset($this->horariosReagendar);
    }

    public function confirmarReagendamento(AgendamentoService $service): void
    {
        $this->validate([
            'reagendarData' => 'required|date_format:Y-m-d',
            'reagendarSlot' => 'required|date_format:H:i',
        ], [
            'reagendarData.required' => 'Informe a nova data.',
            'reagendarSlot.required'    => 'Selecione o novo horário.',
            'reagendarSlot.date_format' => 'Informe o horário no formato 00:00.',
        ]);

        try {
            $agendamento = Agendamento::findOrFail($this->reagendarId);
            $service->reagendar($agendamento, $this->reagendarData, $this->reagendarSlot);
            $this->flashSucesso = 'Agendamento reagendado.';
            $this->fecharModalReagendar();
        } catch (RuntimeException $e) {
            $this->flashErro = $this->mensagemDeErro($e);
            $this->fecharModalReagendar();
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível reagendar');
            $this->fecharModalReagendar();
        }
    }

    // ─── Ações rápidas ────────────────────────────────────────────
    // ─── Concluir atendimento ─────────────────────────────────────
    public function abrirModalConcluir(string $id): void
    {
        $agendamento = Agendamento::with(['paciente:id,nome,forma_pagamento,valor_mensalidade'])
            ->select(['id', 'paciente_id', 'procedimento_id', 'procedimentos_ids', 'status'])
            ->findOrFail($id);

        $temMensalidade = (float) ($agendamento->paciente?->valor_mensalidade ?? 0) > 0;
        $forma          = FormaPagamento::tryFrom((string) $agendamento->paciente?->forma_pagamento);

        $this->fecharDetalhe();
        $this->resetErrorBag();
        $this->concluirId             = $id;
        $this->concluirLancarReceita  = ! $temMensalidade;
        $this->concluirValor          = number_format(ConcluirAtendimentoAction::valorSugerido($agendamento), 2, '.', '');
        $this->concluirCategoria      = '';
        $this->concluirFormaPagamento = ($forma && $forma !== FormaPagamento::AportePessoal ? $forma : FormaPagamento::Pix)->value;
        $this->concluirPago           = true;

        // Pacote com saldo para o procedimento: sugere usar a sessão em vez de lançar receita
        $pacote = Pacote::query()->select(['id'])->utilizaveis()
            ->where('paciente_id', $agendamento->paciente_id)
            ->where('procedimento_id', $agendamento->procedimento_id)
            ->orderBy('created_at')->first();
        $this->concluirPacoteId = $pacote?->id ?? '';
        if ($pacote) {
            $this->concluirLancarReceita = false;
        }

        $this->modalConcluir          = true;
    }

    public function fecharModalConcluir(): void
    {
        $this->modalConcluir = false;
        $this->concluirId    = '';
        $this->resetErrorBag();
    }

    public function updatedConcluirPacoteId(): void
    {
        if ($this->concluirPacoteId !== '') {
            $this->concluirLancarReceita = false;
        }
    }

    public function updatedConcluirLancarReceita(): void
    {
        if ($this->concluirLancarReceita) {
            $this->concluirPacoteId = '';
        }
    }

    public function confirmarConclusao(ConcluirAtendimentoAction $concluir): void
    {
        $usaPacote = $this->concluirPacoteId !== '';
        if ($usaPacote) {
            $this->concluirLancarReceita = false;
        }

        if ($this->concluirLancarReceita) {
            $this->validate([
                'concluirValor'          => 'required|numeric|min:0.01|max:999999.99',
                'concluirCategoria'      => ['required', \Illuminate\Validation\Rule::in(\App\Models\PlanoConta::nomes('entrada'))],
                'concluirFormaPagamento' => ['required', 'in:' . implode(',', array_map(
                    fn (FormaPagamento $f) => $f->value,
                    array_filter(FormaPagamento::cases(), fn (FormaPagamento $f) => $f !== FormaPagamento::AportePessoal),
                ))],
            ], [
                'concluirValor.required'     => 'Informe o valor.',
                'concluirValor.min'          => 'O valor deve ser maior que zero.',
                'concluirCategoria.required' => 'Escolha a categoria da receita.',
            ]);
        }

        try {
            $concluir->execute($this->concluirId, $this->concluirLancarReceita ? [
                'valor_bruto'     => (float) str_replace(',', '.', $this->concluirValor),
                'categoria'       => $this->concluirCategoria,
                'forma_pagamento' => $this->concluirFormaPagamento,
                'pago'            => $this->concluirPago,
            ] : null, $usaPacote ? $this->concluirPacoteId : null);

            $this->flashSucesso = match (true) {
                $usaPacote                   => 'Atendimento concluído e sessão descontada do pacote.',
                $this->concluirLancarReceita => 'Atendimento concluído e receita lançada no financeiro.',
                default                      => 'Atendimento concluído, sem lançar receita.',
            };
            $this->fecharModalConcluir();
        } catch (RuntimeException $e) {
            $this->flashErro = $this->mensagemDeErro($e);
            $this->fecharModalConcluir();
        } catch (Throwable $e) {
            Log::error('[AgendamentoIndex] confirmarConclusao erro', ['id' => $this->concluirId, 'message' => $e->getMessage()]);
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível concluir o atendimento');
        }
    }

    public function marcarFalta(string $id, AgendamentoService $service): void
    {
        try {
            $service->marcarFalta(Agendamento::findOrFail($id));
            $this->flashSucesso = 'Falta registrada.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível registrar a falta');
        }
    }

    public function confirmarAgendamento(string $id): void
    {
        try {
            $agendamento = Agendamento::findOrFail($id);
            if ($agendamento->status !== StatusAgendamento::Agendado) {
                $this->flashErro = 'Só dá para confirmar agendamentos com status "Agendado".';
                return;
            }
            $agendamento->update(['status' => StatusAgendamento::Confirmado->value]);
            $this->flashSucesso = 'Agendamento confirmado.';
        } catch (Throwable $e) {
            $this->flashErro = $this->mensagemDeErro($e, 'Não foi possível confirmar');
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
        $this->filtroData           = now()->toDateString();
        $this->filtroProfissionalId = '';
        $this->filtroStatus         = '';
        $this->resetPage();
    }

    public function updatedFiltroData(): void           { $this->resetPage(); }
    public function updatedVisao(): void                { $this->visao = $this->visaoAtual()->value; }
    public function updatedFiltroProfissionalId(): void { $this->resetPage(); }
    public function updatedFiltroStatus(): void         { $this->resetPage(); }

    // ─── Calendário: visão e navegação ────────────────────────────
    public function mudarVisao(string $visao): void
    {
        $this->visao = (VisaoAgenda::tryFrom($visao) ?? VisaoAgenda::Semana)->value;
        $this->resetPage();
    }

    public function navegar(int $direcao, AgendaCalendarioService $calendario): void
    {
        $this->filtroData = $calendario->navegar($this->visaoAtual(), $this->dataReferencia(), $direcao)->toDateString();
        $this->resetPage();
    }

    public function irParaHoje(): void
    {
        $this->filtroData = now()->toDateString();
        $this->resetPage();
    }

    public function irParaDia(string $data): void
    {
        $this->filtroData = $this->dataReferencia($data)->toDateString();
        $this->visao      = VisaoAgenda::Dia->value;
        $this->resetPage();
    }

    private function visaoAtual(): VisaoAgenda
    {
        return VisaoAgenda::tryFrom($this->visao) ?? VisaoAgenda::Semana;
    }

    private function dataReferencia(?string $data = null): CarbonImmutable
    {
        $data ??= $this->filtroData;
        $ref = CarbonImmutable::createFromFormat('!Y-m-d', $data);

        return $ref !== false && $ref->toDateString() === $data ? $ref : CarbonImmutable::today();
    }

    /** @return array<string, mixed> */
    private function dadosCalendario(VisaoAgenda $visao, AgendaCalendarioService $calendario): array
    {
        [$inicio, $fim] = $calendario->periodo($visao, $this->dataReferencia());

        $agendamentos = $calendario->agendamentosDoPeriodo(
            $inicio, $fim, $this->filtroProfissionalId, $this->filtroStatus,
        );
        $porDia = $agendamentos->groupBy(fn (Agendamento $ag) => $ag->inicio_em->toDateString());

        $dias = [];
        for ($d = $inicio; $d->lt($fim); $d = $d->addDay()) {
            $dias[] = $d;
        }

        $faixa = $visao === VisaoAgenda::Mes
            ? [0, 0]
            : $calendario->faixaHoraria($calendario->limitesGrade($this->filtroProfissionalId), $agendamentos);

        $layout = [];
        if ($visao !== VisaoAgenda::Mes) {
            foreach ($porDia as $dia => $doDia) {
                $layout[$dia] = $calendario->layoutDia($doDia);
            }
        }

        $bloqueios = $calendario->bloqueiosDoPeriodo($inicio, $fim, $this->filtroProfissionalId);

        return [
            'calBloqueios'    => $calendario->bloqueiosPorDia($bloqueios, $dias),
            // Só faz sentido mostrar a pausa da grade quando um profissional está filtrado
            'calIntervalos'   => $this->filtroProfissionalId && $visao !== VisaoAgenda::Mes
                ? $calendario->intervalosGrade($this->filtroProfissionalId)
                : [],
            'calReferencia'   => $this->dataReferencia(),
            'calInicio'       => $inicio,
            'calFim'          => $fim,
            'calDias'         => $dias,
            'calPorDia'       => $porDia,
            'calLayout'       => $layout,
            'calFaixa'        => $faixa,
            'calLimite'       => $agendamentos->count() >= AgendaCalendarioService::LIMITE_POR_PERIODO,
        ];
    }

    private function tituloPeriodo(VisaoAgenda $visao): string
    {
        $ref = $this->dataReferencia();

        return match ($visao) {
            VisaoAgenda::Dia, VisaoAgenda::Lista => $ref->translatedFormat('D, d \\d\\e F \\d\\e Y'),
            VisaoAgenda::Semana => sprintf(
                '%s – %s',
                $ref->startOfWeek(CarbonImmutable::MONDAY)->translatedFormat('d M'),
                $ref->startOfWeek(CarbonImmutable::MONDAY)->addDays(6)->translatedFormat('d M Y'),
            ),
            VisaoAgenda::Mes => Str::ucfirst($ref->translatedFormat('F \\d\\e Y')),
        };
    }

    // ─── Render ───────────────────────────────────────────────────
    public function render(): View
    {
        $this->filtroProfissionalId = $this->profissionalFixo() ?? $this->filtroProfissionalId;
        $visao = $this->visaoAtual();

        if ($visao->isCalendario()) {
            $calendario = app(AgendaCalendarioService::class);
            $dados      = $this->dadosCalendario($visao, $calendario);
            $agendamentos = null;
            // No mês, os totais consideram só o mês de referência (não os dias vizinhos da grade).
            [$statsInicio, $statsFim] = $visao === VisaoAgenda::Mes
                ? [$dados['calReferencia']->startOfMonth(), $dados['calReferencia']->startOfMonth()->addMonth()]
                : [$dados['calInicio'], $dados['calFim']];
            $statsBase = Agendamento::where('inicio_em', '>=', $statsInicio)
                ->where('inicio_em', '<', $statsFim);
        } else {
            $dados        = [];
            $agendamentos = Agendamento::with([
                'paciente:id,nome,telefone',
                'profissional:id,nome,cor_agenda',
                'procedimento:id,nome,duracao_minutos',
            ])
                ->select(['id', 'paciente_id', 'profissional_id', 'procedimento_id', 'procedimentos_ids',
                          'inicio_em', 'fim_em', 'status', 'observacoes', 'motivo_cancelamento', 'google_event_id'])
                ->orderBy('inicio_em', 'asc')
                ->when($this->filtroData, fn ($q) => $q->whereDate('inicio_em', $this->filtroData))
                ->when($this->filtroProfissionalId, fn ($q) => $q->where('profissional_id', $this->filtroProfissionalId))
                ->when($this->filtroStatus, fn ($q) => $q->where('status', $this->filtroStatus))
                ->paginate(20);
            $statsBase = Agendamento::when($this->filtroData, fn ($q) => $q->whereDate('inicio_em', $this->filtroData));
        }

        // Stats com queries diretas (não baseadas na página atual / no limite do calendário)
        $statsBase->when($this->filtroProfissionalId, fn ($q) => $q->where('profissional_id', $this->filtroProfissionalId));

        $statsTotal      = (clone $statsBase)->count();
        $statsConfirmado = (clone $statsBase)->where('status', StatusAgendamento::Confirmado->value)->count();
        $statsPendente   = (clone $statsBase)->whereIn('status', [
            StatusAgendamento::Agendado->value,
            StatusAgendamento::Confirmado->value,
        ])->count();

        $agendamentoDetalhe = $this->detalheId
            ? Agendamento::with(['paciente', 'profissional', 'procedimento', 'agendamentoOrigem', 'receita:id,agendamento_id,valor_bruto,status'])->find($this->detalheId)
            : null;

        $agendamentoConcluir = $this->modalConcluir && $this->concluirId
            ? Agendamento::with(['paciente:id,nome,valor_mensalidade', 'procedimento:id,nome'])
                ->select(['id', 'paciente_id', 'procedimento_id', 'procedimentos_ids', 'inicio_em', 'fim_em'])
                ->find($this->concluirId)
            : null;

        $pacotesConcluir = $agendamentoConcluir
            ? Pacote::query()->select(['id', 'paciente_id', 'procedimento_id', 'nome', 'sessoes_total', 'sessoes_usadas', 'validade'])
                ->utilizaveis()->where('paciente_id', $agendamentoConcluir->paciente_id)
                ->orderBy('created_at')->limit(20)->get()
            : collect();

        return view('livewire.agendamento-index', [
            ...$dados,
            'pacotesConcluir'    => $pacotesConcluir,
            'visaoAtual'         => $visao,
            'tituloPeriodo'      => $this->tituloPeriodo($visao),
            'agendamentos'       => $agendamentos,
            'agendamentoDetalhe' => $agendamentoDetalhe,
            'agendamentoConcluir' => $agendamentoConcluir,
            'statsTotal'         => $statsTotal,
            'statsConfirmado'    => $statsConfirmado,
            'statsPendente'      => $statsPendente,
        ])->layout('layouts.app', ['title' => 'Agenda']);
    }
}
