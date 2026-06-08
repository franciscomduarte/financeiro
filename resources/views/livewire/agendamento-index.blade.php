<div class="space-y-6">

    {{-- ─── Flash Messages ─────────────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 z-50 max-w-sm">
            <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                {{ $flashSucesso }}
            </div>
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 max-w-sm">
            <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Agenda</h1>
            <p class="text-sm text-stone-500 mt-0.5">Agendamentos da clínica</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm shadow-violet-200 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Novo Agendamento
        </button>
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-stone-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-stone-800 tabular-nums">{{ number_format($statsTotal) }}</p>
            <p class="text-xs text-stone-400 mt-0.5">{{ $filtroData ? \Carbon\Carbon::parse($filtroData)->translatedFormat('d \d\e F') : 'filtro atual' }}</p>
        </div>
        <div class="rounded-2xl border border-stone-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Confirmados</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 tabular-nums">{{ $statsConfirmado }}</p>
        </div>
        <div class="rounded-2xl border border-stone-100 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-stone-400">Pendentes</p>
            <p class="mt-1 text-2xl font-bold text-violet-600 tabular-nums">{{ $statsPendente }}</p>
        </div>
    </div>

    {{-- ─── Filtros ─────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-stone-500">Data</label>
                <input type="date" wire:model.live="filtroData"
                       class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-stone-500">Profissional</label>
                <select wire:model.live="filtroProfissionalId"
                        class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                    <option value="">Todos</option>
                    @foreach ($this->profissionais as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-stone-500">Status</label>
                <select wire:model.live="filtroStatus"
                        class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                    <option value="">Todos</option>
                    @foreach ($this->statusOpcoes as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            @if ($filtroData !== now()->toDateString() || $filtroProfissionalId || $filtroStatus)
                <button wire:click="limparFiltros"
                        class="text-sm font-medium text-violet-600 hover:text-violet-700 whitespace-nowrap">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- ─── Tabela ──────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Horário</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Paciente</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden md:table-cell">Profissional</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden lg:table-cell">Procedimento</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($agendamentos as $ag)
                        @php
                            $isPendente = $ag->status->isPendente();
                            $isNow = $filtroData === now()->toDateString()
                                && $ag->inicio_em->lte(now())
                                && $ag->fim_em->gte(now())
                                && $isPendente;
                        @endphp
                        <tr class="hover:bg-stone-50/60 transition-colors {{ $isNow ? 'border-l-2 border-l-violet-400' : '' }}">
                            {{-- Horário --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="text-sm font-semibold text-stone-900 tabular-nums">{{ $ag->inicio_em->format('H:i') }}</span>
                                <span class="text-xs text-stone-400 tabular-nums"> – {{ $ag->fim_em->format('H:i') }}</span>
                                @if ($isNow)
                                    <div class="mt-0.5">
                                        <span class="inline-flex items-center gap-1 text-xs font-medium text-violet-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-violet-500 animate-pulse"></span>
                                            agora
                                        </span>
                                    </div>
                                @endif
                            </td>
                            {{-- Paciente --}}
                            <td class="px-5 py-3.5">
                                <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                        class="text-left text-sm font-semibold text-stone-800 hover:text-violet-700 transition-colors">
                                    {{ $ag->paciente?->nome ?? '—' }}
                                </button>
                                @if ($ag->paciente?->telefone)
                                    <div class="text-xs text-stone-400 mt-0.5">{{ $ag->paciente->telefone }}</div>
                                @endif
                            </td>
                            {{-- Profissional --}}
                            <td class="px-5 py-3.5 hidden md:table-cell">
                                <span class="inline-flex items-center gap-2 text-sm text-stone-700">
                                    <span class="h-2.5 w-2.5 rounded-full shrink-0"
                                          style="background-color: {{ $ag->profissional?->cor_agenda ?? '#8b5cf6' }}"></span>
                                    {{ $ag->profissional?->nome ?? '—' }}
                                </span>
                            </td>
                            {{-- Procedimento --}}
                            <td class="px-5 py-3.5 hidden lg:table-cell">
                                <span class="text-sm text-stone-700">{{ $ag->procedimento?->nome ?? '—' }}</span>
                                @if ($ag->procedimento)
                                    <div class="text-xs text-stone-400">{{ $ag->procedimento->duracao_minutos }} min</div>
                                @endif
                            </td>
                            {{-- Status --}}
                            <td class="px-5 py-3.5">
                                @php
                                    $badge = match($ag->status) {
                                        \App\Enums\StatusAgendamento::Agendado   => 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200',
                                        \App\Enums\StatusAgendamento::Confirmado => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                                        \App\Enums\StatusAgendamento::Realizado  => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
                                        \App\Enums\StatusAgendamento::Cancelado  => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
                                        \App\Enums\StatusAgendamento::Reagendado => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
                                        \App\Enums\StatusAgendamento::Falta      => 'bg-stone-100 text-stone-500 ring-1 ring-inset ring-stone-200',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">
                                    {{ $ag->status->label() }}
                                </span>
                            </td>
                            {{-- Ações --}}
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-0.5">
                                    @if ($ag->status === \App\Enums\StatusAgendamento::Agendado)
                                        <button wire:click="confirmarAgendamento('{{ $ag->id }}')"
                                                title="Confirmar presença"
                                                class="rounded-lg p-1.5 text-emerald-600 hover:bg-emerald-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    @endif
                                    @if ($isPendente)
                                        <button wire:click="marcarRealizado('{{ $ag->id }}')"
                                                title="Marcar como realizado"
                                                class="rounded-lg p-1.5 text-sky-600 hover:bg-sky-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button wire:click="marcarFalta('{{ $ag->id }}')"
                                                title="Registrar falta"
                                                class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </button>
                                        <button wire:click="abrirModalReagendar('{{ $ag->id }}')"
                                                title="Reagendar"
                                                class="rounded-lg p-1.5 text-amber-600 hover:bg-amber-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button wire:click="abrirModalCancelar('{{ $ag->id }}')"
                                                title="Cancelar"
                                                class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    @endif
                                    <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                            title="Ver detalhes"
                                            class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <p class="text-sm font-medium text-stone-400">Nenhum agendamento encontrado</p>
                                    <p class="text-xs text-stone-300">Ajuste os filtros ou crie um novo agendamento</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($agendamentos->hasPages())
            <div class="border-t border-stone-100 px-5 py-3">
                {{ $agendamentos->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Criar Agendamento                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalCriar"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <div>
                    <h2 class="text-base font-bold text-stone-900">Novo Agendamento</h2>
                    <p class="text-xs text-stone-400 mt-0.5">Preencha os dados para confirmar</p>
                </div>
                <button wire:click="fecharModalCriar" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-5 p-6 max-h-[75vh] overflow-y-auto">

                {{-- ── Paciente: combobox com busca client-side ── --}}
                <div x-data="{
                        open: false,
                        search: '',
                        patients: [],
                        get filtered() {
                            if (!this.search) return this.patients;
                            const q = this.search.toLowerCase();
                            return this.patients.filter(p => p.nome.toLowerCase().includes(q));
                        }
                    }"
                     x-init="patients = $wire.pacientesLista"
                     x-on:click.outside="open = false">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Paciente <span class="text-red-400">*</span></label>
                    <div class="relative">
                        {{-- Botão trigger --}}
                        <button type="button"
                                @click="open = !open"
                                class="w-full flex items-center justify-between rounded-xl border px-3 py-2.5 text-sm text-left transition-colors
                                    {{ $criarPacienteId ? 'border-violet-300 bg-violet-50 text-violet-800' : 'border-stone-200 bg-white text-stone-400' }}
                                    @error('criarPacienteId') !border-red-300 @enderror
                                    focus:outline-none focus:ring-2 focus:ring-violet-100">
                            <span class="{{ $criarPacienteId ? 'font-semibold' : '' }}">
                                {{ $criarPacienteNome ?: 'Selecione um paciente...' }}
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                @if ($criarPacienteId)
                                    <button type="button"
                                            wire:click.stop="limparPaciente"
                                            class="rounded-full p-0.5 text-violet-400 hover:bg-violet-100 hover:text-violet-700 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                                <svg class="h-4 w-4 text-stone-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                        {{-- Dropdown --}}
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute z-20 mt-1 w-full rounded-xl border border-stone-200 bg-white shadow-xl overflow-hidden">
                            <div class="p-2 border-b border-stone-100">
                                <input x-model="search"
                                       x-ref="searchInput"
                                       x-init="$watch('open', v => v && $nextTick(() => $refs.searchInput.focus()))"
                                       type="text"
                                       placeholder="Buscar paciente..."
                                       class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                            </div>
                            <ul class="max-h-52 overflow-auto py-1">
                                <template x-for="p in filtered" :key="p.id">
                                    <li>
                                        <button type="button"
                                                @click="$wire.selecionarPaciente(p.id, p.nome); open = false; search = ''"
                                                :class="$wire.criarPacienteId === p.id ? 'bg-violet-50 text-violet-800 font-semibold' : 'text-stone-700 hover:bg-stone-50'"
                                                class="w-full px-4 py-2.5 text-left text-sm transition-colors">
                                            <span x-text="p.nome"></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="filtered.length === 0"
                                    class="px-4 py-3 text-sm text-stone-400 text-center">
                                    Nenhum paciente encontrado
                                </li>
                            </ul>
                        </div>
                    </div>
                    @error('criarPacienteId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Profissional --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Profissional <span class="text-red-400">*</span></label>
                        <select wire:model.live="criarProfissionalId"
                                class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('criarProfissionalId') border-red-300 @enderror">
                            <option value="">Selecione...</option>
                            @foreach ($this->profissionais as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                        @error('criarProfissionalId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    {{-- Data --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Data <span class="text-red-400">*</span></label>
                        <input type="date" wire:model.live="criarData" min="{{ now()->toDateString() }}"
                               class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                        @error('criarData') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ── Procedimentos: multi-seleção via checkboxes ── --}}
                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                            Procedimentos <span class="text-red-400">*</span>
                        </label>
                        @if ($this->duracaoTotal > 0)
                            <span class="text-xs font-semibold text-violet-600">
                                Duração total: {{ $this->duracaoTotal }} min
                            </span>
                        @endif
                    </div>
                    <div class="rounded-xl border border-stone-200 divide-y divide-stone-100 overflow-hidden
                        @error('criarProcedimentoIds') border-red-300 @enderror">
                        @forelse ($this->procedimentos as $proc)
                            @php $checked = in_array($proc->id, $this->criarProcedimentoIds, false); @endphp
                            <label class="flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors
                                {{ $checked ? 'bg-violet-50' : 'bg-white hover:bg-stone-50' }}">
                                <input type="checkbox"
                                       wire:model.live="criarProcedimentoIds"
                                       value="{{ $proc->id }}"
                                       class="h-4 w-4 rounded border-stone-300 text-violet-600 focus:ring-violet-100">
                                <span class="flex-1 text-sm {{ $checked ? 'font-semibold text-violet-800' : 'text-stone-700' }}">
                                    {{ $proc->nome }}
                                </span>
                                <span class="text-xs text-stone-400 tabular-nums shrink-0">
                                    {{ $proc->duracao_minutos }}min · R$ {{ number_format($proc->valor, 2, ',', '.') }}
                                </span>
                            </label>
                        @empty
                            <p class="px-4 py-3 text-sm text-stone-400">Nenhum procedimento ativo. Cadastre em Configurações.</p>
                        @endforelse
                    </div>
                    @error('criarProcedimentoIds') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- ── Horários disponíveis ── --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Horário <span class="text-red-400">*</span></label>
                    @if ($criarProfissionalId && count($criarProcedimentoIds) > 0 && $criarData)
                        @if (count($this->slots) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->slots as $slot)
                                    <button type="button"
                                            wire:click="$set('criarSlot', '{{ $slot }}')"
                                            class="rounded-xl border px-3.5 py-1.5 text-sm font-semibold tabular-nums transition-colors
                                                {{ $criarSlot === $slot
                                                    ? 'border-violet-500 bg-violet-600 text-white shadow-sm shadow-violet-200'
                                                    : 'border-stone-200 bg-white text-stone-700 hover:border-violet-300 hover:text-violet-700' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">
                                Nenhum horário disponível. Verifique a grade do profissional ou escolha outra data.
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-stone-400">Selecione profissional, procedimento(s) e data.</p>
                    @endif
                    @error('criarSlot') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Observações --}}
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Observações</label>
                    <textarea wire:model="criarObservacoes" rows="2"
                              placeholder="Informações adicionais (opcional)..."
                              class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 placeholder:text-stone-400 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalCriar"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="salvarAgendamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 hover:bg-violet-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="salvarAgendamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Agendamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Cancelar                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalCancelar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalCancelar"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <h2 class="text-base font-bold text-stone-900">Cancelar Agendamento</h2>
                <button wire:click="fecharModalCancelar" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Motivo do cancelamento <span class="text-red-400">*</span></label>
                <textarea wire:model="cancelarMotivo" rows="3"
                          placeholder="Descreva o motivo..."
                          class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100 @error('cancelarMotivo') border-red-300 @enderror"></textarea>
                @error('cancelarMotivo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalCancelar"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Voltar
                </button>
                <button wire:click="confirmarCancelamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-200 hover:bg-red-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="confirmarCancelamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Reagendar                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalReagendar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalReagendar"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <div>
                    <h2 class="text-base font-bold text-stone-900">Reagendar</h2>
                    <p class="text-xs text-stone-400 mt-0.5">Selecione a nova data e horário</p>
                </div>
                <button wire:click="fecharModalReagendar" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-5 p-6">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Nova data <span class="text-red-400">*</span></label>
                    <input type="date" wire:model.live="reagendarData" min="{{ now()->toDateString() }}"
                           class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                    @error('reagendarData') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Novo horário <span class="text-red-400">*</span></label>
                    @if ($reagendarData && $reagendarId)
                        @if (count($this->slotsReagendar) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->slotsReagendar as $slot)
                                    <button type="button"
                                            wire:click="$set('reagendarSlot', '{{ $slot }}')"
                                            class="rounded-xl border px-3.5 py-1.5 text-sm font-semibold tabular-nums transition-colors
                                                {{ $reagendarSlot === $slot
                                                    ? 'border-violet-500 bg-violet-600 text-white shadow-sm shadow-violet-200'
                                                    : 'border-stone-200 bg-white text-stone-700 hover:border-violet-300 hover:text-violet-700' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-sm text-amber-700">
                                Nenhum horário disponível para esta data.
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-stone-400">Selecione uma data para ver os horários.</p>
                    @endif
                    @error('reagendarSlot') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalReagendar"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="confirmarReagendamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 hover:bg-violet-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="confirmarReagendamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Confirmar Reagendamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Detalhe                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $agendamentoDetalhe)
    @php $d = $agendamentoDetalhe; @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharDetalhe"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            {{-- Header colorido conforme status --}}
            @php
                $headerBg = match($d->status) {
                    \App\Enums\StatusAgendamento::Agendado   => 'bg-violet-600',
                    \App\Enums\StatusAgendamento::Confirmado => 'bg-emerald-600',
                    \App\Enums\StatusAgendamento::Realizado  => 'bg-sky-600',
                    \App\Enums\StatusAgendamento::Cancelado  => 'bg-red-600',
                    \App\Enums\StatusAgendamento::Reagendado => 'bg-amber-500',
                    \App\Enums\StatusAgendamento::Falta      => 'bg-stone-500',
                };
            @endphp
            <div class="{{ $headerBg }} px-6 py-5 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest opacity-70">{{ $d->status->label() }}</p>
                        <h2 class="mt-1 text-lg font-bold">{{ $d->paciente?->nome ?? '—' }}</h2>
                        <p class="mt-0.5 text-sm opacity-80">
                            {{ $d->inicio_em->format('d/m/Y') }} · {{ $d->inicio_em->format('H:i') }} – {{ $d->fim_em->format('H:i') }}
                        </p>
                    </div>
                    <button wire:click="fecharDetalhe" class="rounded-lg p-1.5 text-white/70 hover:bg-white/20 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="divide-y divide-stone-100">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4 p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Profissional</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-medium text-stone-800">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ $d->profissional?->cor_agenda ?? '#8b5cf6' }}"></span>
                            {{ $d->profissional?->nome ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Telefone</p>
                        <p class="mt-1 text-sm text-stone-700">{{ $d->paciente?->telefone ?? '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Procedimento</p>
                        <p class="mt-1 text-sm font-medium text-stone-800">{{ $d->procedimento?->nome ?? '—' }}</p>
                        @if ($d->procedimento)
                            <p class="text-xs text-stone-400">{{ $d->procedimento->duracao_minutos }} min · R$ {{ number_format($d->procedimento->valor, 2, ',', '.') }}</p>
                        @endif
                    </div>
                    @if ($d->observacoes)
                        <div class="col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Observações</p>
                            <p class="mt-1 text-sm text-stone-700">{{ $d->observacoes }}</p>
                        </div>
                    @endif
                    @if ($d->motivo_cancelamento)
                        <div class="col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-400">Motivo do Cancelamento</p>
                            <p class="mt-1 text-sm text-red-700">{{ $d->motivo_cancelamento }}</p>
                        </div>
                    @endif
                    @if ($d->agendamentoOrigem)
                        <div class="col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Reagendado de</p>
                            <p class="mt-1 text-sm text-stone-600">{{ $d->agendamentoOrigem->inicio_em->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                    @if ($d->google_event_id)
                        <div class="col-span-2">
                            <p class="mt-1 flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Sincronizado com Google Calendar
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex justify-end border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharDetalhe"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
