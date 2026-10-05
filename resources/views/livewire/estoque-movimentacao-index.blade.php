<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Movimentações</h1>
            <p class="text-sm text-stone-500 mt-0.5">Histórico de entradas, saídas e consumos</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="rounded-2xl border border-stone-100 bg-surface p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.400ms="busca" type="text" placeholder="Buscar paciente, produto..."
                       class="w-full rounded-lg border border-stone-200 py-2 pl-9 pr-3 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
            </div>

            <select wire:model.live="filtroProduto"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <option value="">Todos os produtos</option>
                @foreach ($produtos as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filtroTipo"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                <option value="">Todos os tipos</option>
                @foreach ($tiposMovimento as $tipo)
                    <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-2">
                <input type="month" wire:model.live="filtroMes"
                       class="flex-1 rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                @if ($busca || $filtroProduto || $filtroTipo)
                    <button wire:click="$set('busca', ''); $set('filtroProduto', ''); $set('filtroTipo', '')"
                            class="text-sm text-emerald-600 hover:text-emerald-700 font-medium whitespace-nowrap">
                        Limpar
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="rounded-2xl border border-stone-100 bg-surface shadow-sm overflow-hidden">
        @if ($movimentacoes->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="h-10 w-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-stone-500 font-medium">Nenhuma movimentação encontrada</p>
                <p class="text-xs text-stone-400 mt-1">Ajuste os filtros ou registre entradas/saídas de estoque.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 bg-stone-50/60">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Produto · Lote</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Tipo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Qtd</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 sm:table-cell">Paciente</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 md:table-cell">Procedimento</th>
                            <th class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500 lg:table-cell">Custo/un.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach ($movimentacoes as $mov)
                            @php
                                $isEntry = $mov->type->isEntry();
                            @endphp
                            <tr class="hover:bg-stone-50/40 transition-colors">
                                <td class="px-4 py-3 text-stone-500 whitespace-nowrap">
                                    <p class="text-xs">{{ $mov->created_at->format('d/m/Y') }}</p>
                                    <p class="text-xs text-stone-400">{{ $mov->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-800">{{ $mov->product?->name ?? '—' }}</p>
                                    @if ($mov->batch?->lot_number)
                                        <p class="text-xs text-stone-400 font-mono">Lote {{ $mov->batch->lot_number }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $mov->type->badgeClass() }}">
                                        {{ $mov->type->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ $isEntry ? 'text-emerald-700' : 'text-red-600' }}">
                                    {{ $isEntry ? '+' : '−' }}{{ number_format(abs((float) $mov->quantity), 3) }}
                                    <span class="text-xs font-normal text-stone-400 ml-0.5">{{ $mov->product?->unit_type?->value }}</span>
                                </td>
                                <td class="hidden px-4 py-3 sm:table-cell text-stone-600">
                                    {{ $mov->paciente?->nome ?? '—' }}
                                </td>
                                <td class="hidden px-4 py-3 md:table-cell text-stone-500 text-xs">
                                    {{ $mov->procedure_name ?? ($mov->notes ? Str::limit($mov->notes, 40) : '—') }}
                                </td>
                                <td class="hidden px-4 py-3 lg:table-cell text-right text-stone-500">
                                    @if ($mov->unit_cost_at_time)
                                        R$ {{ number_format((float) $mov->unit_cost_at_time, 2, ',', '.') }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($movimentacoes->hasPages())
                <div class="border-t border-stone-100 px-4 py-3">
                    {{ $movimentacoes->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
