<div>
    <x-ui.page-header titulo="Movimentações" subtitulo="Acompanhe todas as entradas, saídas e usos em atendimentos.">
        <x-slot:acoes>
            <a href="{{ route('estoque.produtos') }}" class="btn-secondary w-full sm:w-auto">Ir para produtos</a>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- Filtros --}}
    <div class="mb-4 card p-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input wire:model.live.debounce.400ms="busca" type="search" aria-label="Buscar movimentação"
                       placeholder="Buscar paciente ou produto" class="input pl-10">
            </div>

            <select wire:model.live="filtroProduto" aria-label="Filtrar por produto" class="input">
                <option value="">Todos os produtos</option>
                @foreach ($produtos as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filtroTipo" aria-label="Filtrar por tipo" class="input">
                <option value="">Todos os tipos</option>
                @foreach ($tiposMovimento as $tipo)
                    <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-2">
                <input type="month" wire:model.live="filtroMes" aria-label="Mês" class="input flex-1 min-w-0">
                @if ($busca || $filtroProduto || $filtroTipo)
                    <button type="button" wire:click="$set('busca', ''); $set('filtroProduto', ''); $set('filtroTipo', '')"
                            class="btn-ghost shrink-0 px-3">
                        Limpar
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="card overflow-hidden">
        @if ($movimentacoes->isEmpty())
            @if ($busca || $filtroProduto || $filtroTipo)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Tente outro produto, outro tipo ou mude o mês."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button type="button" wire:click="$set('busca', ''); $set('filtroProduto', ''); $set('filtroTipo', '')" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhuma movimentação neste mês"
                    texto="Entradas de compra, saídas manuais e usos em atendimentos aparecem aqui. Escolha outro mês ou registre uma entrada."
                    icone="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5">
                    <a href="{{ route('estoque.produtos') }}" class="btn-primary">Registrar entrada</a>
                </x-ui.empty-state>
            @endif
        @else
            {{-- Desktop: tabela --}}
            <table class="hidden md:table w-full text-sm">
                <thead class="bg-stone-50">
                    <tr class="text-left text-xs font-medium text-stone-500">
                        <th class="px-5 py-3 font-medium">Data</th>
                        <th class="px-5 py-3 font-medium">Produto</th>
                        <th class="px-5 py-3 font-medium">Tipo</th>
                        <th class="px-5 py-3 font-medium text-right">Quantidade</th>
                        <th class="px-5 py-3 font-medium">Paciente</th>
                        <th class="hidden px-5 py-3 font-medium lg:table-cell">Procedimento</th>
                        <th class="hidden px-5 py-3 font-medium text-right lg:table-cell">Custo/un.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($movimentacoes as $mov)
                        @php
                            $isEntry = $mov->type->isEntry();
                        @endphp
                        <tr wire:key="mov-{{ $mov->id }}" class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3 whitespace-nowrap tabular-nums">
                                <p class="text-stone-700">{{ $mov->created_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-stone-500">{{ $mov->created_at->format('H:i') }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-stone-900">{{ $mov->product?->name ?? '—' }}</p>
                                @if ($mov->batch?->lot_number)
                                    <p class="text-xs text-stone-500">Lote {{ $mov->batch->lot_number }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="badge border {{ $mov->type->badgeClass() }}">{{ $mov->type->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold whitespace-nowrap tabular-nums {{ $isEntry ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $isEntry ? '+' : '−' }}{{ number_format(abs((float) $mov->quantity), 3, ',', '.') }}
                                <span class="ml-0.5 text-xs font-normal text-stone-500">{{ $mov->product?->unit_type?->value }}</span>
                            </td>
                            <td class="px-5 py-3 text-stone-600">{{ $mov->paciente?->nome ?? '—' }}</td>
                            <td class="hidden px-5 py-3 lg:table-cell text-xs text-stone-500">
                                {{ $mov->procedure_name ?? ($mov->notes ? Str::limit($mov->notes, 40) : '—') }}
                            </td>
                            <td class="hidden px-5 py-3 lg:table-cell text-right text-stone-600 tabular-nums">
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

            {{-- Celular: cartões --}}
            <ul class="md:hidden divide-y divide-stone-100">
                @foreach ($movimentacoes as $mov)
                    @php
                        $isEntry = $mov->type->isEntry();
                    @endphp
                    <li wire:key="mov-m-{{ $mov->id }}" class="px-4 py-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $mov->product?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-xs text-stone-500 tabular-nums">
                                    {{ $mov->created_at->format('d/m/Y H:i') }}
                                    @if ($mov->batch?->lot_number) · Lote {{ $mov->batch->lot_number }} @endif
                                </p>
                            </div>
                            <p class="shrink-0 text-right text-sm font-semibold tabular-nums {{ $isEntry ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $isEntry ? '+' : '−' }}{{ number_format(abs((float) $mov->quantity), 3, ',', '.') }}
                                <span class="text-xs font-normal text-stone-500">{{ $mov->product?->unit_type?->value }}</span>
                            </p>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-stone-500">
                            <span class="badge border {{ $mov->type->badgeClass() }}">{{ $mov->type->label() }}</span>
                            @if ($mov->paciente?->nome)
                                <span class="truncate">{{ $mov->paciente->nome }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($movimentacoes->hasPages())
                <div class="border-t border-stone-100 px-4 py-3">
                    {{ $movimentacoes->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
