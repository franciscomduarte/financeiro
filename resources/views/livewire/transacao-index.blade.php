<div>
    @php
        $temFiltro = $filtroTipo || $filtroFase || $filtroStatus || $filtroCategoria || $periodoInicio || $periodoFim;
    @endphp

    {{-- ─── Avisos ──────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="fixed top-4 inset-x-4 z-[70] sm:left-auto sm:right-4 sm:max-w-sm"
            role="status"
        >
            <div class="bg-surface border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                {{ $flashSucesso }}
            </div>
        </div>
    @endif

    @if ($flashErro)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed top-4 inset-x-4 z-[70] sm:left-auto sm:right-4 sm:max-w-sm"
            role="alert"
        >
            <div class="bg-surface border border-red-200 text-red-700 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Cabeçalho ─────────────────────────────────── --}}
    <x-ui.page-header titulo="Lançamentos" subtitulo="Registre e acompanhe as entradas e saídas da clínica.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCriar" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Novo lançamento
            </button>
        </x-slot:acoes>
    </x-ui.page-header>
    <x-ui.abas-lancamentos />

    {{-- ─── Indicadores ──────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-3 mb-6">

        {{-- Entradas --}}
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-stone-500">Entradas</p>
                <div class="w-9 h-9 shrink-0 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold text-stone-900 tabular-nums">
                R$&nbsp;{{ number_format($totalEntradas, 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-500 mt-1">Receitas, sem contar as canceladas</p>
        </div>

        {{-- Saídas --}}
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-stone-500">Saídas</p>
                <div class="w-9 h-9 shrink-0 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold text-stone-900 tabular-nums">
                R$&nbsp;{{ number_format($totalSaidas, 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-500 mt-1">Despesas, sem contar as canceladas</p>
        </div>

        {{-- Saldo --}}
        <div class="card p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-stone-500">Saldo</p>
                <div class="w-9 h-9 shrink-0 rounded-xl {{ $saldo >= 0 ? 'bg-blue-50' : 'bg-orange-50' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $saldo >= 0 ? 'text-blue-600' : 'text-orange-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="mt-2 text-2xl font-semibold tabular-nums {{ $saldo >= 0 ? 'text-stone-900' : 'text-orange-600' }}">
                @if ($saldo < 0)−&nbsp;@endif R$&nbsp;{{ number_format(abs($saldo), 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-500 mt-1">{{ $saldo >= 0 ? 'Resultado positivo no período' : 'Resultado negativo no período' }}</p>
        </div>

    </div>

    {{-- ─── Filtros ──────────────────────────────────────── --}}
    <div class="card p-4 mb-4">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
            </svg>
            <span class="text-sm font-medium text-stone-700">Filtros</span>
            @if ($temFiltro)
                <button
                    type="button"
                    wire:click="$set('filtroTipo',''); $set('filtroFase',''); $set('filtroStatus',''); $set('filtroCategoria',''); $set('periodoInicio',''); $set('periodoFim','')"
                    class="btn-ghost ml-auto -my-2 -mr-2 text-rose-600"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpar filtros
                </button>
            @endif
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label for="filtro-tipo" class="label">Tipo</label>
                <select id="filtro-tipo" wire:model.live="filtroTipo" class="input">
                    <option value="">Todos</option>
                    @foreach ($tiposEnum as $tipo)
                        <option value="{{ $tipo->value }}">{{ $tipo === \App\Enums\TipoTransacao::Entrada ? 'Entrada' : 'Saída' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtro-fase" class="label">Fase</label>
                <select id="filtro-fase" wire:model.live="filtroFase" class="input">
                    <option value="">Todas</option>
                    @foreach ($fasesEnum as $fase)
                        <option value="{{ $fase->value }}">{{ $fase->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtro-status" class="label">Situação</label>
                <select id="filtro-status" wire:model.live="filtroStatus" class="input">
                    <option value="">Todas</option>
                    @foreach ($statusEnum as $st)
                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtro-categoria" class="label">Categoria</label>
                <select id="filtro-categoria" wire:model.live="filtroCategoria" class="input">
                    <option value="">Todas</option>
                    @foreach ($todasCategorias as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtro-de" class="label">De</label>
                <input id="filtro-de" type="date" wire:model.live="periodoInicio" class="input">
            </div>
            <div>
                <label for="filtro-ate" class="label">Até</label>
                <input id="filtro-ate" type="date" wire:model.live="periodoFim" class="input">
            </div>
        </div>
    </div>

    {{-- ─── Lista ────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        @if ($transacoes->isEmpty())
            @if ($temFiltro)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Tente outro período ou outra categoria, ou limpe os filtros para ver todos os lançamentos."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button
                        type="button"
                        wire:click="$set('filtroTipo',''); $set('filtroFase',''); $set('filtroStatus',''); $set('filtroCategoria',''); $set('periodoInicio',''); $set('periodoFim','')"
                        class="btn-secondary"
                    >Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhum lançamento ainda"
                    texto="Registre aqui o que entra e o que sai do caixa. Você também pode preencher falando, pelo botão de voz."
                    icone="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5">
                    <button type="button" wire:click="abrirModalCriar" class="btn-primary">Novo lançamento</button>
                </x-ui.empty-state>
            @endif
        @else
        {{-- Celular: cartões --}}
        <ul class="divide-y divide-stone-100 md:hidden">
            @foreach ($transacoes as $transacao)
                @php $entrada = $transacao->tipo === \App\Enums\TipoTransacao::Entrada; @endphp
                <li wire:key="tx-card-{{ $transacao->id }}">
                    <button type="button" wire:click="abrirModalDetalhe('{{ $transacao->id }}')"
                            class="flex w-full min-h-[64px] items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-stone-50 active:bg-stone-100">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $entrada ? 'bg-emerald-500' : 'bg-red-500' }}" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-stone-800">@if ($transacao->recorrencia_id)<svg class="inline-block w-3.5 h-3.5 mr-1 -mt-0.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" role="img" aria-label="Recorrente"><title>Recorrente</title><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>@endif{{ $transacao->descricao }}</span>
                            <span class="mt-0.5 block truncate text-xs text-stone-500">
                                {{ $transacao->data_competencia?->format('d/m/Y') ?? '—' }} · {{ $transacao->categoria }}@if ($transacao->paciente) · {{ $transacao->paciente->nome }}@elseif ($transacao->cliente) · {{ $transacao->cliente }}@endif
                            </span>
                        </span>
                        <span class="flex shrink-0 flex-col items-end gap-1">
                            <span class="text-sm font-semibold tabular-nums {{ $entrada ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $entrada ? '+' : '−' }}&nbsp;R$&nbsp;{{ number_format((float) $transacao->valor_bruto, 2, ',', '.') }}
                            </span>
                            <x-transacao.status-badge :status="$transacao->status" />
                        </span>
                    </button>
                </li>
            @endforeach
        </ul>

        {{-- Desktop: tabela --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50 text-xs font-medium text-stone-500">
                        <th scope="col" class="text-left px-4 py-3 font-medium whitespace-nowrap">Data</th>
                        <th scope="col" class="text-left px-4 py-3 font-medium whitespace-nowrap">Tipo</th>
                        <th scope="col" class="text-left px-4 py-3 font-medium whitespace-nowrap">Fase</th>
                        <th scope="col" class="text-left px-4 py-3 font-medium whitespace-nowrap">Categoria</th>
                        <th scope="col" class="text-left px-4 py-3 font-medium">Descrição</th>
                        <th scope="col" class="text-right px-4 py-3 font-medium whitespace-nowrap">Valor</th>
                        <th scope="col" class="text-left px-4 py-3 font-medium whitespace-nowrap">Situação</th>
                        <th scope="col" class="px-4 py-3 w-40"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($transacoes as $transacao)
                        <tr wire:key="tx-row-{{ $transacao->id }}" class="hover:bg-stone-50 transition-colors group">
                            <td class="px-4 py-3 text-stone-500 whitespace-nowrap tabular-nums">
                                {{ $transacao->data_competencia?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($transacao->tipo->value === 'entrada')
                                    <span class="badge bg-emerald-50 text-emerald-700">Entrada</span>
                                @else
                                    <span class="badge bg-red-50 text-red-700">Saída</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="badge bg-stone-100 text-stone-600">{{ $transacao->fase->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-stone-600 max-w-[140px]">
                                <span class="truncate block">{{ $transacao->categoria }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-[240px]">
                                <p class="text-stone-800 font-medium truncate">@if ($transacao->recorrencia_id)<svg class="inline-block w-3.5 h-3.5 mr-1 -mt-0.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" role="img" aria-label="Recorrente"><title>Recorrente</title><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>@endif{{ $transacao->descricao }}</p>
                                @if ($transacao->paciente)
                                    <p class="text-xs text-stone-500 truncate mt-0.5">{{ $transacao->paciente->nome }}</p>
                                @elseif ($transacao->cliente)
                                    <p class="text-xs text-stone-500 truncate mt-0.5">{{ $transacao->cliente }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap tabular-nums
                                {{ $transacao->tipo->value === 'entrada' ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $transacao->tipo->value === 'saida' ? '−' : '+' }}&nbsp;R$&nbsp;{{ number_format((float)$transacao->valor_bruto, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <x-transacao.status-badge :status="$transacao->status" />
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex items-center justify-end gap-0.5">
                                    @if ($transacao->status === \App\Enums\StatusTransacao::Pendente)
                                        <button
                                            type="button"
                                            wire:click="marcarComoPago('{{ $transacao->id }}')"
                                            class="inline-flex h-9 w-9 items-center justify-center text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Marcar como pago"
                                            aria-label="Marcar como pago"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        wire:click="abrirModalDetalhe('{{ $transacao->id }}')"
                                        class="inline-flex h-9 w-9 items-center justify-center text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                        title="Ver detalhes"
                                        aria-label="Ver detalhes"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="abrirModalEditar('{{ $transacao->id }}')"
                                        class="inline-flex h-9 w-9 items-center justify-center text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                        title="Editar"
                                        aria-label="Editar"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                    @if ($transacao->status->value !== 'cancelado')
                                        <button
                                            type="button"
                                            wire:click="cancelarTransacao('{{ $transacao->id }}')"
                                            wire:confirm="Cancelar este lançamento? Ele deixa de contar nos totais."
                                            class="inline-flex h-9 w-9 items-center justify-center text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Cancelar lançamento"
                                            aria-label="Cancelar lançamento"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if ($transacoes->hasPages())
            <div class="px-4 py-3 border-t border-stone-100">
                {{ $transacoes->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: NOVO LANÇAMENTO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center sm:p-4" wire:click.self="fecharModalCriar">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-criar" class="bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-start justify-between gap-4 px-5 sm:px-6 py-4 border-b border-stone-100">
                    <div>
                        <h2 id="titulo-modal-criar" class="text-lg font-semibold text-stone-900">Novo lançamento</h2>
                        <p class="text-sm text-stone-500 mt-0.5">Preencha os dados ou use a voz para agilizar.</p>
                    </div>
                    <button type="button" wire:click="fecharModalCriar" class="btn-ghost -mr-2 -mt-1 w-11 px-0 text-stone-400" aria-label="Fechar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    @include('livewire.partials.transacao-form')
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 px-5 sm:px-6 py-4 border-t border-stone-100 bg-stone-50">
                    <button type="button" wire:click="fecharModalCriar" class="btn-secondary">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvarNova" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="salvarNova">Salvar lançamento</span>
                        <span wire:loading wire:target="salvarNova">Salvando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: EDITAR LANÇAMENTO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalEditar)
        <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center sm:p-4" wire:click.self="fecharModalEditar">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-editar" class="bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-start justify-between gap-4 px-5 sm:px-6 py-4 border-b border-stone-100">
                    <div>
                        <h2 id="titulo-modal-editar" class="text-lg font-semibold text-stone-900">Editar lançamento</h2>
                        <p class="text-sm text-stone-500 mt-0.5">Ajuste o que precisar e salve.</p>
                    </div>
                    <button type="button" wire:click="fecharModalEditar" class="btn-ghost -mr-2 -mt-1 w-11 px-0 text-stone-400" aria-label="Fechar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    @include('livewire.partials.transacao-form')
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 px-5 sm:px-6 py-4 border-t border-stone-100 bg-stone-50">
                    <button type="button" wire:click="fecharModalEditar" class="btn-secondary">
                        Cancelar
                    </button>
                    <button type="button" wire:click="salvarEdicao" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="salvarEdicao">Salvar alterações</span>
                        <span wire:loading wire:target="salvarEdicao">Salvando…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: DETALHES DO LANÇAMENTO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $this->transacaoDetalhe)
        @php $t = $this->transacaoDetalhe; @endphp
        <div class="fixed inset-0 bg-black/40 z-50 flex items-end sm:items-center justify-center sm:p-4" wire:click.self="fecharModalDetalhe">
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-detalhe" class="bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">

                {{-- Cabeçalho --}}
                <div class="flex items-start justify-between gap-4 px-5 sm:px-6 py-4 border-b border-stone-100">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-detalhe" class="text-lg font-semibold text-stone-900 break-words">{{ $t->descricao }}</h2>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            @if ($t->tipo->value === 'entrada')
                                <span class="badge bg-emerald-50 text-emerald-700">Entrada</span>
                            @else
                                <span class="badge bg-red-50 text-red-700">Saída</span>
                            @endif
                            <x-transacao.status-badge :status="$t->status" />
                            <span class="badge bg-stone-100 text-stone-600">{{ $t->fase->label() }}</span>
                        </div>
                    </div>
                    <button type="button" wire:click="fecharModalDetalhe" class="btn-ghost -mr-2 -mt-1 w-11 px-0 text-stone-400 shrink-0" aria-label="Fechar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 sm:p-6 space-y-6">

                    @if ($t->status === \App\Enums\StatusTransacao::Pendente)
                        <div class="flex flex-col gap-3 rounded-xl border border-amber-100 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-amber-700">
                                {{ $t->tipo === \App\Enums\TipoTransacao::Entrada ? 'Recebimento pendente.' : 'Pagamento pendente.' }}
                                @if ($t->anexos->contains(fn ($a) => $a->tipo === \App\Enums\TipoAnexo::Comprovante)) O comprovante já está anexado. @endif
                            </p>
                            <button type="button" wire:click="marcarComoPago('{{ $t->id }}')" wire:loading.attr="disabled"
                                    class="btn shrink-0 bg-emerald-600 text-white hover:bg-emerald-500">
                                Marcar como pago
                            </button>
                        </div>
                    @endif

                    {{-- Nota fiscal (receitas) --}}
                    @if ($t->tipo === \App\Enums\TipoTransacao::Entrada && $t->status !== \App\Enums\StatusTransacao::Cancelado)
                        @php $nf = $t->notasFiscais->first(); @endphp
                        <div class="flex flex-col gap-3 rounded-xl border border-stone-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm">
                                <p class="font-medium text-stone-700">Nota fiscal</p>
                                @if ($nf)
                                    <p class="mt-0.5 text-stone-500">
                                        <span class="badge {{ $nf->status->badge() }}">{{ $nf->status->label() }}</span>
                                        @if ($nf->numero) nº {{ $nf->numero }} @endif
                                        @if ($nf->homologacao) <span class="text-xs">(teste)</span> @endif
                                    </p>
                                @else
                                    <p class="mt-0.5 text-stone-500">Ainda sem NFS-e.</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-2">
                                @if ($nf?->url && $nf->status === \App\Enums\StatusNotaFiscal::Autorizada)
                                    <a href="{{ $nf->url }}" target="_blank" rel="noopener" class="btn-secondary">Ver nota</a>
                                @endif
                                @if (! $nf || ! $nf->status->ativa())
                                    <a href="{{ route('notas-fiscais.index', ['transacao' => $t->id]) }}" wire:navigate class="btn-secondary">Emitir NFS-e</a>
                                @else
                                    <a href="{{ route('notas-fiscais.index') }}" wire:navigate class="btn-ghost">Notas fiscais</a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Composição do valor --}}
                    <div class="bg-stone-50 rounded-xl p-4">
                        <p class="text-sm font-medium text-stone-700 mb-3">Composição do valor</p>
                        <div class="space-y-2">
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="text-stone-500">Valor bruto</span>
                                <span class="font-medium text-stone-800 tabular-nums">R$ {{ number_format((float)$t->valor_bruto, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="text-stone-500">Taxa do meio de pagamento</span>
                                <span class="text-red-600 tabular-nums">− R$ {{ number_format((float)$t->taxa_operacional, 2, ',', '.') }}</span>
                            </div>
                            @if ((float)$t->imposto_estimado > 0)
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="text-stone-500">Imposto estimado ({{ rtrim(rtrim(number_format((float) ($clinicaAtual?->aliquota_imposto ?? 6), 2, ",", "."), "0"), ",") }}%)</span>
                                <span class="text-red-600 tabular-nums">− R$ {{ number_format((float)$t->imposto_estimado, 2, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between gap-4 text-sm pt-2.5 border-t border-stone-200 font-semibold">
                                <span class="text-stone-700">Valor líquido</span>
                                <span class="text-emerald-700 tabular-nums text-base">R$ {{ number_format((float)$t->valor_liquido, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Detalhes em grade --}}
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Categoria</dt>
                            <dd class="text-sm text-stone-800 font-medium">{{ $t->categoria }}</dd>
                        </div>
                        @if ($t->paciente)
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Paciente</dt>
                            <dd class="text-sm text-stone-800 font-medium">{{ $t->paciente->nome }}</dd>
                        </div>
                        @elseif ($t->cliente)
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Cliente</dt>
                            <dd class="text-sm text-stone-800 font-medium">{{ $t->cliente }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Data de competência</dt>
                            <dd class="text-sm text-stone-800 tabular-nums">{{ $t->data_competencia?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Data de pagamento</dt>
                            <dd class="text-sm text-stone-800 tabular-nums">{{ $t->data_pagamento?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Forma de pagamento</dt>
                            <dd class="text-sm text-stone-800">{{ $t->forma_pagamento->label() }}</dd>
                        </div>
                        @if ($t->num_parcelas > 1)
                        <div>
                            <dt class="text-xs text-stone-500 mb-1">Parcelas</dt>
                            <dd class="text-sm text-stone-800">{{ $t->num_parcelas }}x</dd>
                        </div>
                        @endif
                    </dl>

                    @if ($t->observacoes)
                        <div>
                            <p class="text-xs text-stone-500 mb-2">Observações</p>
                            <p class="text-sm text-stone-700 bg-stone-50 rounded-xl p-3.5 leading-relaxed">{{ $t->observacoes }}</p>
                        </div>
                    @endif

                    {{-- Anexos --}}
                    <div>
                        <p class="text-sm font-medium text-stone-700 mb-3">Anexos</p>

                        @if ($t->anexos->count() > 0)
                            <div class="space-y-2 mb-4">
                                @foreach ($t->anexos as $anexo)
                                    <div class="flex items-center justify-between bg-stone-50 rounded-xl pl-4 pr-2 py-2 group">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm text-stone-700 font-medium truncate">{{ $anexo->nome_arquivo }}</p>
                                                <p class="text-xs text-stone-500">{{ ucfirst($anexo->tipo->value) }} · {{ number_format($anexo->tamanho_bytes / 1024, 1, ',', '.') }} KB</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center shrink-0 ml-2">
                                            <a
                                                href="{{ route('api.v1.anexos.download', $anexo->id) }}"
                                                target="_blank"
                                                class="inline-flex h-11 w-11 items-center justify-center text-stone-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                                title="Abrir arquivo"
                                                aria-label="Abrir arquivo"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                            @if ($t->paciente_id)
                                                <button
                                                    type="button"
                                                    wire:click="abrirModalEnviarAnexo('{{ $anexo->id }}')"
                                                    class="inline-flex h-11 w-11 items-center justify-center text-stone-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                    title="Enviar para o paciente"
                                                    aria-label="Enviar para o paciente"
                                                >
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <button
                                                type="button"
                                                wire:click="removerAnexo('{{ $anexo->id }}')"
                                                wire:confirm="Excluir este anexo? Essa ação não pode ser desfeita."
                                                class="inline-flex h-11 w-11 items-center justify-center text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Excluir anexo"
                                                aria-label="Excluir anexo"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-stone-500 mb-4">Nenhum anexo ainda. Anexe o boleto, a nota ou o comprovante abaixo.</p>
                        @endif

                        {{-- Botões de envio --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <input type="file" wire:model="arquivoBoleto" accept=".pdf,.jpg,.jpeg,.png,.docx" id="boleto-upload" class="hidden">
                                <label for="boleto-upload" class="btn-secondary w-full cursor-pointer">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                    </svg>
                                    <span class="truncate">{{ $arquivoBoleto ? $arquivoBoleto->getClientOriginalName() : 'Escolher boleto ou nota' }}</span>
                                </label>
                                @if ($arquivoBoleto)
                                    <button type="button" wire:click="uploadBoleto" wire:loading.attr="disabled" class="btn-primary w-full">
                                        <span wire:loading.remove wire:target="uploadBoleto">Anexar boleto ou nota</span>
                                        <span wire:loading wire:target="uploadBoleto">Enviando…</span>
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <input type="file" wire:model="arquivoComprovante" accept=".pdf,.jpg,.jpeg,.png,.docx" id="comprovante-upload" class="hidden">
                                <label for="comprovante-upload" class="btn-secondary w-full cursor-pointer">
                                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="truncate">{{ $arquivoComprovante ? $arquivoComprovante->getClientOriginalName() : 'Escolher comprovante' }}</span>
                                </label>
                                @if ($arquivoComprovante)
                                    <button type="button" wire:click="uploadComprovante" wire:loading.attr="disabled" class="btn-primary w-full">
                                        <span wire:loading.remove wire:target="uploadComprovante">Anexar comprovante</span>
                                        <span wire:loading wire:target="uploadComprovante">Enviando…</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="hint mt-2">PDF, imagem ou DOCX, até 100 MB.</p>
                    </div>
                </div>

                <div class="flex justify-end px-5 sm:px-6 py-4 border-t border-stone-100 bg-stone-50">
                    <button type="button" wire:click="fecharModalDetalhe" class="btn-secondary w-full sm:w-auto">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         Modal: enviar anexo para o paciente
         ══════════════════════════════════════════════════ --}}
    @if ($modalEnviarAnexo && $this->transacaoDetalhe?->paciente)
        @php $paciente = $this->transacaoDetalhe->paciente; @endphp
        <div
            class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center sm:p-4"
            x-data
            x-on:keydown.escape.window="$wire.modalEnviarAnexo = false"
        >
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalEnviarAnexo', false)"></div>

            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-enviar" class="relative bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl w-full sm:max-w-sm">
                <div class="flex items-center justify-between gap-4 px-5 sm:px-6 py-4 border-b border-stone-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <h3 id="titulo-modal-enviar" class="text-lg font-semibold text-stone-900">Enviar documento</h3>
                    </div>
                    <button type="button" wire:click="$set('modalEnviarAnexo', false)" class="btn-ghost -mr-2 w-11 px-0 text-stone-400" aria-label="Fechar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-5 sm:px-6 py-5 space-y-4">
                    {{-- Arquivo --}}
                    <div class="flex items-center gap-3 bg-stone-50 rounded-xl px-4 py-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                        </div>
                        <p class="text-sm text-stone-700 font-medium truncate">{{ $anexoEnviarNome }}</p>
                    </div>

                    {{-- Destinatário --}}
                    <div>
                        <p class="text-xs text-stone-500 mb-1">Para</p>
                        <p class="text-sm text-stone-800 font-medium">{{ $paciente->nome }}</p>
                    </div>

                    {{-- Canais --}}
                    <div>
                        <p class="text-xs text-stone-500 mb-2">Enviar por</p>
                        <div class="space-y-1">
                            {{-- E-mail --}}
                            <label class="flex min-h-[44px] items-center gap-3 cursor-pointer {{ $paciente->email ? '' : 'opacity-50 cursor-not-allowed' }}">
                                <input
                                    type="checkbox"
                                    wire:model="enviarEmail"
                                    {{ $paciente->email ? '' : 'disabled' }}
                                    class="w-5 h-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300"
                                >
                                <div>
                                    <p class="text-sm text-stone-700 font-medium">E-mail</p>
                                    @if ($paciente->email)
                                        <p class="text-xs text-stone-500">{{ $paciente->email }}</p>
                                    @else
                                        <p class="text-xs text-red-600">Cadastre o e-mail do paciente para usar esta opção.</p>
                                    @endif
                                </div>
                            </label>

                            {{-- WhatsApp --}}
                            <label class="flex min-h-[44px] items-center gap-3 cursor-pointer {{ $paciente->telefone ? '' : 'opacity-50 cursor-not-allowed' }}">
                                <input
                                    type="checkbox"
                                    wire:model="enviarWhatsapp"
                                    {{ $paciente->telefone ? '' : 'disabled' }}
                                    class="w-5 h-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300"
                                >
                                <div>
                                    <p class="text-sm text-stone-700 font-medium">WhatsApp</p>
                                    @if ($paciente->telefone)
                                        <p class="text-xs text-stone-500">{{ $paciente->telefone }}</p>
                                    @else
                                        <p class="text-xs text-red-600">Cadastre o telefone do paciente para usar esta opção.</p>
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 px-5 sm:px-6 py-4 border-t border-stone-100 bg-stone-50 rounded-b-none sm:rounded-b-2xl">
                    <button
                        type="button"
                        wire:click="$set('modalEnviarAnexo', false)"
                        class="btn-secondary"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        wire:click="confirmarEnviarAnexo"
                        wire:loading.attr="disabled"
                        wire:target="confirmarEnviarAnexo"
                        class="btn-primary"
                    >
                        <span wire:loading.remove wire:target="confirmarEnviarAnexo">Enviar documento</span>
                        <span wire:loading wire:target="confirmarEnviarAnexo" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Enviando…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
