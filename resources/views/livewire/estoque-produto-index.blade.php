<div>
    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 z-50 sm:max-w-sm pointer-events-none">
            <div class="bg-surface border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm pointer-events-auto">
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
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 z-50 sm:max-w-sm">
            <div class="bg-surface border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    <x-ui.page-header titulo="Produtos" subtitulo="Cadastre seus produtos e controle lotes, validades e frascos abertos.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalEntrada" class="btn-secondary flex-1 sm:flex-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Registrar entrada
            </button>
            <button type="button" wire:click="abrirModalNovoProduto" class="btn-primary flex-1 sm:flex-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Novo produto
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="space-y-4 sm:space-y-6">

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input wire:model.live.debounce.400ms="busca" type="search" aria-label="Buscar produto" placeholder="Buscar produto" class="input pl-10">
            </div>
            <select wire:model.live="filtroCategoria" aria-label="Filtrar por categoria" class="input sm:w-56">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @if ($busca || $filtroCategoria)
                <button type="button" wire:click="$set('busca', ''); $set('filtroCategoria', '')" class="btn-ghost shrink-0">
                    Limpar
                </button>
            @endif
        </div>
    </div>

    {{-- Layout: lista + painel de lotes --}}
    <div class="{{ $produtoSelecionadoId ? 'lg:grid lg:grid-cols-5 lg:gap-6 lg:items-start' : '' }} space-y-4 lg:space-y-0">

        {{-- Lista de produtos --}}
        <div class="{{ $produtoSelecionadoId ? 'lg:col-span-3' : '' }} card overflow-hidden">
            @if ($produtos->isEmpty())
                @if ($busca || $filtroCategoria)
                    <x-ui.empty-state
                        titulo="Nada encontrado com esses filtros"
                        texto="Confira o nome digitado ou escolha outra categoria."
                        icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                        <button type="button" wire:click="$set('busca', ''); $set('filtroCategoria', '')" class="btn-secondary">Limpar filtros</button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state
                        titulo="Nenhum produto cadastrado"
                        texto="Cadastre os produtos que você usa nos atendimentos para controlar saldo, lotes e validade."
                        icone="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9">
                        <button type="button" wire:click="abrirModalNovoProduto" class="btn-primary">Cadastrar produto</button>
                    </x-ui.empty-state>
                @endif
            @else
                @php
                    $situacoes = [];
                    foreach ($produtos as $produto) {
                        $saldo = (float) $produto->available_quantity;
                        $min   = (float) $produto->minimum_stock_quantity;
                        if ($min > 0 && $saldo === 0.0) {
                            $situacoes[$produto->id] = ['Zerado', 'bg-red-50 text-red-700'];
                        } elseif ($min > 0 && $saldo <= $min) {
                            $situacoes[$produto->id] = ['Baixo', 'bg-amber-50 text-amber-700'];
                        } else {
                            $situacoes[$produto->id] = ['Normal', 'bg-emerald-50 text-emerald-700'];
                        }
                    }
                @endphp

                {{-- Desktop: tabela --}}
                <table class="hidden md:table w-full text-sm">
                    <thead class="bg-stone-50">
                        <tr class="text-left text-xs font-medium text-stone-500">
                            <th class="px-5 py-3 font-medium">Produto</th>
                            <th class="hidden px-5 py-3 font-medium xl:table-cell">Categoria</th>
                            <th class="px-5 py-3 font-medium text-right">Saldo</th>
                            <th class="hidden px-5 py-3 font-medium text-right lg:table-cell">Custo/un.</th>
                            <th class="px-5 py-3 font-medium">Situação</th>
                            <th class="px-5 py-3"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($produtos as $produto)
                            @php
                                [$statusLabel, $statusCls] = $situacoes[$produto->id];
                                $isSelected = $produtoSelecionadoId === $produto->id;
                            @endphp
                            <tr wire:key="produto-{{ $produto->id }}"
                                class="group cursor-pointer transition-colors {{ $isSelected ? 'bg-rose-50' : 'hover:bg-stone-50' }}"
                                wire:click="selecionarProduto({{ $produto->id }})">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-stone-900 truncate">{{ $produto->name }}</p>
                                    @if ($produto->beyond_use_hours)
                                        <p class="text-xs text-stone-500">Vale {{ $produto->beyond_use_hours }}h depois de aberto</p>
                                    @endif
                                </td>
                                <td class="hidden px-5 py-3 xl:table-cell">
                                    @if ($produto->category)
                                        <span class="badge border"
                                              style="color: {{ $produto->category->color }}; border-color: {{ $produto->category->color }}33; background-color: {{ $produto->category->color }}14;">
                                            {{ $produto->category->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">
                                    <span class="font-semibold text-stone-900">{{ number_format((float) $produto->available_quantity, 1, ',', '.') }}</span>
                                    <span class="ml-0.5 text-xs text-stone-500">{{ $produto->unit_type->value }}</span>
                                </td>
                                <td class="hidden px-5 py-3 text-right text-stone-600 tabular-nums lg:table-cell">
                                    R$ {{ number_format((float) $produto->unit_cost, 2, ',', '.') }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge {{ $statusCls }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click.stop="abrirModalEntrada({{ $produto->id }})"
                                                class="btn-ghost px-2.5" title="Registrar entrada" aria-label="Registrar entrada de {{ $produto->name }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </button>
                                        <button type="button" wire:click.stop="abrirModalEditarProduto({{ $produto->id }})"
                                                class="btn-ghost px-2.5" title="Editar produto" aria-label="Editar {{ $produto->name }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Celular: cartões --}}
                <ul class="md:hidden divide-y divide-stone-100">
                    @foreach ($produtos as $produto)
                        @php
                            [$statusLabel, $statusCls] = $situacoes[$produto->id];
                            $isSelected = $produtoSelecionadoId === $produto->id;
                        @endphp
                        <li wire:key="produto-m-{{ $produto->id }}" class="{{ $isSelected ? 'bg-rose-50' : '' }}">
                            <button type="button" wire:click="selecionarProduto({{ $produto->id }})" class="block w-full px-4 pt-3.5 pb-2 text-left">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-stone-900">{{ $produto->name }}</p>
                                        <p class="mt-0.5 text-xs text-stone-500">
                                            {{ $produto->category?->name ?? 'Sem categoria' }}
                                            · R$ {{ number_format((float) $produto->unit_cost, 2, ',', '.') }}/un.
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right tabular-nums">
                                        <p class="text-sm font-semibold text-stone-900">{{ number_format((float) $produto->available_quantity, 1, ',', '.') }} <span class="text-xs font-normal text-stone-500">{{ $produto->unit_type->value }}</span></p>
                                        <span class="badge mt-1 {{ $statusCls }}">{{ $statusLabel }}</span>
                                    </div>
                                </div>
                            </button>
                            <div class="flex gap-2 px-4 pb-3.5">
                                <button type="button" wire:click="abrirModalEntrada({{ $produto->id }})" class="btn-secondary flex-1 px-2 text-xs">Registrar entrada</button>
                                <button type="button" wire:click="abrirModalEditarProduto({{ $produto->id }})" class="btn-secondary flex-1 px-2 text-xs">Editar</button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($produtos->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">
                        {{ $produtos->links() }}
                    </div>
                @endif
            @endif
        </div>

        {{-- Painel de lotes --}}
        @if ($produtoSelecionadoId)
        <div class="lg:col-span-2 card overflow-hidden">
            @php $produtoLotes = $produtos->firstWhere('id', $produtoSelecionadoId); @endphp
            <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-3">
                <div class="min-w-0">
                    <h2 class="truncate text-base font-semibold text-stone-900">{{ $produtoLotes?->name ?? 'Lotes' }}</h2>
                    <p class="text-xs text-stone-500">{{ $lotesSelecionados->count() }} {{ $lotesSelecionados->count() === 1 ? 'lote' : 'lotes' }}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button type="button" wire:click="abrirModalEntrada({{ $produtoSelecionadoId }})" class="btn-secondary px-3 text-xs">
                        + Entrada
                    </button>
                    <button type="button" wire:click="selecionarProduto(null)" class="btn-ghost px-2.5" aria-label="Fechar lotes">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            @if ($lotesSelecionados->isEmpty())
                <x-ui.empty-state
                    titulo="Nenhum lote ainda"
                    texto="Registre a primeira entrada de compra para começar a controlar o saldo deste produto."
                    icone="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z">
                    <button type="button" wire:click="abrirModalEntrada({{ $produtoSelecionadoId }})" class="btn-primary">Registrar entrada</button>
                </x-ui.empty-state>
            @else
                <ul class="divide-y divide-stone-100 lg:max-h-[70vh] lg:overflow-y-auto">
                    @foreach ($lotesSelecionados as $batch)
                        @php
                            $isNearExpiry = $batch->isNearExpiry(4);
                            $isExpiredBeyond = $batch->beyond_use_expires_at && $batch->beyond_use_expires_at->isPast();
                            $pctSaldo = $batch->quantity_total > 0
                                ? max(0, min(100, round($batch->quantity_available / $batch->quantity_total * 100)))
                                : 0;
                        @endphp
                        <li wire:key="lote-{{ $batch->id }}" class="px-4 py-3.5 {{ $isExpiredBeyond ? 'bg-red-50' : ($isNearExpiry ? 'bg-amber-50' : '') }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge border {{ $batch->status->badgeClass() }}">{{ $batch->status->label() }}</span>
                                @if ($batch->lot_number)
                                    <span class="text-xs text-stone-500">Lote {{ $batch->lot_number }}</span>
                                @endif
                                @if ($isExpiredBeyond)
                                    <span class="badge bg-red-50 text-red-700">Vencido após abertura</span>
                                @elseif ($isNearExpiry)
                                    <span class="badge bg-amber-50 text-amber-700">Vence em breve</span>
                                @endif
                            </div>

                            <p class="mt-2 text-sm font-semibold text-stone-900 tabular-nums">
                                {{ number_format((float) $batch->quantity_available, 1, ',', '.') }}
                                <span class="font-normal text-stone-500">de {{ number_format((float) $batch->quantity_total, 1, ',', '.') }} {{ $produtoLotes?->unit_type->value }}</span>
                            </p>

                            {{-- Barra de saldo --}}
                            <div class="mt-1.5 h-1.5 w-full rounded-full bg-stone-100">
                                <div class="h-1.5 rounded-full transition-all {{ $pctSaldo > 50 ? 'bg-emerald-500' : ($pctSaldo > 20 ? 'bg-amber-500' : 'bg-red-500') }}"
                                     style="width: {{ $pctSaldo }}%"></div>
                            </div>

                            <div class="mt-2 space-y-0.5 text-xs text-stone-500">
                                <p>Validade do fabricante: <span class="text-stone-700 tabular-nums">{{ $batch->expires_at->format('d/m/Y') }}</span></p>
                                @if ($batch->beyond_use_expires_at)
                                    <p class="font-medium {{ $isExpiredBeyond ? 'text-red-700' : ($isNearExpiry ? 'text-amber-700' : 'text-stone-600') }}">
                                        {{ $batch->beyondUseLabel() }}
                                    </p>
                                @elseif ($batch->opened_at)
                                    <p>Aberto em {{ $batch->opened_at->format('d/m H:i') }}</p>
                                @endif
                            </div>

                            {{-- Ações do lote --}}
                            @if (in_array($batch->status->value, ['open', 'sealed']))
                                <div class="mt-3 flex gap-2">
                                    @if ($batch->status->value === 'sealed')
                                        <button type="button" wire:click="abrirModalAbrirFrasco({{ $batch->id }})" class="btn-secondary flex-1 px-3 text-xs">
                                            Abrir frasco
                                        </button>
                                    @endif
                                    <button type="button" wire:click="abrirModalConsumo({{ $batch->id }})" class="btn-secondary flex-1 px-3 text-xs">
                                        Registrar uso
                                    </button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        @endif

    </div>{{-- fim layout grid --}}
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: PRODUTO (CRIAR / EDITAR)
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalProduto)
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-produto-titulo">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-surface shadow-xl sm:max-w-xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="modal-produto-titulo" class="text-lg font-semibold text-stone-900">{{ $produtoEditandoId ? 'Editar produto' : 'Novo produto' }}</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form wire:submit="salvarProduto" class="space-y-4 overflow-y-auto px-5 py-5 sm:px-6">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="produto-nome" class="label">Nome do produto</label>
                            <input id="produto-nome" type="text" wire:model="formNome" placeholder="Ex.: Toxina botulínica 100 UI" class="input">
                            @error('formNome') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-categoria" class="label">Categoria</label>
                            <select id="produto-categoria" wire:model="formCategoriaId" class="input">
                                <option value="">Selecione</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('formCategoriaId') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-unidade" class="label">Unidade</label>
                            <select id="produto-unidade" wire:model="formUnitType" class="input">
                                @foreach ($unitTypes as $ut)
                                    <option value="{{ $ut->value }}">{{ $ut->label() }}</option>
                                @endforeach
                            </select>
                            @error('formUnitType') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-qtd-pacote" class="label">Quantidade por embalagem</label>
                            <input id="produto-qtd-pacote" type="number" inputmode="decimal" wire:model="formQtdPorPacote" step="0.001" min="0.001" placeholder="Ex.: 100" class="input tabular-nums">
                            @error('formQtdPorPacote') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-custo" class="label">Custo unitário (R$)</label>
                            <input id="produto-custo" type="number" inputmode="decimal" wire:model="formCustoUnitario" step="0.01" min="0" placeholder="Ex.: 8.50" class="input tabular-nums">
                            @error('formCustoUnitario') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-minimo" class="label">Estoque mínimo</label>
                            <input id="produto-minimo" type="number" inputmode="decimal" wire:model="formEstoqueMinimo" step="0.001" min="0" placeholder="Ex.: 2" class="input tabular-nums">
                            <p class="hint">Avisamos você quando o saldo chegar a esse valor.</p>
                            @error('formEstoqueMinimo') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="produto-pos-abertura" class="label">Validade depois de aberto (horas)</label>
                            <input id="produto-pos-abertura" type="number" inputmode="numeric" wire:model="formBeyondUseHours" min="1" max="9999" placeholder="Ex.: 4" class="input tabular-nums">
                            <p class="hint">Ex.: 4 para toxina botulínica. Deixe vazio se não houver limite.</p>
                            @error('formBeyondUseHours') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="formRequerLote" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                            <span class="text-sm text-stone-700">Exigir número de lote</span>
                        </label>
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="formAtivo" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                            <span class="text-sm text-stone-700">Produto ativo</span>
                        </label>
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="salvarProduto">{{ $produtoEditandoId ? 'Salvar alterações' : 'Cadastrar produto' }}</span>
                            <span wire:loading wire:target="salvarProduto">Salvando…</span>
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
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-entrada-titulo">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-surface shadow-xl sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="modal-entrada-titulo" class="text-lg font-semibold text-stone-900">Registrar entrada de compra</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form wire:submit="salvarEntrada" class="space-y-4 overflow-y-auto px-5 py-5 sm:px-6">

                    <div>
                        <label for="entrada-produto" class="label">Produto</label>
                        <select id="entrada-produto" wire:model="entradaProdutoId" class="input">
                            <option value="">Selecione o produto</option>
                            @foreach ($produtos as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('entradaProdutoId') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="entrada-lote" class="label">Número do lote</label>
                            <input id="entrada-lote" type="text" wire:model="entradaLoteNumero" placeholder="Opcional" class="input">
                        </div>
                        <div>
                            <label for="entrada-qtd" class="label">Quantidade</label>
                            <input id="entrada-qtd" type="number" inputmode="decimal" wire:model="entradaQuantidade" step="0.001" min="0.001" placeholder="Ex.: 100" class="input tabular-nums">
                            @error('entradaQuantidade') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="entrada-custo" class="label">Custo total (R$)</label>
                            <input id="entrada-custo" type="number" inputmode="decimal" wire:model="entradaCusto" step="0.01" min="0" placeholder="Ex.: 850.00" class="input tabular-nums">
                            @error('entradaCusto') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="entrada-data" class="label">Data da compra</label>
                            <input id="entrada-data" type="date" wire:model="entradaDataCompra" class="input">
                            @error('entradaDataCompra') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="entrada-validade" class="label">Validade do fabricante</label>
                        <input id="entrada-validade" type="date" wire:model="entradaValidade" class="input">
                        @error('entradaValidade') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="salvarEntrada">Registrar entrada</span>
                            <span wire:loading wire:target="salvarEntrada">Registrando…</span>
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
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-abrir-titulo">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl sm:max-w-sm sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="modal-abrir-titulo" class="text-lg font-semibold text-stone-900">Abrir frasco</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="space-y-3 px-5 py-5 sm:px-6">
                    @if ($batchAbrir)
                        <p class="text-sm text-stone-600">
                            Abrir o frasco do lote <span class="font-semibold text-stone-900">{{ $batchAbrir->lot_number ?? 'sem número' }}</span>,
                            com <span class="font-semibold text-stone-900 tabular-nums">{{ number_format((float) $batchAbrir->quantity_available, 1, ',', '.') }}</span> unidades disponíveis?
                        </p>
                        @php $horas = $batchAbrir->product?->beyond_use_hours; @endphp
                        @if ($horas)
                            <div class="flex gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <p>Depois de aberto, este produto vale <strong>{{ $horas }}h</strong>. O horário de abertura é registrado agora.</p>
                            </div>
                        @else
                            <p class="text-sm text-stone-500">Este produto não tem limite de validade depois de aberto.</p>
                        @endif
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="confirmarAbrirFrasco" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="confirmarAbrirFrasco">Abrir frasco</span>
                        <span wire:loading wire:target="confirmarAbrirFrasco">Abrindo…</span>
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
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-consumo-titulo">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full flex-col rounded-t-2xl bg-surface shadow-xl sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="modal-consumo-titulo" class="text-lg font-semibold text-stone-900">Registrar uso</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <form wire:submit="salvarConsumo" class="space-y-4 overflow-y-auto px-5 py-5 sm:px-6">

                    @if ($batchConsumo)
                        <div class="rounded-xl bg-stone-50 px-3 py-2.5 text-sm text-stone-600">
                            Lote <span class="font-medium text-stone-900">{{ $batchConsumo->lot_number ?? 'sem número' }}</span>
                            · Saldo <span class="font-semibold text-stone-900 tabular-nums">{{ number_format((float) $batchConsumo->quantity_available, 1, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="consumo-qtd" class="label">Quantidade</label>
                            <input id="consumo-qtd" type="number" inputmode="decimal" wire:model="consumoQuantidade" step="0.001" min="0.001" placeholder="Ex.: 20" class="input tabular-nums">
                            @error('consumoQuantidade') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="consumo-motivo" class="label">Motivo</label>
                            <select id="consumo-motivo" wire:model="consumoMotivo" class="input">
                                @foreach ($movementTypes as $mt)
                                    @if ($mt->value !== 'purchase' && $mt->value !== 'discard_expired')
                                        <option value="{{ $mt->value }}">{{ $mt->label() }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="consumo-paciente" class="label">Paciente <span class="font-normal text-stone-500">(opcional)</span></label>
                        <select id="consumo-paciente" wire:model="consumoPacienteId" class="input">
                            <option value="">Sem paciente</option>
                            @foreach ($pacientesSelect as $p)
                                <option value="{{ $p->id }}">{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="consumo-procedimento" class="label">Procedimento <span class="font-normal text-stone-500">(opcional)</span></label>
                        <input id="consumo-procedimento" type="text" wire:model="consumoProcedimento" placeholder="Ex.: Botox na testa" class="input">
                    </div>

                    <div>
                        <label for="consumo-notas" class="label">Observação <span class="font-normal text-stone-500">(opcional)</span></label>
                        <input id="consumo-notas" type="text" wire:model="consumoNotes" placeholder="Ex.: Retoque da semana passada" class="input">
                    </div>

                    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="salvarConsumo">Registrar uso</span>
                            <span wire:loading wire:target="salvarConsumo">Registrando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
