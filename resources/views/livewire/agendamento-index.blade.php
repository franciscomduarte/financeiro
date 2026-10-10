<div class="space-y-6"
     x-data
     x-init="if (window.innerWidth < 768 && ! new URLSearchParams(location.search).has('visao') && $wire.visao === 'semana') $wire.mudarVisao('dia')">

    {{-- ─── Avisos (toast) ─────────────────────────────────────────────── --}}
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
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Cabeçalho ───────────────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Agenda" subtitulo="Veja os atendimentos do dia e marque novos horários.">
        <x-slot:acoes>
            @podeEditar
            <button type="button" wire:click="abrirModalNovoBloqueio(null, null, '{{ $filtroProfissionalId }}')" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                Bloquear horário
            </button>
            <button wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Novo agendamento
            </button>
            @endpodeEditar
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- ─── Indicadores ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Total</p>
            <p class="mt-1 text-xl font-semibold text-stone-900 tabular-nums sm:text-2xl">{{ number_format($statsTotal) }}</p>
            <p class="mt-0.5 hidden truncate text-xs text-stone-400 first-letter:uppercase sm:block">{{ $tituloPeriodo }}</p>
        </div>
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Confirmados</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700 tabular-nums sm:text-2xl">{{ $statsConfirmado }}</p>
        </div>
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Pendentes</p>
            <p class="mt-1 text-xl font-semibold text-stone-900 tabular-nums sm:text-2xl">{{ $statsPendente }}</p>
        </div>
    </div>

    {{-- ─── Navegação do período + troca de visão ──────────────────────── --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="irParaHoje" class="btn-secondary">
                Hoje
            </button>
            <div class="flex">
                <button type="button" wire:click="navegar(-1)" title="Anterior" aria-label="Período anterior"
                        class="flex h-11 w-11 items-center justify-center rounded-l-xl border border-stone-200 bg-surface text-stone-600 hover:bg-stone-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                </button>
                <button type="button" wire:click="navegar(1)" title="Próximo" aria-label="Próximo período"
                        class="-ml-px flex h-11 w-11 items-center justify-center rounded-r-xl border border-stone-200 bg-surface text-stone-600 hover:bg-stone-50 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </button>
            </div>
            <h2 class="ml-1 min-w-0 truncate text-base font-semibold text-stone-900 first-letter:uppercase">{{ $tituloPeriodo }}</h2>
        </div>
        <div class="grid grid-cols-4 rounded-xl bg-stone-100 p-1">
            @foreach (\App\Enums\VisaoAgenda::cases() as $opcao)
                <button type="button" wire:click="mudarVisao('{{ $opcao->value }}')"
                        class="min-h-[40px] rounded-lg px-4 text-sm font-medium transition-colors
                            {{ $visaoAtual === $opcao ? 'bg-surface text-stone-900 shadow-sm' : 'text-stone-500 hover:text-stone-800' }}">
                    {{ $opcao->label() }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ─── Filtros ─────────────────────────────────────────────────────── --}}
    <div class="card p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto] lg:items-end">
            <div>
                <label for="agenda-filtro-data" class="label">Data</label>
                <input id="agenda-filtro-data" type="date" wire:model.live="filtroData" class="input">
            </div>
            <div>
                <label for="agenda-filtro-profissional" class="label">Profissional</label>
                <select id="agenda-filtro-profissional" wire:model.live="filtroProfissionalId" class="input">
                    <option value="">Todos</option>
                    @foreach ($this->profissionais as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="agenda-filtro-status" class="label">Status</label>
                <select id="agenda-filtro-status" wire:model.live="filtroStatus" class="input">
                    <option value="">{{ $visaoAtual->isCalendario() ? 'Todos (menos cancelados)' : 'Todos' }}</option>
                    @foreach ($this->statusOpcoes as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            @if ($filtroData !== now()->toDateString() || $filtroProfissionalId || $filtroStatus)
                <button wire:click="limparFiltros" class="btn-ghost whitespace-nowrap">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- ─── Nenhum profissional ativo ───────────────────────────────────── --}}
    @if ($this->profissionais->isEmpty())
        <div class="card">
            <x-ui.empty-state
                titulo="Nenhum profissional cadastrado"
                texto="Cadastre quem atende na clínica e os horários de cada um para começar a agendar."
                icone="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z">
                <a href="{{ route('agenda.configuracao') }}" class="btn-primary">Cadastrar profissional</a>
            </x-ui.empty-state>
        </div>
    @endif

    @if ($visaoAtual->isCalendario())
        {{-- ─── Calendário ──────────────────────────────────────────────────── --}}
        @if ($calLimite)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Há muitos agendamentos neste período e alguns podem não aparecer. Filtre por profissional ou use a visão Dia.
            </div>
        @endif
        @if ($visaoAtual === \App\Enums\VisaoAgenda::Dia && empty($calLayout) && $this->profissionais->isNotEmpty())
            <div class="flex items-center gap-3 rounded-xl border border-stone-200 bg-surface px-4 py-3 text-sm text-stone-600">
                <svg class="h-5 w-5 shrink-0 text-rose-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <p><span class="font-medium text-stone-900">Nenhum agendamento neste dia.</span> Toque num horário livre para agendar.</p>
            </div>
        @endif
        @if ($visaoAtual === \App\Enums\VisaoAgenda::Mes)
            @include('livewire.partials.agenda-mes')
        @else
            @include('livewire.partials.agenda-grade-horarios')
        @endif
        @include('livewire.partials.agenda-legenda')
    @else
    {{-- ─── Lista ───────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        @if ($agendamentos->isEmpty())
            @if ($filtroProfissionalId || $filtroStatus)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Tente outro profissional ou status, ou limpe os filtros para ver o dia inteiro."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button wire:click="limparFiltros" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhum agendamento neste dia"
                    texto="Os atendimentos marcados para a data escolhida aparecem aqui."
                    icone="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5">
                    @podeEditar<button wire:click="abrirModalCriar" class="btn-primary">Novo agendamento</button>@endpodeEditar
                </x-ui.empty-state>
            @endif
        @else
            @php
                $badgesStatus = [
                    \App\Enums\StatusAgendamento::Agendado->value   => 'bg-violet-50 text-violet-700',
                    \App\Enums\StatusAgendamento::Confirmado->value => 'bg-emerald-50 text-emerald-700',
                    \App\Enums\StatusAgendamento::Realizado->value  => 'bg-sky-50 text-sky-700',
                    \App\Enums\StatusAgendamento::Cancelado->value  => 'bg-red-50 text-red-700',
                    \App\Enums\StatusAgendamento::Reagendado->value => 'bg-amber-50 text-amber-700',
                    \App\Enums\StatusAgendamento::Falta->value      => 'bg-stone-100 text-stone-600',
                ];
            @endphp

            {{-- Celular: lista de cartões (toque abre o detalhe com as ações) --}}
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($agendamentos as $ag)
                    @php
                        $isNowCel = $filtroData === now()->toDateString()
                            && $ag->inicio_em->lte(now())
                            && $ag->fim_em->gte(now())
                            && $ag->status->isPendente();
                    @endphp
                    <li wire:key="ag-cel-{{ $ag->id }}">
                        <button type="button" wire:click="abrirDetalhe('{{ $ag->id }}')"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-stone-50 transition-colors {{ $isNowCel ? 'border-l-2 border-l-rose-500' : '' }}">
                            <div class="w-14 shrink-0">
                                <p class="text-sm font-semibold text-stone-900 tabular-nums">{{ $ag->inicio_em->format('H:i') }}</p>
                                <p class="text-xs text-stone-400 tabular-nums">{{ $ag->fim_em->format('H:i') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $ag->paciente?->nome ?? '—' }}</p>
                                <p class="flex items-center gap-1.5 truncate text-xs text-stone-500">
                                    <span class="h-2 w-2 shrink-0 rounded-full"
                                          style="background-color: {{ \App\Services\AgendaCalendarioService::corSegura($ag->profissional?->cor_agenda) }}"></span>
                                    <span class="truncate">{{ $ag->profissional?->nome ?? '—' }}{{ $ag->procedimento ? ' · ' . $ag->procedimento->nome : '' }}</span>
                                </p>
                            </div>
                            <span class="badge shrink-0 {{ $badgesStatus[$ag->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $ag->status->label() }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: tabela --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-stone-50 border-b border-stone-100">
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500">Horário</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500">Paciente</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500">Profissional</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500 hidden lg:table-cell">Procedimento</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-stone-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-stone-500"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($agendamentos as $ag)
                            @php
                                $isPendente = $ag->status->isPendente();
                                $isNow = $filtroData === now()->toDateString()
                                    && $ag->inicio_em->lte(now())
                                    && $ag->fim_em->gte(now())
                                    && $isPendente;
                            @endphp
                            <tr class="hover:bg-stone-50 transition-colors {{ $isNow ? 'border-l-2 border-l-rose-500' : '' }}">
                                {{-- Horário --}}
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-stone-900 tabular-nums">{{ $ag->inicio_em->format('H:i') }}</span>
                                    <span class="text-xs text-stone-400 tabular-nums"> – {{ $ag->fim_em->format('H:i') }}</span>
                                    @if ($isNow)
                                        <div class="mt-0.5">
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-rose-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                agora
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                {{-- Paciente --}}
                                <td class="px-5 py-3.5">
                                    <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                            class="text-left text-sm font-medium text-stone-900 hover:text-rose-700 transition-colors">
                                        {{ $ag->paciente?->nome ?? '—' }}
                                    </button>
                                    @if ($ag->paciente?->telefone)
                                        <div class="text-xs text-stone-500 mt-0.5">{{ $ag->paciente->telefone }}</div>
                                    @endif
                                </td>
                                {{-- Profissional --}}
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-2 text-sm text-stone-700">
                                        <span class="h-2.5 w-2.5 rounded-full shrink-0"
                                              style="background-color: {{ \App\Services\AgendaCalendarioService::corSegura($ag->profissional?->cor_agenda) }}"></span>
                                        {{ $ag->profissional?->nome ?? '—' }}
                                    </span>
                                </td>
                                {{-- Procedimento --}}
                                <td class="px-5 py-3.5 hidden lg:table-cell">
                                    <span class="text-sm text-stone-700">{{ $ag->procedimento?->nome ?? '—' }}</span>
                                    @if ($ag->procedimento)
                                        <div class="text-xs text-stone-500">{{ $ag->procedimento->duracao_minutos }} min</div>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="px-5 py-3.5">
                                    <span class="badge {{ $badgesStatus[$ag->status->value] ?? 'bg-stone-100 text-stone-600' }}">
                                        {{ $ag->status->label() }}
                                    </span>
                                </td>
                                {{-- Ações --}}
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-0.5">
                                        @if ($ag->status === \App\Enums\StatusAgendamento::Agendado)
                                            <button wire:click="confirmarAgendamento('{{ $ag->id }}')"
                                                    title="Confirmar presença" aria-label="Confirmar presença"
                                                    class="rounded-lg p-2 text-emerald-600 hover:bg-emerald-50 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        @endif
                                        @if ($isPendente)
                                            <button wire:click="abrirModalConcluir('{{ $ag->id }}')"
                                                    title="Marcar como realizado" aria-label="Marcar como realizado"
                                                    class="rounded-lg p-2 text-sky-600 hover:bg-sky-50 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                            <button wire:click="marcarFalta('{{ $ag->id }}')"
                                                    title="Registrar falta" aria-label="Registrar falta"
                                                    class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                            <button wire:click="abrirModalReagendar('{{ $ag->id }}')"
                                                    title="Reagendar" aria-label="Reagendar"
                                                    class="rounded-lg p-2 text-amber-600 hover:bg-amber-50 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </button>
                                            <button wire:click="abrirModalCancelar('{{ $ag->id }}')"
                                                    title="Cancelar agendamento" aria-label="Cancelar agendamento"
                                                    class="rounded-lg p-2 text-red-600 hover:bg-red-50 transition-colors">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        @endif
                                        <button wire:click="abrirDetalhe('{{ $ag->id }}')"
                                                title="Ver detalhes" aria-label="Ver detalhes"
                                                class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($agendamentos->hasPages())
                <div class="border-t border-stone-100 px-5 py-3">
                    {{ $agendamentos->links() }}
                </div>
            @endif
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Novo agendamento                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalCriar"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Novo agendamento</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Escolha paciente, profissional e horário.</p>
                    @if ($criarSlotSugerido)
                        <button type="button" wire:click="bloquearNoHorario" class="mt-1 inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-stone-600 underline underline-offset-2 hover:text-stone-900">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Bloquear este horário em vez de agendar
                        </button>
                    @endif
                </div>
                <button wire:click="fecharModalCriar" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">

                {{-- JSON dos pacientes embutido com segurança fora do atributo HTML --}}
                <script type="application/json" id="pac-combobox-data">
                    @json($this->pacientes->map(fn($p) => ['id' => (string) $p->id, 'nome' => $p->nome])->values())
                </script>

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
                     x-init="patients = JSON.parse(document.getElementById('pac-combobox-data').textContent)"
                     x-on:click.outside="open = false">
                    <label class="label">Paciente <span class="text-rose-600">*</span></label>
                    <div class="relative">
                        {{-- Trigger: div evita button aninhado (HTML inválido) --}}
                        <div role="button" tabindex="0"
                             @click="open = !open"
                             @keydown.enter.prevent="open = !open"
                             @keydown.space.prevent="open = !open"
                             class="flex min-h-[44px] w-full cursor-pointer items-center justify-between rounded-xl border border-stone-200 bg-surface px-3.5 py-2.5 text-sm transition
                                 {{ $criarPacienteId ? 'text-stone-900' : 'text-stone-400' }}
                                 @error('criarPacienteId') !border-red-300 @enderror
                                 focus:outline-none focus:border-rose-300 focus:ring-4 focus:ring-rose-100">
                            <span class="{{ $criarPacienteId ? 'font-medium' : '' }}">
                                {{ $criarPacienteNome ?: 'Selecione o paciente' }}
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                @if ($criarPacienteId)
                                    <button type="button"
                                            @click.stop="$wire.limparPaciente()"
                                            aria-label="Remover paciente"
                                            class="rounded-full p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-700 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                @endif
                                <svg class="h-4 w-4 text-stone-400 transition-transform duration-150" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        {{-- Dropdown --}}
                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute z-20 mt-1 w-full rounded-xl border border-stone-200 bg-surface shadow-xl overflow-hidden">
                            <div class="p-2 border-b border-stone-100">
                                <input x-model="search"
                                       x-ref="searchInput"
                                       x-init="$watch('open', v => v && $nextTick(() => $refs.searchInput.focus()))"
                                       type="text"
                                       placeholder="Buscar pelo nome"
                                       class="input">
                            </div>
                            <ul class="max-h-52 overflow-auto py-1">
                                <template x-for="p in filtered" :key="p.id">
                                    <li>
                                        <button type="button"
                                                @click="$wire.selecionarPaciente(p.id, p.nome); open = false; search = ''"
                                                :class="$wire.criarPacienteId === p.id ? 'bg-rose-50 text-rose-700 font-medium' : 'text-stone-700 hover:bg-stone-50'"
                                                class="min-h-[44px] w-full px-4 py-2.5 text-left text-sm transition-colors">
                                            <span x-text="p.nome"></span>
                                        </button>
                                    </li>
                                </template>
                                <li x-show="filtered.length === 0"
                                    class="px-4 py-3 text-sm text-stone-500 text-center">
                                    Nenhum paciente com esse nome.
                                </li>
                            </ul>
                        </div>
                    </div>
                    @error('criarPacienteId') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- Profissional --}}
                    <div>
                        <label for="criar-profissional" class="label">Profissional <span class="text-rose-600">*</span></label>
                        <select id="criar-profissional" wire:model.live="criarProfissionalId"
                                class="input @error('criarProfissionalId') !border-red-300 @enderror">
                            <option value="">Selecione</option>
                            @foreach ($this->profissionais as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                        @error('criarProfissionalId') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    {{-- Data --}}
                    <div>
                        <label for="criar-data" class="label">Data <span class="text-rose-600">*</span></label>
                        <input id="criar-data" type="date" wire:model.live="criarData" min="{{ now()->toDateString() }}"
                               class="input @error('criarData') !border-red-300 @enderror">
                        @error('criarData') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ── Procedimentos: pill multi-select com busca ── --}}
                <div x-data="{ busca: '' }">
                    <div class="mb-1.5 flex items-center justify-between gap-3">
                        <label class="label !mb-0">
                            Procedimentos <span class="text-rose-600">*</span>
                        </label>
                        @if (count($this->criarProcedimentoIds) > 0)
                            <span class="badge bg-rose-50 text-rose-700 tabular-nums">
                                {{ count($this->criarProcedimentoIds) }} selecionado{{ count($this->criarProcedimentoIds) > 1 ? 's' : '' }} · {{ $this->duracaoTotal }} min
                            </span>
                        @endif
                    </div>

                    {{-- Busca inline --}}
                    <div class="relative mb-2">
                        <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <input x-model.debounce.150ms="busca"
                               type="search"
                               placeholder="Filtrar procedimentos"
                               autocomplete="off"
                               class="input !pl-10">
                    </div>

                    {{-- Grade de pills --}}
                    <div class="rounded-xl border p-2 min-h-[52px]
                        @error('criarProcedimentoIds') border-red-300 bg-red-50 @enderror
                        @if (!$errors->has('criarProcedimentoIds')) border-stone-200 bg-stone-50 @endif">
                        @forelse ($this->procedimentos as $proc)
                            @php $sel = in_array($proc->id, $this->criarProcedimentoIds, false); @endphp
                            <label
                                data-nome="{{ strtolower($proc->nome) }}"
                                x-show="!busca || $el.dataset.nome.includes(busca.toLowerCase())"
                                class="m-0.5 inline-flex min-h-[44px] cursor-pointer select-none items-center gap-1.5 rounded-lg px-3 text-sm font-medium transition-colors sm:min-h-[36px]
                                    {{ $sel
                                        ? 'bg-rose-600 text-white ring-1 ring-rose-600'
                                        : 'bg-surface text-stone-700 ring-1 ring-inset ring-stone-200 hover:ring-rose-300 hover:text-rose-700' }}">
                                <input type="checkbox"
                                       wire:model.live="criarProcedimentoIds"
                                       value="{{ $proc->id }}"
                                       class="sr-only">
                                @if ($sel)
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                @endif
                                <span>{{ $proc->nome }}</span>
                                <span class="tabular-nums text-xs {{ $sel ? 'text-white/80' : 'text-stone-400' }}">{{ $proc->duracao_minutos }} min</span>
                            </label>
                        @empty
                            <p class="px-2 py-1 text-sm text-stone-500">
                                Nenhum procedimento ativo.
                                <a href="{{ route('agenda.configuracao') }}" class="font-medium text-rose-700 underline underline-offset-2">Cadastre em Profissionais e horários</a>.
                            </p>
                        @endforelse
                    </div>
                    @error('criarProcedimentoIds') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- ── Horários disponíveis ── --}}
                <div>
                    <label class="label">Horário <span class="text-rose-600">*</span></label>
                    @if ($criarProfissionalId && count($criarProcedimentoIds) > 0 && $criarData)
                        @if (count($this->horariosDisponiveis) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->horariosDisponiveis as $slot)
                                    <button type="button"
                                            wire:click="$set('criarSlot', '{{ $slot }}')"
                                            class="min-h-[44px] rounded-xl border px-3.5 text-sm font-medium tabular-nums transition-colors sm:min-h-[36px]
                                                {{ $criarSlot === $slot
                                                    ? 'border-rose-600 bg-rose-600 text-white'
                                                    : 'border-stone-200 bg-surface text-stone-700 hover:border-rose-300 hover:text-rose-700' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                            @if (empty($criarSlot))
                                @if ($criarSlotSugerido && ! in_array($criarSlotSugerido, $this->horariosDisponiveis, true))
                                    <p class="hint !text-amber-700">O horário das {{ $criarSlotSugerido }}, escolhido no calendário, não está livre para esse profissional e procedimento.
                                        <button type="button" wire:click="$set('criarSlot', '{{ $criarSlotSugerido }}')" class="font-medium underline underline-offset-2">Encaixar às {{ $criarSlotSugerido }}</button> ou escolha outro.</p>
                                @else
                                    <p class="hint">Escolha um horário para continuar.</p>
                                @endif
                            @endif
                        @else
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 space-y-1">
                                <p class="font-medium">Nenhum horário livre nesta data.</p>
                                <p class="text-xs">O profissional não atende neste dia da semana ou a agenda já está cheia. Tente outra data, digite o horário abaixo para encaixar ou <a href="{{ route('agenda.configuracao') }}" class="font-medium underline underline-offset-2">ajuste os horários</a>.</p>
                            </div>
                        @endif

                        {{-- Encaixe: qualquer horário, com aviso se não estiver livre --}}
                        @php $criarForaDaLista = $criarSlot !== '' && ! in_array($criarSlot, $this->horariosDisponiveis, true); @endphp
                        <div class="mt-4">
                            <label for="criar-outro" class="label">Outro horário</label>
                            <input id="criar-outro" type="time" step="300" class="input w-36"
                                   value="{{ $criarForaDaLista ? $criarSlot : '' }}"
                                   x-on:change="$wire.set('criarSlot', $event.target.value)">
                            @if ($criarForaDaLista && $this->conflitoCriar)
                                <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800" role="alert">
                                    <span class="font-medium">Atenção:</span> {{ $this->conflitoCriar }} Você pode encaixar mesmo assim.
                                </p>
                            @elseif ($criarForaDaLista)
                                <p class="hint">Horário livre.</p>
                            @else
                                <p class="hint">Para encaixar num horário que não está na lista.</p>
                            @endif
                        </div>
                    @elseif ($criarSlotSugerido)
                        <p class="text-sm text-stone-600">Horário escolhido: <span class="font-semibold tabular-nums text-stone-900">{{ $criarSlotSugerido }}</span>. Agora escolha profissional e procedimento.</p>
                    @else
                        <p class="text-sm text-stone-500">Escolha profissional, procedimento e data para ver os horários livres.</p>
                    @endif
                    @error('criarSlot') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Observações --}}
                <div>
                    <label for="criar-observacoes" class="label">Observações</label>
                    <textarea id="criar-observacoes" wire:model="criarObservacoes" rows="2"
                              placeholder="Ex.: Prefere ser atendida no fim da tarde"
                              class="input"></textarea>
                </div>
            </div>
            <div class="border-t border-stone-100 px-5 py-4 sm:px-6">
                @if ($errors->any())
                    <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button wire:click="fecharModalCriar" type="button" class="btn-secondary">
                        Cancelar
                    </button>
                    <button wire:click="salvarAgendamento"
                            wire:loading.attr="disabled" wire:target="salvarAgendamento"
                            type="button"
                            {{ empty($criarSlot) ? 'disabled' : '' }}
                            class="btn-primary">
                        <svg wire:loading wire:target="salvarAgendamento"
                             class="h-4 w-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        {{ $criarSlot !== '' && $this->conflitoCriar ? 'Encaixar mesmo assim' : 'Confirmar agendamento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Cancelar                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalCancelar)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalCancelar"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Cancelar agendamento</h2>
                    <p class="mt-0.5 text-sm text-stone-500">O horário fica livre de novo na agenda.</p>
                </div>
                <button wire:click="fecharModalCancelar" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <label for="cancelar-motivo" class="label">Motivo do cancelamento <span class="text-rose-600">*</span></label>
                <textarea id="cancelar-motivo" wire:model="cancelarMotivo" rows="3"
                          placeholder="Ex.: Paciente pediu para remarcar"
                          class="input @error('cancelarMotivo') !border-red-300 @enderror"></textarea>
                @error('cancelarMotivo') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalCancelar" class="btn-secondary">
                    Voltar
                </button>
                <button wire:click="confirmarCancelamento" wire:loading.attr="disabled" class="btn-danger">
                    <span wire:loading wire:target="confirmarCancelamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Cancelar agendamento
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Reagendar                                                   --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalReagendar)
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalReagendar"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-stone-900">Reagendar</h2>
                    <p class="mt-0.5 text-sm text-stone-500">Escolha a nova data e o horário.</p>
                </div>
                <button wire:click="fecharModalReagendar" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label for="reagendar-data" class="label">Nova data <span class="text-rose-600">*</span></label>
                    <input id="reagendar-data" type="date" wire:model.live="reagendarData" min="{{ now()->toDateString() }}"
                           class="input @error('reagendarData') !border-red-300 @enderror">
                    @error('reagendarData') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Novo horário <span class="text-rose-600">*</span></label>
                    @if ($reagendarData && $reagendarId)
                        @if (count($this->horariosReagendar) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->horariosReagendar as $slot)
                                    <button type="button"
                                            wire:click="$set('reagendarSlot', '{{ $slot }}')"
                                            class="min-h-[44px] rounded-xl border px-3.5 text-sm font-medium tabular-nums transition-colors sm:min-h-[36px]
                                                {{ $reagendarSlot === $slot
                                                    ? 'border-rose-600 bg-rose-600 text-white'
                                                    : 'border-stone-200 bg-surface text-stone-700 hover:border-rose-300 hover:text-rose-700' }}">
                                        {{ $slot }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Nenhum horário livre nesta data. Escolha outro dia ou digite o horário abaixo para encaixar.
                            </div>
                        @endif

                        {{-- Encaixe: qualquer horário, com aviso se não estiver livre --}}
                        @php $foraDaLista = $reagendarSlot !== '' && ! in_array($reagendarSlot, $this->horariosReagendar, true); @endphp
                        <div class="mt-4">
                            <label for="reagendar-outro" class="label">Outro horário</label>
                            <input id="reagendar-outro" type="time" step="300" class="input w-36"
                                   value="{{ $foraDaLista ? $reagendarSlot : '' }}"
                                   x-on:change="$wire.set('reagendarSlot', $event.target.value)">
                            @if ($foraDaLista && $this->conflitoReagendar)
                                <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800" role="alert">
                                    <span class="font-medium">Atenção:</span> {{ $this->conflitoReagendar }} Você pode encaixar mesmo assim.
                                </p>
                            @elseif ($foraDaLista)
                                <p class="hint">Horário livre.</p>
                            @else
                                <p class="hint">Para encaixar num horário que não está na lista.</p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-stone-500">Escolha uma data para ver os horários livres.</p>
                    @endif
                    @error('reagendarSlot') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalReagendar" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="confirmarReagendamento" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="confirmarReagendamento">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    {{ $reagendarSlot !== '' && $this->conflitoReagendar ? 'Encaixar mesmo assim' : 'Confirmar reagendamento' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- Modal: Concluir atendimento (Realizado + receita)                  --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if ($modalConcluir && $agendamentoConcluir)
    @php
        $c           = $agendamentoConcluir;
        $mensalidade = (float) ($c->paciente?->valor_mensalidade ?? 0);
        $campo       = 'input';
    @endphp
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalConcluir"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-stone-900">Concluir atendimento</h2>
                    <p class="mt-0.5 truncate text-sm text-stone-500">
                        {{ $c->paciente?->nome ?? '—' }} · {{ $c->inicio_em->format('d/m H:i') }} · {{ $c->procedimento?->nome ?? '—' }}
                    </p>
                </div>
                <button wire:click="fecharModalConcluir" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                @if ($mensalidade > 0)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <p class="font-medium">Paciente com mensalidade de R$ {{ number_format($mensalidade, 2, ',', '.') }}.</p>
                        <p class="mt-0.5 text-xs">Veja se este atendimento já está incluído antes de lançar a receita.</p>
                    </div>
                @endif

                @if ($pacotesConcluir->isNotEmpty())
                    <div>
                        <label for="concluir-pacote" class="label">Usar sessão de pacote</label>
                        <select id="concluir-pacote" wire:model.live="concluirPacoteId" class="input">
                            <option value="">Não usar pacote</option>
                            @foreach ($pacotesConcluir as $pc)
                                <option value="{{ $pc->id }}">{{ $pc->nome }} · saldo {{ $pc->saldo() }} de {{ $pc->sessoes_total }}</option>
                            @endforeach
                        </select>
                        @if ($concluirPacoteId !== '')
                            <p class="hint">A receita entrou na venda do pacote. Aqui só descontamos uma sessão.</p>
                        @endif
                    </div>
                @endif

                @if ($concluirPacoteId === '')
                <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-stone-200 px-4 py-2.5">
                    <input type="checkbox" wire:model.live="concluirLancarReceita"
                           class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                    <span class="text-sm font-medium text-stone-800">Lançar receita no financeiro</span>
                </label>

                @if ($concluirLancarReceita)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="concluir-valor" class="label">Valor (R$) <span class="text-rose-600">*</span></label>
                            <input id="concluir-valor" type="number" inputmode="decimal" step="0.01" min="0" wire:model="concluirValor"
                                   placeholder="Ex.: 250,00"
                                   class="{{ $campo }} tabular-nums @error('concluirValor') !border-red-300 @enderror">
                            @error('concluirValor') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="concluir-forma" class="label">Pagamento <span class="text-rose-600">*</span></label>
                            <select id="concluir-forma" wire:model="concluirFormaPagamento" class="{{ $campo }} @error('concluirFormaPagamento') !border-red-300 @enderror">
                                @foreach (\App\Enums\FormaPagamento::cases() as $fp)
                                    @continue($fp === \App\Enums\FormaPagamento::AportePessoal)
                                    <option value="{{ $fp->value }}">{{ $fp->label() }}</option>
                                @endforeach
                            </select>
                            @error('concluirFormaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="concluir-categoria" class="label">Categoria <span class="text-rose-600">*</span></label>
                        <select id="concluir-categoria" wire:model="concluirCategoria" class="{{ $campo }} @error('concluirCategoria') !border-red-300 @enderror">
                            <option value="">Selecione</option>
                            @foreach (\App\Models\PlanoConta::nomes('entrada') as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('concluirCategoria') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 rounded-xl bg-stone-100 p-1">
                        <button type="button" wire:click="$set('concluirPago', true)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ $concluirPago ? 'bg-surface text-emerald-700 shadow-sm' : 'text-stone-500' }}">
                            Pago agora
                        </button>
                        <button type="button" wire:click="$set('concluirPago', false)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ ! $concluirPago ? 'bg-surface text-amber-700 shadow-sm' : 'text-stone-500' }}">
                            A receber
                        </button>
                    </div>
                    <p class="hint">A taxa do cartão e o imposto são calculados sozinhos, como nos outros lançamentos.</p>
                @else
                    <p class="text-sm text-stone-600">O agendamento fica como realizado, sem lançar receita.</p>
                @endif
                @endif
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button wire:click="fecharModalConcluir" class="btn-secondary">
                    Voltar
                </button>
                <button wire:click="confirmarConclusao" wire:loading.attr="disabled" class="btn-primary">
                    <span wire:loading wire:target="confirmarConclusao">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                    </span>
                    Concluir atendimento
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
    <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
        <div class="absolute inset-0 bg-black/40" wire:click="fecharDetalhe"></div>
        <div class="relative flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
            @php
                $badgeDetalhe = match($d->status) {
                    \App\Enums\StatusAgendamento::Agendado   => 'bg-violet-50 text-violet-700',
                    \App\Enums\StatusAgendamento::Confirmado => 'bg-emerald-50 text-emerald-700',
                    \App\Enums\StatusAgendamento::Realizado  => 'bg-sky-50 text-sky-700',
                    \App\Enums\StatusAgendamento::Cancelado  => 'bg-red-50 text-red-700',
                    \App\Enums\StatusAgendamento::Reagendado => 'bg-amber-50 text-amber-700',
                    \App\Enums\StatusAgendamento::Falta      => 'bg-stone-100 text-stone-600',
                };
            @endphp
            <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6"
                 style="border-top: 4px solid {{ \App\Services\AgendaCalendarioService::corSegura($d->profissional?->cor_agenda) }}">
                <div class="min-w-0">
                    <span class="badge {{ $badgeDetalhe }}">{{ $d->status->label() }}</span>
                    <h2 class="mt-2 truncate text-lg font-semibold text-stone-900">{{ $d->paciente?->nome ?? '—' }}</h2>
                    <p class="mt-0.5 text-sm text-stone-500 tabular-nums">
                        {{ $d->inicio_em->format('d/m/Y') }} · {{ $d->inicio_em->format('H:i') }} – {{ $d->fim_em->format('H:i') }}
                    </p>
                    @if ($d->paciente_id && auth()->user()->pode(\App\Enums\Modulo::DadosClinicos))
                        <a href="{{ route('pacientes.prontuario', $d->paciente_id) }}" wire:navigate
                           class="mt-1 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-rose-600 hover:text-rose-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            Abrir prontuário
                        </a>
                    @endif
                </div>
                <button wire:click="fecharDetalhe" aria-label="Fechar"
                        class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 px-5 py-5 sm:px-6">
                    <div>
                        <dt class="text-xs text-stone-500">Profissional</dt>
                        <dd class="mt-1 flex items-center gap-1.5 text-sm font-medium text-stone-800">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background-color: {{ \App\Services\AgendaCalendarioService::corSegura($d->profissional?->cor_agenda) }}"></span>
                            {{ $d->profissional?->nome ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-stone-500">Telefone</dt>
                        <dd class="mt-1 text-sm text-stone-800">{{ $d->paciente?->telefone ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-stone-500">Procedimento</dt>
                        <dd class="mt-1 text-sm font-medium text-stone-800">{{ $d->procedimento?->nome ?? '—' }}</dd>
                        @if ($d->procedimento)
                            <dd class="text-xs text-stone-500 tabular-nums">{{ $d->procedimento->duracao_minutos }} min · R$ {{ number_format($d->procedimento->valor, 2, ',', '.') }}</dd>
                        @endif
                    </div>
                    @if ($d->observacoes)
                        <div class="col-span-2">
                            <dt class="text-xs text-stone-500">Observações</dt>
                            <dd class="mt-1 text-sm text-stone-700">{{ $d->observacoes }}</dd>
                        </div>
                    @endif
                    @if ($d->motivo_cancelamento)
                        <div class="col-span-2">
                            <dt class="text-xs text-red-700">Motivo do cancelamento</dt>
                            <dd class="mt-1 text-sm text-red-700">{{ $d->motivo_cancelamento }}</dd>
                        </div>
                    @endif
                    @if ($d->agendamentoOrigem)
                        <div class="col-span-2">
                            <dt class="text-xs text-stone-500">Reagendado de</dt>
                            <dd class="mt-1 text-sm text-stone-700 tabular-nums">{{ $d->agendamentoOrigem->inicio_em->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($d->receita)
                        <div class="col-span-2">
                            <dt class="text-xs text-stone-500">Financeiro</dt>
                            <dd class="mt-1 text-sm text-stone-700">
                                Receita de <span class="font-semibold tabular-nums">R$ {{ number_format((float) $d->receita->valor_bruto, 2, ',', '.') }}</span>
                                · {{ $d->receita->status === \App\Enums\StatusTransacao::Pago ? 'paga' : 'a receber' }}
                            </dd>
                        </div>
                    @endif
                    @if ($d->google_event_id)
                        <div class="col-span-2">
                            <dd class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Sincronizado com o Google Agenda
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
            <div class="border-t border-stone-100 px-5 py-4 sm:px-6">
                @if ($d->status->isPendente())
                    {{-- Ações rápidas (mesmas da lista) --}}
                    <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @if ($d->paciente_id && auth()->user()->pode(\App\Enums\Modulo::DadosClinicos))
                            <button wire:click="iniciarAtendimento('{{ $d->id }}')" wire:loading.attr="disabled"
                                    class="btn-primary col-span-2 sm:col-span-4">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/></svg>
                                Iniciar atendimento
                            </button>
                        @endif
                        @if ($d->status === \App\Enums\StatusAgendamento::Agendado)
                            <button wire:click="confirmarAgendamento('{{ $d->id }}')" wire:loading.attr="disabled"
                                    class="btn col-span-2 bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 sm:col-span-4">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Confirmar presença
                            </button>
                        @endif
                        <button wire:click="abrirModalConcluir('{{ $d->id }}')" wire:loading.attr="disabled"
                                class="btn-secondary px-3">
                            <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Realizado
                        </button>
                        <button wire:click="marcarFalta('{{ $d->id }}')" wire:loading.attr="disabled"
                                wire:confirm="Registrar falta de {{ $d->paciente?->nome ?? 'paciente' }}?"
                                class="btn-secondary px-3">
                            <svg class="h-4 w-4 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            Falta
                        </button>
                        <button wire:click="abrirModalReagendar('{{ $d->id }}')"
                                class="btn-secondary px-3">
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Reagendar
                        </button>
                        <button wire:click="abrirModalCancelar('{{ $d->id }}')"
                                class="btn-secondary px-3 !text-red-700 hover:!bg-red-50 hover:!border-red-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancelar
                        </button>
                    </div>
                @endif
                <div class="flex justify-end">
                    <button wire:click="fecharDetalhe" class="btn-ghost">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('livewire.partials.modal-bloqueio')
</div>
