<div>

    {{-- ─── Flash Messages ──────────────────────────────── --}}
    @if ($flashSucesso)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 4000)"
            x-transition:leave="transition duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="fixed top-4 right-4 z-50 max-w-sm"
        >
            <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
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
            class="fixed top-4 right-4 z-50 max-w-sm"
        >
            <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- ─── Page header ─────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Transações</h1>
            <p class="text-sm text-stone-500 mt-0.5">Entradas e saídas financeiras da clínica</p>
        </div>
        <button
            wire:click="abrirModalCriar"
            class="flex items-center gap-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm shadow-rose-200 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Nova Transação
        </button>
    </div>

    {{-- ─── Stats Cards ──────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        {{-- Entradas --}}
        <div class="bg-white rounded-2xl border border-stone-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-stone-500 uppercase tracking-widest">Entradas</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-700 tabular-nums">
                R$&nbsp;{{ number_format($totalEntradas, 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-400 mt-1">receitas não canceladas</p>
        </div>

        {{-- Saídas --}}
        <div class="bg-white rounded-2xl border border-stone-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-stone-500 uppercase tracking-widest">Saídas</span>
                <div class="w-8 h-8 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600 tabular-nums">
                R$&nbsp;{{ number_format($totalSaidas, 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-400 mt-1">despesas não canceladas</p>
        </div>

        {{-- Saldo --}}
        <div class="bg-white rounded-2xl border border-stone-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-stone-500 uppercase tracking-widest">Saldo</span>
                <div class="w-8 h-8 rounded-xl {{ $saldo >= 0 ? 'bg-blue-50' : 'bg-orange-50' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $saldo >= 0 ? 'text-blue-600' : 'text-orange-500' }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold {{ $saldo >= 0 ? 'text-blue-700' : 'text-orange-600' }} tabular-nums">
                R$&nbsp;{{ number_format(abs($saldo), 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-400 mt-1">{{ $saldo >= 0 ? 'resultado positivo' : 'resultado negativo' }}</p>
        </div>

    </div>

    {{-- ─── Filters ──────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm mb-4">
        <div class="px-4 py-3 flex items-center gap-2 border-b border-stone-50">
            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
            </svg>
            <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Filtros</span>
            @if ($filtroTipo || $filtroFase || $filtroStatus || $filtroCategoria || $periodoInicio || $periodoFim)
                <button
                    wire:click="$set('filtroTipo',''); $set('filtroFase',''); $set('filtroStatus',''); $set('filtroCategoria',''); $set('periodoInicio',''); $set('periodoFim','')"
                    class="ml-auto text-xs text-rose-600 hover:text-rose-700 font-medium flex items-center gap-1"
                >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Limpar
                </button>
            @endif
        </div>
        <div class="px-4 py-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">Tipo</label>
                <select wire:model.live="filtroTipo" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white text-stone-700">
                    <option value="">Todos</option>
                    @foreach ($tiposEnum as $tipo)
                        <option value="{{ $tipo->value }}">{{ ucfirst($tipo->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">Fase</label>
                <select wire:model.live="filtroFase" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white text-stone-700">
                    <option value="">Todas</option>
                    @foreach ($fasesEnum as $fase)
                        <option value="{{ $fase->value }}">{{ ucfirst($fase->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">Status</label>
                <select wire:model.live="filtroStatus" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 bg-white text-stone-700">
                    <option value="">Todos</option>
                    @foreach ($statusEnum as $st)
                        <option value="{{ $st->value }}">{{ ucfirst($st->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">Categoria</label>
                <input type="text" wire:model.live.debounce.400ms="filtroCategoria" placeholder="Filtrar..." class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 text-stone-700 placeholder:text-stone-300">
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">De</label>
                <input type="date" wire:model.live="periodoInicio" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 text-stone-700">
            </div>
            <div>
                <label class="block text-xs font-medium text-stone-500 mb-1.5">Até</label>
                <input type="date" wire:model.live="periodoFim" class="w-full border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300 text-stone-700">
            </div>
        </div>
    </div>

    {{-- ─── Table ────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-stone-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50/80">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider whitespace-nowrap">Data</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider whitespace-nowrap">Tipo</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider hidden md:table-cell whitespace-nowrap">Fase</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider hidden sm:table-cell whitespace-nowrap">Categoria</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider">Descrição</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider whitespace-nowrap">Valor</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wider hidden lg:table-cell whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @forelse ($transacoes as $transacao)
                        <tr class="hover:bg-stone-50/60 transition-colors group">
                            <td class="px-4 py-3 text-stone-500 whitespace-nowrap text-xs tabular-nums">
                                {{ $transacao->data_competencia?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($transacao->tipo->value === 'entrada')
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full text-xs font-medium">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                        Entrada
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-100 px-2 py-0.5 rounded-full text-xs font-medium">
                                        <span class="w-1 h-1 rounded-full bg-red-500"></span>
                                        Saída
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap">
                                <span class="bg-stone-100 text-stone-600 px-2 py-0.5 rounded-md text-xs">
                                    {{ ucfirst($transacao->fase->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-stone-600 hidden sm:table-cell text-xs max-w-[120px]">
                                <span class="truncate block">{{ $transacao->categoria }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-[220px]">
                                <p class="text-stone-800 font-medium truncate text-sm">{{ $transacao->descricao }}</p>
                                @if ($transacao->paciente)
                                    <p class="text-xs text-stone-400 truncate mt-0.5">{{ $transacao->paciente->nome }}</p>
                                @elseif ($transacao->cliente)
                                    <p class="text-xs text-stone-400 truncate mt-0.5">{{ $transacao->cliente }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap tabular-nums
                                {{ $transacao->tipo->value === 'entrada' ? 'text-emerald-700' : 'text-red-600' }}">
                                {{ $transacao->tipo->value === 'saida' ? '−' : '+' }}&nbsp;R$&nbsp;{{ number_format((float)$transacao->valor_bruto, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap">
                                @if ($transacao->status->value === 'pago')
                                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full text-xs">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500"></span>Pago
                                    </span>
                                @elseif ($transacao->status->value === 'pendente')
                                    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded-full text-xs">
                                        <span class="w-1 h-1 rounded-full bg-amber-400"></span>Pendente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-stone-100 text-stone-500 border border-stone-200 px-2 py-0.5 rounded-full text-xs">
                                        <span class="w-1 h-1 rounded-full bg-stone-400"></span>Cancelado
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        wire:click="abrirModalDetalhe('{{ $transacao->id }}')"
                                        class="p-1.5 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                        title="Ver detalhes"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="abrirModalEditar('{{ $transacao->id }}')"
                                        class="p-1.5 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition-colors"
                                        title="Editar"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                    @if ($transacao->status->value !== 'cancelado')
                                        <button
                                            wire:click="cancelarTransacao('{{ $transacao->id }}')"
                                            wire:confirm="Cancelar esta transação?"
                                            class="p-1.5 text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Cancelar"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-stone-400">
                                    <div class="w-12 h-12 rounded-2xl bg-stone-100 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-stone-600">Nenhuma transação encontrada</p>
                                        <p class="text-xs text-stone-400 mt-0.5">Tente ajustar os filtros ou crie uma nova transação</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($transacoes->hasPages())
            <div class="px-4 py-3 border-t border-stone-100 bg-stone-50/50">
                {{ $transacoes->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: CRIAR TRANSAÇÃO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="fecharModalCriar">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900">Nova Transação</h2>
                        <p class="text-xs text-stone-400 mt-0.5">Preencha os dados da transação</p>
                    </div>
                    <button wire:click="fecharModalCriar" class="p-1.5 text-stone-400 hover:text-stone-600 rounded-lg hover:bg-stone-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    @include('livewire.partials.transacao-form')
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-stone-100 bg-stone-50/50">
                    <button wire:click="fecharModalCriar" class="px-4 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 rounded-lg text-sm font-medium transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="salvarNova" wire:loading.attr="disabled" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold transition-colors disabled:opacity-60 shadow-sm shadow-rose-200">
                        <span wire:loading.remove wire:target="salvarNova">Criar Transação</span>
                        <span wire:loading wire:target="salvarNova">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: EDITAR TRANSAÇÃO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalEditar)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="fecharModalEditar">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900">Editar Transação</h2>
                        <p class="text-xs text-stone-400 mt-0.5">Atualize os dados desta transação</p>
                    </div>
                    <button wire:click="fecharModalEditar" class="p-1.5 text-stone-400 hover:text-stone-600 rounded-lg hover:bg-stone-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    @include('livewire.partials.transacao-form')
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-stone-100 bg-stone-50/50">
                    <button wire:click="fecharModalEditar" class="px-4 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 rounded-lg text-sm font-medium transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="salvarEdicao" wire:loading.attr="disabled" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold transition-colors disabled:opacity-60 shadow-sm shadow-rose-200">
                        <span wire:loading.remove wire:target="salvarEdicao">Salvar Alterações</span>
                        <span wire:loading wire:target="salvarEdicao">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: DETALHES DA TRANSAÇÃO
    ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $this->transacaoDetalhe)
        @php $t = $this->transacaoDetalhe; @endphp
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="fecharModalDetalhe">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">

                {{-- Header --}}
                <div class="flex items-start justify-between px-6 py-4 border-b border-stone-100">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900">{{ $t->descricao }}</h2>
                        <div class="flex items-center gap-2 mt-1.5">
                            @if ($t->tipo->value === 'entrada')
                                <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full text-xs font-medium">Entrada</span>
                            @else
                                <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-100 px-2 py-0.5 rounded-full text-xs font-medium">Saída</span>
                            @endif
                            @if ($t->status->value === 'pago')
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded-full text-xs">Pago</span>
                            @elseif ($t->status->value === 'pendente')
                                <span class="bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded-full text-xs">Pendente</span>
                            @else
                                <span class="bg-stone-100 text-stone-500 border border-stone-200 px-2 py-0.5 rounded-full text-xs">Cancelado</span>
                            @endif
                            <span class="bg-stone-100 text-stone-600 px-2 py-0.5 rounded-full text-xs">{{ ucfirst($t->fase->value) }}</span>
                        </div>
                    </div>
                    <button wire:click="fecharModalDetalhe" class="p-1.5 text-stone-400 hover:text-stone-600 rounded-lg hover:bg-stone-100 transition-colors shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">

                    {{-- Valor highlight --}}
                    <div class="bg-stone-50 rounded-xl p-4">
                        <div class="flex items-end justify-between mb-3">
                            <span class="text-xs font-semibold text-stone-500 uppercase tracking-wider">Composição do Valor</span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">Valor Bruto</span>
                                <span class="font-medium text-stone-800 tabular-nums">R$ {{ number_format((float)$t->valor_bruto, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">Taxa Operacional</span>
                                <span class="text-red-600 tabular-nums">− R$ {{ number_format((float)$t->taxa_operacional, 2, ',', '.') }}</span>
                            </div>
                            @if ((float)$t->imposto_estimado > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-500">Imposto Estimado (6%)</span>
                                <span class="text-red-600 tabular-nums">− R$ {{ number_format((float)$t->imposto_estimado, 2, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-sm pt-2.5 border-t border-stone-200 font-bold">
                                <span class="text-stone-700">Valor Líquido</span>
                                <span class="text-emerald-700 tabular-nums text-base">R$ {{ number_format((float)$t->valor_liquido, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Detalhes em grid --}}
                    <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Categoria</p>
                            <p class="text-sm text-stone-700 font-medium">{{ $t->categoria }}</p>
                        </div>
                        @if ($t->paciente)
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Paciente</p>
                            <p class="text-sm text-stone-700 font-medium">{{ $t->paciente->nome }}</p>
                        </div>
                        @elseif ($t->cliente)
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Cliente</p>
                            <p class="text-sm text-stone-700 font-medium">{{ $t->cliente }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Data de Competência</p>
                            <p class="text-sm text-stone-700">{{ $t->data_competencia?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Data de Pagamento</p>
                            <p class="text-sm text-stone-700">{{ $t->data_pagamento?->format('d/m/Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Forma de Pagamento</p>
                            <p class="text-sm text-stone-700">{{ str_replace('_', ' ', ucfirst($t->forma_pagamento->value)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-1">Recorrência</p>
                            <p class="text-sm text-stone-700">{{ ucfirst($t->recorrencia->value) }}</p>
                        </div>
                    </div>

                    @if ($t->observacoes)
                        <div>
                            <p class="text-xs font-medium text-stone-400 mb-2">Observações</p>
                            <p class="text-sm text-stone-700 bg-stone-50 rounded-xl p-3.5 leading-relaxed">{{ $t->observacoes }}</p>
                        </div>
                    @endif

                    {{-- Anexos --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">Anexos</p>

                        @if ($t->anexos->count() > 0)
                            <div class="space-y-2 mb-4">
                                @foreach ($t->anexos as $anexo)
                                    <div class="flex items-center justify-between bg-stone-50 rounded-xl px-4 py-2.5 group">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm text-stone-700 font-medium truncate">{{ $anexo->nome_arquivo }}</p>
                                                <p class="text-xs text-stone-400">{{ ucfirst($anexo->tipo->value) }} · {{ number_format($anexo->tamanho_bytes / 1024, 1) }} KB</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0 ml-2">
                                            <a
                                                href="{{ route('api.v1.anexos.download', $anexo->id) }}"
                                                target="_blank"
                                                class="p-1.5 text-stone-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                                                title="Visualizar"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </a>
                                            @if ($t->paciente_id)
                                                <button
                                                    wire:click="abrirModalEnviarAnexo('{{ $anexo->id }}')"
                                                    class="p-1.5 text-stone-300 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                    title="Enviar para paciente"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <button
                                                wire:click="removerAnexo('{{ $anexo->id }}')"
                                                wire:confirm="Remover este anexo?"
                                                class="p-1.5 text-stone-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-stone-400 mb-4">Nenhum anexo ainda.</p>
                        @endif

                        {{-- Upload buttons --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <input type="file" wire:model="arquivoBoleto" accept=".pdf,.jpg,.jpeg,.png,.docx" id="boleto-upload" class="hidden">
                                <label for="boleto-upload" class="flex items-center justify-center gap-2 cursor-pointer bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-xl text-xs font-medium transition-colors w-full">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                    </svg>
                                    {{ $arquivoBoleto ? $arquivoBoleto->getClientOriginalName() : 'Nota Fiscal' }}
                                </label>
                                @if ($arquivoBoleto)
                                    <button wire:click="uploadBoleto" wire:loading.attr="disabled" class="w-full bg-rose-600 hover:bg-rose-700 text-white px-3 py-2 rounded-xl text-xs font-semibold transition-colors disabled:opacity-60">
                                        <span wire:loading.remove wire:target="uploadBoleto">Anexar Nota Fiscal</span>
                                        <span wire:loading wire:target="uploadBoleto">Enviando...</span>
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <input type="file" wire:model="arquivoComprovante" accept=".pdf,.jpg,.jpeg,.png,.docx" id="comprovante-upload" class="hidden">
                                <label for="comprovante-upload" class="flex items-center justify-center gap-2 cursor-pointer bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-xl text-xs font-medium transition-colors w-full">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $arquivoComprovante ? $arquivoComprovante->getClientOriginalName() : 'Comprovante' }}
                                </label>
                                @if ($arquivoComprovante)
                                    <button wire:click="uploadComprovante" wire:loading.attr="disabled" class="w-full bg-rose-600 hover:bg-rose-700 text-white px-3 py-2 rounded-xl text-xs font-semibold transition-colors disabled:opacity-60">
                                        <span wire:loading.remove wire:target="uploadComprovante">Anexar Comprovante</span>
                                        <span wire:loading wire:target="uploadComprovante">Enviando...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end px-6 py-4 border-t border-stone-100 bg-stone-50/50">
                    <button wire:click="fecharModalDetalhe" class="px-4 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 rounded-lg text-sm font-medium transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         Modal: Enviar Anexo para Paciente
         ══════════════════════════════════════════════════ --}}
    @if ($modalEnviarAnexo && $this->transacaoDetalhe?->paciente)
        @php $paciente = $this->transacaoDetalhe->paciente; @endphp
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.modalEnviarAnexo = false"
        >
            <div class="absolute inset-0 bg-stone-900/40 backdrop-blur-sm" wire:click="$set('modalEnviarAnexo', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm">
                <div class="flex items-center justify-between px-6 py-4 border-b border-stone-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-stone-800">Enviar Documento</h3>
                    </div>
                    <button wire:click="$set('modalEnviarAnexo', false)" class="text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    {{-- Arquivo --}}
                    <div class="flex items-center gap-3 bg-stone-50 rounded-xl px-4 py-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                            </svg>
                        </div>
                        <p class="text-sm text-stone-700 font-medium truncate">{{ $anexoEnviarNome }}</p>
                    </div>

                    {{-- Destinatário --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">Destinatário</p>
                        <p class="text-sm text-stone-800 font-medium">{{ $paciente->nome }}</p>
                    </div>

                    {{-- Canais --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">Enviar via</p>
                        <div class="space-y-2.5">
                            {{-- Email --}}
                            <label class="flex items-center gap-3 cursor-pointer {{ $paciente->email ? '' : 'opacity-50 cursor-not-allowed' }}">
                                <input
                                    type="checkbox"
                                    wire:model="enviarEmail"
                                    {{ $paciente->email ? '' : 'disabled' }}
                                    class="w-4 h-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                                >
                                <div>
                                    <p class="text-sm text-stone-700 font-medium">E-mail</p>
                                    @if ($paciente->email)
                                        <p class="text-xs text-stone-400">{{ $paciente->email }}</p>
                                    @else
                                        <p class="text-xs text-red-400">Paciente sem e-mail cadastrado</p>
                                    @endif
                                </div>
                            </label>

                            {{-- WhatsApp --}}
                            <label class="flex items-center gap-3 cursor-pointer {{ $paciente->telefone ? '' : 'opacity-50 cursor-not-allowed' }}">
                                <input
                                    type="checkbox"
                                    wire:model="enviarWhatsapp"
                                    {{ $paciente->telefone ? '' : 'disabled' }}
                                    class="w-4 h-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                                >
                                <div>
                                    <p class="text-sm text-stone-700 font-medium">WhatsApp</p>
                                    @if ($paciente->telefone)
                                        <p class="text-xs text-stone-400">{{ $paciente->telefone }}</p>
                                    @else
                                        <p class="text-xs text-red-400">Paciente sem telefone cadastrado</p>
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-stone-100 bg-stone-50/50">
                    <button
                        wire:click="$set('modalEnviarAnexo', false)"
                        class="px-4 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 rounded-lg text-sm font-medium transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        wire:click="confirmarEnviarAnexo"
                        wire:loading.attr="disabled"
                        wire:target="confirmarEnviarAnexo"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold transition-colors disabled:opacity-60 flex items-center gap-2"
                    >
                        <span wire:loading.remove wire:target="confirmarEnviarAnexo">Enviar</span>
                        <span wire:loading wire:target="confirmarEnviarAnexo" class="flex items-center gap-1.5">
                            <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Enviando...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
