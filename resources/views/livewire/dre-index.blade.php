@php
    $brl = fn ($v) => ((float) $v < 0 ? '− ' : '') . 'R$ ' . number_format(abs((float) $v), 2, ',', '.');
    $pct = fn ($v) => $dre['receita_bruta'] > 0 ? number_format($v / $dre['receita_bruta'] * 100, 1, ',', '.') . '%' : '—';
    $varia = function ($atual, $antes) {
        if (abs($antes) < 0.01) return null;
        return round(($atual - $antes) / abs($antes) * 100);
    };
    // [tipo, chave, sinal]: grupo do plano ou linha de total
    $estrutura = [
        ['grupo', 'receita_servicos', '+'], ['grupo', 'receita_produtos', '+'], ['grupo', 'outras_receitas', '+'],
        ['total', 'receita_bruta', 'Receita bruta'],
        ['grupo', 'deducoes', '−'],
        ['total', 'receita_liquida', 'Receita líquida'],
        ['grupo', 'custos_variaveis', '−'],
        ['total', 'margem', 'Margem de contribuição'],
        ['grupo', 'pessoal', '−'], ['grupo', 'ocupacao', '−'], ['grupo', 'administrativas', '−'], ['grupo', 'marketing', '−'],
        ['total', 'resultado_operacional', 'Resultado operacional'],
        ['grupo', 'receitas_financeiras', '+'], ['grupo', 'despesas_financeiras', '−'],
        ['total', 'resultado', 'Resultado do mês'],
    ];
    $maxSerie = max(1, collect($serie)->flatMap(fn ($s) => [abs($s['receita']), abs($s['despesas'])])->max());
@endphp
<div>
    <x-ui.page-header titulo="DRE" subtitulo="O resultado do mês por competência: quanto a clínica faturou, gastou e lucrou.">
        <x-slot:acoes>
            <a href="{{ route('financeiro.exportar', ['relatorio' => 'dre', 'formato' => 'pdf', 'mes' => $mesRef->format('Y-m')]) }}" target="_blank" class="btn-secondary">PDF</a>
            <a href="{{ route('financeiro.exportar', ['relatorio' => 'dre', 'formato' => 'csv', 'mes' => $mesRef->format('Y-m')]) }}" class="btn-secondary">CSV</a>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="mb-4 flex items-center gap-2">
        <button type="button" wire:click="navegar(-1)" class="btn-secondary min-h-[44px] px-3" aria-label="Mês anterior">‹</button>
        <input type="month" wire:model.live="mes" class="input w-auto" aria-label="Mês">
        <button type="button" wire:click="navegar(1)" class="btn-secondary min-h-[44px] px-3" aria-label="Próximo mês">›</button>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Faturamento', $dre['receita_bruta'], $anterior['receita_bruta'], 'text-stone-900'],
            ['Margem de contribuição', $dre['margem'], $anterior['margem'], 'text-stone-900'],
            ['Despesas fixas', $dre['despesas_fixas'], $anterior['despesas_fixas'], 'text-stone-900'],
            ['Resultado', $dre['resultado'], $anterior['resultado'], $dre['resultado'] < 0 ? 'text-red-700' : 'text-emerald-700'],
        ] as [$rotulo, $valor, $antes, $cor])
            @php $v = $varia($valor, $antes); @endphp
            <div class="card p-4">
                <p class="text-sm text-stone-500">{{ $rotulo }}</p>
                <p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl {{ $cor }}">{{ $brl($valor) }}</p>
                <p class="text-xs text-stone-400">
                    @if ($v === null) sem base no mês anterior @else {{ $v > 0 ? '+' : '' }}{{ $v }}% vs. mês anterior @endif
                </p>
            </div>
        @endforeach
    </div>

    <section class="card overflow-hidden" aria-label="Demonstrativo">
        <div class="grid grid-cols-[1fr_auto_auto] gap-x-4 border-b border-stone-100 bg-stone-50 px-4 py-2 text-xs font-medium text-stone-500 sm:grid-cols-[1fr_8rem_4rem_8rem]">
            <span>{{ ucfirst($mesRef->translatedFormat('F \d\e Y')) }}</span>
            <span class="text-right">Valor</span>
            <span class="text-right">% fat.</span>
            <span class="hidden text-right sm:block">Mês anterior</span>
        </div>
        @foreach ($estrutura as [$tipoLinha, $chave, $extra])
            @if ($tipoLinha === 'grupo')
                @php $g = $dre['linhas'][$chave]; $ga = $anterior['linhas'][$chave]; @endphp
                @continue($g['valor'] == 0 && $ga['valor'] == 0)
                <div x-data="{ aberto: false }" class="border-b border-stone-100">
                    <button type="button" @click="aberto = ! aberto" class="grid w-full grid-cols-[1fr_auto_auto] items-center gap-x-4 px-4 py-2.5 text-left text-sm hover:bg-stone-50 sm:grid-cols-[1fr_8rem_4rem_8rem]" :aria-expanded="aberto">
                        <span class="flex min-w-0 items-center gap-2 text-stone-700">
                            <span class="w-3 shrink-0 text-stone-400" x-text="aberto ? '▾' : '▸'">▸</span>
                            <span class="truncate">({{ $extra }}) {{ $g['rotulo'] }}</span>
                        </span>
                        <span class="text-right tabular-nums text-stone-900">{{ $brl($g['valor']) }}</span>
                        <span class="text-right text-xs tabular-nums text-stone-500">{{ $pct($g['valor']) }}</span>
                        <span class="hidden text-right tabular-nums text-stone-500 sm:block">{{ $brl($ga['valor']) }}</span>
                    </button>
                    <div x-show="aberto" x-cloak class="bg-stone-50/60 pb-1">
                        @forelse ($g['contas'] as $conta => $valor)
                            <div class="grid grid-cols-[1fr_auto_auto] gap-x-4 px-4 py-1 pl-11 text-xs sm:grid-cols-[1fr_8rem_4rem_8rem]">
                                <span class="truncate text-stone-600">{{ $conta }}</span>
                                <span class="text-right tabular-nums text-stone-700">{{ $brl($valor) }}</span>
                                <span class="text-right tabular-nums text-stone-400">{{ $pct($valor) }}</span>
                                <span class="hidden text-right tabular-nums text-stone-400 sm:block">{{ $brl($ga['contas'][$conta] ?? 0) }}</span>
                            </div>
                        @empty
                            <p class="px-4 py-1 pl-11 text-xs text-stone-400">Nada neste mês.</p>
                        @endforelse
                    </div>
                </div>
            @else
                @php $valor = $dre[$chave]; $antes = $anterior[$chave]; $final = $chave === 'resultado'; @endphp
                <div class="grid grid-cols-[1fr_auto_auto] items-center gap-x-4 border-b border-stone-100 px-4 py-2.5 text-sm font-semibold sm:grid-cols-[1fr_8rem_4rem_8rem] {{ $final ? 'bg-stone-50' : '' }}">
                    <span class="text-stone-900">= {{ $extra }}</span>
                    <span class="text-right tabular-nums {{ $valor < 0 ? 'text-red-700' : ($final ? 'text-emerald-700' : 'text-stone-900') }}">{{ $brl($valor) }}</span>
                    <span class="text-right text-xs tabular-nums text-stone-500">{{ $pct($valor) }}</span>
                    <span class="hidden text-right tabular-nums text-stone-500 sm:block">{{ $brl($antes) }}</span>
                </div>
            @endif
        @endforeach
    </section>

    <p class="hint mt-2">
        Por competência: cada receita e despesa conta no mês a que pertence, pago ou não. Lançamentos cancelados ficam fora.
        @if ($dre['imposto_estimado'] > 0 && $dre['deducoes'] == 0)
            Nenhum imposto lançado neste mês; pela alíquota da clínica, a estimativa é {{ $brl($dre['imposto_estimado']) }}.
        @endif
    </p>

    @php $fora = collect($dre['fora'])->filter(fn ($f) => $f['valor'] != 0); @endphp
    @if ($fora->isNotEmpty())
        <section class="card mt-4 p-4">
            <h2 class="text-sm font-semibold text-stone-900">Fora do resultado</h2>
            <p class="text-xs text-stone-500">Mexem no caixa, mas não são receita nem despesa da operação.</p>
            <dl class="mt-2 space-y-1 text-sm">
                @foreach ($fora as $f)
                    <div class="flex justify-between gap-3"><dt class="text-stone-600">{{ $f['rotulo'] }}</dt><dd class="tabular-nums">{{ $brl($f['valor']) }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Evolução 12 meses --}}
    <section class="card mt-4 p-4" aria-label="Últimos 12 meses">
        <h2 class="text-sm font-semibold text-stone-900">Últimos 12 meses</h2>
        <div class="mt-3 flex h-40 items-end gap-1.5 overflow-x-auto" role="img" aria-label="Faturamento e despesas por mês">
            @foreach ($serie as $s)
                <div class="flex min-w-[2rem] flex-1 flex-col items-center gap-1" title="{{ ucfirst($s['mes']->translatedFormat('M/y')) }}: faturamento {{ $brl($s['receita']) }}, despesas {{ $brl($s['despesas']) }}, resultado {{ $brl($s['resultado']) }}">
                    <div class="flex h-32 w-full items-end justify-center gap-0.5">
                        <div class="w-1/2 rounded-t bg-emerald-500/80" style="height: {{ max(1, round(abs($s['receita']) / $maxSerie * 100)) }}%"></div>
                        <div class="w-1/2 rounded-t bg-rose-500/80" style="height: {{ max(1, round(abs($s['despesas']) / $maxSerie * 100)) }}%"></div>
                    </div>
                    <span class="text-[10px] text-stone-500">{{ $s['mes']->translatedFormat('M') }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[32rem] text-xs">
                <thead><tr class="text-stone-500"><th class="py-1 text-left font-medium">Mês</th><th class="text-right font-medium">Faturamento</th><th class="text-right font-medium">Despesas</th><th class="text-right font-medium">Resultado</th></tr></thead>
                <tbody>
                    @foreach (array_reverse($serie) as $s)
                        <tr class="border-t border-stone-100">
                            <td class="py-1.5">{{ ucfirst($s['mes']->translatedFormat('M/Y')) }}</td>
                            <td class="text-right tabular-nums">{{ $brl($s['receita']) }}</td>
                            <td class="text-right tabular-nums">{{ $brl($s['despesas']) }}</td>
                            <td class="text-right tabular-nums font-medium {{ $s['resultado'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $brl($s['resultado']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 flex gap-4 text-xs text-stone-500"><span><span class="mr-1 inline-block h-2 w-2 rounded-sm bg-emerald-500"></span>Faturamento</span><span><span class="mr-1 inline-block h-2 w-2 rounded-sm bg-rose-500"></span>Despesas</span></p>
    </section>
</div>
