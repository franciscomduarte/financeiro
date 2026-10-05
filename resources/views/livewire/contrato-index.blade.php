<div>
    {{-- ── Avisos (toast) ───────────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             role="status"
             class="fixed top-4 left-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg sm:left-auto sm:max-w-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif

    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             role="alert"
             class="fixed top-4 left-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg sm:left-auto sm:max-w-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- ── Cabeçalho ────────────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Contratos" subtitulo="Acompanhe valores, vencimentos e reajustes dos contratos com fornecedores.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo contrato
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- ── Indicadores ──────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Contratos ativos</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $ativos }}</p>
            <p class="mt-1 text-xs text-stone-500">em vigência hoje</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Custo mensal</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">
                R$ {{ number_format($valorTotal, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-stone-500">soma dos contratos ativos</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Alertas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $alertasCount > 0 ? 'text-amber-700' : 'text-stone-900' }}">
                {{ $alertasCount }}
            </p>
            <p class="mt-1 text-xs text-stone-500">vencimentos e reajustes próximos</p>
        </div>
    </div>

    @php
        $temFiltro = $filtroStatus !== '' || $filtroRisco !== '' || $alertas;

        $statusInfo = [
            'ativo'         => ['Ativo', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
            'em_negociacao' => ['Em negociação', 'bg-blue-50 text-blue-700', 'bg-blue-500'],
            'suspenso'      => ['Suspenso', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
            'encerrado'     => ['Encerrado', 'bg-stone-100 text-stone-600', 'bg-stone-400'],
        ];
        $riscoInfo = [
            'baixo' => ['Baixo', 'bg-stone-100 text-stone-600'],
            'medio' => ['Médio', 'bg-amber-50 text-amber-700'],
            'alto'  => ['Alto', 'bg-red-50 text-red-700'],
        ];
        $meses = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
    @endphp

    {{-- ── Filtros ──────────────────────────────────────────────────── --}}
    <div class="card mb-4 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:w-48">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="em_negociacao">Em negociação</option>
                <option value="encerrado">Encerrado</option>
                <option value="suspenso">Suspenso</option>
            </select>
            <select wire:model.live="filtroRisco" aria-label="Filtrar por risco" class="input sm:w-44">
                <option value="">Todos os riscos</option>
                <option value="baixo">Risco baixo</option>
                <option value="medio">Risco médio</option>
                <option value="alto">Risco alto</option>
            </select>
            <label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded-xl border px-3.5 text-sm font-medium transition {{ $alertas ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-stone-200 text-stone-700 hover:bg-stone-50' }}">
                <input wire:model.live="alertas" type="checkbox" class="h-4 w-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                <svg class="h-5 w-5 {{ $alertas ? 'text-amber-600' : 'text-stone-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                Só com alertas
            </label>
            @if ($temFiltro)
                <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroRisco', ''); $set('alertas', false)"
                        class="btn-ghost">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- ── Lista ────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        @if ($contratos->isEmpty())
            @if ($temFiltro)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Nenhum contrato bate com o que você escolheu. Limpe os filtros para ver todos."
                    icone="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z">
                    <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroRisco', ''); $set('alertas', false)" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhum contrato ainda"
                    texto="Cadastre os contratos com fornecedores para receber alertas de vencimento e reajuste."
                    icone="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z">
                    <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar contrato</button>
                </x-ui.empty-state>
            @endif
        @else
            {{-- Celular: cartões --}}
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($contratos as $contrato)
                    @php
                        $venceEm60    = $contrato->data_fim && $contrato->data_fim->lte(now()->addDays(60));
                        $reajusteEm30 = $contrato->data_proximo_reajuste && $contrato->data_proximo_reajuste->lte(now()->addDays(30));
                        $diasVenc     = $contrato->diasParaVencimento();
                        $pagamentoEm5 = $diasVenc !== null && $diasVenc <= 5;
                        $temAlerta    = $venceEm60 || $reajusteEm30 || $pagamentoEm5;
                        [$statusLabel, $statusCor, $statusDot] = $statusInfo[$contrato->status->value] ?? $statusInfo['encerrado'];
                    @endphp
                    <li wire:key="contrato-m-{{ $contrato->id }}" class="p-4 {{ $temAlerta ? 'bg-amber-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" wire:click="abrirDetalhe('{{ $contrato->id }}')" class="min-w-0 flex-1 text-left">
                                <p class="flex items-center gap-1.5 font-medium text-stone-900">
                                    @if ($temAlerta)
                                        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-label="Com alerta"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                    @endif
                                    <span class="truncate">{{ $contrato->fornecedor?->nome_fantasia ?? 'Sem fornecedor' }}</span>
                                </p>
                                <p class="mt-0.5 text-sm font-semibold text-stone-800 tabular-nums">
                                    R$ {{ number_format((float) $contrato->getRawOriginal('valor_mensal'), 2, ',', '.') }}<span class="font-normal text-stone-500">/mês</span>
                                </p>
                            </button>
                            <span class="badge shrink-0 {{ $statusCor }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <div class="mt-2 space-y-0.5 text-xs text-stone-500">
                            @if ($contrato->data_fim)
                                <p class="{{ $venceEm60 ? 'font-medium text-amber-700' : '' }}">
                                    Termina em {{ $contrato->data_fim->format('d/m/Y') }}@if ($venceEm60) · {{ $contrato->data_fim->diffForHumans() }}@endif
                                </p>
                            @endif
                            @if ($contrato->dia_vencimento)
                                @php $proximo = $contrato->proximoVencimento(); @endphp
                                <p class="{{ $pagamentoEm5 ? 'font-medium text-rose-700' : '' }}">
                                    @if ($pagamentoEm5)
                                        Pagamento {{ $diasVenc === 0 ? 'hoje' : 'em ' . $diasVenc . ($diasVenc === 1 ? ' dia' : ' dias') }} ({{ $proximo?->format('d/m') }})
                                    @else
                                        Pagamento todo dia {{ $contrato->dia_vencimento }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center gap-2">
                            <button type="button" wire:click="abrirDetalhe('{{ $contrato->id }}')" class="btn-secondary flex-1">Ver detalhes</button>
                            @if ($contrato->status->value === 'ativo' && $contrato->dia_vencimento !== null)
                                <button type="button" wire:click="abrirModalPagarContrato('{{ $contrato->id }}')" class="btn-secondary flex-1">Pagar</button>
                            @endif
                            <button type="button" wire:click="abrirModalEditar('{{ $contrato->id }}')" class="btn-ghost px-3" aria-label="Editar contrato">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </button>
                            <button type="button" wire:click="abrirModalExcluir('{{ $contrato->id }}')" class="btn-ghost px-3 hover:bg-red-50 hover:text-red-700" aria-label="Excluir contrato">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: tabela --}}
            <table class="hidden w-full text-sm md:table">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50 text-left text-xs font-medium text-stone-500">
                        <th class="px-4 py-3 font-medium">Fornecedor</th>
                        <th class="px-4 py-3 text-right font-medium">Valor mensal</th>
                        <th class="px-4 py-3 font-medium">Vencimento</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="hidden px-4 py-3 font-medium lg:table-cell">Risco</th>
                        <th class="px-4 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($contratos as $contrato)
                        @php
                            $venceEm60      = $contrato->data_fim && $contrato->data_fim->lte(now()->addDays(60));
                            $reajusteEm30   = $contrato->data_proximo_reajuste && $contrato->data_proximo_reajuste->lte(now()->addDays(30));
                            $diasVenc       = $contrato->diasParaVencimento();
                            $pagamentoEm5   = $diasVenc !== null && $diasVenc <= 5;
                            $temAlerta      = $venceEm60 || $reajusteEm30 || $pagamentoEm5;
                            [$statusLabel, $statusCor, $statusDot] = $statusInfo[$contrato->status->value] ?? $statusInfo['encerrado'];
                            [$riscoLabel, $riscoCor] = $riscoInfo[$contrato->risco->value] ?? $riscoInfo['baixo'];
                        @endphp
                        <tr wire:key="contrato-d-{{ $contrato->id }}" class="group transition-colors hover:bg-stone-50 {{ $temAlerta ? 'bg-amber-50/40' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($temAlerta)
                                        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-label="Com alerta"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-stone-900">{{ $contrato->fornecedor?->nome_fantasia ?? 'Sem fornecedor' }}</div>
                                        @if ($contrato->fornecedor?->categoria)
                                            <div class="text-xs text-stone-500">{{ $contrato->fornecedor->categoria }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="font-semibold tabular-nums text-stone-900">
                                    R$ {{ number_format((float) $contrato->getRawOriginal('valor_mensal'), 2, ',', '.') }}
                                </div>
                                @if ($contrato->data_inicio)
                                    <div class="text-xs text-stone-500">desde {{ $contrato->data_inicio->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($contrato->data_fim)
                                    <div class="tabular-nums {{ $venceEm60 ? 'font-semibold text-amber-700' : 'text-stone-700' }}">
                                        {{ $contrato->data_fim->format('d/m/Y') }}
                                    </div>
                                    @if ($venceEm60)
                                        <div class="text-xs text-amber-700">{{ $contrato->data_fim->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-stone-400">Sem data de término</span>
                                @endif
                                @if ($contrato->dia_vencimento)
                                    @php $proximo = $contrato->proximoVencimento(); @endphp
                                    <div class="mt-1 flex items-center gap-1 text-xs {{ $pagamentoEm5 ? 'font-semibold text-rose-700' : 'text-stone-500' }}">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @if ($pagamentoEm5)
                                            Pagamento {{ $diasVenc === 0 ? 'hoje' : 'em ' . $diasVenc . ($diasVenc === 1 ? ' dia' : ' dias') }} ({{ $proximo?->format('d/m') }})
                                        @else
                                            Pagamento todo dia {{ $contrato->dia_vencimento }}
                                        @endif
                                    </div>
                                    @if ($contrato->pagamentos_max_competencia)
                                        @php
                                            [$pAno, $pMes] = explode('-', $contrato->pagamentos_max_competencia);
                                            $pLabel = $meses[(int) $pMes - 1] . '/' . $pAno;
                                        @endphp
                                        <div class="mt-0.5 flex items-center gap-1 text-xs text-emerald-700">
                                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            Pago até {{ $pLabel }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $statusCor }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span class="badge {{ $riscoCor }}">{{ $riscoLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button type="button" wire:click="abrirDetalhe('{{ $contrato->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700" title="Ver detalhes" aria-label="Ver detalhes">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button type="button" wire:click="abrirModalEditar('{{ $contrato->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-rose-50 hover:text-rose-700" title="Editar" aria-label="Editar">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                    @if ($contrato->status->value === 'ativo' && $contrato->dia_vencimento !== null)
                                        <button type="button" wire:click="abrirModalPagarContrato('{{ $contrato->id }}')"
                                                class="rounded-lg p-2 text-stone-400 transition hover:bg-emerald-50 hover:text-emerald-700" title="Registrar pagamento" aria-label="Registrar pagamento">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="abrirModalReajuste('{{ $contrato->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-blue-50 hover:text-blue-700" title="Registrar reajuste" aria-label="Registrar reajuste">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                                    </button>
                                    <button type="button" wire:click="abrirModalArquivo('{{ $contrato->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-violet-50 hover:text-violet-700" title="Enviar arquivo" aria-label="Enviar arquivo">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                    </button>
                                    <button type="button" wire:click="abrirModalExcluir('{{ $contrato->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-red-50 hover:text-red-700" title="Excluir" aria-label="Excluir">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($contratos->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $contratos->links() }}
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: NOVO / EDITAR CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-contrato"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-2xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-contrato" class="text-lg font-semibold text-stone-900">
                        {{ $modalCriar ? 'Novo contrato' : 'Editar contrato' }}
                    </h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-5 sm:p-6">
                    {{-- Fornecedor, status, risco e valor --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label for="ct-fornecedor" class="label">Fornecedor</label>
                            <select id="ct-fornecedor" wire:model="fornecedorId" class="input @error('fornecedorId') border-red-300 @enderror">
                                <option value="">Selecione um fornecedor</option>
                                @foreach ($fornecedoresAtivos as $f)
                                    <option value="{{ $f->id }}">{{ $f->nome_fantasia }}</option>
                                @endforeach
                            </select>
                            @error('fornecedorId') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ct-status" class="label">Status</label>
                            <select id="ct-status" wire:model="status" class="input">
                                <option value="ativo">Ativo</option>
                                <option value="em_negociacao">Em negociação</option>
                                <option value="suspenso">Suspenso</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                            @error('status') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ct-risco" class="label">Risco</label>
                            <select id="ct-risco" wire:model="risco" class="input">
                                <option value="baixo">Baixo</option>
                                <option value="medio">Médio</option>
                                <option value="alto">Alto</option>
                            </select>
                            @error('risco') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ct-valor" class="label">Valor mensal (R$) <span class="text-red-600">*</span></label>
                            <input id="ct-valor" wire:model="valorMensal" type="number" step="0.01" min="0" inputmode="decimal"
                                   class="input tabular-nums @error('valorMensal') border-red-300 @enderror"
                                   placeholder="Ex.: 450,00">
                            @error('valorMensal') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="ct-inicio" class="label">Data de início</label>
                            <input id="ct-inicio" wire:model="dataInicio" type="date" class="input @error('dataInicio') border-red-300 @enderror">
                            @error('dataInicio') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ct-fim" class="label">Data de término</label>
                            <input id="ct-fim" wire:model="dataFim" type="date" class="input @error('dataFim') border-red-300 @enderror">
                            @error('dataFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ct-dia" class="label">Dia do pagamento</label>
                            <input id="ct-dia" wire:model="diaVencimento" type="number" min="1" max="31" inputmode="numeric" placeholder="Ex.: 10"
                                   class="input @error('diaVencimento') border-red-300 @enderror">
                            @error('diaVencimento')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="hint">Deixe vazio se não houver pagamento mensal.</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t border-stone-100 pt-4">
                        <p class="text-sm font-semibold text-stone-800">Reajuste</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="ct-periodicidade" class="label">Periodicidade</label>
                            <select id="ct-periodicidade" wire:model="periodicidadeReajuste" class="input">
                                <option value="">Sem reajuste</option>
                                <option value="anual">Anual</option>
                                <option value="semestral">Semestral</option>
                            </select>
                        </div>
                        <div>
                            <label for="ct-indice" class="label">Índice</label>
                            <select id="ct-indice" wire:model="indiceReajuste" class="input">
                                <option value="">Não definido</option>
                                <option value="igpm">IGP-M</option>
                                <option value="ipca">IPCA</option>
                                <option value="inpc">INPC</option>
                                <option value="fixo">Fixo</option>
                                <option value="livre">Livre</option>
                            </select>
                        </div>
                        <div>
                            <label for="ct-prox-reajuste" class="label">Próximo reajuste</label>
                            <input id="ct-prox-reajuste" wire:model="dataProximoReajuste" type="date" class="input">
                        </div>
                    </div>

                    <div class="border-t border-stone-100 pt-4">
                        <p class="text-sm font-semibold text-stone-800">Rescisão</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="ct-multa-valor" class="label">Multa (R$)</label>
                            <input id="ct-multa-valor" wire:model="multaRescisaoValor" type="number" step="0.01" min="0" inputmode="decimal"
                                   class="input tabular-nums" placeholder="Ex.: 1.000,00">
                        </div>
                        <div>
                            <label for="ct-multa-pct" class="label">Multa (%)</label>
                            <input id="ct-multa-pct" wire:model="multaRescisaoPercentual" type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                   class="input tabular-nums" placeholder="Ex.: 20">
                        </div>
                        <div>
                            <label for="ct-aviso" class="label">Aviso prévio (dias)</label>
                            <input id="ct-aviso" wire:model="avisoPrevioDias" type="number" min="0" inputmode="numeric"
                                   class="input tabular-nums" placeholder="Ex.: 30">
                        </div>
                    </div>

                    <div>
                        <label for="ct-link" class="label">Link do contrato</label>
                        <input id="ct-link" wire:model="linkContrato" type="url" inputmode="url"
                               class="input @error('linkContrato') border-red-300 @enderror"
                               placeholder="Ex.: https://drive.google.com/...">
                        @error('linkContrato') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ct-observacoes" class="label">Observações</label>
                        <textarea id="ct-observacoes" wire:model="observacoes" rows="2" class="input"
                                  placeholder="Ex.: Renovação automática se não houver aviso"></textarea>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    @if ($modalCriar)
                        <button type="button" wire:click="salvar" wire:loading.attr="disabled" wire:target="salvar" class="btn-primary">
                            <span wire:loading.remove wire:target="salvar">Salvar contrato</span>
                            <span wire:loading wire:target="salvar">Salvando...</span>
                        </button>
                    @else
                        <button type="button" wire:click="atualizar" wire:loading.attr="disabled" wire:target="atualizar" class="btn-primary">
                            <span wire:loading.remove wire:target="atualizar">Salvar alterações</span>
                            <span wire:loading wire:target="atualizar">Salvando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHES DO CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $contratoDetalhe)
        @php
            $c = $contratoDetalhe;
            [$cStatusLabel] = $statusInfo[$c->status->value] ?? $statusInfo['encerrado'];
            [$cRiscoLabel]  = $riscoInfo[$c->risco->value] ?? $riscoInfo['baixo'];
        @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-detalhe"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-detalhe" class="min-w-0 truncate text-lg font-semibold text-stone-900">{{ $c->fornecedor?->nome_fantasia ?? 'Contrato' }}</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-5 sm:p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-stone-500">Status</dt>
                            <dd class="mt-0.5 font-medium text-stone-800">{{ $cStatusLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500">Risco</dt>
                            <dd class="mt-0.5 font-medium text-stone-800">{{ $cRiscoLabel }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500">Valor mensal</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-stone-900">R$ {{ number_format((float) $c->getRawOriginal('valor_mensal'), 2, ',', '.') }}</dd>
                        </div>
                        @if ($c->data_inicio)
                            <div>
                                <dt class="text-xs text-stone-500">Início</dt>
                                <dd class="mt-0.5 font-medium tabular-nums text-stone-800">{{ $c->data_inicio->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if ($c->data_fim)
                            <div>
                                <dt class="text-xs text-stone-500">Término</dt>
                                <dd class="mt-0.5 font-medium tabular-nums {{ $c->data_fim->lte(now()->addDays(60)) ? 'text-amber-700' : 'text-stone-800' }}">
                                    {{ $c->data_fim->format('d/m/Y') }}
                                </dd>
                            </div>
                        @endif
                        @if ($c->indice_reajuste)
                            <div>
                                <dt class="text-xs text-stone-500">Índice de reajuste</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ strtoupper($c->indice_reajuste->value) }}</dd>
                            </div>
                        @endif
                        @if ($c->data_proximo_reajuste)
                            <div>
                                <dt class="text-xs text-stone-500">Próximo reajuste</dt>
                                <dd class="mt-0.5 font-medium tabular-nums {{ $c->data_proximo_reajuste->lte(now()->addDays(30)) ? 'text-amber-700' : 'text-stone-800' }}">
                                    {{ $c->data_proximo_reajuste->format('d/m/Y') }}
                                </dd>
                            </div>
                        @endif
                        @if ($c->aviso_previo_dias)
                            <div>
                                <dt class="text-xs text-stone-500">Aviso prévio</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ $c->aviso_previo_dias }} dias</dd>
                            </div>
                        @endif
                        @if ($c->temArquivo())
                            <div class="col-span-2">
                                <dt class="text-xs text-stone-500">Arquivo</dt>
                                <dd class="mt-0.5">
                                    <a href="{{ route('contratos.arquivo.download', $c->id) }}"
                                       class="inline-flex min-h-[44px] items-center gap-1.5 text-sm font-medium text-rose-700 hover:underline"
                                       target="_blank">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                        <span class="break-all">{{ $c->arquivo_contrato_nome }}</span>
                                    </a>
                                </dd>
                            </div>
                        @endif
                        @if ($c->link_contrato)
                            <div class="col-span-2">
                                <dt class="text-xs text-stone-500">Link do contrato</dt>
                                <dd class="mt-0.5">
                                    <a href="{{ $c->link_contrato }}" target="_blank" rel="noopener noreferrer"
                                       class="break-all font-medium text-rose-700 hover:underline">{{ $c->link_contrato }}</a>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if ($c->pagamentos->count() > 0)
                        <div class="border-t border-stone-100 pt-4">
                            <p class="mb-2 text-sm font-semibold text-stone-800">Pagamentos</p>
                            <ul class="space-y-2">
                                @foreach ($c->pagamentos->take(6) as $pgto)
                                    <li class="flex items-center justify-between gap-2 rounded-xl bg-stone-50 py-1.5 pl-3 pr-1.5 text-sm">
                                        <div class="min-w-0">
                                            <span class="font-medium text-stone-800">{{ $pgto->competenciaFormatada() }}</span>
                                            <span class="mx-1.5 text-stone-300">·</span>
                                            <span class="text-xs text-stone-500 tabular-nums">pago em {{ $pgto->data_pagamento->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="font-semibold tabular-nums text-stone-900">
                                                R$ {{ number_format((float) $pgto->getRawOriginal('valor'), 2, ',', '.') }}
                                            </span>
                                            <button type="button"
                                                wire:click="deletarPagamento('{{ $pgto->id }}')"
                                                wire:confirm="Excluir o pagamento de {{ $pgto->competenciaFormatada() }}? O lançamento ligado a ele também será excluído. Essa ação não pode ser desfeita."
                                                class="rounded-lg p-2 text-stone-400 transition hover:bg-red-50 hover:text-red-700"
                                                title="Excluir pagamento" aria-label="Excluir pagamento">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($c->reajustes->count() > 0)
                        <div class="border-t border-stone-100 pt-4">
                            <p class="mb-2 text-sm font-semibold text-stone-800">Reajustes</p>
                            <ul class="space-y-2">
                                @foreach ($c->reajustes as $r)
                                    <li class="rounded-xl bg-stone-50 px-3 py-2.5 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-stone-500 tabular-nums">{{ \Carbon\Carbon::parse($r->data_reajuste)->format('d/m/Y') }}</span>
                                            <span class="font-semibold tabular-nums {{ $r->percentual_efetivo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ $r->percentual_efetivo >= 0 ? '+' : '' }}{{ number_format($r->percentual_efetivo, 2, ',', '.') }}%
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-stone-500 tabular-nums">
                                            R$ {{ number_format($r->valor_anterior, 2, ',', '.') }} → R$ {{ number_format($r->valor_novo, 2, ',', '.') }}
                                            @if ($r->indice) · {{ strtoupper($r->indice) }} @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($c->observacoes)
                        <div>
                            <p class="mb-1 text-xs text-stone-500">Observações</p>
                            <p class="text-sm text-stone-700">{{ $c->observacoes }}</p>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:flex-wrap sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Fechar</button>
                    @if ($c->status->value === 'ativo' && $c->dia_vencimento !== null)
                        <button type="button" wire:click="abrirModalPagarContrato('{{ $c->id }}')" class="btn-secondary">Registrar pagamento</button>
                    @endif
                    <button type="button" wire:click="abrirModalReajuste('{{ $c->id }}')" class="btn-secondary">Registrar reajuste</button>
                    <button type="button" wire:click="abrirModalArquivo('{{ $c->id }}')" class="btn-secondary">Enviar arquivo</button>
                    <button type="button" wire:click="abrirModalEditar('{{ $c->id }}')" class="btn-primary">Editar contrato</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REAJUSTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalReajuste)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-reajuste"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-reajuste" class="text-lg font-semibold text-stone-900">Registrar reajuste</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rj-data" class="label">Data do reajuste <span class="text-red-600">*</span></label>
                            <input id="rj-data" wire:model="dataReajuste" type="date" class="input @error('dataReajuste') border-red-300 @enderror">
                            @error('dataReajuste') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rj-indice" class="label">Índice</label>
                            <select id="rj-indice" wire:model="indiceReajusteOp" class="input">
                                <option value="">Livre</option>
                                <option value="igpm">IGP-M</option>
                                <option value="ipca">IPCA</option>
                                <option value="inpc">INPC</option>
                                <option value="fixo">Fixo</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="rj-valor" class="label">Novo valor mensal (R$) <span class="text-red-600">*</span></label>
                        <input id="rj-valor" wire:model="valorNovo" type="number" step="0.01" min="0.01" inputmode="decimal"
                               class="input tabular-nums @error('valorNovo') border-red-300 @enderror"
                               placeholder="Ex.: 480,00">
                        @error('valorNovo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="rj-obs" class="label">Observações</label>
                        <textarea id="rj-obs" wire:model="observacoesReajuste" rows="2" class="input"
                                  placeholder="Ex.: Reajuste anual pelo IPCA"></textarea>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="registrarReajuste" wire:loading.attr="disabled" wire:target="registrarReajuste" class="btn-primary">
                        <span wire:loading.remove wire:target="registrarReajuste">Registrar reajuste</span>
                        <span wire:loading wire:target="registrarReajuste">Registrando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REGISTRAR PAGAMENTO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalPagarContrato)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-pagamento"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-pagamento" class="text-lg font-semibold text-stone-900">Registrar pagamento</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pg-competencia" class="label">Mês de referência <span class="text-red-600">*</span></label>
                            <input id="pg-competencia" wire:model="pgCompetencia" type="month" class="input @error('pgCompetencia') border-red-300 @enderror">
                            @error('pgCompetencia') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pg-data" class="label">Data do pagamento <span class="text-red-600">*</span></label>
                            <input id="pg-data" wire:model="pgDataPagamento" type="date" class="input @error('pgDataPagamento') border-red-300 @enderror">
                            @error('pgDataPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pg-valor" class="label">Valor (R$) <span class="text-red-600">*</span></label>
                            <input id="pg-valor" wire:model="pgValor" type="number" step="0.01" min="0.01" inputmode="decimal"
                                   class="input tabular-nums @error('pgValor') border-red-300 @enderror"
                                   placeholder="Ex.: 450,00">
                            @error('pgValor') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pg-forma" class="label">Forma de pagamento <span class="text-red-600">*</span></label>
                            <select id="pg-forma" wire:model="pgFormaPagamento" class="input">
                                <option value="pix">Pix</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito</option>
                                <option value="credito_1x">Crédito 1x</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="credito_2x">Crédito 2x</option>
                                <option value="credito_3x">Crédito 3x</option>
                            </select>
                            @error('pgFormaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="pg-obs" class="label">Observações</label>
                        <textarea id="pg-obs" wire:model="pgObservacoes" rows="2" class="input"
                                  placeholder="Ex.: Pago com desconto de pontualidade"></textarea>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="pagarContrato" wire:loading.attr="disabled" wire:target="pagarContrato" class="btn-primary">
                        <span wire:loading.remove wire:target="pagarContrato">Registrar pagamento</span>
                        <span wire:loading wire:target="pagarContrato">Registrando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: EXCLUIR CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalExcluir)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="alertdialog" aria-modal="true" aria-labelledby="titulo-modal-excluir"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-sm sm:rounded-2xl">
                <div class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        </div>
                        <div>
                            <h2 id="titulo-modal-excluir" class="text-lg font-semibold text-stone-900">Excluir este contrato?</h2>
                            <p class="mt-1 text-sm text-stone-500">
                                O contrato com
                                <span class="font-medium text-stone-800">{{ $contratoExcluirNome }}</span>
                                e todos os pagamentos e reajustes dele serão excluídos. Essa ação não pode ser desfeita.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="excluir" wire:loading.attr="disabled" wire:target="excluir" class="btn-danger">
                        <span wire:loading.remove wire:target="excluir">Excluir contrato</span>
                        <span wire:loading wire:target="excluir">Excluindo...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ENVIAR ARQUIVO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalArquivo)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-arquivo"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-arquivo" class="text-lg font-semibold text-stone-900">Enviar arquivo do contrato</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    <label for="ct-arquivo" class="label">Arquivo <span class="text-red-600">*</span></label>
                    <input id="ct-arquivo" wire:model="arquivoContrato" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx"
                           class="input file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    @error('arquivoContrato')
                        <p class="field-error">{{ $message }}</p>
                    @else
                        <p class="hint">PDF, JPG, PNG ou DOCX, com até 10 MB.</p>
                    @enderror
                    <p wire:loading wire:target="arquivoContrato" class="mt-2 text-xs text-stone-500">Carregando arquivo...</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="uploadArquivo" wire:loading.attr="disabled" wire:target="uploadArquivo, arquivoContrato" class="btn-primary">
                        <span wire:loading.remove wire:target="uploadArquivo">Enviar arquivo</span>
                        <span wire:loading wire:target="uploadArquivo">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
