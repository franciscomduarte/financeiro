<div class="space-y-6">

    {{-- ─── Flash Messages (session — OAuth redirect) ─────────────────── --}}
    @if (session('sucesso'))
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 z-[9999] max-w-sm">
            <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                {{ session('sucesso') }}
            </div>
        </div>
    @endif
    @if (session('erro'))
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 8000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-[9999] max-w-sm">
            <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                {{ session('erro') }}
            </div>
        </div>
    @endif

    {{-- ─── Flash Messages (Livewire) ─────────────────────────────────── --}}
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
    <div>
        <h1 class="text-xl font-bold text-stone-900">Configurações — Agenda</h1>
        <p class="text-sm text-stone-500 mt-0.5">Profissionais, grades horárias e procedimentos</p>
    </div>

    {{-- ─── Abas ────────────────────────────────────────────────────────── --}}
    <div class="flex gap-1 rounded-2xl border border-stone-100 bg-stone-50 p-1.5 shadow-sm w-fit">
        <button wire:click="$set('aba', 'profissionais')"
                class="rounded-xl px-5 py-2 text-sm font-semibold transition-colors
                    {{ $aba === 'profissionais' ? 'bg-white text-violet-700 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            Profissionais
        </button>
        <button wire:click="$set('aba', 'procedimentos')"
                class="rounded-xl px-5 py-2 text-sm font-semibold transition-colors
                    {{ $aba === 'procedimentos' ? 'bg-white text-violet-700 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            Procedimentos
        </button>
    </div>

    {{-- ════════════════════════ ABA: PROFISSIONAIS ════════════════════════ --}}
    @if ($aba === 'profissionais')
    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-stone-800">Profissionais</h2>
                <p class="text-xs text-stone-400 mt-0.5">{{ $profissionais->count() }} cadastrados</p>
            </div>
            <button wire:click="abrirModalNovoProfissional"
                    class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm shadow-violet-200 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Nome</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden sm:table-cell">E-mail</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden md:table-cell">Telefone</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($profissionais as $p)
                        <tr class="hover:bg-stone-50/60 transition-colors">
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-2.5 text-sm font-semibold text-stone-800">
                                    <span class="h-3 w-3 rounded-full shrink-0 ring-2 ring-white shadow-sm"
                                          style="background-color: {{ $p->cor_agenda }}"></span>
                                    {{ $p->nome }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 hidden sm:table-cell text-sm text-stone-600">{{ $p->email }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-sm text-stone-600">{{ $p->telefone ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $p->ativo ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' : 'bg-stone-100 text-stone-500' }}">
                                    {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button wire:click="abrirModalGrade('{{ $p->id }}')"
                                            title="Grade horária"
                                            class="rounded-lg p-1.5 text-violet-600 hover:bg-violet-50 transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                                    </button>
                                    @if ($p->google_refresh_token)
                                        <form method="POST" action="{{ route('agenda.google.desconectar', $p->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    title="Desconectar Google Calendar"
                                                    class="rounded-lg p-1.5 text-emerald-600 hover:bg-red-50 hover:text-red-500 transition-colors">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('agenda.google.auth', $p->id) }}"
                                           title="Conectar Google Calendar"
                                           class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                                        </a>
                                    @endif
                                    <button wire:click="abrirModalEditarProfissional('{{ $p->id }}')"
                                            title="Editar"
                                            class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                                    <p class="text-sm font-medium text-stone-400">Nenhum profissional cadastrado</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ════════════════════════ ABA: PROCEDIMENTOS ════════════════════════ --}}
    @if ($aba === 'procedimentos')
    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold text-stone-800">Procedimentos</h2>
                <p class="text-xs text-stone-400 mt-0.5">{{ $procedimentos->count() }} cadastrados</p>
            </div>
            <button wire:click="abrirModalNovoProcedimento"
                    class="flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm shadow-violet-200 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Nome</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden sm:table-cell">Duração</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 hidden sm:table-cell">Valor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-stone-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($procedimentos as $proc)
                        <tr class="hover:bg-stone-50/60 transition-colors">
                            <td class="px-5 py-3.5 text-sm font-semibold text-stone-800">{{ $proc->nome }}</td>
                            <td class="px-5 py-3.5 hidden sm:table-cell text-sm text-stone-600">{{ $proc->duracao_minutos }} min</td>
                            <td class="px-5 py-3.5 hidden sm:table-cell text-sm font-medium text-stone-800 tabular-nums">R$ {{ number_format($proc->valor, 2, ',', '.') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $proc->ativo ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' : 'bg-stone-100 text-stone-500' }}">
                                    {{ $proc->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <button wire:click="abrirModalEditarProcedimento({{ $proc->id }})"
                                        class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1-.34 2.2-1.34 1.198L19.8 15.3zm-14.8.8L3.8 17.5c-1.001.999.34 2.2 1.34 1.198L5 14.5z"/></svg>
                                    <p class="text-sm font-medium text-stone-400">Nenhum procedimento cadastrado</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═════════════════ Modal: Profissional (criar/editar) ═══════════════ --}}
    @if ($modalProfissional)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalProfissional"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <h2 class="text-base font-bold text-stone-900">
                    {{ $profissionalEditandoId ? 'Editar Profissional' : 'Novo Profissional' }}
                </h2>
                <button wire:click="fecharModalProfissional" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-5 p-6">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Nome <span class="text-red-400">*</span></label>
                    <input type="text" wire:model="profNome"
                           class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('profNome') border-red-300 @enderror">
                    @error('profNome') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">E-mail <span class="text-red-400">*</span></label>
                    <input type="email" wire:model="profEmail"
                           class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('profEmail') border-red-300 @enderror">
                    @error('profEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Telefone</label>
                    <input type="tel" wire:model="profTelefone"
                           class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Cor na agenda</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model="profCor"
                               class="h-10 w-20 cursor-pointer rounded-xl border border-stone-200 p-0.5">
                        <span class="font-mono text-sm text-stone-500">{{ $profCor }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <input type="checkbox" id="profAtivo" wire:model="profAtivo"
                           class="h-4 w-4 rounded border-stone-300 text-violet-600 focus:ring-violet-100">
                    <label for="profAtivo" class="text-sm font-medium text-stone-700">Ativo</label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalProfissional"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="salvarProfissional" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 hover:bg-violet-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="salvarProfissional">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════ Modal: Grade Horária ════════════════════════ --}}
    @if ($modalGrade)
    @php $diasNomes = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']; @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalGrade"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <div>
                    <h2 class="text-base font-bold text-stone-900">Grade Horária</h2>
                    <p class="text-xs text-stone-400 mt-0.5">{{ $gradeEditandoNome }}</p>
                </div>
                <button wire:click="fecharModalGrade" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-2.5">
                    @foreach ($grade as $dia => $config)
                        <div class="flex items-center gap-4 rounded-xl border border-stone-100 px-4 py-3
                            {{ $config['ativo'] ? 'bg-white' : 'bg-stone-50' }}">
                            <input type="checkbox"
                                   wire:model.live="grade.{{ $dia }}.ativo"
                                   id="grade_{{ $dia }}"
                                   class="h-4 w-4 rounded border-stone-300 text-violet-600 focus:ring-violet-100">
                            <label for="grade_{{ $dia }}"
                                   class="w-8 text-sm font-semibold {{ $config['ativo'] ? 'text-stone-800' : 'text-stone-400' }}">
                                {{ $diasNomes[$dia] }}
                            </label>
                            @if ($config['ativo'])
                                <input type="time" wire:model="grade.{{ $dia }}.hora_inicio"
                                       class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-700 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                                <span class="text-xs font-medium text-stone-400">até</span>
                                <input type="time" wire:model="grade.{{ $dia }}.hora_fim"
                                       class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-700 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100">
                            @else
                                <span class="text-sm text-stone-400">Sem atendimento</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalGrade"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="salvarGrade" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 hover:bg-violet-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="salvarGrade">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar Grade
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═════════════════ Modal: Procedimento (criar/editar) ═══════════════ --}}
    @if ($modalProcedimento)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModalProcedimento"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-stone-100 overflow-hidden">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <h2 class="text-base font-bold text-stone-900">
                    {{ $procedimentoEditandoId ? 'Editar Procedimento' : 'Novo Procedimento' }}
                </h2>
                <button wire:click="fecharModalProcedimento" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-5 p-6">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Nome <span class="text-red-400">*</span></label>
                    <input type="text" wire:model="procNome"
                           class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('procNome') border-red-300 @enderror">
                    @error('procNome') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Descrição</label>
                    <textarea wire:model="procDescricao" rows="2"
                              class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Duração (min) <span class="text-red-400">*</span></label>
                        <input type="number" wire:model="procDuracao" min="15" max="480" step="15"
                               class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('procDuracao') border-red-300 @enderror">
                        @error('procDuracao') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-stone-500">Valor (R$) <span class="text-red-400">*</span></label>
                        <input type="number" wire:model="procValor" min="0" step="0.01"
                               class="w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm text-stone-800 focus:border-violet-300 focus:outline-none focus:ring-2 focus:ring-violet-100 @error('procValor') border-red-300 @enderror">
                        @error('procValor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <input type="checkbox" id="procAtivo" wire:model="procAtivo"
                           class="h-4 w-4 rounded border-stone-300 text-violet-600 focus:ring-violet-100">
                    <label for="procAtivo" class="text-sm font-medium text-stone-700">Ativo</label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4 bg-stone-50/50">
                <button wire:click="fecharModalProcedimento"
                        class="rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="salvarProcedimento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-violet-200 hover:bg-violet-700 transition-colors disabled:opacity-60">
                    <span wire:loading wire:target="salvarProcedimento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
