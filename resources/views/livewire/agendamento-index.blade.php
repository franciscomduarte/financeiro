<div class="space-y-4">

    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
            <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2h-2v-2zm0-8h2v6h-2V5z" clip-rule="evenodd"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- Header ──────────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Agenda</h1>
            <p class="text-sm text-gray-500">Gerencie os agendamentos da clínica</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-violet-700 active:bg-violet-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Novo Agendamento
        </button>
    </div>

    {{-- Filtros ──────────────────────────────────────────────────────────────── --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Data</label>
                <input type="date" wire:model.live="filtroData"
                       class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Profissional</label>
                <select wire:model.live="filtroProfissionalId"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Todos</option>
                    @foreach ($this->profissionais as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-600">Status</label>
                <select wire:model.live="filtroStatus"
                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="">Todos</option>
                    @foreach ($this->statusOpcoes as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button wire:click="limparFiltros"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Limpar
            </button>
        </div>
    </div>

    {{-- Tabela ───────────────────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Horário</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Paciente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden md:table-cell">Profissional</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 hidden lg:table-cell">Procedimento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($agendamentos as $ag)
                        @php $isPendente = $ag->status->isPendente(); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            {{-- Horário --}}
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="text-sm font-semibold text-gray-900">{{ $ag->inicio_em->format('H:i') }}</span>
                                <span class="text-xs text-gray-400"> – {{ $ag->fim_em->format('H:i') }}</span>
                                @if ($filtroData !== $ag->inicio_em->toDateString())
                                    <div class="text-xs text-gray-400">{{ $ag->inicio_em->format('d/m') }}</div>
                                @endif
                            </td>
                            {{-- Paciente --}}
                            <td class="px-4 py-3">
                                <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                        class="text-left text-sm font-medium text-gray-900 hover:text-violet-700">
                                    {{ $ag->paciente?->nome ?? '—' }}
                                </button>
                                @if ($ag->paciente?->telefone)
                                    <div class="text-xs text-gray-400">{{ $ag->paciente->telefone }}</div>
                                @endif
                            </td>
                            {{-- Profissional --}}
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                    <span class="h-2.5 w-2.5 rounded-full flex-shrink-0"
                                          style="background-color: {{ $ag->profissional?->cor_agenda ?? '#be123c' }}"></span>
                                    {{ $ag->profissional?->nome ?? '—' }}
                                </span>
                            </td>
                            {{-- Procedimento --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="text-sm text-gray-700">{{ $ag->procedimento?->nome ?? '—' }}</span>
                                <div class="text-xs text-gray-400">{{ $ag->procedimento?->duracao_minutos }} min</div>
                            </td>
                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @php
                                    $statusClasses = match($ag->status) {
                                        \App\Enums\StatusAgendamento::Agendado   => 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
                                        \App\Enums\StatusAgendamento::Confirmado => 'bg-green-50 text-green-700 ring-1 ring-green-200',
                                        \App\Enums\StatusAgendamento::Realizado  => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                        \App\Enums\StatusAgendamento::Cancelado  => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                        \App\Enums\StatusAgendamento::Reagendado => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                                        \App\Enums\StatusAgendamento::Falta      => 'bg-gray-100 text-gray-600 ring-1 ring-gray-200',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $ag->status->label() }}
                                </span>
                            </td>
                            {{-- Ações --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($ag->status === \App\Enums\StatusAgendamento::Agendado)
                                        <button wire:click="confirmarAgendamento('{{ $ag->id }}')"
                                                title="Confirmar"
                                                class="rounded-md p-1.5 text-green-600 hover:bg-green-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    @endif
                                    @if ($isPendente)
                                        <button wire:click="marcarRealizado('{{ $ag->id }}')"
                                                title="Marcar como realizado"
                                                class="rounded-md p-1.5 text-blue-600 hover:bg-blue-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button wire:click="marcarFalta('{{ $ag->id }}')"
                                                title="Registrar falta"
                                                class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                        <button wire:click="abrirModalReagendar('{{ $ag->id }}')"
                                                title="Reagendar"
                                                class="rounded-md p-1.5 text-orange-600 hover:bg-orange-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button wire:click="abrirModalCancelar('{{ $ag->id }}')"
                                                title="Cancelar"
                                                class="rounded-md p-1.5 text-red-600 hover:bg-red-50">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                    <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                            title="Ver detalhes"
                                            class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">
                                Nenhum agendamento encontrado para os filtros selecionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($agendamentos->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $agendamentos->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Criar Agendamento                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalCriar"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl" x-trap.noreturn="true">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Novo Agendamento</h2>
                <button wire:click="fecharModalCriar" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-6">
                {{-- Paciente (autocomplete) --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Paciente <span class="text-red-500">*</span></label>
                    <div class="relative" x-data x-on:click.outside="$wire.mostrarSugestoes = false">
                        <input type="text" wire:model.live.debounce.300ms="criarPacienteBusca"
                               placeholder="Digite o nome do paciente..."
                               autocomplete="off"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('criarPacienteId') border-red-300 @enderror">
                        @if ($criarPacienteId)
                            <button wire:click="limparPaciente" class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        @endif
                        @if ($mostrarSugestoes && count($sugestoesPaciente) > 0)
                            <ul class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                                @foreach ($sugestoesPaciente as $sug)
                                    <li>
                                        <button type="button"
                                                wire:click="selecionarPaciente('{{ $sug['id'] }}', '{{ addslashes($sug['nome']) }}')"
                                                class="w-full px-3 py-2 text-left text-sm hover:bg-violet-50 hover:text-violet-700">
                                            {{ $sug['nome'] }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @error('criarPacienteId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Profissional --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Profissional <span class="text-red-500">*</span></label>
                    <select wire:model.live="criarProfissionalId"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('criarProfissionalId') border-red-300 @enderror">
                        <option value="">Selecione...</option>
                        @foreach ($this->profissionais as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                    @error('criarProfissionalId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Procedimento --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Procedimento <span class="text-red-500">*</span></label>
                    <select wire:model.live="criarProcedimentoId"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 @error('criarProcedimentoId') border-red-300 @enderror">
                        <option value="">Selecione...</option>
                        @foreach ($this->procedimentos as $proc)
                            <option value="{{ $proc->id }}">{{ $proc->nome }} ({{ $proc->duracao_minutos }}min — R$ {{ number_format($proc->valor, 2, ',', '.') }})</option>
                        @endforeach
                    </select>
                    @error('criarProcedimentoId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Data --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Data <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="criarData" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    @error('criarData') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Horários disponíveis --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Horário disponível <span class="text-red-500">*</span></label>
                    @if ($criarProfissionalId && $criarProcedimentoId && $criarData)
                        @if (count($this->slots) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->slots as $slot)
                                    <button type="button"
                                            wire:click="$set('criarSlot', '{{ $slot }}')"
                                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                                                {{ $criarSlot === $slot
                                                    ? 'border-violet-500 bg-violet-50 text-violet-700'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:border-violet-300 hover:bg-violet-50' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                Nenhum horário disponível para esta data. Verifique a grade do profissional ou escolha outra data.
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Selecione profissional, procedimento e data para ver os horários.</p>
                    @endif
                    @error('criarSlot') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Observações --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Observações</label>
                    <textarea wire:model="criarObservacoes" rows="2"
                              placeholder="Informações adicionais (opcional)..."
                              class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalCriar"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="salvarAgendamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading wire:target="salvarAgendamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Agendamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Cancelar                                                        --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($modalCancelar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalCancelar"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Cancelar Agendamento</h2>
                <button wire:click="fecharModalCancelar" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <label class="mb-1 block text-sm font-medium text-gray-700">Motivo do cancelamento <span class="text-red-500">*</span></label>
                <textarea wire:model="cancelarMotivo" rows="3"
                          placeholder="Descreva o motivo..."
                          class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-red-400 focus:ring-red-400 @error('cancelarMotivo') border-red-300 @enderror"></textarea>
                @error('cancelarMotivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalCancelar"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Voltar
                </button>
                <button wire:click="confirmarCancelamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60">
                    <span wire:loading wire:target="confirmarCancelamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Reagendar                                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($modalReagendar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharModalReagendar"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Reagendar</h2>
                <button wire:click="fecharModalReagendar" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4 p-6">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nova data <span class="text-red-500">*</span></label>
                    <input type="date" wire:model.live="reagendarData" min="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    @error('reagendarData') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Novo horário <span class="text-red-500">*</span></label>
                    @if ($reagendarData && $reagendarId)
                        @if (count($this->slotsReagendar) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->slotsReagendar as $slot)
                                    <button type="button"
                                            wire:click="$set('reagendarSlot', '{{ $slot }}')"
                                            class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                                                {{ $reagendarSlot === $slot
                                                    ? 'border-violet-500 bg-violet-50 text-violet-700'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:border-violet-300' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                Nenhum horário disponível para esta data. Escolha outra data.
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Selecione uma data para ver os horários disponíveis.</p>
                    @endif
                    @error('reagendarSlot') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharModalReagendar"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button wire:click="confirmarReagendamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60">
                    <span wire:loading wire:target="confirmarReagendamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Reagendamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Detalhe                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $agendamentoDetalhe)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="fecharDetalhe"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h2 class="text-base font-semibold text-gray-900">Detalhes do Agendamento</h2>
                <button wire:click="fecharDetalhe" class="rounded-full p-1.5 text-gray-400 hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="divide-y divide-gray-50 p-6">
                @php
                    $d = $agendamentoDetalhe;
                    $statusDetalheClasses = match($d->status) {
                        \App\Enums\StatusAgendamento::Agendado   => 'bg-violet-50 text-violet-700',
                        \App\Enums\StatusAgendamento::Confirmado => 'bg-green-50 text-green-700',
                        \App\Enums\StatusAgendamento::Realizado  => 'bg-blue-50 text-blue-700',
                        \App\Enums\StatusAgendamento::Cancelado  => 'bg-red-50 text-red-700',
                        \App\Enums\StatusAgendamento::Reagendado => 'bg-orange-50 text-orange-700',
                        \App\Enums\StatusAgendamento::Falta      => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 pb-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500">Status</p>
                        <span class="mt-0.5 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusDetalheClasses }}">
                            {{ $d->status->label() }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Data e Horário</p>
                        <p class="mt-0.5 text-sm text-gray-900">
                            {{ $d->inicio_em->format('d/m/Y') }} — {{ $d->inicio_em->format('H:i') }} às {{ $d->fim_em->format('H:i') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Paciente</p>
                        <p class="mt-0.5 text-sm font-medium text-gray-900">{{ $d->paciente?->nome ?? '—' }}</p>
                        @if ($d->paciente?->telefone)
                            <p class="text-xs text-gray-400">{{ $d->paciente->telefone }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">Profissional</p>
                        <p class="mt-0.5 flex items-center gap-1.5 text-sm text-gray-900">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $d->profissional?->cor_agenda ?? '#be123c' }}"></span>
                            {{ $d->profissional?->nome ?? '—' }}
                        </p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-gray-500">Procedimento</p>
                        <p class="mt-0.5 text-sm text-gray-900">{{ $d->procedimento?->nome ?? '—' }}</p>
                        @if ($d->procedimento)
                            <p class="text-xs text-gray-400">{{ $d->procedimento->duracao_minutos }} min — R$ {{ number_format($d->procedimento->valor, 2, ',', '.') }}</p>
                        @endif
                    </div>
                    @if ($d->observacoes)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-gray-500">Observações</p>
                            <p class="mt-0.5 text-sm text-gray-700">{{ $d->observacoes }}</p>
                        </div>
                    @endif
                    @if ($d->motivo_cancelamento)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-red-500">Motivo do Cancelamento</p>
                            <p class="mt-0.5 text-sm text-red-700">{{ $d->motivo_cancelamento }}</p>
                        </div>
                    @endif
                    @if ($d->agendamentoOrigem)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-gray-500">Reagendamento de</p>
                            <p class="mt-0.5 text-sm text-gray-700">
                                {{ $d->agendamentoOrigem->inicio_em->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    @endif
                    @if ($d->google_event_id)
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-gray-500">Google Calendar</p>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-green-700">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Evento sincronizado
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-end border-t border-gray-100 px-6 py-4">
                <button wire:click="fecharDetalhe"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
