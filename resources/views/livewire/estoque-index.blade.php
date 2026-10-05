<div>
    @php
        $temBaixo     = $produtosAbaixoMinimo->isNotEmpty();
        $temVencido   = $frascosVencidos->isNotEmpty();
        $temVencendo  = $frascosVencendo->isNotEmpty();
        $corFrascos   = $temVencido ? 'text-red-700' : ($temVencendo ? 'text-amber-700' : 'text-stone-900');
    @endphp
    <x-ui.page-header titulo="Visão geral" subtitulo="Veja o que precisa de atenção no estoque hoje, {{ now()->translatedFormat('d \d\e F') }}.">
        <x-slot:acoes>
            <a href="{{ route('estoque.movimentacoes') }}" class="btn-secondary flex-1 sm:flex-none">Ver movimentações</a>
            <a href="{{ route('estoque.produtos') }}" class="btn-primary flex-1 sm:flex-none">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                Gerenciar produtos
            </a>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="space-y-6">

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Produtos ativos</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalProdutos }}</p>
            <p class="mt-0.5 text-xs text-stone-500">cadastrados</p>
        </div>
        <div class="card p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-stone-500">Estoque baixo</p>
                @if ($temBaixo)
                    <span class="h-2 w-2 rounded-full bg-red-500" aria-hidden="true"></span>
                @endif
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $temBaixo ? 'text-red-700' : 'text-stone-900' }}">{{ $produtosAbaixoMinimo->count() }}</p>
            <p class="mt-0.5 text-xs text-stone-500">abaixo do mínimo</p>
        </div>
        <div class="card p-4 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-stone-500">Frascos abertos</p>
                @if ($temVencido || $temVencendo)
                    <span class="h-2 w-2 rounded-full {{ $temVencido ? 'bg-red-500' : 'bg-amber-500' }}" aria-hidden="true"></span>
                @endif
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $corFrascos }}">{{ $frascosVencendo24h->count() }}</p>
            <p class="mt-0.5 text-xs text-stone-500">vencem em 24h</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Valor em estoque</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums truncate">R$ {{ number_format((float) $valorTotalStock, 2, ',', '.') }}</p>
            <p class="mt-0.5 text-xs text-stone-500">estimado pelo custo</p>
        </div>
    </div>

    {{-- Frascos vencidos --}}
    @if ($temVencido)
    <section class="card overflow-hidden">
        <div class="flex items-start gap-3 border-b border-stone-100 bg-red-50 px-5 py-4">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <div>
                <h2 class="text-sm font-semibold text-red-800">{{ $frascosVencidos->count() }} {{ $frascosVencidos->count() === 1 ? 'frasco aberto venceu' : 'frascos abertos venceram' }}</h2>
                <p class="mt-0.5 text-sm text-red-700">Descarte o conteúdo. O sistema dá baixa sozinho na próxima verificação automática.</p>
            </div>
        </div>
        <ul class="divide-y divide-stone-100">
            @foreach ($frascosVencidos as $batch)
            <li class="flex items-center justify-between gap-3 px-5 py-3">
                <div class="min-w-0 text-sm">
                    <p class="truncate font-medium text-stone-900">{{ $batch->product?->name ?? '—' }}</p>
                    @if ($batch->lot_number)
                        <p class="text-xs text-stone-500">Lote {{ $batch->lot_number }}</p>
                    @endif
                </div>
                <span class="badge shrink-0 bg-red-50 text-red-700">Venceu {{ $batch->beyond_use_expires_at?->diffForHumans() }}</span>
            </li>
            @endforeach
        </ul>
    </section>
    @endif

    {{-- Frascos vencendo --}}
    @if ($temVencendo)
    <section class="card overflow-hidden">
        <div class="flex items-start gap-3 border-b border-stone-100 bg-amber-50 px-5 py-4">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <h2 class="text-sm font-semibold text-amber-800">{{ $frascosVencendo->count() }} {{ $frascosVencendo->count() === 1 ? 'frasco aberto vence' : 'frascos abertos vencem' }} nas próximas 4h</h2>
                <p class="mt-0.5 text-sm text-amber-700">Use primeiro esses frascos nos próximos atendimentos.</p>
            </div>
        </div>
        <ul class="divide-y divide-stone-100">
            @foreach ($frascosVencendo as $batch)
            <li class="flex items-center justify-between gap-3 px-5 py-3">
                <div class="min-w-0 text-sm">
                    <p class="truncate font-medium text-stone-900">{{ $batch->product?->name ?? '—' }}</p>
                    <p class="text-xs text-stone-500 tabular-nums">
                        {{ number_format((float) $batch->quantity_available, 1, ',', '.') }} {{ $batch->product?->unit_type?->value }}
                        @if ($batch->lot_number) · Lote {{ $batch->lot_number }} @endif
                    </p>
                </div>
                <span class="badge shrink-0 bg-amber-50 text-amber-700">{{ $batch->beyondUseLabel() }}</span>
            </li>
            @endforeach
        </ul>
    </section>
    @endif

    {{-- Produtos abaixo do mínimo --}}
    @if ($temBaixo)
    <section class="card overflow-hidden">
        <div class="border-b border-stone-100 px-5 py-4">
            <h2 class="text-base font-semibold text-stone-900">Abaixo do estoque mínimo</h2>
            <p class="mt-0.5 text-sm text-stone-500">Registre uma entrada quando chegar a reposição.</p>
        </div>
        <ul class="divide-y divide-stone-100">
            @foreach ($produtosAbaixoMinimo as $product)
            @php
                $pct = $product->minimum_stock_quantity > 0
                    ? min(100, round($product->available_quantity / $product->minimum_stock_quantity * 100))
                    : 100;
                $critico = $pct < 25;
            @endphp
            <li class="flex items-center gap-3 sm:gap-4 px-5 py-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="truncate text-sm font-medium text-stone-900">{{ $product->name }}</span>
                        @if ($product->category)
                            <span class="badge border"
                                  style="color: {{ $product->category->color }}; border-color: {{ $product->category->color }}33; background-color: {{ $product->category->color }}14;">
                                {{ $product->category->name }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-stone-100">
                        <div class="h-1.5 rounded-full transition-all {{ $critico ? 'bg-red-500' : 'bg-amber-500' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                <div class="shrink-0 text-right text-sm tabular-nums">
                    <p class="font-semibold {{ $critico ? 'text-red-700' : 'text-amber-700' }}">{{ number_format((float) $product->available_quantity, 1, ',', '.') }} {{ $product->unit_type?->value }}</p>
                    <p class="text-xs text-stone-500">mín. {{ number_format((float) $product->minimum_stock_quantity, 1, ',', '.') }}</p>
                </div>
                <a href="{{ route('estoque.produtos') }}" class="btn-secondary shrink-0 px-3 text-xs">
                    Dar entrada
                </a>
            </li>
            @endforeach
        </ul>
    </section>
    @endif

    @if (! $temVencido && ! $temVencendo && ! $temBaixo)
    <div class="card">
        <x-ui.empty-state
            titulo="Tudo em ordem por aqui"
            texto="Nenhum produto abaixo do mínimo e nenhum frasco aberto perto de vencer. Os alertas aparecem aqui assim que algo precisar de atenção."
            icone="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
            <a href="{{ route('estoque.produtos') }}" class="btn-secondary">Ver produtos</a>
        </x-ui.empty-state>
    </div>
    @endif

    </div>
</div>
