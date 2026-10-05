<div class="space-y-6">

    {{-- ─── Avisos (session — retorno do OAuth) ───────────────────────── --}}
    @if (session('sucesso'))
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 left-4 z-[9999] sm:left-auto sm:max-w-sm" role="status">
            <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-surface px-4 py-3 text-sm text-emerald-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
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
             class="fixed top-4 right-4 left-4 z-[9999] sm:left-auto sm:max-w-sm" role="alert">
            <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-surface px-4 py-3 text-sm text-red-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                {{ session('erro') }}
            </div>
        </div>
    @endif

    {{-- ─── Avisos (Livewire) ─────────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 left-4 z-[9999] sm:left-auto sm:max-w-sm" role="status">
            <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-surface px-4 py-3 text-sm text-emerald-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
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
             class="fixed top-4 right-4 left-4 z-[9999] sm:left-auto sm:max-w-sm" role="alert">
            <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-surface px-4 py-3 text-sm text-red-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-3 w-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Cabeçalho ───────────────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Profissionais e horários"
                      subtitulo="Cadastre quem atende, os horários de cada um, os procedimentos e as folgas."
                      class="!mb-0" />

    {{-- ─── Abas ────────────────────────────────────────────────────────── --}}
    <div class="flex w-full gap-1 overflow-x-auto rounded-xl bg-stone-100 p-1 sm:w-fit">
        <button wire:click="$set('aba', 'profissionais')"
                class="min-h-[40px] flex-1 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none
                    {{ $aba === 'profissionais' ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            Profissionais
        </button>
        <button wire:click="$set('aba', 'procedimentos')"
                class="min-h-[40px] flex-1 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none
                    {{ $aba === 'procedimentos' ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            Procedimentos
        </button>
        <button wire:click="$set('aba', 'bloqueios')"
                class="min-h-[40px] flex-1 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none
                    {{ $aba === 'bloqueios' ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
            Bloqueios
        </button>
    </div>

    {{-- ════════════════════════ ABA: PROFISSIONAIS ════════════════════════ --}}
    @if ($aba === 'profissionais')
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-stone-900">Profissionais</h2>
                <p class="mt-0.5 text-sm text-stone-500">{{ $profissionais->count() }} {{ $profissionais->count() === 1 ? 'cadastrado' : 'cadastrados' }}</p>
            </div>
            @if ($profissionais->isNotEmpty())
                <button wire:click="abrirModalNovoProfissional" class="btn-primary shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Novo profissional
                </button>
            @endif
        </div>
        @if ($profissionais->isEmpty())
            <x-ui.empty-state
                titulo="Nenhum profissional cadastrado"
                texto="Cadastre quem atende na clínica e defina os horários de cada um para liberar a agenda."
                icone="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z">
                <button wire:click="abrirModalNovoProfissional" class="btn-primary">Cadastrar profissional</button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-100">
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 sm:px-5">Nome</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500 hidden sm:table-cell">E-mail</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500 hidden md:table-cell">Telefone</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500 hidden sm:table-cell">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-stone-500 sm:px-5"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($profissionais as $p)
                            <tr class="hover:bg-stone-50 transition-colors">
                                <td class="px-4 py-3 sm:px-5">
                                    <span class="inline-flex items-center gap-2.5 text-sm font-medium text-stone-900">
                                        <span class="h-3 w-3 shrink-0 rounded-full"
                                              style="background-color: {{ $p->cor_agenda }}"></span>
                                        {{ $p->nome }}
                                    </span>
                                    @if (! $p->ativo)
                                        <span class="badge ml-1 bg-stone-100 text-stone-600 sm:hidden">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 hidden sm:table-cell text-sm text-stone-600">{{ $p->email }}</td>
                                <td class="px-5 py-3 hidden md:table-cell text-sm text-stone-600">{{ $p->telefone ?? '—' }}</td>
                                <td class="px-5 py-3 hidden sm:table-cell">
                                    <span class="badge {{ $p->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                        {{ $p->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right sm:px-5">
                                    <div class="flex items-center justify-end gap-0.5">
                                        <button wire:click="abrirModalGrade('{{ $p->id }}')"
                                                title="Horários de atendimento" aria-label="Horários de atendimento"
                                                class="flex h-11 w-11 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50 transition-colors sm:h-9 sm:w-9">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        @if ($p->google_refresh_token)
                                            <form method="POST" action="{{ route('agenda.google.desconectar', $p->id) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        title="Desconectar do Google Agenda" aria-label="Desconectar do Google Agenda"
                                                        class="flex h-11 w-11 items-center justify-center rounded-lg text-emerald-600 hover:bg-red-50 hover:text-red-600 transition-colors sm:h-9 sm:w-9">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('agenda.google.auth', $p->id) }}"
                                               title="Conectar ao Google Agenda" aria-label="Conectar ao Google Agenda"
                                               class="flex h-11 w-11 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors sm:h-9 sm:w-9">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                                            </a>
                                        @endif
                                        <button wire:click="abrirModalEditarProfissional('{{ $p->id }}')"
                                                title="Editar" aria-label="Editar {{ $p->nome }}"
                                                class="flex h-11 w-11 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors sm:h-9 sm:w-9">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endif

    {{-- ════════════════════════ ABA: PROCEDIMENTOS ════════════════════════ --}}
    @if ($aba === 'procedimentos')
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-6">
            <div>
                <h2 class="text-base font-semibold text-stone-900">Procedimentos</h2>
                <p class="mt-0.5 text-sm text-stone-500">{{ $procedimentos->count() }} {{ $procedimentos->count() === 1 ? 'cadastrado' : 'cadastrados' }}</p>
            </div>
            @if ($procedimentos->isNotEmpty())
                <button wire:click="abrirModalNovoProcedimento" class="btn-primary shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Novo procedimento
                </button>
            @endif
        </div>
        @if ($procedimentos->isEmpty())
            <x-ui.empty-state
                titulo="Nenhum procedimento cadastrado"
                texto="Cadastre os procedimentos com duração e valor. A agenda usa a duração para achar os horários livres."
                icone="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1-.34 2.2-1.34 1.198L19.8 15.3zm-14.8.8L3.8 17.5c-1.001.999.34 2.2 1.34 1.198L5 14.5z">
                <button wire:click="abrirModalNovoProcedimento" class="btn-primary">Cadastrar procedimento</button>
            </x-ui.empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-100">
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500 sm:px-5">Nome</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-stone-500 hidden sm:table-cell">Duração</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-stone-500 hidden sm:table-cell">Valor</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500 hidden sm:table-cell">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-stone-500 sm:px-5"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($procedimentos as $proc)
                            <tr class="hover:bg-stone-50 transition-colors">
                                <td class="px-4 py-3 sm:px-5">
                                    <p class="text-sm font-medium text-stone-900">{{ $proc->nome }}</p>
                                    <p class="text-xs text-stone-500 tabular-nums sm:hidden">
                                        {{ $proc->duracao_minutos }} min · R$ {{ number_format($proc->valor, 2, ',', '.') }}{{ $proc->ativo ? '' : ' · Inativo' }}
                                    </p>
                                </td>
                                <td class="px-5 py-3 hidden sm:table-cell text-right text-sm text-stone-600 tabular-nums">{{ $proc->duracao_minutos }} min</td>
                                <td class="px-5 py-3 hidden sm:table-cell text-right text-sm font-medium text-stone-900 tabular-nums">R$ {{ number_format($proc->valor, 2, ',', '.') }}</td>
                                <td class="px-5 py-3 hidden sm:table-cell">
                                    <span class="badge {{ $proc->ativo ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                        {{ $proc->ativo ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right sm:px-5">
                                    <button wire:click="abrirModalEditarProcedimento({{ $proc->id }})"
                                            title="Editar" aria-label="Editar {{ $proc->nome }}"
                                            class="ml-auto flex h-11 w-11 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors sm:h-9 sm:w-9">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endif

    {{-- ═════════════════════════ ABA: BLOQUEIOS ═════════════════════════ --}}
    @if ($aba === 'bloqueios')

    {{-- Aviso: agendamentos dentro do bloqueio recém-criado --}}
    @if (count($bloqConflitos) > 0)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-amber-800">
                    {{ count($bloqConflitos) }} {{ count($bloqConflitos) === 1 ? 'agendamento está' : 'agendamentos estão' }} no período bloqueado
                </p>
                <p class="mt-0.5 text-sm text-amber-700">{{ $bloqConflitosResumo }}. Reagende ou cancele pela agenda.</p>
            </div>
            <button wire:click="fecharConflitos" title="Fechar aviso" aria-label="Fechar aviso"
                    class="-mr-2 -mt-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-amber-700 hover:bg-amber-100 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <ul class="mt-3 divide-y divide-stone-100 overflow-hidden rounded-xl border border-amber-200 bg-surface">
            @foreach ($bloqConflitos as $c)
                <li>
                    <a href="{{ route('agenda.index', ['visao' => 'dia', 'filtroData' => $c['data'], 'filtroProfissionalId' => $c['profissional_id']]) }}"
                       class="flex min-h-[44px] items-center justify-between gap-3 px-4 py-2 text-sm hover:bg-stone-50">
                        <span class="min-w-0">
                            <span class="font-semibold tabular-nums text-stone-900">{{ $c['horario'] }}</span>
                            <span class="text-stone-700"> · {{ $c['paciente'] }}</span>
                            <span class="block truncate text-xs text-stone-500 sm:inline"> · {{ $c['profissional'] }}</span>
                        </span>
                        <span class="shrink-0 text-xs font-medium text-rose-700">Abrir na agenda →</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-4 sm:px-6">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-stone-900">Bloqueios</h2>
                <p class="mt-0.5 text-sm text-stone-500">Férias, folgas, feriados e compromissos de hoje em diante.</p>
            </div>
            @if (count($bloqueios) > 0)
                <button wire:click="abrirModalNovoBloqueio" class="btn-primary shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Novo bloqueio
                </button>
            @endif
        </div>
        @if (count($bloqueios) === 0)
            <x-ui.empty-state
                titulo="Nenhum bloqueio marcado"
                texto="Bloqueie férias, folgas ou feriados para que ninguém agende nesses horários."
                icone="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                <button wire:click="abrirModalNovoBloqueio" class="btn-primary">Bloquear horário</button>
            </x-ui.empty-state>
        @else
            <ul class="divide-y divide-stone-100">
                @foreach ($bloqueios as $b)
                    <li class="flex items-center gap-3 px-4 py-3 hover:bg-stone-50 sm:px-6" wire:key="bloq-{{ $b['id'] }}">
                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $b['cor'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-stone-900">
                                {{ \App\Services\BloqueioAgendaService::descreverPeriodo($b['inicio_em'], $b['fim_em'], $b['dia_inteiro']) }}
                            </p>
                            <p class="truncate text-xs text-stone-500">
                                <span title="{{ implode(', ', $b['profissionais']) }}">{{ $b['rotulo'] }}</span>{{ $b['motivo'] ? ' · ' . $b['motivo'] : '' }}
                            </p>
                        </div>
                        <button wire:click="removerBloqueio({{ $b['id'] }})"
                                wire:confirm="{{ count($b['profissionais']) > 1 ? 'Excluir este bloqueio de todos os profissionais? Essa ação não pode ser desfeita.' : 'Excluir este bloqueio? Essa ação não pode ser desfeita.' }}"
                                title="Excluir bloqueio" aria-label="Excluir bloqueio"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif

    {{-- ═════════════════ Modal: Profissional (criar/editar) ═══════════════ --}}
    @if ($modalProfissional)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalProfissional"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-semibold text-stone-900">
                    {{ $profissionalEditandoId ? 'Editar profissional' : 'Novo profissional' }}
                </h2>
                <button wire:click="fecharModalProfissional" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="prof-nome" class="label">Nome <span class="text-rose-600">*</span></label>
                    <input id="prof-nome" type="text" wire:model="profNome" placeholder="Ex.: Ana Souza" autocomplete="name"
                           class="input @error('profNome') !border-red-300 @enderror">
                    @error('profNome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="prof-email" class="label">E-mail <span class="text-rose-600">*</span></label>
                    <input id="prof-email" type="email" wire:model="profEmail" placeholder="Ex.: ana@suaclinica.com.br" autocomplete="email"
                           class="input @error('profEmail') !border-red-300 @enderror">
                    @error('profEmail') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="prof-telefone" class="label">Telefone</label>
                    <input id="prof-telefone" type="tel" wire:model="profTelefone" placeholder="Ex.: (11) 98765-4321" autocomplete="tel"
                           class="input">
                </div>
                <div>
                    <label for="prof-cor" class="label">Cor na agenda</label>
                    <div class="flex items-center gap-3">
                        <input id="prof-cor" type="color" wire:model="profCor"
                               class="h-11 w-20 cursor-pointer rounded-xl border border-stone-200 bg-surface p-1">
                        <span class="font-mono text-sm text-stone-500">{{ $profCor }}</span>
                    </div>
                    <p class="hint">Os atendimentos desse profissional aparecem com essa cor no calendário.</p>
                </div>
                <label for="profAtivo" class="flex min-h-[44px] cursor-pointer items-center gap-3">
                    <input type="checkbox" id="profAtivo" wire:model="profAtivo"
                           class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                    <span class="text-sm font-medium text-stone-700">Ativo (aparece na agenda)</span>
                </label>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalProfissional" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="salvarProfissional" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="salvarProfissional">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    {{ $profissionalEditandoId ? 'Salvar alterações' : 'Salvar profissional' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════ Modal: Horários de atendimento ═════════════ --}}
    @if ($modalGrade)
    @php $diasNomes = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']; @endphp
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalGrade"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-stone-900">Horários de atendimento</h2>
                    <p class="mt-0.5 truncate text-sm text-stone-500">{{ $gradeEditandoNome }}</p>
                </div>
                <button wire:click="fecharModalGrade" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-6 sm:py-5">
                <div class="space-y-2.5">
                    @foreach ($grade as $dia => $config)
                        <div wire:key="grade-dia-{{ $dia }}"
                             class="space-y-2 rounded-xl border border-stone-200 px-3 py-3 sm:px-4 {{ $config['ativo'] ? 'bg-surface' : 'bg-stone-50' }}">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <input type="checkbox"
                                       wire:model.live="grade.{{ $dia }}.ativo"
                                       id="grade_{{ $dia }}"
                                       class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                                <label for="grade_{{ $dia }}"
                                       class="w-8 text-sm font-semibold {{ $config['ativo'] ? 'text-stone-900' : 'text-stone-400' }}">
                                    {{ $diasNomes[$dia] }}
                                </label>
                                @if ($config['ativo'])
                                    <button type="button" wire:click="copiarGradeParaTodos({{ $dia }})"
                                            title="Copiar horário e intervalo deste dia para todos os dias ativos"
                                            class="ml-auto flex min-h-[44px] items-center gap-1.5 rounded-lg px-2 text-xs font-medium text-rose-700 hover:bg-rose-50 transition-colors sm:min-h-[36px]">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>
                                        Copiar para todos
                                    </button>
                                @else
                                    <span class="text-sm text-stone-500">Não atende</span>
                                @endif
                            </div>

                            @if ($config['ativo'])
                                <div class="flex items-center gap-2 pl-8">
                                    <input type="time" wire:model="grade.{{ $dia }}.hora_inicio" step="900" aria-label="Início"
                                           class="input min-w-0 flex-1 sm:w-32 sm:flex-none @error('grade.'.$dia.'.hora_inicio') !border-red-300 @enderror">
                                    <span class="text-xs text-stone-500">até</span>
                                    <input type="time" wire:model="grade.{{ $dia }}.hora_fim" step="900" aria-label="Fim"
                                           class="input min-w-0 flex-1 sm:w-32 sm:flex-none @error('grade.'.$dia.'.hora_fim') !border-red-300 @enderror">
                                </div>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 pl-8">
                                    <label class="flex min-h-[44px] items-center gap-2 text-sm text-stone-700 sm:min-h-[36px]">
                                        <input type="checkbox" wire:model.live="grade.{{ $dia }}.tem_intervalo"
                                               class="h-4 w-4 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                                        Intervalo
                                    </label>
                                    @if ($config['tem_intervalo'])
                                        <div class="flex w-full items-center gap-2 sm:w-auto">
                                        <input type="time" wire:model="grade.{{ $dia }}.intervalo_inicio" step="900" aria-label="Início do intervalo"
                                               class="input min-w-0 flex-1 sm:w-32 sm:flex-none @error('grade.'.$dia.'.intervalo_inicio') !border-red-300 @enderror">
                                        <span class="text-xs text-stone-500">até</span>
                                        <input type="time" wire:model="grade.{{ $dia }}.intervalo_fim" step="900" aria-label="Fim do intervalo"
                                               class="input min-w-0 flex-1 sm:w-32 sm:flex-none @error('grade.'.$dia.'.intervalo_fim') !border-red-300 @enderror">
                                        </div>
                                    @else
                                        <span class="text-xs text-stone-500">sem pausa</span>
                                    @endif
                                </div>
                                @foreach (['hora_inicio', 'hora_fim', 'intervalo_inicio', 'intervalo_fim'] as $campo)
                                    @error('grade.'.$dia.'.'.$campo) <p class="field-error pl-8">{{ $message }}</p> @enderror
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalGrade" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="salvarGrade" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="salvarGrade">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar horários
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═════════════════ Modal: Procedimento (criar/editar) ═══════════════ --}}
    @if ($modalProcedimento)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalProcedimento"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-semibold text-stone-900">
                    {{ $procedimentoEditandoId ? 'Editar procedimento' : 'Novo procedimento' }}
                </h2>
                <button wire:click="fecharModalProcedimento" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="proc-nome" class="label">Nome <span class="text-rose-600">*</span></label>
                    <input id="proc-nome" type="text" wire:model="procNome" placeholder="Ex.: Limpeza de pele"
                           class="input @error('procNome') !border-red-300 @enderror">
                    @error('procNome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="proc-descricao" class="label">Descrição</label>
                    <textarea id="proc-descricao" wire:model="procDescricao" rows="2"
                              placeholder="Ex.: Inclui extração e máscara calmante"
                              class="input"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="proc-duracao" class="label">Duração (min) <span class="text-rose-600">*</span></label>
                        <input id="proc-duracao" type="number" inputmode="numeric" wire:model="procDuracao" min="15" max="480" step="15"
                               placeholder="Ex.: 60"
                               class="input tabular-nums @error('procDuracao') !border-red-300 @enderror">
                        @error('procDuracao') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="proc-valor" class="label">Valor (R$) <span class="text-rose-600">*</span></label>
                        <input id="proc-valor" type="number" inputmode="decimal" wire:model="procValor" min="0" step="0.01"
                               placeholder="Ex.: 180,00"
                               class="input tabular-nums @error('procValor') !border-red-300 @enderror">
                        @error('procValor') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label for="procAtivo" class="flex min-h-[44px] cursor-pointer items-center gap-3">
                    <input type="checkbox" id="procAtivo" wire:model="procAtivo"
                           class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                    <span class="text-sm font-medium text-stone-700">Ativo (aparece ao agendar)</span>
                </label>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalProcedimento" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="salvarProcedimento" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="salvarProcedimento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    {{ $procedimentoEditandoId ? 'Salvar alterações' : 'Salvar procedimento' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════ Modal: Novo bloqueio ═══════════════════════ --}}
    @if ($modalBloqueio)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalBloqueio"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Novo bloqueio</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Ninguém consegue agendar no período bloqueado.</p>
                </div>
                <button wire:click="fecharModalBloqueio" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="bloq-profissional" class="label">Profissional <span class="text-rose-600">*</span></label>
                    <select id="bloq-profissional" wire:model="bloqProfissionalId"
                            class="input @error('bloqProfissionalId') !border-red-300 @enderror">
                        <option value="{{ \App\Livewire\AgendamentoConfiguracaoIndex::BLOQUEIO_TODOS }}">Todos os profissionais</option>
                        @foreach ($profissionais->where('ativo', true) as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }}</option>
                        @endforeach
                    </select>
                    @error('bloqProfissionalId') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 rounded-xl bg-stone-100 p-1">
                    <button type="button" wire:click="$set('bloqDiaInteiro', true)"
                            class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ $bloqDiaInteiro ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500' }}">
                        Dia inteiro
                    </button>
                    <button type="button" wire:click="$set('bloqDiaInteiro', false)"
                            class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ ! $bloqDiaInteiro ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500' }}">
                        Algumas horas
                    </button>
                </div>

                @if ($bloqDiaInteiro)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bloq-data-inicio" class="label">De <span class="text-rose-600">*</span></label>
                            <input id="bloq-data-inicio" type="date" wire:model.live="bloqDataInicio"
                                   class="input @error('bloqDataInicio') !border-red-300 @enderror">
                            @error('bloqDataInicio') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bloq-data-fim" class="label">Até <span class="text-rose-600">*</span></label>
                            <input id="bloq-data-fim" type="date" wire:model="bloqDataFim" min="{{ $bloqDataInicio }}"
                                   class="input @error('bloqDataFim') !border-red-300 @enderror">
                            @error('bloqDataFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @else
                    <div>
                        <label for="bloq-data" class="label">Data <span class="text-rose-600">*</span></label>
                        <input id="bloq-data" type="date" wire:model="bloqDataInicio"
                               class="input @error('bloqDataInicio') !border-red-300 @enderror">
                        @error('bloqDataInicio') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="bloq-hora-inicio" class="label">Das <span class="text-rose-600">*</span></label>
                            <input id="bloq-hora-inicio" type="time" wire:model="bloqHoraInicio" step="900"
                                   class="input @error('bloqHoraInicio') !border-red-300 @enderror">
                            @error('bloqHoraInicio') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="bloq-hora-fim" class="label">Até <span class="text-rose-600">*</span></label>
                            <input id="bloq-hora-fim" type="time" wire:model="bloqHoraFim" step="900"
                                   class="input @error('bloqHoraFim') !border-red-300 @enderror">
                            @error('bloqHoraFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div>
                    <label for="bloq-motivo" class="label">Motivo</label>
                    <input id="bloq-motivo" type="text" wire:model="bloqMotivo" maxlength="500" placeholder="Ex.: Férias, feriado, congresso"
                           class="input @error('bloqMotivo') !border-red-300 @enderror">
                    @error('bloqMotivo') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalBloqueio" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="salvarBloqueio" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="salvarBloqueio">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Salvar bloqueio
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
