<div class="space-y-6">

    {{-- Flash inline ────────────────────────────────────────────── --}}
    @if($flashSucesso)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-5 py-3 text-sm text-emerald-800">
        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ $flashSucesso }}
    </div>
    @endif
    @if($flashErro)
    <div class="flex items-center gap-3 rounded-xl bg-red-50 border border-red-200 px-5 py-3 text-sm text-red-800">
        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        {{ $flashErro }}
    </div>
    @endif

    {{-- ─── Cabeçalho ──────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-stone-800">Relatório</h1>
            <p class="text-sm text-stone-500 mt-0.5">{{ $mesLabel }}</p>
        </div>
        <div class="flex items-center gap-3">
            <input type="month"
                   wire:model.live="competencia"
                   class="rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 focus:border-indigo-500 focus:ring-indigo-500">
            @if($tab === 'contabil')
            <button wire:click="exportarCsv"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar CSV
            </button>
            @endif
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-surface px-3 py-2 text-sm font-medium text-stone-600 shadow-sm hover:bg-stone-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.75 19.5m10.56-5.671L17.25 19.5m0 0l.75-3.75M17.25 19.5H6.75m0 0l-.75-3.75M3 12.75V8.25A2.25 2.25 0 015.25 6h13.5A2.25 2.25 0 0121 8.25v4.5" />
                </svg>
                Imprimir
            </button>
        </div>
    </div>

    {{-- ─── Abas ───────────────────────────────────────────────── --}}
    <div class="border-b border-stone-200">
        <nav class="-mb-px flex gap-1">
            <button wire:click="$set('tab', 'gerencial')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                           {{ $tab === 'gerencial'
                               ? 'border-rose-500 text-rose-600'
                               : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300' }}">
                Gerencial
            </button>
            <button wire:click="$set('tab', 'contabil')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                           {{ $tab === 'contabil'
                               ? 'border-rose-500 text-rose-600'
                               : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300' }}">
                Contábil / Contador
            </button>
        </nav>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ABA: GERENCIAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'gerencial')

    {{-- ─── KPIs — "Como foi o mês?" ──────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Resultado: destaque visual maior, ocupa coluna própria no desktop --}}
        <div class="sm:col-span-1 rounded-xl border-2 p-5 shadow-sm
            {{ $saldo >= 0 ? 'bg-emerald-50 border-emerald-300' : 'bg-red-50 border-red-300' }}">
            <p class="text-xs font-semibold uppercase tracking-wide {{ $saldo >= 0 ? 'text-emerald-600' : 'text-red-500' }}">Resultado Líquido</p>
            <p class="text-3xl font-extrabold mt-1 {{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                R$ {{ number_format($saldo, 2, ',', '.') }}
            </p>
            <p class="text-xs mt-1.5 {{ $saldo >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                Margem: {{ number_format($margem, 1, ',', '.') }}%
            </p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Receita Total</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">R$ {{ number_format($totalReceita, 2, ',', '.') }}</p>
            <p class="text-xs text-stone-400 mt-1">efetivamente recebido</p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Despesa Total</p>
            <p class="text-2xl font-bold text-red-600 mt-1">R$ {{ number_format($totalDespesa, 2, ',', '.') }}</p>
            <p class="text-xs text-stone-400 mt-1">efetivamente pago</p>
        </div>
    </div>

    {{-- ─── Insights ───────────────────────────────────────────── --}}
    @if(count($insights) > 0)
    <div class="space-y-2">
        @foreach($insights as $insight)
        <div class="flex items-start gap-3 rounded-lg px-4 py-3 text-sm
            @if($insight['tipo'] === 'positivo') bg-emerald-50 border border-emerald-200 text-emerald-800
            @elseif($insight['tipo'] === 'critico') bg-red-50 border border-red-200 text-red-800
            @elseif($insight['tipo'] === 'atencao') bg-amber-50 border border-amber-200 text-amber-800
            @else bg-blue-50 border border-blue-200 text-blue-800 @endif">
            <svg class="w-4 h-4 mt-0.5 shrink-0
                @if($insight['tipo'] === 'positivo') text-emerald-500
                @elseif($insight['tipo'] === 'critico') text-red-500
                @elseif($insight['tipo'] === 'atencao') text-amber-500
                @else text-blue-500 @endif"
                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                @if($insight['tipo'] === 'positivo')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                @elseif($insight['tipo'] === 'critico')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                @endif
            </svg>
            <span>{{ $insight['msg'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ─── Seção: O que aconteceu este mês ───────────────────── --}}
    <div class="flex items-center gap-3 pt-2">
        <span class="text-xs font-semibold text-stone-400 uppercase tracking-widest whitespace-nowrap">O que aconteceu</span>
        <div class="flex-1 border-t border-stone-200"></div>
    </div>

    {{-- DRE + Categorias --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100">
                <h2 class="text-sm font-semibold text-stone-700">Receitas e Despesas do Período</h2>
            </div>
            <div class="divide-y divide-stone-50">
                <div class="px-5 py-3">
                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Receitas</p>
                    @forelse($receitaRows as $row)
                    <div class="flex justify-between items-center py-1 text-sm">
                        <span class="text-stone-600">{{ $row->categoria }}</span>
                        <span class="font-medium text-emerald-700">R$ {{ number_format($row->total, 2, ',', '.') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-stone-400 italic">Sem receitas no período.</p>
                    @endforelse
                    <div class="flex justify-between items-center pt-2 mt-1 border-t border-stone-100 text-sm font-semibold">
                        <span class="text-stone-700">Total Receitas</span>
                        <span class="text-emerald-700">R$ {{ number_format($totalReceita, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="px-5 py-3">
                    <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide mb-2">Despesas</p>
                    @forelse($categoriasDespesa as $cat => $valor)
                    <div class="flex justify-between items-center py-1 text-sm">
                        <span class="text-stone-600">{{ $cat }}</span>
                        <span class="font-medium text-red-700">R$ {{ number_format($valor, 2, ',', '.') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-stone-400 italic">Sem despesas no período.</p>
                    @endforelse
                    <div class="flex justify-between items-center pt-2 mt-1 border-t border-stone-100 text-sm font-semibold">
                        <span class="text-stone-700">Total Despesas</span>
                        <span class="text-red-700">R$ {{ number_format($totalDespesa, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="px-5 py-3 {{ $saldo >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                    <div class="flex justify-between items-center text-sm font-bold">
                        <span class="{{ $saldo >= 0 ? 'text-emerald-800' : 'text-red-800' }}">Resultado do Período</span>
                        <span class="{{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100">
                <h2 class="text-sm font-semibold text-stone-700">Despesas por Categoria</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                @forelse($categoriasDespesa as $cat => $valor)
                @php $pct = $totalDespesa > 0 ? ($valor / $totalDespesa) * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-stone-700">{{ $cat }}</span>
                        <span class="text-stone-500">{{ number_format($pct, 1, ',', '.') }}% &nbsp; R$ {{ number_format($valor, 2, ',', '.') }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-500 transition-all duration-500" style="width: {{ min(100, $pct) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-stone-400 italic">Sem despesas no período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Fornecedores — contexto do que aconteceu --}}
    @if($topFornecedores->isNotEmpty())
    <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100">
            <h2 class="text-sm font-semibold text-stone-700">Top Fornecedores — Despesas do Período</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Fornecedor</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Valor Pago</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">% Despesas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @foreach($topFornecedores as $i => $item)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-5 py-3 text-stone-400 font-medium">{{ $i + 1 }}</td>
                        <td class="px-5 py-3 font-medium text-stone-700">{{ $item->fornecedor?->nome_fantasia ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-medium text-red-700">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right text-stone-500">
                            {{ $totalDespesa > 0 ? number_format(($item->total / $totalDespesa) * 100, 1, ',', '.') : '0,0' }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ─── Seção: O que está previsto ────────────────────────── --}}
    @if(!empty($previsao) && $previsao['totalPrevisto'] > 0)
    <div class="flex items-center gap-3 pt-2">
        <span class="text-xs font-semibold text-stone-400 uppercase tracking-widest whitespace-nowrap">O que está previsto</span>
        <div class="flex-1 border-t border-stone-200"></div>
    </div>
    @endif

    {{-- ── Previsão de Gastos ──────────────────────────────────── --}}
    @if(!empty($previsao) && $previsao['totalPrevisto'] > 0)
    @php
        $pv = $previsao;
        $pctBar = min(100, $pv['pctExecutado']);
    @endphp
    <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-stone-700">Previsão de Gastos do Período</h2>
                <p class="text-xs text-stone-400 mt-0.5">Previsto × Realizado por categoria</p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
                {{ $pv['pctExecutado'] >= 100 ? 'bg-red-100 text-red-700' : ($pv['pctExecutado'] >= 75 ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700') }}">
                {{ number_format($pv['pctExecutado'], 1, ',', '.') }}% executado
            </span>
        </div>

        {{-- KPIs --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-stone-100">
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Total Previsto</p>
                <p class="text-xl font-bold text-indigo-600 mt-1 tabular-nums">R$ {{ number_format($pv['totalPrevisto'], 2, ',', '.') }}</p>
                <p class="text-xs text-stone-400 mt-0.5">soma de todos os itens do mês</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Total Realizado</p>
                <p class="text-xl font-bold text-emerald-600 mt-1 tabular-nums">R$ {{ number_format($pv['totalRealizado'], 2, ',', '.') }}</p>
                <p class="text-xs text-stone-400 mt-0.5">efetivamente pago</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Saldo a Pagar</p>
                <p class="text-xl font-bold mt-1 tabular-nums {{ $pv['totalPendente'] > 0 ? 'text-amber-600' : 'text-stone-400' }}">
                    R$ {{ number_format($pv['totalPendente'], 2, ',', '.') }}
                </p>
                <p class="text-xs text-stone-400 mt-0.5">pendente de quitação</p>
            </div>
            <div class="px-5 py-4">
                <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Execução</p>
                <div class="mt-2">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-stone-500">{{ number_format($pv['pctExecutado'], 1, ',', '.') }}%</span>
                        <span class="text-xs text-stone-400">de 100%</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-700
                            {{ $pv['pctExecutado'] >= 100 ? 'bg-red-500' : ($pv['pctExecutado'] >= 75 ? 'bg-amber-500' : 'bg-indigo-500') }}"
                             style="width: {{ $pctBar }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabela por categoria --}}
        <div class="border-t border-stone-100 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Categoria</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Previsto</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Realizado</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Pendente</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Execução</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">

                    {{-- Contratos --}}
                    @if($pv['previstoContratos'] > 0)
                    @php $pctC = $pv['previstoContratos'] > 0 ? min(100, ($pv['pagoContratos'] / $pv['previstoContratos']) * 100) : 0; @endphp
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-rose-400 shrink-0"></span>
                                <span class="font-medium text-stone-700">Contratos Fixos</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-stone-600">R$ {{ number_format($pv['previstoContratos'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-emerald-700 font-medium">R$ {{ number_format($pv['pagoContratos'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ max(0, $pv['previstoContratos'] - $pv['pagoContratos']) > 0 ? 'text-amber-600 font-medium' : 'text-stone-400' }}">
                            R$ {{ number_format(max(0, $pv['previstoContratos'] - $pv['pagoContratos']), 2, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-20 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctC }}%"></div>
                                </div>
                                <span class="text-xs text-stone-500 w-10 text-right">{{ number_format($pctC, 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Contas de Consumo --}}
                    @if($pv['previstoFaturas'] > 0)
                    @php $pctF = $pv['previstoFaturas'] > 0 ? min(100, ($pv['pagoFaturas'] / $pv['previstoFaturas']) * 100) : 0; @endphp
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-blue-400 shrink-0"></span>
                                <span class="font-medium text-stone-700">Contas de Consumo</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-stone-600">R$ {{ number_format($pv['previstoFaturas'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-emerald-700 font-medium">R$ {{ number_format($pv['pagoFaturas'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ max(0, $pv['previstoFaturas'] - $pv['pagoFaturas']) > 0 ? 'text-amber-600 font-medium' : 'text-stone-400' }}">
                            R$ {{ number_format(max(0, $pv['previstoFaturas'] - $pv['pagoFaturas']), 2, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-20 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctF }}%"></div>
                                </div>
                                <span class="text-xs text-stone-500 w-10 text-right">{{ number_format($pctF, 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Fiscal --}}
                    @if($pv['previstoFiscal'] > 0)
                    @php $pctFi = $pv['previstoFiscal'] > 0 ? min(100, ($pv['pagoFiscal'] / $pv['previstoFiscal']) * 100) : 0; @endphp
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-orange-400 shrink-0"></span>
                                <span class="font-medium text-stone-700">Fiscal / Tributos</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-stone-600">R$ {{ number_format($pv['previstoFiscal'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums text-emerald-700 font-medium">R$ {{ number_format($pv['pagoFiscal'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums {{ max(0, $pv['previstoFiscal'] - $pv['pagoFiscal']) > 0 ? 'text-amber-600 font-medium' : 'text-stone-400' }}">
                            R$ {{ number_format(max(0, $pv['previstoFiscal'] - $pv['pagoFiscal']), 2, ',', '.') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-20 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctFi }}%"></div>
                                </div>
                                <span class="text-xs text-stone-500 w-10 text-right">{{ number_format($pctFi, 0) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- Avulsas Pendentes --}}
                    @if($pv['pendentesAvulsos'] > 0)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-stone-400 shrink-0"></span>
                                <span class="font-medium text-stone-700">Despesas Avulsas Pendentes</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right tabular-nums text-stone-400">—</td>
                        <td class="px-5 py-3 text-right tabular-nums text-stone-400">—</td>
                        <td class="px-5 py-3 text-right tabular-nums text-amber-600 font-medium">R$ {{ number_format($pv['pendentesAvulsos'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right text-xs text-stone-400">aguardando pagto</td>
                    </tr>
                    @endif

                </tbody>
                <tfoot class="border-t-2 border-stone-200 bg-stone-50">
                    <tr>
                        <td class="px-5 py-3 text-sm font-bold text-stone-700">Total</td>
                        <td class="px-5 py-3 text-right font-bold tabular-nums text-indigo-700">R$ {{ number_format($pv['totalPrevisto'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-bold tabular-nums text-emerald-700">R$ {{ number_format($pv['totalRealizado'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-bold tabular-nums {{ $pv['totalPendente'] > 0 ? 'text-amber-600' : 'text-stone-400' }}">R$ {{ number_format($pv['totalPendente'], 2, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-bold text-stone-700">{{ number_format($pv['pctExecutado'], 1, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- ─── Seção: O que precisa de atenção ──────────────────── --}}
    <div class="flex items-center gap-3 pt-2">
        <span class="text-xs font-semibold text-stone-400 uppercase tracking-widest whitespace-nowrap">O que precisa de atenção</span>
        <div class="flex-1 border-t border-stone-200"></div>
    </div>

    {{-- Pendências --}}
    @php $totalPendencias = $contratosPendentes->count() + $faturasPendentes->count() + $fiscaisPendentes->count(); @endphp

    @if($totalPendencias > 0)
    <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-stone-700">Pendências do Período</h2>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                {{ $totalPendencias }} {{ $totalPendencias === 1 ? 'item' : 'itens' }}
            </span>
        </div>
        <div class="divide-y divide-stone-50">
            @foreach($contratosPendentes as $contrato)
            <div class="flex items-center gap-4 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <span class="font-medium text-stone-700">{{ $contrato->fornecedor?->nome_fantasia ?? 'Contrato' }}</span>
                    <span class="ml-2 text-xs text-stone-400">Contrato fixo mensal</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="font-medium text-stone-700">R$ {{ number_format($contrato->valor_mensal, 2, ',', '.') }}</p>
                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Não pago</span>
                    </div>
                    <button wire:click="abrirPagarContrato('{{ $contrato->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        Pagar
                    </button>
                </div>
            </div>
            @endforeach
            @foreach($faturasPendentes as $fat)
            <div class="flex items-center gap-4 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <span class="font-medium text-stone-700">{{ $fat->contaConsumo?->descricao ?? 'Fatura' }}</span>
                    <span class="ml-2 text-xs text-stone-400">{{ $fat->contaConsumo?->tipo }} · {{ $fat->competenciaFormatada() }}</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="font-medium text-stone-700">{{ $fat->valor !== null ? 'R$ ' . number_format($fat->valor, 2, ',', '.') : 'Valor não informado' }}</p>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $fat->status->value === 'vencida' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $fat->status->label() }}
                        </span>
                    </div>
                    <button wire:click="abrirPagarFatura('{{ $fat->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        Pagar
                    </button>
                </div>
            </div>
            @endforeach
            @foreach($fiscaisPendentes as $lanc)
            <div class="flex items-center gap-4 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <span class="font-medium text-stone-700">{{ $lanc->obrigacaoFiscal?->descricao ?? $lanc->obrigacaoFiscal?->tipo_tributo }}</span>
                    <span class="ml-2 text-xs text-stone-400">
                        Fiscal · {{ $lanc->competenciaFormatada() }}
                        @if($lanc->data_vencimento) · vence {{ $lanc->data_vencimento->format('d/m') }} @endif
                    </span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <p class="font-medium text-stone-700">R$ {{ number_format($lanc->valorTotal(), 2, ',', '.') }}</p>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $lanc->status->value === 'vencido' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $lanc->status->label() }}
                        </span>
                    </div>
                    <button wire:click="abrirPagarFiscal('{{ $lanc->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                        Pagar
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-5 py-4 text-sm text-emerald-700 flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Nenhuma pendência encontrada para o período.
    </div>
    @endif

    @endif {{-- fim aba gerencial --}}

    {{-- ═══════════════════════════════════════════════════════════
         ABA: CONTÁBIL
    ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'contabil')

    @if(!empty($totaisContabil))

    {{-- Validação de integridade --}}
    <div class="flex items-center gap-3 rounded-xl px-5 py-3 text-sm font-medium
        {{ $totaisContabil['integridade_ok'] ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
        @if($totaisContabil['integridade_ok'])
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Integridade verificada — soma dos lançamentos confere com os totais.
        <span class="ml-auto text-xs font-normal text-emerald-600">{{ $totaisContabil['qtd_lancamentos'] }} lançamentos</span>
        @else
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        Divergência detectada — verifique os lançamentos.
        <span class="ml-auto text-xs font-normal">
            Soma linhas: R$ {{ number_format($totaisContabil['soma_linhas'], 2, ',', '.') }} |
            Soma totais: R$ {{ number_format($totaisContabil['soma_agregados'], 2, ',', '.') }}
        </span>
        @endif
    </div>

    {{-- KPIs contábil --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Receitas</p>
            <p class="text-xl font-bold text-emerald-600 mt-1">R$ {{ number_format($totaisContabil['total_receitas'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Despesas</p>
            <p class="text-xl font-bold text-red-600 mt-1">R$ {{ number_format($totaisContabil['total_despesas'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Tributos</p>
            <p class="text-xl font-bold text-orange-600 mt-1">R$ {{ number_format($totaisContabil['total_tributos'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-4">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Taxas</p>
            <p class="text-xl font-bold text-blue-600 mt-1">R$ {{ number_format($totaisContabil['total_taxas'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-surface rounded-xl border border-stone-100 shadow-sm p-4 {{ $totaisContabil['resultado'] >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-wide">Resultado</p>
            <p class="text-xl font-bold mt-1 {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                R$ {{ number_format($totaisContabil['resultado'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabela de lançamentos --}}
    <div class="bg-surface rounded-xl border border-stone-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-stone-700">Lançamentos — status Pago</h2>
            <span class="text-xs text-stone-400">{{ $transacoesContabil->count() }} registros</span>
        </div>

        @if($transacoesContabil->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-stone-400">
            Nenhum lançamento pago encontrado para o período.
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50 border-b border-stone-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Classificação</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Categoria</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase tracking-wide">Descrição</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-stone-500 uppercase tracking-wide">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @foreach($transacoesContabil as $t)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-4 py-2.5 text-stone-500 whitespace-nowrap font-mono text-xs">{{ $t['data'] }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ $t['tipo'] === 'Entrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $t['tipo'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                @if($t['classificacao'] === 'Receita')  bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200
                                @elseif($t['classificacao'] === 'Tributo') bg-orange-50 text-orange-700 ring-1 ring-orange-200
                                @elseif($t['classificacao'] === 'Taxa')    bg-blue-50 text-blue-700 ring-1 ring-blue-200
                                @else                                      bg-stone-100 text-stone-600 ring-1 ring-stone-200
                                @endif">
                                {{ $t['classificacao'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-stone-600 max-w-[160px] truncate">{{ $t['categoria'] }}</td>
                        <td class="px-4 py-2.5 text-stone-700 max-w-[200px] truncate">{{ $t['descricao'] }}</td>
                        <td class="px-4 py-2.5 text-right font-medium whitespace-nowrap
                            {{ $t['tipo'] === 'Entrada' ? 'text-emerald-700' : 'text-stone-700' }}">
                            {{ number_format($t['valor'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-stone-200 bg-stone-50">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">
                            Total Receitas
                        </td>
                        <td></td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-700">
                            {{ number_format($totaisContabil['total_receitas'], 2, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="border-t border-stone-200">
                        <td colspan="4" class="px-4 py-3 text-xs font-semibold text-stone-500 uppercase tracking-wide">
                            Total Saídas (Despesa + Tributo + Taxa)
                        </td>
                        <td></td>
                        <td class="px-4 py-3 text-right font-bold text-red-700">
                            {{ number_format($totaisContabil['total_saidas'], 2, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="border-t-2 border-stone-300 {{ $totaisContabil['resultado'] >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                        <td colspan="4" class="px-4 py-3 text-sm font-bold {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-800' : 'text-red-800' }}">
                            Resultado do Período
                        </td>
                        <td></td>
                        <td class="px-4 py-3 text-right font-bold text-lg {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ number_format($totaisContabil['resultado'], 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

    @else
    <div class="bg-surface rounded-xl border border-stone-100 shadow-sm px-5 py-10 text-center text-sm text-stone-400">
        Nenhum dado disponível para o período selecionado.
    </div>
    @endif

    @endif {{-- fim aba contábil --}}

    {{-- ─── Modal de Pagamento ─────────────────────────────────── --}}
    @if($modalPagar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data x-on:keydown.escape.window="$wire.fecharModalPagamento()">

        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalPagamento"></div>

        <div class="relative w-full max-w-md bg-surface rounded-2xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-start justify-between px-6 py-4 border-b border-stone-100">
                <div>
                    <h3 class="text-base font-semibold text-stone-800">Registrar Pagamento</h3>
                    <p class="text-sm text-stone-500 mt-0.5 truncate max-w-xs">{{ $pagarNome }}</p>
                </div>
                <button wire:click="fecharModalPagamento" class="p-1.5 text-stone-400 hover:text-stone-600 hover:bg-stone-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Erro inline no modal --}}
                @if($flashErro)
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $flashErro }}
                </div>
                @endif

                {{-- Competência (só contratos) --}}
                @if($modalPagarTipo === 'contrato')
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Competência</label>
                    <input type="month" wire:model="pagarCompetencia"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('pagarCompetencia') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Valor (contratos e faturas) --}}
                @if(in_array($modalPagarTipo, ['contrato', 'fatura']))
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Valor (R$)</label>
                    <input type="number" step="0.01" min="0.01" wire:model="pagarValor"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('pagarValor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Campos fiscais --}}
                @if($modalPagarTipo === 'fiscal')
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Principal (R$)</label>
                        <input type="number" step="0.01" wire:model="pagarValor" readonly
                               class="w-full rounded-lg border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Multa (R$)</label>
                        <input type="number" step="0.01" min="0" wire:model="pagarValorMulta"
                               class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Juros (R$)</label>
                        <input type="number" step="0.01" min="0" wire:model="pagarValorJuros"
                               class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Nº Autenticação <span class="font-normal normal-case text-stone-400">(opcional)</span></label>
                    <input type="text" wire:model="pagarNumAutenticacao"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                @endif

                {{-- Data de pagamento --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Data de Pagamento</label>
                    <input type="date" wire:model="pagarDataPagamento"
                           class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('pagarDataPagamento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Forma de pagamento --}}
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Forma de Pagamento</label>
                    <select wire:model="pagarFormaPagamento"
                            class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                        <option value="debito_automatico">Débito Automático</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                    @error('pagarFormaPagamento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Observações (contratos) --}}
                @if($modalPagarTipo === 'contrato')
                <div>
                    <label class="block text-xs font-semibold text-stone-500 uppercase tracking-wide mb-1">Observações <span class="font-normal normal-case text-stone-400">(opcional)</span></label>
                    <textarea wire:model="pagarObservacoes" rows="2"
                              class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-stone-100 bg-stone-50">
                <button wire:click="fecharModalPagamento"
                        class="rounded-lg border border-stone-200 bg-surface px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50 transition-colors">
                    Cancelar
                </button>
                <button wire:click="confirmarPagamento" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors disabled:opacity-60">
                    <svg wire:loading wire:target="confirmarPagamento" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="confirmarPagamento" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Confirmar Pagamento
                </button>
            </div>

        </div>
    </div>
    @endif

</div>

@push('head')
<style>
@media print {
    *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    @page {
        size: A4;
        margin: 1.5cm;
    }

    html, body {
        height: auto !important;
        overflow: visible !important;
        background: white !important;
    }

    /* Ocultar navegação e controles interativos */
    aside, header, nav, button, .fixed,
    input[type="month"], input[type="date"],
    input[type="number"], input[type="text"],
    select, textarea {
        display: none !important;
    }

    /* Desbloquear os containers flex que limitam a altura da viewport */
    body > div,
    .flex.h-screen,
    .flex-1.flex.flex-col.min-w-0.overflow-hidden {
        display: block !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
    }

    main {
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
        flex: none !important;
    }

    /* Desbloquear wrappers com scroll horizontal */
    .overflow-x-auto {
        overflow: visible !important;
    }

    /* Corrigir texto truncado — causa das descrições cortadas com "..." */
    .truncate {
        overflow: visible !important;
        text-overflow: clip !important;
        white-space: normal !important;
        max-width: none !important;
    }

    /* Células da tabela: exibir conteúdo completo */
    td, th {
        white-space: normal !important;
        overflow: visible !important;
        max-width: none !important;
    }

    table {
        width: 100% !important;
    }

    /* Evitar quebra de página no meio de cards */
    .rounded-xl {
        break-inside: avoid;
    }
}
</style>
@endpush
