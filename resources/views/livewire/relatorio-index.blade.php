<div>

    {{-- ─── Cabeçalho ──────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Relatórios" :subtitulo="'Resultado de ' . mb_strtolower($mesLabel) . ' e o fechamento para o seu contador.'">
        <x-slot:acoes>
            <label for="relatorio-competencia" class="sr-only">Mês do relatório</label>
            <input id="relatorio-competencia"
                   type="month"
                   wire:model.live="competencia"
                   class="input w-auto">
            @if($tab === 'contabil')
            <button type="button" wire:click="exportarCsv" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                Exportar CSV
            </button>
            @endif
            <button type="button" onclick="window.print()" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.75 19.5m10.56-5.671L17.25 19.5m0 0l.75-3.75M17.25 19.5H6.75m0 0l-.75-3.75M3 12.75V8.25A2.25 2.25 0 015.25 6h13.5A2.25 2.25 0 0121 8.25v4.5" />
                </svg>
                Imprimir
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="space-y-6">

    {{-- Avisos ─────────────────────────────────────────────────── --}}
    @if($flashSucesso)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" role="status"
         class="flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ $flashSucesso }}
    </div>
    @endif
    @if($flashErro)
    <div class="flex items-center gap-3 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" role="alert">
        <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
        </svg>
        {{ $flashErro }}
    </div>
    @endif

    {{-- ─── Abas ───────────────────────────────────────────────── --}}
    <div class="border-b border-stone-200">
        <nav class="-mb-px flex gap-1 overflow-x-auto" aria-label="Tipo de relatório">
            <button type="button" wire:click="$set('tab', 'gerencial')"
                    @if($tab === 'gerencial') aria-current="page" @endif
                    class="min-h-[44px] px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                           {{ $tab === 'gerencial'
                               ? 'border-rose-500 text-rose-600'
                               : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300' }}">
                Visão gerencial
            </button>
            <button type="button" wire:click="$set('tab', 'contabil')"
                    @if($tab === 'contabil') aria-current="page" @endif
                    class="min-h-[44px] px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors
                           {{ $tab === 'contabil'
                               ? 'border-rose-500 text-rose-600'
                               : 'border-transparent text-stone-500 hover:text-stone-700 hover:border-stone-300' }}">
                Para o contador
            </button>
        </nav>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ABA: GERENCIAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'gerencial')

    {{-- ─── KPIs — "Como foi o mês?" ──────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-stone-500">Resultado líquido</p>
                <span class="badge {{ $saldo >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    Margem {{ number_format($margem, 1, ',', '.') }}%
                </span>
            </div>
            <p class="mt-2 text-2xl font-semibold tabular-nums {{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                R$ {{ number_format($saldo, 2, ',', '.') }}
            </p>
            <p class="text-xs text-stone-500 mt-1">Receitas menos despesas do mês</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Receitas</p>
            <p class="mt-2 text-2xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totalReceita, 2, ',', '.') }}</p>
            <p class="text-xs text-stone-500 mt-1">O que de fato entrou no caixa</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Despesas</p>
            <p class="mt-2 text-2xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totalDespesa, 2, ',', '.') }}</p>
            <p class="text-xs text-stone-500 mt-1">O que de fato foi pago</p>
        </div>
    </div>

    {{-- ─── Leituras automáticas ───────────────────────────────── --}}
    @if(count($insights) > 0)
    <div class="space-y-2">
        @foreach($insights as $insight)
        <div class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm
            @if($insight['tipo'] === 'positivo') bg-emerald-50 border border-emerald-100 text-emerald-700
            @elseif($insight['tipo'] === 'critico') bg-red-50 border border-red-100 text-red-700
            @elseif($insight['tipo'] === 'atencao') bg-amber-50 border border-amber-100 text-amber-700
            @else bg-blue-50 border border-blue-100 text-blue-700 @endif">
            <svg class="w-5 h-5 shrink-0
                @if($insight['tipo'] === 'positivo') text-emerald-600
                @elseif($insight['tipo'] === 'critico') text-red-600
                @elseif($insight['tipo'] === 'atencao') text-amber-600
                @else text-blue-600 @endif"
                fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
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
    <h2 class="pt-2 text-base font-semibold text-stone-900">O que aconteceu no mês</h2>

    {{-- DRE + Categorias --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-900">Receitas e despesas</h3>
            </div>
            <div class="divide-y divide-stone-100">
                <div class="px-5 py-3">
                    <p class="text-xs font-medium text-stone-500 mb-2">Receitas</p>
                    @forelse($receitaRows as $row)
                    <div class="flex justify-between items-center gap-4 py-1 text-sm">
                        <span class="text-stone-600">{{ $row->categoria }}</span>
                        <span class="font-medium tabular-nums text-emerald-700">R$ {{ number_format($row->total, 2, ',', '.') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-stone-500">Nenhuma receita neste mês.</p>
                    @endforelse
                    <div class="flex justify-between items-center gap-4 pt-2 mt-1 border-t border-stone-100 text-sm font-semibold">
                        <span class="text-stone-700">Total de receitas</span>
                        <span class="tabular-nums text-emerald-700">R$ {{ number_format($totalReceita, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="px-5 py-3">
                    <p class="text-xs font-medium text-stone-500 mb-2">Despesas</p>
                    @forelse($categoriasDespesa as $cat => $valor)
                    <div class="flex justify-between items-center gap-4 py-1 text-sm">
                        <span class="text-stone-600">{{ $cat }}</span>
                        <span class="font-medium tabular-nums text-red-700">R$ {{ number_format($valor, 2, ',', '.') }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-stone-500">Nenhuma despesa neste mês.</p>
                    @endforelse
                    <div class="flex justify-between items-center gap-4 pt-2 mt-1 border-t border-stone-100 text-sm font-semibold">
                        <span class="text-stone-700">Total de despesas</span>
                        <span class="tabular-nums text-red-700">R$ {{ number_format($totalDespesa, 2, ',', '.') }}</span>
                    </div>
                </div>
                <div class="px-5 py-3 {{ $saldo >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                    <div class="flex justify-between items-center gap-4 text-sm font-semibold">
                        <span class="{{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Resultado do mês</span>
                        <span class="tabular-nums {{ $saldo >= 0 ? 'text-emerald-700' : 'text-red-700' }}">R$ {{ number_format($saldo, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-stone-100">
                <h3 class="text-sm font-semibold text-stone-900">Despesas por categoria</h3>
            </div>
            <div class="px-5 py-4 space-y-3">
                @forelse($categoriasDespesa as $cat => $valor)
                @php $pct = $totalDespesa > 0 ? ($valor / $totalDespesa) * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between gap-4 text-sm mb-1">
                        <span class="font-medium text-stone-700 truncate">{{ $cat }}</span>
                        <span class="shrink-0 text-stone-500 tabular-nums">{{ number_format($pct, 1, ',', '.') }}% · R$ {{ number_format($valor, 2, ',', '.') }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-500 transition-all duration-500" style="width: {{ min(100, $pct) }}%"></div>
                    </div>
                </div>
                @empty
                <x-ui.empty-state
                    class="!py-8"
                    titulo="Nenhuma despesa neste mês"
                    texto="Quando você lançar uma saída, ela aparece aqui separada por categoria."
                    icone="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z">
                    <a href="{{ route('transacoes.index') }}" class="btn-secondary">Ver lançamentos</a>
                </x-ui.empty-state>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Maiores fornecedores — contexto do que aconteceu --}}
    @if($topFornecedores->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100">
            <h3 class="text-sm font-semibold text-stone-900">Fornecedores que mais receberam</h3>
            <p class="text-xs text-stone-500 mt-0.5">Despesas pagas no mês</p>
        </div>

        {{-- Celular --}}
        <ul class="divide-y divide-stone-100 md:hidden">
            @foreach($topFornecedores as $i => $item)
            <li class="flex items-center gap-3 px-5 py-3 text-sm">
                <span class="w-5 shrink-0 text-stone-400 tabular-nums">{{ $i + 1 }}</span>
                <span class="min-w-0 flex-1 truncate font-medium text-stone-800">{{ $item->fornecedor?->nome_fantasia ?? '—' }}</span>
                <span class="shrink-0 text-right">
                    <span class="block font-medium tabular-nums text-stone-900">R$ {{ number_format($item->total, 2, ',', '.') }}</span>
                    <span class="block text-xs text-stone-500 tabular-nums">{{ $totalDespesa > 0 ? number_format(($item->total / $totalDespesa) * 100, 1, ',', '.') : '0,0' }}% das despesas</span>
                </span>
            </li>
            @endforeach
        </ul>

        {{-- Desktop --}}
        <table class="hidden md:table w-full text-sm">
            <thead>
                <tr class="bg-stone-50 text-xs font-medium text-stone-500">
                    <th scope="col" class="px-5 py-3 text-left font-medium w-12">#</th>
                    <th scope="col" class="px-5 py-3 text-left font-medium">Fornecedor</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">Valor pago</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">% das despesas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach($topFornecedores as $i => $item)
                <tr class="hover:bg-stone-50 transition-colors">
                    <td class="px-5 py-3 text-stone-400 tabular-nums">{{ $i + 1 }}</td>
                    <td class="px-5 py-3 font-medium text-stone-800">{{ $item->fornecedor?->nome_fantasia ?? '—' }}</td>
                    <td class="px-5 py-3 text-right font-medium tabular-nums text-stone-900">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tabular-nums text-stone-500">
                        {{ $totalDespesa > 0 ? number_format(($item->total / $totalDespesa) * 100, 1, ',', '.') : '0,0' }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Previsão de gastos ──────────────────────────────────── --}}
    @if(!empty($previsao) && $previsao['totalPrevisto'] > 0)
    @php
        $pv = $previsao;
        $pctBar = min(100, $pv['pctExecutado']);
        // Linhas da tabela (apenas apresentação dos valores já calculados)
        $linhasPrevisao = collect([
            ['rotulo' => 'Contratos fixos',     'ponto' => 'bg-rose-400',   'previsto' => $pv['previstoContratos'], 'pago' => $pv['pagoContratos']],
            ['rotulo' => 'Contas de consumo',   'ponto' => 'bg-blue-400',   'previsto' => $pv['previstoFaturas'],   'pago' => $pv['pagoFaturas']],
            ['rotulo' => 'Impostos e tributos', 'ponto' => 'bg-orange-400', 'previsto' => $pv['previstoFiscal'],    'pago' => $pv['pagoFiscal']],
        ])->filter(fn ($l) => $l['previsto'] > 0);
    @endphp

    <h2 class="pt-2 text-base font-semibold text-stone-900">O que está previsto</h2>

    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-stone-900">Previsão de gastos do mês</h3>
                <p class="text-xs text-stone-500 mt-0.5">Quanto estava previsto e quanto já foi pago, por grupo</p>
            </div>
            <span class="badge {{ $pv['pctExecutado'] >= 100 ? 'bg-red-50 text-red-700' : ($pv['pctExecutado'] >= 75 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                {{ number_format($pv['pctExecutado'], 1, ',', '.') }}% pago
            </span>
        </div>

        {{-- Indicadores --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-stone-100">
            <div class="bg-surface px-5 py-4">
                <p class="text-sm text-stone-500">Previsto</p>
                <p class="mt-1 text-xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($pv['totalPrevisto'], 2, ',', '.') }}</p>
                <p class="text-xs text-stone-500 mt-0.5">Soma de tudo que vence no mês</p>
            </div>
            <div class="bg-surface px-5 py-4">
                <p class="text-sm text-stone-500">Já pago</p>
                <p class="mt-1 text-xl font-semibold text-emerald-700 tabular-nums">R$ {{ number_format($pv['totalRealizado'], 2, ',', '.') }}</p>
                <p class="text-xs text-stone-500 mt-0.5">Pagamentos registrados</p>
            </div>
            <div class="bg-surface px-5 py-4">
                <p class="text-sm text-stone-500">Falta pagar</p>
                <p class="mt-1 text-xl font-semibold tabular-nums {{ $pv['totalPendente'] > 0 ? 'text-amber-700' : 'text-stone-400' }}">
                    R$ {{ number_format($pv['totalPendente'], 2, ',', '.') }}
                </p>
                <p class="text-xs text-stone-500 mt-0.5">Ainda pendente</p>
            </div>
            <div class="bg-surface px-5 py-4">
                <p class="text-sm text-stone-500">Andamento</p>
                <div class="mt-2">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-stone-700 tabular-nums">{{ number_format($pv['pctExecutado'], 1, ',', '.') }}%</span>
                        <span class="text-xs text-stone-500">de 100%</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-stone-100 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-700
                            {{ $pv['pctExecutado'] >= 100 ? 'bg-red-500' : ($pv['pctExecutado'] >= 75 ? 'bg-amber-500' : 'bg-rose-500') }}"
                             style="width: {{ $pctBar }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Celular: lista por grupo --}}
        <ul class="divide-y divide-stone-100 border-t border-stone-100 md:hidden">
            @foreach($linhasPrevisao as $linha)
            @php $pctLinha = min(100, ($linha['pago'] / $linha['previsto']) * 100); $falta = max(0, $linha['previsto'] - $linha['pago']); @endphp
            <li class="px-5 py-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="flex items-center gap-2 font-medium text-stone-800"><span class="h-2 w-2 rounded-full shrink-0 {{ $linha['ponto'] }}"></span>{{ $linha['rotulo'] }}</span>
                    <span class="text-xs text-stone-500 tabular-nums">{{ number_format($pctLinha, 0) }}%</span>
                </div>
                <div class="mt-2 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctLinha }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-stone-500 tabular-nums">
                    R$ {{ number_format($linha['pago'], 2, ',', '.') }} de R$ {{ number_format($linha['previsto'], 2, ',', '.') }}
                    @if($falta > 0) · <span class="font-medium text-amber-700">falta R$ {{ number_format($falta, 2, ',', '.') }}</span> @endif
                </p>
            </li>
            @endforeach
            @if($pv['pendentesAvulsos'] > 0)
            <li class="px-5 py-3 text-sm flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 font-medium text-stone-800"><span class="h-2 w-2 rounded-full bg-stone-400 shrink-0"></span>Despesas avulsas pendentes</span>
                <span class="font-medium tabular-nums text-amber-700">R$ {{ number_format($pv['pendentesAvulsos'], 2, ',', '.') }}</span>
            </li>
            @endif
            <li class="px-5 py-3 text-sm flex items-center justify-between gap-3 bg-stone-50 font-semibold">
                <span class="text-stone-700">Total</span>
                <span class="tabular-nums text-stone-900">R$ {{ number_format($pv['totalRealizado'], 2, ',', '.') }} de R$ {{ number_format($pv['totalPrevisto'], 2, ',', '.') }}</span>
            </li>
        </ul>

        {{-- Desktop: tabela por grupo --}}
        <table class="hidden md:table w-full text-sm border-t border-stone-100">
            <thead>
                <tr class="bg-stone-50 text-xs font-medium text-stone-500">
                    <th scope="col" class="px-5 py-3 text-left font-medium">Grupo</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">Previsto</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">Pago</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">Falta pagar</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium">Andamento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach($linhasPrevisao as $linha)
                @php $pctLinha = min(100, ($linha['pago'] / $linha['previsto']) * 100); $falta = max(0, $linha['previsto'] - $linha['pago']); @endphp
                <tr class="hover:bg-stone-50 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full shrink-0 {{ $linha['ponto'] }}"></span>
                            <span class="font-medium text-stone-800">{{ $linha['rotulo'] }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums text-stone-600">R$ {{ number_format($linha['previsto'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tabular-nums text-emerald-700 font-medium">R$ {{ number_format($linha['pago'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right tabular-nums {{ $falta > 0 ? 'text-amber-700 font-medium' : 'text-stone-400' }}">
                        R$ {{ number_format($falta, 2, ',', '.') }}
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <div class="w-20 h-1.5 rounded-full bg-stone-100 overflow-hidden">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $pctLinha }}%"></div>
                            </div>
                            <span class="text-xs text-stone-500 w-10 text-right tabular-nums">{{ number_format($pctLinha, 0) }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach

                {{-- Avulsas pendentes --}}
                @if($pv['pendentesAvulsos'] > 0)
                <tr class="hover:bg-stone-50 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-stone-400 shrink-0"></span>
                            <span class="font-medium text-stone-800">Despesas avulsas pendentes</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums text-stone-400">—</td>
                    <td class="px-5 py-3 text-right tabular-nums text-stone-400">—</td>
                    <td class="px-5 py-3 text-right tabular-nums text-amber-700 font-medium">R$ {{ number_format($pv['pendentesAvulsos'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right text-xs text-stone-500">Aguardando pagamento</td>
                </tr>
                @endif
            </tbody>
            <tfoot class="border-t border-stone-200 bg-stone-50">
                <tr>
                    <td class="px-5 py-3 text-sm font-semibold text-stone-700">Total</td>
                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-stone-900">R$ {{ number_format($pv['totalPrevisto'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-emerald-700">R$ {{ number_format($pv['totalRealizado'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right font-semibold tabular-nums {{ $pv['totalPendente'] > 0 ? 'text-amber-700' : 'text-stone-400' }}">R$ {{ number_format($pv['totalPendente'], 2, ',', '.') }}</td>
                    <td class="px-5 py-3 text-right font-semibold tabular-nums text-stone-700">{{ number_format($pv['pctExecutado'], 1, ',', '.') }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- ─── Seção: O que precisa de atenção ──────────────────── --}}
    <h2 class="pt-2 text-base font-semibold text-stone-900">O que precisa da sua atenção</h2>

    {{-- Pendências --}}
    @php $totalPendencias = $contratosPendentes->count() + $faturasPendentes->count() + $fiscaisPendentes->count(); @endphp

    @if($totalPendencias > 0)
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-stone-900">Contas a pagar do mês</h3>
            <span class="badge bg-amber-50 text-amber-700">
                {{ $totalPendencias }} {{ $totalPendencias === 1 ? 'item' : 'itens' }}
            </span>
        </div>
        <div class="divide-y divide-stone-100">
            @foreach($contratosPendentes as $contrato)
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-2 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-stone-800 truncate">{{ $contrato->fornecedor?->nome_fantasia ?? 'Contrato' }}</p>
                    <p class="text-xs text-stone-500">Contrato fixo mensal</p>
                </div>
                <div class="text-right">
                    <p class="font-medium tabular-nums text-stone-900">R$ {{ number_format($contrato->valor_mensal, 2, ',', '.') }}</p>
                    <span class="badge bg-amber-50 text-amber-700">Não pago</span>
                </div>
                <button type="button" wire:click="abrirPagarContrato('{{ $contrato->id }}')" class="btn-secondary w-full sm:w-auto">
                    Registrar pagamento
                </button>
            </div>
            @endforeach
            @foreach($faturasPendentes as $fat)
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-2 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-stone-800 truncate">{{ $fat->contaConsumo?->descricao ?? 'Fatura' }}</p>
                    <p class="text-xs text-stone-500">{{ $fat->contaConsumo?->tipo }} · {{ $fat->competenciaFormatada() }}</p>
                </div>
                <div class="text-right">
                    <p class="font-medium tabular-nums text-stone-900">{{ $fat->valor !== null ? 'R$ ' . number_format($fat->valor, 2, ',', '.') : 'Valor não informado' }}</p>
                    <span class="badge {{ $fat->status->value === 'vencida' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $fat->status->label() }}
                    </span>
                </div>
                <button type="button" wire:click="abrirPagarFatura('{{ $fat->id }}')" class="btn-secondary w-full sm:w-auto">
                    Registrar pagamento
                </button>
            </div>
            @endforeach
            @foreach($fiscaisPendentes as $lanc)
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-x-4 gap-y-2 px-5 py-3 text-sm">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-stone-800 truncate">{{ $lanc->obrigacaoFiscal?->descricao ?? $lanc->obrigacaoFiscal?->tipo_tributo }}</p>
                    <p class="text-xs text-stone-500">
                        Imposto · {{ $lanc->competenciaFormatada() }}
                        @if($lanc->data_vencimento) · vence em {{ $lanc->data_vencimento->format('d/m') }} @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-medium tabular-nums text-stone-900">R$ {{ number_format($lanc->valorTotal(), 2, ',', '.') }}</p>
                    <span class="badge {{ $lanc->status->value === 'vencido' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $lanc->status->label() }}
                    </span>
                </div>
                <button type="button" wire:click="abrirPagarFiscal('{{ $lanc->id }}')" class="btn-secondary w-full sm:w-auto">
                    Registrar pagamento
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span><strong class="font-semibold">Tudo em dia!</strong> Nenhuma conta pendente neste mês.</span>
    </div>
    @endif

    @endif {{-- fim aba gerencial --}}

    {{-- ═══════════════════════════════════════════════════════════
         ABA: CONTÁBIL
    ═══════════════════════════════════════════════════════════ --}}
    @if($tab === 'contabil')

    @if(!empty($totaisContabil))

    {{-- Conferência dos totais --}}
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl px-4 py-3 text-sm
        {{ $totaisContabil['integridade_ok'] ? 'bg-emerald-50 border border-emerald-100 text-emerald-700' : 'bg-red-50 border border-red-100 text-red-700' }}">
        @if($totaisContabil['integridade_ok'])
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="font-medium">Números conferidos: a soma dos lançamentos bate com os totais.</span>
        <span class="sm:ml-auto text-xs">{{ $totaisContabil['qtd_lancamentos'] }} lançamentos</span>
        @else
        <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        <span class="font-medium">Os totais não batem com a soma dos lançamentos. Revise os lançamentos do mês antes de enviar ao contador.</span>
        <span class="sm:ml-auto text-xs tabular-nums">
            Soma das linhas: R$ {{ number_format($totaisContabil['soma_linhas'], 2, ',', '.') }} ·
            Soma dos totais: R$ {{ number_format($totaisContabil['soma_agregados'], 2, ',', '.') }}
        </span>
        @endif
    </div>

    {{-- Indicadores contábeis --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Receitas</p>
            <p class="mt-2 text-xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totaisContabil['total_receitas'], 2, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Despesas</p>
            <p class="mt-2 text-xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totaisContabil['total_despesas'], 2, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Tributos</p>
            <p class="mt-2 text-xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totaisContabil['total_tributos'], 2, ',', '.') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Taxas</p>
            <p class="mt-2 text-xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totaisContabil['total_taxas'], 2, ',', '.') }}</p>
        </div>
        <div class="card p-5 col-span-2 lg:col-span-1">
            <p class="text-sm text-stone-500">Resultado</p>
            <p class="mt-2 text-xl font-semibold tabular-nums {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                R$ {{ number_format($totaisContabil['resultado'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Lançamentos pagos --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-stone-900">Lançamentos pagos</h2>
                <p class="text-xs text-stone-500 mt-0.5">Só entram aqui os lançamentos com situação "Pago".</p>
            </div>
            <span class="text-xs text-stone-500 shrink-0">{{ $transacoesContabil->count() }} {{ $transacoesContabil->count() === 1 ? 'registro' : 'registros' }}</span>
        </div>

        @if($transacoesContabil->isEmpty())
        <x-ui.empty-state
            titulo="Nenhum lançamento pago neste mês"
            texto="Quando você marcar lançamentos como pagos, eles aparecem aqui prontos para o contador."
            icone="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z">
            <a href="{{ route('transacoes.index') }}" class="btn-secondary">Ver lançamentos</a>
        </x-ui.empty-state>
        @else
        {{-- Celular: lista --}}
        <ul class="divide-y divide-stone-100 md:hidden">
            @foreach($transacoesContabil as $t)
            <li class="flex items-start gap-3 px-5 py-3 text-sm">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium text-stone-800">{{ $t['descricao'] }}</p>
                    <p class="mt-0.5 truncate text-xs text-stone-500">{{ $t['data'] }} · {{ $t['classificacao'] }} · {{ $t['categoria'] }}</p>
                </div>
                <span class="shrink-0 font-medium tabular-nums {{ $t['tipo'] === 'Entrada' ? 'text-emerald-700' : 'text-stone-800' }}">
                    {{ $t['tipo'] === 'Entrada' ? '+' : '−' }} R$ {{ number_format($t['valor'], 2, ',', '.') }}
                </span>
            </li>
            @endforeach
        </ul>

        {{-- Desktop: tabela --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50 text-xs font-medium text-stone-500">
                        <th scope="col" class="px-4 py-3 text-left font-medium">Data</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Classificação</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Categoria</th>
                        <th scope="col" class="px-4 py-3 text-left font-medium">Descrição</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach($transacoesContabil as $t)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-4 py-2.5 text-stone-500 whitespace-nowrap tabular-nums">{{ $t['data'] }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="badge {{ $t['tipo'] === 'Entrada' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                {{ $t['tipo'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="badge
                                @if($t['classificacao'] === 'Receita')  bg-emerald-50 text-emerald-700
                                @elseif($t['classificacao'] === 'Tributo') bg-orange-50 text-orange-700
                                @elseif($t['classificacao'] === 'Taxa')    bg-blue-50 text-blue-700
                                @else                                      bg-stone-100 text-stone-600
                                @endif">
                                {{ $t['classificacao'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-stone-600 max-w-[160px] truncate">{{ $t['categoria'] }}</td>
                        <td class="px-4 py-2.5 text-stone-800 max-w-[220px] truncate">{{ $t['descricao'] }}</td>
                        <td class="px-4 py-2.5 text-right font-medium whitespace-nowrap tabular-nums
                            {{ $t['tipo'] === 'Entrada' ? 'text-emerald-700' : 'text-stone-800' }}">
                            {{ number_format($t['valor'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-stone-200 bg-stone-50">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-sm font-medium text-stone-600">
                            Total de receitas
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">
                            {{ number_format($totaisContabil['total_receitas'], 2, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="border-t border-stone-200">
                        <td colspan="5" class="px-4 py-3 text-sm font-medium text-stone-600">
                            Total de saídas (despesas + tributos + taxas)
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-red-700">
                            {{ number_format($totaisContabil['total_saidas'], 2, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="border-t border-stone-200 {{ $totaisContabil['resultado'] >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}">
                        <td colspan="5" class="px-4 py-3 text-sm font-semibold {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            Resultado do mês
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-base tabular-nums {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ number_format($totaisContabil['resultado'], 2, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Celular: totais --}}
        <dl class="md:hidden border-t border-stone-200 bg-stone-50 divide-y divide-stone-200 text-sm">
            <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-600">Total de receitas</dt><dd class="font-semibold tabular-nums text-emerald-700">R$ {{ number_format($totaisContabil['total_receitas'], 2, ',', '.') }}</dd></div>
            <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-stone-600">Total de saídas</dt><dd class="font-semibold tabular-nums text-red-700">R$ {{ number_format($totaisContabil['total_saidas'], 2, ',', '.') }}</dd></div>
            <div class="flex justify-between gap-4 px-5 py-3 {{ $totaisContabil['resultado'] >= 0 ? 'bg-emerald-50' : 'bg-red-50' }}"><dt class="font-semibold {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Resultado do mês</dt><dd class="font-semibold tabular-nums {{ $totaisContabil['resultado'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">R$ {{ number_format($totaisContabil['resultado'], 2, ',', '.') }}</dd></div>
        </dl>
        @endif
    </div>

    @else
    <div class="card">
        <x-ui.empty-state
            titulo="Sem dados para este mês"
            texto="Escolha outro mês no topo da tela ou registre os lançamentos deste mês."
            icone="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5">
            <a href="{{ route('transacoes.index') }}" class="btn-secondary">Ver lançamentos</a>
        </x-ui.empty-state>
    </div>
    @endif

    @endif {{-- fim aba contábil --}}

    </div>

    {{-- ─── Modal de pagamento ─────────────────────────────────── --}}
    @if($modalPagar)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4"
         x-data x-on:keydown.escape.window="$wire.fecharModalPagamento()">

        <div class="absolute inset-0 bg-black/40" wire:click="fecharModalPagamento"></div>

        <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-pagamento" class="relative w-full sm:max-w-md max-h-[92vh] overflow-y-auto bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl">

            {{-- Cabeçalho --}}
            <div class="flex items-start justify-between gap-4 px-5 sm:px-6 py-4 border-b border-stone-100">
                <div class="min-w-0">
                    <h3 id="titulo-modal-pagamento" class="text-lg font-semibold text-stone-900">Registrar pagamento</h3>
                    <p class="text-sm text-stone-500 mt-0.5 truncate">{{ $pagarNome }}</p>
                </div>
                <button type="button" wire:click="fecharModalPagamento" class="btn-ghost -mr-2 -mt-1 w-11 px-0 text-stone-400" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Corpo --}}
            <div class="px-5 sm:px-6 py-5 space-y-4">

                {{-- Erro dentro do modal --}}
                @if($flashErro)
                <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700" role="alert">
                    {{ $flashErro }}
                </div>
                @endif

                {{-- Competência (só contratos) --}}
                @if($modalPagarTipo === 'contrato')
                <div>
                    <label for="pagar-competencia" class="label">Mês de referência</label>
                    <input id="pagar-competencia" type="month" wire:model="pagarCompetencia" class="input">
                    @error('pagarCompetencia') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Valor (contratos e faturas) --}}
                @if(in_array($modalPagarTipo, ['contrato', 'fatura']))
                <div>
                    <label for="pagar-valor" class="label">Valor pago (R$)</label>
                    <input id="pagar-valor" type="number" inputmode="decimal" step="0.01" min="0.01" wire:model="pagarValor" placeholder="Ex.: 350,00" class="input tabular-nums">
                    @error('pagarValor') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Campos fiscais --}}
                @if($modalPagarTipo === 'fiscal')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label for="pagar-principal" class="label">Principal (R$)</label>
                        <input id="pagar-principal" type="number" step="0.01" wire:model="pagarValor" readonly
                               class="input tabular-nums bg-stone-50 text-stone-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="pagar-multa" class="label">Multa (R$)</label>
                        <input id="pagar-multa" type="number" inputmode="decimal" step="0.01" min="0" wire:model="pagarValorMulta" placeholder="0,00" class="input tabular-nums">
                    </div>
                    <div>
                        <label for="pagar-juros" class="label">Juros (R$)</label>
                        <input id="pagar-juros" type="number" inputmode="decimal" step="0.01" min="0" wire:model="pagarValorJuros" placeholder="0,00" class="input tabular-nums">
                    </div>
                </div>
                <div>
                    <label for="pagar-autenticacao" class="label">Nº de autenticação <span class="font-normal text-stone-500">(opcional)</span></label>
                    <input id="pagar-autenticacao" type="text" wire:model="pagarNumAutenticacao" placeholder="Ex.: o código impresso no comprovante" class="input">
                </div>
                @endif

                {{-- Data de pagamento --}}
                <div>
                    <label for="pagar-data" class="label">Data do pagamento</label>
                    <input id="pagar-data" type="date" wire:model="pagarDataPagamento" class="input">
                    @error('pagarDataPagamento') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Forma de pagamento --}}
                <div>
                    <label for="pagar-forma" class="label">Forma de pagamento</label>
                    <select id="pagar-forma" wire:model="pagarFormaPagamento" class="input">
                        <option value="pix">Pix</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                        <option value="debito_automatico">Débito automático</option>
                        <option value="cartao_credito">Cartão de crédito</option>
                        <option value="cartao_debito">Cartão de débito</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                    @error('pagarFormaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Observações (contratos) --}}
                @if($modalPagarTipo === 'contrato')
                <div>
                    <label for="pagar-obs" class="label">Observações <span class="font-normal text-stone-500">(opcional)</span></label>
                    <textarea id="pagar-obs" wire:model="pagarObservacoes" rows="2" placeholder="Ex.: Pago com desconto de pontualidade" class="input resize-none"></textarea>
                </div>
                @endif
            </div>

            {{-- Rodapé --}}
            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-3 px-5 sm:px-6 py-4 border-t border-stone-100 bg-stone-50">
                <button type="button" wire:click="fecharModalPagamento" class="btn-secondary">
                    Cancelar
                </button>
                <button type="button" wire:click="confirmarPagamento" wire:loading.attr="disabled" class="btn-primary">
                    <svg wire:loading wire:target="confirmarPagamento" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Confirmar pagamento
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

    /* Na impressão, sempre usar as tabelas (e não as listas do celular) */
    .md\:hidden { display: none !important; }
    .hidden.md\:block { display: block !important; }
    .hidden.md\:table { display: table !important; }

    /* Evitar quebra de página no meio de cards */
    .rounded-xl, .card {
        break-inside: avoid;
    }
}
</style>
@endpush
