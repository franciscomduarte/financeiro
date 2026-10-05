@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush


<div>

    {{-- ─── Cabeçalho ─────────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Início" :subtitulo="'Resumo de ' . mb_strtolower($mesLabel) . ', comparado com ' . mb_strtolower($mesAnteriorLabel) . '.'">
        @if ($totalAlertas > 0)
            <x-slot:acoes>
                <span class="badge bg-red-50 text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    {{ $totalAlertas }} {{ $totalAlertas > 1 ? 'alertas' : 'alerta' }}
                </span>
            </x-slot:acoes>
        @endif
    </x-ui.page-header>

    {{-- Espaço reservado logo abaixo do cabeçalho (ex.: primeiros passos) --}}

    <div class="space-y-6">

    {{-- ─── 1. KPIs — "Como estou este mês?" ──────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @php
            $kpis = [
                ['label' => 'Receitas do mês',  'valor' => $receitaMes,  'anterior' => $receitaMesAnterior,  'cor' => 'emerald', 'icone' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
                ['label' => 'Despesas do mês',  'valor' => $despesaMes,  'anterior' => $despesaMesAnterior,  'cor' => 'rose',    'icone' => 'M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181', 'inverso' => true],
                ['label' => 'Saldo do mês',     'valor' => $saldoMes,    'anterior' => $saldoMesAnterior,    'cor' => $saldoMes >= 0 ? 'blue' : 'orange', 'icone' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
            // Classes completas (Tailwind só gera classes escritas por extenso)
            $iconeCor = [
                'emerald' => 'bg-emerald-50 text-emerald-600',
                'rose'    => 'bg-rose-50 text-rose-600',
                'blue'    => 'bg-blue-50 text-blue-600',
                'orange'  => 'bg-orange-50 text-orange-600',
            ];
        @endphp
        @foreach ($kpis as $kpi)
            @php
                $delta  = $kpi['anterior'] > 0 ? round((($kpi['valor'] - $kpi['anterior']) / $kpi['anterior']) * 100, 1) : null;
                $subiu  = $delta !== null && $delta > 0;
                $cor    = $kpi['cor'];
                $isGood = isset($kpi['inverso']) ? !$subiu : $subiu;
            @endphp
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm text-stone-500">{{ $kpi['label'] }}</p>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $iconeCor[$cor] ?? 'bg-stone-100 text-stone-600' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icone'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-2 text-2xl font-semibold tabular-nums {{ $kpi['valor'] < 0 ? 'text-rose-600' : 'text-stone-900' }}">
                    @if ($kpi['valor'] < 0)−@endif R$ {{ number_format(abs($kpi['valor']), 2, ',', '.') }}
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                    @if ($delta !== null)
                        <span class="inline-flex items-center gap-0.5 font-medium {{ $isGood ? 'text-emerald-600' : 'text-rose-600' }}">
                            @if ($delta > 0)<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/></svg>
                            @elseif ($delta < 0)<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
                            @endif
                            {{ number_format(abs($delta), 1, ',', '.') }}%
                        </span>
                        <span class="text-stone-500">em relação a {{ mb_strtolower($mesAnteriorLabel) }}</span>
                    @else
                        <span class="text-stone-500">Sem dados de {{ mb_strtolower($mesAnteriorLabel) }} para comparar</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── 2. Alertas — "O que precisa de atenção?" ───────────────── --}}
    {{-- Mesma fonte do resumo diário enviado por e-mail/WhatsApp (AlertasVencimentoService) --}}
    @if ($totalAlertas > 0)
    <div class="rounded-2xl border border-stone-200 bg-surface shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            <h2 class="text-sm font-semibold text-stone-800">O que precisa de atenção</h2>
            <span class="ml-auto text-xs text-stone-400">{{ $totalAlertas }} item{{ $totalAlertas > 1 ? 's' : '' }}</span>
        </div>
        <div class="divide-y divide-stone-50">
            @foreach ([['vencidos', 'Já venceram', $alertasVencidos], ['avencer', 'Vencimento próximo', $alertasAVencer]] as [$grupo, $tituloGrupo, $itens])
                @continue($itens->isEmpty())
                @php $urgente = $grupo === 'vencidos'; @endphp
                <div class="px-5 py-2 flex items-center gap-2 {{ $urgente ? 'bg-red-50' : 'bg-amber-50' }}">
                    <span class="h-1.5 w-1.5 rounded-full shrink-0 {{ $urgente ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                    <p class="text-xs font-bold uppercase tracking-wider {{ $urgente ? 'text-red-700' : 'text-amber-700' }}">
                        {{ $tituloGrupo }} · {{ $itens->count() }} item{{ $itens->count() > 1 ? 's' : '' }}
                    </p>
                </div>
                @foreach ($itens as $alerta)
                    <a href="{{ route($alerta->tipo->rota()) }}"
                       class="flex min-h-[56px] items-center gap-3 px-5 py-3 transition {{ $urgente ? 'bg-red-50/30 hover:bg-red-50/60' : 'hover:bg-stone-50/60' }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-800">{{ $alerta->titulo }}</p>
                            <p class="text-xs text-stone-400">
                                <span class="sm:hidden">{{ $alerta->tipo->label() }} · </span>{{ $alerta->data->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            @if ($alerta->valorFormatado())
                                <p class="text-sm font-semibold tabular-nums text-stone-800">{{ $alerta->valorFormatado() }}</p>
                            @endif
                            <p class="text-xs font-semibold {{ $urgente ? 'text-red-600' : 'text-amber-600' }}">{{ ucfirst($alerta->prazo($hojeAlertas)) }}</p>
                        </div>
                        <span class="hidden shrink-0 rounded-full px-2 py-0.5 text-xs font-medium sm:inline {{ $urgente ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">{{ $alerta->tipo->label() }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
    @else
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-center gap-3 text-sm text-emerald-800">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span><strong>Tudo em dia!</strong> Nenhum vencimento próximo.</span>
    </div>
    @endif

    {{-- ─── 3. Gráficos — "Como estou evoluindo?" ──────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4"
         x-data
         x-init="
            const ctx1 = document.getElementById('chart-fluxo');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'bar',
                    data: {
                        labels: {{ Js::from($fluxo->pluck('label')) }},
                        datasets: [
                            { label: 'Receita', data: {{ Js::from($fluxo->pluck('receita')) }}, backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 6, borderSkipped: false },
                            { label: 'Despesa', data: {{ Js::from($fluxo->pluck('despesa')) }}, backgroundColor: 'rgba(244,63,94,0.75)',  borderRadius: 6, borderSkipped: false }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }, tooltip: { callbacks: { label: ctx => 'R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) } } },
                        scales: { x: { grid: { display: false }, ticks: { font: { size: 11 } } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 11 }, callback: v => 'R$ ' + v.toLocaleString('pt-BR') } } }
                    }
                });
            }
            const ctx2 = document.getElementById('chart-categorias');
            @if ($despesasCat->isNotEmpty())
            if (ctx2) {
                const cores = ['#f43f5e','#f97316','#eab308','#10b981','#3b82f6','#8b5cf6','#ec4899','#06b6d4'];
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: { labels: {{ Js::from($despesasCat->pluck('categoria')) }}, datasets: [{ data: {{ Js::from($despesasCat->pluck('total')->map(fn($v) => (float)$v)) }}, backgroundColor: cores.slice(0, {{ $despesasCat->count() }}), borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, padding: 12 } }, tooltip: { callbacks: { label: ctx => ' R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) } } }, cutout: '68%' }
                });
            }
            @endif
         ">
        <div class="lg:col-span-2 rounded-2xl border border-stone-200 bg-surface p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-stone-800 mb-0.5">Fluxo de Caixa</h2>
            <p class="text-xs text-stone-400 mb-4">Últimos 6 meses — receita vs. despesa</p>
            <div class="relative h-56"><canvas id="chart-fluxo"></canvas></div>
        </div>
        <div class="rounded-2xl border border-stone-200 bg-surface p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-stone-800 mb-0.5">Despesas por Categoria</h2>
            <p class="text-xs text-stone-400 mb-4">{{ $mesLabel }}</p>
            @if ($despesasCat->isNotEmpty())
                <div class="relative h-56"><canvas id="chart-categorias"></canvas></div>
            @else
                <div class="flex h-56 items-center justify-center text-sm text-stone-400 flex-col gap-2">
                    <svg class="w-8 h-8 text-stone-200" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/></svg>
                    Sem despesas registradas
                </div>
            @endif
        </div>
    </div>

    {{-- ─── 4. Referência — "Quanto preciso para funcionar?" ──────────── --}}
    @php $cm = $custoMinimo; @endphp
    <div class="rounded-2xl border border-stone-200 bg-surface shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-stone-100 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-stone-800">Custo Mínimo para Operar</h2>
                <p class="text-xs text-stone-400 mt-0.5">Valor de referência — o piso mensal para manter a operação ativa</p>
            </div>
            <a href="{{ route('web.contratos') }}" class="shrink-0 text-xs text-stone-400 hover:text-rose-600 transition-colors">gerenciar →</a>
        </div>
        <div class="px-5 py-5 flex flex-col sm:flex-row sm:items-center gap-6">
            <div class="shrink-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-400 mb-1">Total Mensal</p>
                <p class="text-4xl font-bold tabular-nums text-stone-800">R$ {{ number_format($cm['totalMinimo'], 2, ',', '.') }}</p>
                <p class="text-xs text-stone-400 mt-1">por mês, em condições normais de operação</p>
            </div>
            <div class="hidden sm:block self-stretch border-l border-stone-100"></div>
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-rose-50 border border-rose-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-rose-400 shrink-0"></span><p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">Contratos Fixos</p></div>
                    <p class="text-xl font-bold tabular-nums text-stone-800">R$ {{ number_format($cm['custoContratos'], 2, ',', '.') }}</p>
                    <p class="text-xs text-stone-400 mt-1">{{ $cm['numContratos'] }} contrato{{ $cm['numContratos'] !== 1 ? 's' : '' }} ativo{{ $cm['numContratos'] !== 1 ? 's' : '' }} · valor exato</p>
                </div>
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-blue-400 shrink-0"></span><p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Contas de Consumo</p></div>
                    <p class="text-xl font-bold tabular-nums text-stone-800">R$ {{ number_format($cm['mediaConsumo'], 2, ',', '.') }}</p>
                    <p class="text-xs text-stone-400 mt-1">@if($cm['mesesConsumo'] > 0) média de {{ $cm['mesesConsumo'] }} {{ $cm['mesesConsumo'] === 1 ? 'mês' : 'meses' }} · estimado @else sem histórico ainda @endif</p>
                </div>
                <div class="rounded-xl bg-orange-50 border border-orange-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-orange-400 shrink-0"></span><p class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Fiscal / Tributos</p></div>
                    <p class="text-xl font-bold tabular-nums text-stone-800">R$ {{ number_format($cm['mediaFiscal'], 2, ',', '.') }}</p>
                    <p class="text-xs text-stone-400 mt-1">@if($cm['mesesFiscal'] > 0) média de {{ $cm['mesesFiscal'] }} {{ $cm['mesesFiscal'] === 1 ? 'mês' : 'meses' }} · estimado @else sem histórico ainda @endif</p>
                </div>
            </div>
        </div>
        <div class="border-t border-stone-100 bg-stone-50 px-5 py-3 text-xs text-stone-400">
            Contratos: valor atual dos contratos ativos · Consumo e fiscal: média dos últimos {{ $cm['mesesHistorico'] }} meses com lançamentos registrados
        </div>
    </div>

</div>
