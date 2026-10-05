<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Estoque</h1>
            <p class="text-sm text-stone-500 mt-0.5">Visão geral · {{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('estoque.produtos') }}"
               class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Gerenciar Produtos
            </a>
        </div>
    </div>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium text-stone-500">Produtos Ativos</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $totalProdutos }}</p>
            <p class="mt-0.5 text-xs text-stone-400">cadastrados</p>
        </div>
        <div class="rounded-xl border border-{{ $produtosAbaixoMinimo->isNotEmpty() ? 'red' : 'stone' }}-100 bg-{{ $produtosAbaixoMinimo->isNotEmpty() ? 'red-50/50' : 'white' }} p-4 shadow-sm">
            <p class="text-xs font-medium text-{{ $produtosAbaixoMinimo->isNotEmpty() ? 'red-600' : 'stone-500' }}">Estoque Baixo</p>
            <p class="mt-1 text-2xl font-bold text-{{ $produtosAbaixoMinimo->isNotEmpty() ? 'red-700' : 'stone-800' }}">{{ $produtosAbaixoMinimo->count() }}</p>
            <p class="mt-0.5 text-xs text-{{ $produtosAbaixoMinimo->isNotEmpty() ? 'red-400' : 'stone-400' }}">abaixo do mínimo</p>
        </div>
        <div class="rounded-xl border border-{{ $frascosVencidos->isNotEmpty() ? 'red' : ($frascosVencendo->isNotEmpty() ? 'amber' : 'stone') }}-100 bg-{{ $frascosVencidos->isNotEmpty() ? 'red-50/50' : ($frascosVencendo->isNotEmpty() ? 'amber-50/50' : 'white') }} p-4 shadow-sm">
            <p class="text-xs font-medium text-{{ $frascosVencidos->isNotEmpty() ? 'red-600' : ($frascosVencendo->isNotEmpty() ? 'amber-600' : 'stone-500') }}">Frascos Abertos</p>
            <p class="mt-1 text-2xl font-bold text-{{ $frascosVencidos->isNotEmpty() ? 'red-700' : ($frascosVencendo->isNotEmpty() ? 'amber-700' : 'stone-800') }}">{{ $frascosVencendo24h->count() }}</p>
            <p class="mt-0.5 text-xs text-{{ $frascosVencidos->isNotEmpty() ? 'red-400' : ($frascosVencendo->isNotEmpty() ? 'amber-400' : 'stone-400') }}">vencendo em 24h</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-sm col-span-2 sm:col-span-1">
            <p class="text-xs font-medium text-emerald-600">Valor em Estoque</p>
            <p class="mt-1 text-xl font-bold text-emerald-700">R$ {{ number_format((float) $valorTotalStock, 2, ',', '.') }}</p>
            <p class="mt-0.5 text-xs text-emerald-400">estimado</p>
        </div>
    </div>

    {{-- Alertas prioritários --}}
    @if ($frascosVencidos->isNotEmpty())
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
            <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h2 class="text-sm font-semibold text-red-800">{{ $frascosVencidos->count() }} frasco(s) aberto(s) vencido(s) — descarte necessário</h2>
        </div>
        <div class="space-y-1.5">
            @foreach ($frascosVencidos as $batch)
            <div class="flex items-center justify-between rounded-lg bg-surface border border-red-100 px-3 py-2">
                <div class="text-sm">
                    <span class="font-medium text-stone-800">{{ $batch->product?->name ?? '—' }}</span>
                    @if ($batch->lot_number)
                        <span class="text-stone-400 ml-1.5">· Lote {{ $batch->lot_number }}</span>
                    @endif
                </div>
                <div class="text-right text-xs text-red-600 font-medium">
                    Venceu {{ $batch->beyond_use_expires_at?->diffForHumans() }}
                </div>
            </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-red-500">Execute <code class="bg-red-100 px-1 rounded">php artisan stock:check-expired-batches</code> ou aguarde o agendamento automático.</p>
    </div>
    @endif

    @if ($frascosVencendo->isNotEmpty())
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
        <div class="flex items-center gap-2 mb-3">
            <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <h2 class="text-sm font-semibold text-amber-800">{{ $frascosVencendo->count() }} frasco(s) aberto(s) vencendo nas próximas 4h</h2>
        </div>
        <div class="space-y-1.5">
            @foreach ($frascosVencendo as $batch)
            <div class="flex items-center justify-between rounded-lg bg-surface border border-amber-100 px-3 py-2">
                <div class="text-sm">
                    <span class="font-medium text-stone-800">{{ $batch->product?->name ?? '—' }}</span>
                    <span class="text-stone-500 ml-1.5">{{ number_format((float) $batch->quantity_available, 1) }} {{ $batch->product?->unit_type?->value }}</span>
                    @if ($batch->lot_number)
                        <span class="text-stone-400 ml-1.5">· Lote {{ $batch->lot_number }}</span>
                    @endif
                </div>
                <div class="text-right text-xs font-medium text-amber-700">
                    {{ $batch->beyondUseLabel() }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if ($produtosAbaixoMinimo->isNotEmpty())
    <div class="rounded-2xl border border-stone-100 bg-surface shadow-sm overflow-hidden">
        <div class="flex items-center gap-2 border-b border-stone-100 px-5 py-3.5">
            <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            <h2 class="text-sm font-semibold text-stone-700">Produtos abaixo do estoque mínimo</h2>
        </div>
        <div class="divide-y divide-stone-50">
            @foreach ($produtosAbaixoMinimo as $product)
            @php
                $pct = $product->minimum_stock_quantity > 0
                    ? min(100, round($product->available_quantity / $product->minimum_stock_quantity * 100))
                    : 100;
                $color = $pct < 25 ? 'red' : 'amber';
            @endphp
            <div class="flex items-center gap-4 px-5 py-3">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-stone-800">{{ $product->name }}</span>
                        @if ($product->category)
                            <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-medium"
                                  style="color: {{ $product->category->color }}; border-color: {{ $product->category->color }}20; background-color: {{ $product->category->color }}10;">
                                {{ $product->category->name }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-1.5 h-1.5 w-full rounded-full bg-stone-100">
                        <div class="h-1.5 rounded-full bg-{{ $color }}-500 transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                <div class="shrink-0 text-right text-sm">
                    <p class="font-semibold text-{{ $color }}-700">{{ number_format((float) $product->available_quantity, 1) }} {{ $product->unit_type?->value }}</p>
                    <p class="text-xs text-stone-400">mín: {{ number_format((float) $product->minimum_stock_quantity, 1) }}</p>
                </div>
                <a href="{{ route('estoque.produtos') }}"
                   class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors">
                    Entrada
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if ($frascosVencidos->isEmpty() && $frascosVencendo->isEmpty() && $produtosAbaixoMinimo->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50/50 py-12 text-center">
        <svg class="h-10 w-10 text-emerald-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-emerald-700 font-medium">Tudo em ordem!</p>
        <p class="text-emerald-500 text-sm mt-1">Nenhum alerta de estoque no momento.</p>
    </div>
    @endif

</div>
