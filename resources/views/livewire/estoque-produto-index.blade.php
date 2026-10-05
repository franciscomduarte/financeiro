<div class="space-y-6">

    {{-- Flash: Sucesso --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Produtos</h1>
            <p class="text-sm text-stone-500 mt-0.5">Cadastro e controle de lotes</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="abrirModalEntrada"
                    class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Entrada de Compra
            </button>
            <button wire:click="abrirModalNovoProduto"
                    class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Novo Produto
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="rounded-2xl border border-stone-100 bg-surface p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.400ms="busca" type="text" placeholder="Buscar produto..."
                       class="w-full rounded-lg border border-stone-200 py-2 pl-9 pr-3 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
            </div>
            <select wire:model.live="filtroCategoria"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100 sm:w-52">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @if ($busca || $filtroCategoria)
                <button wire:click="$set('busca', ''); $set('filtroCategoria', '')"
                        class="text-sm text-emerald-600 hover:text-emerald-700 font-medium whitespace-nowrap">
                    Limpar
                </button>
            @endif
        </div>
    </div>

    {{-- Layout: tabela + painel de lotes --}}
    <div class="{{ $produtoSelecionadoId ? 'lg:grid lg:grid-cols-5 lg:gap-6' : '' }} space-y-4 lg:space-y-0">

        {{-- Tabela de Produtos --}}
        <div class="{{ $produtoSelecionadoId ? 'lg:col-span-3' : '' }} rounded-2xl border border-stone-100 bg-surface shadow-sm overflow-hidden">
            @if ($produtos->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="h-10 w-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-stone-500 font-medium">Nenhum produto encontrado</p>
                    <button wire:click="abrirModalNovoProduto" class="mt-3 text-sm text-emerald-600 hover:underline">Cadastrar produto →</button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100 bg-stone-50/60">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Produto</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 sm:table-cell">Categoria</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Saldo</th>
                                <th class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500 md:table-cell">Custo/un.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-50">
                            @foreach ($produtos as $produto)
                                @php
                                    $saldo = (float) $produto->available_quantity;
                                    $min   = (float) $produto->minimum_stock_quantity;
                                    if ($min > 0 && $saldo === 0.0) {
                                        $statusLabel = 'Crítico';
                                        $statusCls   = 'text-red-700 bg-red-50 border-red-200';
                                    } elseif ($min > 0 && $saldo <= $min) {
                                        $statusLabel = 'Baixo';
                                        $statusCls   = 'text-amber-700 bg-amber-50 border-amber-200';
                                    } else {
                                        $statusLabel = 'OK';
                                        $statusCls   = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                                    }
                                    $isSelected = $produtoSelecionadoId === $produto->id;
                                @endphp
                                <tr class="group hover:bg-stone-50/50 transition-colors {{ $isSelected ? 'bg-emerald-50/30' : '' }} cursor-pointer"
                                    wire:click="selecionarProduto({{ $produto->id }})">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($isSelected)
                                                <svg class="h-3.5 w-3.5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-medium text-stone-800 truncate">{{ $produto->name }}</p>
                                                @if ($produto->beyond_use_hours)
                                                    <p class="text-xs text-amber-500">⏱ {{ $produto->beyond_use_hours }}h pós-abertura</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-3 sm:table-cell">
                                        @if ($produto->category)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                                  style="color: {{ $produto->category->color }}; border-color: {{ $produto->category->color }}30; background-color: {{ $produto->category->color }}15;">
                                                {{ $produto->category->name }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-stone-800">{{ number_format($saldo, 1) }}</span>
                                        <span class="text-xs text-stone-400 ml-0.5">{{ $produto->unit_type->value }}</span>
                                    </td>
                                    <td class="hidden px-4 py-3 text-right text-stone-600 md:table-cell">
                                        R$ {{ number_format((float) $produto->unit_cost, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusCls }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click.stop="abrirModalEntrada({{ $produto->id }})"
                                                    class="rounded-lg p-1.5 text-stone-400 hover:bg-emerald-50 hover:text-emerald-600 transition-colors"
                                                    title="Registrar entrada">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                            <button wire:click.stop="abrirModalEditarProduto({{ $produto->id }})"
                                                    class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors"
                                                    title="Editar produto">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($produtos->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">
                        {{ $produtos->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- Painel de Lotes --}}
        @if ($produtoSelecionadoId)
        <div class="lg:col-span-2 rounded-2xl border border-emerald-200 bg-surface shadow-sm overflow-hidden">
            @php $produtoLotes = $produtos->firstWhere('id', $produtoSelecionadoId); @endphp
            <div class="flex items-center justify-between border-b border-stone-100 bg-emerald-50/50 px-4 py-3.5">
                <div>
                    <h3 class="text-sm font-semibold text-stone-800">{{ $produtoLotes?->name ?? 'Lotes' }}</h3>
                    <p class="text-xs text-stone-500 mt-0.5">{{ $lotesSelecionados->count() }} lote(s)</p>
                </div>
                <div class="flex gap-1.5">
                    <button wire:click="abrirModalEntrada({{ $produtoSelecionadoId }})"
                            class="rounded-lg border border-emerald-200 bg-surface px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 transition-colors">
                        + Entrada
                    </button>
                    <button wire:click="selecionarProduto(null)"
                            class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            @if ($lotesSelecionados->isEmpty())
                <div class="flex flex-col items-center justify-center py-12 text-center text-stone-400">
                    <svg class="h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="text-sm font-medium">Nenhum lote cadastrado</p>
                    <button wire:click="abrirModalEntrada({{ $produtoSelecionadoId }})" class="mt-2 text-xs text-emerald-600 hover:underline">Registrar primeira entrada →</button>
                </div>
            @else
                <div class="divide-y divide-stone-50 max-h-[70vh] overflow-y-auto">
                    @foreach ($lotesSelecionados as $batch)
                        @php
                            $isNearExpiry = $batch->isNearExpiry(4);
                            $isExpiredBeyond = $batch->beyond_use_expires_at && $batch->beyond_use_expires_at->isPast();
                        @endphp
                        <div class="px-4 py-3.5 {{ $isExpiredBeyond ? 'bg-red-50/50' : ($isNearExpiry ? 'bg-amber-50/30' : '') }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $batch->status->badgeClass() }}">
                                            {{ $batch->status->label() }}
                                        </span>
                                        @if ($batch->lot_number)
                                            <span class="text-xs text-stone-500 font-mono">{{ $batch->lot_number }}</span>
                                        @endif
                                        @if ($isExpiredBeyond)
                                            <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">⚠ Beyond-use vencido</span>
                                        @elseif ($isNearExpiry)
                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">⏱ Vencendo</span>
                                        @endif
                                    </div>

                                    <div class="mt-1.5 flex items-center gap-3 text-sm">
                                        <span class="font-semibold text-stone-800">
                                            {{ number_format((float) $batch->quantity_available, 1) }}
                                            / {{ number_format((float) $batch->quantity_total, 1) }}
                                            {{ $produtoLotes?->unit_type->value }}
                                        </span>
                                        @php
                                            $pctSaldo = $batch->quantity_total > 0
                                                ? max(0, min(100, round($batch->quantity_available / $batch->quantity_total * 100)))
                                                : 0;
                                        @endphp
                                    </div>

                                    {{-- Barra de saldo --}}
                                    <div class="mt-1 h-1.5 w-full rounded-full bg-stone-100">
                                        <div class="h-1.5 rounded-full {{ $pctSaldo > 50 ? 'bg-emerald-400' : ($pctSaldo > 20 ? 'bg-amber-400' : 'bg-red-400') }} transition-all"
                                             style="width: {{ $pctSaldo }}%"></div>
                                    </div>

                                    <div class="mt-1.5 text-xs text-stone-400 space-y-0.5">
                                        <p>Validade: <span class="text-stone-600">{{ $batch->expires_at->format('d/m/Y') }}</span></p>
                                        @if ($batch->beyond_use_expires_at)
                                            <p class="text-{{ $isExpiredBeyond ? 'red' : ($isNearExpiry ? 'amber' : 'stone') }}-600 font-medium">
                                                {{ $batch->beyondUseLabel() }}
                                            </p>
                                        @elseif ($batch->opened_at)
                                            <p>Aberto em: {{ $batch->opened_at->format('d/m H:i') }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Ações do lote --}}
                                <div class="flex flex-col gap-1 shrink-0">
                                    @if ($batch->status->value === 'sealed')
                                        <button wire:click="abrirModalAbrirFrasco({{ $batch->id }})"
                                                class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 transition-colors whitespace-nowrap">
                                            Abrir Frasco
                                        </button>
                                    @endif
                                    @if (in_array($batch->status->value, ['open', 'sealed']))
                                        <button wire:click="abrirModalConsumo({{ $batch->id }})"
                                                class="rounded-lg border border-stone-200 px-2.5 py-1.5 text-xs font-medium text-stone-600 hover:bg-stone-50 transition-colors whitespace-nowrap">
                                            Registrar Uso
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

    </div>{{-- fim layout grid --}}

    {{-- ════════════════════════════════════════════════════════════
         MODAL: PRODUTO (CRIAR / EDITAR)
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalProduto)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-xl rounded-2xl bg-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">{{ $produtoEditandoId ? 'Editar Produto' : 'Novo Produto' }}</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="salvarProduto" class="px-6 py-5 space-y-4 max-h-[80vh] overflow-y-auto">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-stone-700 mb-1">Nome do produto</label>
                            <input type="text" wire:model="formNome" placeholder="Ex: Toxina Botulínica Botox 100UI"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('formNome') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Categoria</label>
                            <select wire:model="formCategoriaId"
                                    class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                <option value="">Selecione</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('formCategoriaId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Unidade</label>
                            <select wire:model="formUnitType"
                                    class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                @foreach ($unitTypes as $ut)
                                    <option value="{{ $ut->value }}">{{ $ut->label() }}</option>
                                @endforeach
                            </select>
                            @error('formUnitType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Qtd por embalagem</label>
                            <input type="number" wire:model="formQtdPorPacote" step="0.001" min="0.001" placeholder="100"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('formQtdPorPacote') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Custo unitário (R$)</label>
                            <input type="number" wire:model="formCustoUnitario" step="0.01" min="0" placeholder="8.50"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('formCustoUnitario') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Estoque mínimo</label>
                            <input type="number" wire:model="formEstoqueMinimo" step="0.001" min="0" placeholder="0"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('formEstoqueMinimo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Validade pós-abertura (horas)</label>
                            <input type="number" wire:model="formBeyondUseHours" min="1" max="9999" placeholder="4 (deixe vazio se sem restrição)"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <p class="mt-0.5 text-xs text-stone-400">Ex: 4 para toxina botulínica. Vazio = sem restrição.</p>
                            @error('formBeyondUseHours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="formRequerLote"
                                   class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-stone-700">Controle de lote obrigatório</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="formAtivo"
                                   class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-stone-700">Produto ativo</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 transition-colors">
                            <span wire:loading.remove wire:target="salvarProduto">{{ $produtoEditandoId ? 'Salvar' : 'Cadastrar' }}</span>
                            <span wire:loading wire:target="salvarProduto">Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ENTRADA DE COMPRA
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalEntrada)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">Entrada de Compra</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="salvarEntrada" class="px-6 py-5 space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Produto</label>
                        <select wire:model="entradaProdutoId"
                                class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">Selecione o produto</option>
                            @foreach ($produtos as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('entradaProdutoId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Número do Lote</label>
                            <input type="text" wire:model="entradaLoteNumero" placeholder="Opcional"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Quantidade</label>
                            <input type="number" wire:model="entradaQuantidade" step="0.001" min="0.001" placeholder="100"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('entradaQuantidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Custo total (R$)</label>
                            <input type="number" wire:model="entradaCusto" step="0.01" min="0" placeholder="850.00"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('entradaCusto') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Data da compra</label>
                            <input type="date" wire:model="entradaDataCompra"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('entradaDataCompra') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Validade (fabricante)</label>
                        <input type="date" wire:model="entradaValidade"
                               class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        @error('entradaValidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 transition-colors">
                            <span wire:loading.remove wire:target="salvarEntrada">Registrar Entrada</span>
                            <span wire:loading wire:target="salvarEntrada">Registrando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ABRIR FRASCO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalAbrirFrasco)
        @php $batchAbrir = $lotesSelecionados->firstWhere('id', $abrirFrascoBatchId); @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-sm rounded-2xl bg-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">Abrir Frasco</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-3">
                    @if ($batchAbrir)
                        <p class="text-sm text-stone-600">
                            Abrir frasco <span class="font-semibold">{{ $batchAbrir->lot_number ?? 'sem nº' }}</span>
                            com <span class="font-semibold">{{ number_format((float) $batchAbrir->quantity_available, 1) }}</span> unidades disponíveis?
                        </p>
                        @php $horas = $batchAbrir->product?->beyond_use_hours; @endphp
                        @if ($horas)
                            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5 text-sm text-amber-700">
                                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Este produto vence <strong>{{ $horas }}h</strong> após a abertura.
                                O sistema registrará o horário automaticamente.
                            </div>
                        @else
                            <p class="text-xs text-stone-400">Sem restrição de validade pós-abertura para este produto.</p>
                        @endif
                    @endif
                </div>
                <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4">
                    <button wire:click="fecharModais"
                            class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                    <button wire:click="confirmarAbrirFrasco" wire:loading.attr="disabled"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                        <span wire:loading.remove wire:target="confirmarAbrirFrasco">Confirmar Abertura</span>
                        <span wire:loading wire:target="confirmarAbrirFrasco">Abrindo...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: CONSUMO MANUAL
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalConsumo)
        @php $batchConsumo = $lotesSelecionados->firstWhere('id', $consumoBatchId); @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl bg-surface shadow-xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">Registrar Uso</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="salvarConsumo" class="px-6 py-5 space-y-4">

                    @if ($batchConsumo)
                        <div class="rounded-lg bg-stone-50 px-3 py-2 text-sm text-stone-600">
                            Lote: <span class="font-medium">{{ $batchConsumo->lot_number ?? 'sem nº' }}</span>
                            · Saldo: <span class="font-semibold text-stone-800">{{ number_format((float) $batchConsumo->quantity_available, 1) }}</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Quantidade</label>
                            <input type="number" wire:model="consumoQuantidade" step="0.001" min="0.001"
                                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            @error('consumoQuantidade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-stone-700 mb-1">Motivo</label>
                            <select wire:model="consumoMotivo"
                                    class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                                @foreach ($movementTypes as $mt)
                                    @if ($mt->value !== 'purchase' && $mt->value !== 'discard_expired')
                                        <option value="{{ $mt->value }}">{{ $mt->label() }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Paciente (opcional)</label>
                        <select wire:model="consumoPacienteId"
                                class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                            <option value="">— Sem paciente —</option>
                            @foreach ($pacientesSelect as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Procedimento (opcional)</label>
                        <input type="text" wire:model="consumoProcedimento" placeholder="Ex: Botox Frontal"
                               class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Observação</label>
                        <input type="text" wire:model="consumoNotes"
                               class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 transition-colors">
                            <span wire:loading.remove wire:target="salvarConsumo">Registrar</span>
                            <span wire:loading wire:target="salvarConsumo">Registrando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
