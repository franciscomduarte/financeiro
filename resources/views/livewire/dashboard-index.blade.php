@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endpush

@php
// Pré-classifica alertas em urgentes vs próximos para uso na seção de alertas
$alertasUrgentes = collect();
$alertasProximos = collect();

foreach ($alertasFiscais as $i) {
    $vencida = $i->data_vencimento->isPast() || $i->status->value === 'vencido';
    $vencida
        ? $alertasUrgentes->push(['tipo' => 'fiscal', 'item' => $i, 'vencida' => true])
        : $alertasProximos->push(['tipo' => 'fiscal', 'item' => $i, 'vencida' => false]);
}
foreach ($alertasFaturas as $f) {
    $vencida = $f->status->value === 'vencida' || ($f->data_vencimento && $f->data_vencimento->isPast());
    $vencida
        ? $alertasUrgentes->push(['tipo' => 'fatura', 'item' => $f, 'vencida' => true])
        : $alertasProximos->push(['tipo' => 'fatura', 'item' => $f, 'vencida' => false]);
}
foreach ($alertasContratosFim as $c) {
    $dias = now()->diffInDays($c->data_fim, false);
    $dias <= 14
        ? $alertasUrgentes->push(['tipo' => 'contrato_fim', 'item' => $c, 'dias' => $dias])
        : $alertasProximos->push(['tipo' => 'contrato_fim', 'item' => $c, 'dias' => $dias]);
}
foreach ($alertasContratosPgto as $c) {
    $alertasProximos->push(['tipo' => 'contrato_pgto', 'item' => $c]);
}
foreach ($alertasDocumentos as $d) {
    $diasDoc = $d->diasParaVencer();
    $diasDoc !== null && $diasDoc < 0
        ? $alertasUrgentes->push(['tipo' => 'documento', 'item' => $d, 'diasDoc' => $diasDoc])
        : $alertasProximos->push(['tipo' => 'documento', 'item' => $d, 'diasDoc' => $diasDoc]);
}
@endphp

<div class="space-y-6">

    {{-- ─── Cabeçalho ─────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $mesLabel }} · comparando com {{ $mesAnteriorLabel }}</p>
        </div>
        @if ($totalAlertas > 0)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1.5 text-sm font-semibold text-red-700">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                {{ $totalAlertas }} alerta{{ $totalAlertas > 1 ? 's' : '' }}
            </span>
        @endif
    </div>

    {{-- ─── 1. KPIs — "Como estou este mês?" ──────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @php
            $kpis = [
                ['label' => 'Receita do mês',   'valor' => $receitaMes,  'anterior' => $receitaMesAnterior,  'cor' => 'emerald', 'icone' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941'],
                ['label' => 'Despesas do mês',  'valor' => $despesaMes,  'anterior' => $despesaMesAnterior,  'cor' => 'rose',    'icone' => 'M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181', 'inverso' => true],
                ['label' => 'Saldo do mês',     'valor' => $saldoMes,    'anterior' => $saldoMesAnterior,    'cor' => $saldoMes >= 0 ? 'blue' : 'orange', 'icone' => 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        @endphp
        @foreach ($kpis as $kpi)
            @php
                $delta  = $kpi['anterior'] > 0 ? round((($kpi['valor'] - $kpi['anterior']) / $kpi['anterior']) * 100, 1) : null;
                $subiu  = $delta !== null && $delta > 0;
                $cor    = $kpi['cor'];
                $isGood = isset($kpi['inverso']) ? !$subiu : $subiu;
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $kpi['label'] }}</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-{{ $cor }}-50">
                        <svg class="h-5 w-5 text-{{ $cor }}-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $kpi['icone'] }}"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums {{ $kpi['valor'] < 0 ? 'text-rose-600' : 'text-slate-800' }}">
                    R$ {{ number_format(abs($kpi['valor']), 2, ',', '.') }}
                    @if ($kpi['valor'] < 0)<span class="text-xl">−</span>@endif
                </p>
                <div class="mt-2 flex items-center gap-1.5 text-xs">
                    @if ($delta !== null)
                        <span class="inline-flex items-center gap-0.5 font-semibold {{ $isGood ? 'text-emerald-600' : 'text-rose-600' }}">
                            @if ($delta > 0)<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18"/></svg>
                            @elseif ($delta < 0)<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3"/></svg>
                            @endif
                            {{ abs($delta) }}%
                        </span>
                        <span class="text-slate-400">vs {{ $mesAnteriorLabel }}</span>
                    @else
                        <span class="text-slate-400">sem dados no mês anterior</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ─── 2. Alertas — "O que precisa de atenção?" ───────────────── --}}
    @if ($totalAlertas > 0)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            <h2 class="text-sm font-semibold text-slate-800">O que precisa de atenção</h2>
            <span class="ml-auto text-xs text-slate-400">{{ $totalAlertas }} item{{ $totalAlertas > 1 ? 's' : '' }}</span>
        </div>
        <div class="divide-y divide-slate-50">

            {{-- Grupo: Ação imediata --}}
            @if ($alertasUrgentes->isNotEmpty())
            <div class="px-5 py-2 bg-red-50 flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>
                <p class="text-xs font-bold text-red-700 uppercase tracking-wider">Requer ação imediata · {{ $alertasUrgentes->count() }} item{{ $alertasUrgentes->count() > 1 ? 's' : '' }}</p>
            </div>
            @foreach ($alertasUrgentes as $alerta)
                @php $item = $alerta['item']; $tipo = $alerta['tipo']; @endphp
                <div class="flex items-center gap-4 px-5 py-3.5 bg-red-50/30 hover:bg-red-50/60 transition">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100">
                        @if($tipo === 'fiscal')
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        @elseif($tipo === 'fatura')
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        @elseif($tipo === 'contrato_fim')
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        @else
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($tipo === 'fiscal')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->obrigacaoFiscal->descricao }}</p>
                            <p class="text-xs text-slate-400">{{ $item->obrigacaoFiscal->tipo_tributo->label() }} · {{ $item->competenciaFormatada() }}</p>
                        @elseif($tipo === 'fatura')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->contaConsumo->descricao }}</p>
                            <p class="text-xs text-slate-400">{{ $item->contaConsumo->tipo->label() }} · {{ $item->competenciaFormatada() }}</p>
                        @elseif($tipo === 'contrato_fim')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->fornecedor->nome_fantasia }}</p>
                            <p class="text-xs text-slate-400">Contrato encerra em {{ $item->data_fim->format('d/m/Y') }}</p>
                        @else
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->titulo }}</p>
                            <p class="text-xs text-slate-400">{{ $item->categoria->nome }}</p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        @if($tipo === 'fiscal')
                            <p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format($item->valorTotal(), 2, ',', '.') }}</p>
                            <p class="text-xs text-red-600 font-semibold">Vence {{ $item->data_vencimento->format('d/m') }} · VENCIDA</p>
                        @elseif($tipo === 'fatura')
                            @if($item->valor)<p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format((float)$item->valor, 2, ',', '.') }}</p>@endif
                            <p class="text-xs text-red-600 font-semibold">VENCIDA</p>
                        @elseif($tipo === 'contrato_fim')
                            @if($item->valor_mensal)<p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format((float)$item->valor_mensal, 2, ',', '.') }}/mês</p>@endif
                            <p class="text-xs text-red-600 font-semibold">{{ ($alerta['dias'] ?? 0) <= 0 ? 'Encerrou' : 'Faltam ' . ($alerta['dias'] ?? 0) . ' dias' }}</p>
                        @else
                            <p class="text-xs text-red-600 font-semibold">Vencido há {{ abs($alerta['diasDoc'] ?? 0) }}d</p>
                        @endif
                    </div>
                    @php
                        $badge = match($tipo) { 'fiscal' => 'Fiscal', 'fatura' => 'Consumo', 'contrato_fim' => 'Contrato', default => 'Documento' };
                    @endphp
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700">{{ $badge }}</span>
                </div>
            @endforeach
            @endif

            {{-- Grupo: Vencimento próximo --}}
            @if ($alertasProximos->isNotEmpty())
            <div class="px-5 py-2 bg-amber-50 flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Vencimento próximo · {{ $alertasProximos->count() }} item{{ $alertasProximos->count() > 1 ? 's' : '' }}</p>
            </div>
            @foreach ($alertasProximos as $alerta)
                @php $item = $alerta['item']; $tipo = $alerta['tipo']; @endphp
                <div class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60 transition">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                        @if($tipo === 'fiscal')
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                        @elseif($tipo === 'fatura')
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        @elseif($tipo === 'contrato_fim')
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        @elseif($tipo === 'contrato_pgto')
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                        @else
                            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($tipo === 'fiscal')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->obrigacaoFiscal->descricao }}</p>
                            <p class="text-xs text-slate-400">{{ $item->obrigacaoFiscal->tipo_tributo->label() }} · {{ $item->competenciaFormatada() }}</p>
                        @elseif($tipo === 'fatura')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->contaConsumo->descricao }}</p>
                            <p class="text-xs text-slate-400">{{ $item->contaConsumo->tipo->label() }} · {{ $item->competenciaFormatada() }}</p>
                        @elseif($tipo === 'contrato_fim')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->fornecedor->nome_fantasia }}</p>
                            <p class="text-xs text-slate-400">Contrato encerra em {{ $item->data_fim->format('d/m/Y') }}</p>
                        @elseif($tipo === 'contrato_pgto')
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->fornecedor->nome_fantasia }}</p>
                            <p class="text-xs text-slate-400">Pagamento mensal — dia {{ $item->dia_vencimento }}</p>
                        @else
                            <p class="text-sm font-medium text-slate-800 truncate">{{ $item->titulo }}</p>
                            <p class="text-xs text-slate-400">{{ $item->categoria->nome }}</p>
                        @endif
                    </div>
                    <div class="text-right shrink-0">
                        @if($tipo === 'fiscal')
                            <p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format($item->valorTotal(), 2, ',', '.') }}</p>
                            <p class="text-xs text-amber-600">Vence {{ $item->data_vencimento->format('d/m') }}</p>
                        @elseif($tipo === 'fatura')
                            @if($item->valor)<p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format((float)$item->valor, 2, ',', '.') }}</p>
                            @else<p class="text-xs text-slate-400">Valor pendente</p>@endif
                            @if($item->data_vencimento)<p class="text-xs text-amber-600">Vence {{ $item->data_vencimento->format('d/m') }}</p>@endif
                        @elseif($tipo === 'contrato_fim')
                            @if($item->valor_mensal)<p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format((float)$item->valor_mensal, 2, ',', '.') }}/mês</p>@endif
                            <p class="text-xs text-amber-600">Faltam {{ $alerta['dias'] }} dias</p>
                        @elseif($tipo === 'contrato_pgto')
                            @if($item->valor_mensal)<p class="text-sm font-semibold tabular-nums text-slate-800">R$ {{ number_format((float)$item->valor_mensal, 2, ',', '.') }}</p>@endif
                            <p class="text-xs text-amber-600 font-semibold">{{ $item->diasParaVencimento() === 0 ? 'Vence hoje' : 'Vence em ' . $item->diasParaVencimento() . 'd' }}</p>
                        @else
                            @php $d = $alerta['diasDoc'] ?? null; @endphp
                            <p class="text-xs text-amber-600">{{ $d === 0 ? 'Vence hoje' : 'Vence em ' . $d . 'd' }} · {{ $item->data_validade->format('d/m/Y') }}</p>
                        @endif
                    </div>
                    @php
                        $badge = match($tipo) { 'fiscal' => ['label' => 'Fiscal', 'cls' => 'bg-amber-100 text-amber-700'], 'fatura' => ['label' => 'Consumo', 'cls' => 'bg-orange-100 text-orange-700'], 'contrato_fim' => ['label' => 'Contrato', 'cls' => 'bg-amber-100 text-amber-700'], 'contrato_pgto' => ['label' => 'Pagamento', 'cls' => 'bg-violet-100 text-violet-700'], default => ['label' => 'Documento', 'cls' => 'bg-blue-100 text-blue-700'] };
                    @endphp
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $badge['cls'] }}">{{ $badge['label'] }}</span>
                </div>
            @endforeach
            @endif

        </div>
    </div>
    @else
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-center gap-3 text-sm text-emerald-800">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span><strong>Tudo em dia!</strong> Nenhum alerta no momento.</span>
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
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-0.5">Fluxo de Caixa</h2>
            <p class="text-xs text-slate-400 mb-4">Últimos 6 meses — receita vs. despesa</p>
            <div class="relative h-56"><canvas id="chart-fluxo"></canvas></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800 mb-0.5">Despesas por Categoria</h2>
            <p class="text-xs text-slate-400 mb-4">{{ $mesLabel }}</p>
            @if ($despesasCat->isNotEmpty())
                <div class="relative h-56"><canvas id="chart-categorias"></canvas></div>
            @else
                <div class="flex h-56 items-center justify-center text-sm text-slate-400 flex-col gap-2">
                    <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"/></svg>
                    Sem despesas registradas
                </div>
            @endif
        </div>
    </div>

    {{-- ─── 4. Referência — "Quanto preciso para funcionar?" ──────────── --}}
    @php $cm = $custoMinimo; @endphp
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-800">Custo Mínimo para Operar</h2>
                <p class="text-xs text-slate-400 mt-0.5">Valor de referência — o piso mensal para manter a operação ativa</p>
            </div>
            <a href="{{ route('web.contratos') }}" class="shrink-0 text-xs text-slate-400 hover:text-rose-600 transition-colors">gerenciar →</a>
        </div>
        <div class="px-5 py-5 flex flex-col sm:flex-row sm:items-center gap-6">
            <div class="shrink-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">Total Mensal</p>
                <p class="text-4xl font-bold tabular-nums text-slate-800">R$ {{ number_format($cm['totalMinimo'], 2, ',', '.') }}</p>
                <p class="text-xs text-slate-400 mt-1">por mês, em condições normais de operação</p>
            </div>
            <div class="hidden sm:block self-stretch border-l border-slate-100"></div>
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-rose-50 border border-rose-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-rose-400 shrink-0"></span><p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">Contratos Fixos</p></div>
                    <p class="text-xl font-bold tabular-nums text-slate-800">R$ {{ number_format($cm['custoContratos'], 2, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">{{ $cm['numContratos'] }} contrato{{ $cm['numContratos'] !== 1 ? 's' : '' }} ativo{{ $cm['numContratos'] !== 1 ? 's' : '' }} · valor exato</p>
                </div>
                <div class="rounded-xl bg-blue-50 border border-blue-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-blue-400 shrink-0"></span><p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Contas de Consumo</p></div>
                    <p class="text-xl font-bold tabular-nums text-slate-800">R$ {{ number_format($cm['mediaConsumo'], 2, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">@if($cm['mesesConsumo'] > 0) média de {{ $cm['mesesConsumo'] }} {{ $cm['mesesConsumo'] === 1 ? 'mês' : 'meses' }} · estimado @else sem histórico ainda @endif</p>
                </div>
                <div class="rounded-xl bg-orange-50 border border-orange-100 p-4">
                    <div class="flex items-center gap-2 mb-2"><span class="h-2 w-2 rounded-full bg-orange-400 shrink-0"></span><p class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Fiscal / Tributos</p></div>
                    <p class="text-xl font-bold tabular-nums text-slate-800">R$ {{ number_format($cm['mediaFiscal'], 2, ',', '.') }}</p>
                    <p class="text-xs text-slate-400 mt-1">@if($cm['mesesFiscal'] > 0) média de {{ $cm['mesesFiscal'] }} {{ $cm['mesesFiscal'] === 1 ? 'mês' : 'meses' }} · estimado @else sem histórico ainda @endif</p>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 bg-slate-50 px-5 py-3 text-xs text-slate-400">
            Contratos: valor atual dos contratos ativos · Consumo e fiscal: média dos últimos {{ $cm['mesesHistorico'] }} meses com lançamentos registrados
        </div>
    </div>

</div>
