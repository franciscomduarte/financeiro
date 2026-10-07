@php
    $brl = fn ($v) => ((float) $v < 0 ? '− ' : '') . 'R$ ' . number_format(abs((float) $v), 2, ',', '.');
    $exportar = $aba === 'projetado'
        ? ['relatorio' => 'fluxo-projetado', 'dias' => $dias]
        : ['relatorio' => 'fluxo-realizado', 'periodo' => $periodo];
@endphp
<div>
    <x-ui.page-header titulo="Fluxo de caixa" subtitulo="O dinheiro que entrou e saiu, e quanto a clínica deve ter em caixa nas próximas semanas.">
        <x-slot:acoes>
            <a href="{{ route('financeiro.exportar', $exportar + ['formato' => 'pdf']) }}" target="_blank" class="btn-secondary">PDF</a>
            <a href="{{ route('financeiro.exportar', $exportar + ['formato' => 'csv']) }}" class="btn-secondary">CSV</a>
        </x-slot:acoes>
    </x-ui.page-header>

    <div class="mb-5 flex gap-1 border-b border-stone-200" role="tablist">
        @foreach (['projetado' => 'Projeção', 'realizado' => 'Realizado'] as $chave => $titulo)
            <button type="button" wire:click="$set('aba', '{{ $chave }}')" role="tab" aria-selected="{{ $aba === $chave ? 'true' : 'false' }}"
                    class="-mb-px inline-flex min-h-[44px] items-center border-b-2 px-4 text-sm font-medium {{ $aba === $chave ? 'border-rose-600 text-rose-700' : 'border-transparent text-stone-500 hover:text-stone-700' }}">{{ $titulo }}</button>
        @endforeach
    </div>

    @if ($projecao)
        @php $p = $projecao; @endphp
        <div class="mb-4 flex gap-1" role="group" aria-label="Horizonte">
            @foreach (\App\Livewire\FluxoCaixaIndex::HORIZONTES as $h)
                <button type="button" wire:click="$set('dias', {{ $h }})" class="inline-flex min-h-[44px] items-center rounded-full px-4 text-sm font-medium {{ $dias === $h ? 'bg-rose-600 text-white' : 'border border-stone-200 bg-surface text-stone-600' }}">{{ $h }} dias</button>
            @endforeach
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="card p-4"><p class="text-sm text-stone-500">Disponível hoje</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl">{{ $brl($p['saldo_hoje']) }}</p><p class="text-xs text-stone-400">@if ($p['a_liberar_cartao'] > 0) + {{ $brl($p['a_liberar_cartao']) }} a liberar do cartão @else somando todas as contas @endif</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">A receber</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl text-emerald-700">{{ $brl($p['entradas']) }}</p><p class="text-xs text-stone-400">próximos {{ $dias }} dias</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">A pagar</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl text-red-700">{{ $brl($p['saidas']) }}</p><p class="text-xs text-stone-400">próximos {{ $dias }} dias</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Saldo em {{ $dias }} dias</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl {{ $p['saldo_final'] < 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $brl($p['saldo_final']) }}</p><p class="text-xs text-stone-400">se tudo for pago em dia</p></div>
        </div>

        @if ($p['menor_saldo'] < 0)
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <strong>Atenção:</strong> o caixa fica negativo em {{ $p['menor_saldo_em']?->format('d/m') }} ({{ $brl($p['menor_saldo']) }}). Antecipe recebimentos ou renegocie pagamentos.
            </div>
        @endif
        @if ($p['vencidos_receber'] > 0 || $p['vencidos_pagar'] > 0)
            <p class="mb-4 text-sm text-stone-600">
                Fora da projeção, já vencidos:
                <a href="{{ route('financeiro.receber', ['filtro' => 'vencidos']) }}" wire:navigate class="font-medium text-emerald-700 underline">{{ $brl($p['vencidos_receber']) }} a receber</a> e
                <a href="{{ route('financeiro.pagar', ['filtro' => 'vencidos']) }}" wire:navigate class="font-medium text-red-700 underline">{{ $brl($p['vencidos_pagar']) }} a pagar</a>.
            </p>
        @endif

        <section class="card mb-4 p-4">
            <h2 class="text-sm font-semibold text-stone-900">Saldo previsto dia a dia</h2>
            <x-financeiro.grafico-saldo :pontos="$p['diario']" class="mt-3" aria-label="Saldo previsto nos próximos {{ $dias }} dias" />
            <div class="mt-1 flex justify-between text-xs text-stone-400"><span>hoje</span><span>{{ end($p['diario'])['data']->format('d/m') }}</span></div>
        </section>

        <section class="card overflow-hidden">
            <div class="grid grid-cols-[1fr_auto_auto_auto] gap-x-3 border-b border-stone-100 bg-stone-50 px-4 py-2 text-xs font-medium text-stone-500 sm:gap-x-6">
                <span>Semana</span><span class="text-right">Entra</span><span class="text-right">Sai</span><span class="text-right">Saldo</span>
            </div>
            @foreach ($p['semanas'] as $s)
                <div class="grid grid-cols-[1fr_auto_auto_auto] gap-x-3 border-b border-stone-100 px-4 py-2 text-sm tabular-nums sm:gap-x-6">
                    <span class="text-stone-600">{{ $s['inicio']->format('d/m') }}–{{ $s['fim']->format('d/m') }}</span>
                    <span class="text-right text-emerald-700">{{ $s['entradas'] > 0 ? $brl($s['entradas']) : '—' }}</span>
                    <span class="text-right text-red-700">{{ $s['saidas'] > 0 ? $brl($s['saidas']) : '—' }}</span>
                    <span class="text-right font-medium {{ $s['saldo'] < 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $brl($s['saldo']) }}</span>
                </div>
            @endforeach
        </section>
        <p class="hint mt-2">Conta o que está a receber e a pagar pelo vencimento, as vendas no cartão na data em que a maquininha libera (sem as taxas) e as despesas e receitas recorrentes que ainda vão ser lançadas.</p>
    @endif

    @if ($realizado)
        @php $r = $realizado; @endphp
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <select wire:model.live="periodo" class="input w-auto" aria-label="Período">
                @foreach ($anos as $ano)
                    <option value="{{ $ano }}">{{ $ano }} (por mês)</option>
                @endforeach
                @if (strlen($periodo) === 7)
                    <option value="{{ $periodo }}">{{ ucfirst($inicio->translatedFormat('F/Y')) }} (por dia)</option>
                @endif
            </select>
            @if ($por === 'mes')
                <span class="text-xs text-stone-500">Toque num mês para ver dia a dia.</span>
            @else
                <button type="button" wire:click="$set('periodo', '{{ $inicio->format('Y') }}')" class="btn-ghost min-h-[44px] text-sm">← Voltar ao ano</button>
            @endif
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="card p-4"><p class="text-sm text-stone-500">Saldo no início</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl">{{ $brl($r['saldo_inicial']) }}</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Entrou</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl text-emerald-700">{{ $brl($r['entradas']) }}</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Saiu</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl text-red-700">{{ $brl($r['saidas']) }}</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Saldo no fim</p><p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl {{ $r['saldo_final'] < 0 ? 'text-red-700' : '' }}">{{ $brl($r['saldo_final']) }}</p></div>
        </div>

        <section class="card overflow-hidden">
            <div class="grid grid-cols-[1fr_auto_auto_auto] gap-x-3 border-b border-stone-100 bg-stone-50 px-4 py-2 text-xs font-medium text-stone-500 sm:grid-cols-[1fr_auto_auto_auto_auto] sm:gap-x-6">
                <span>{{ $por === 'mes' ? 'Mês' : 'Dia' }}</span><span class="text-right">Entrou</span><span class="text-right">Saiu</span><span class="hidden text-right sm:block">Líquido</span><span class="text-right">Saldo</span>
            </div>
            @foreach ($r['periodos'] as $pp)
                @continue($por === 'dia' && $pp['entradas'] == 0 && $pp['saidas'] == 0)
                @php $clicavel = $por === 'mes'; @endphp
                <div @if ($clicavel) role="button" tabindex="0" wire:click="$set('periodo', '{{ $pp['chave'] }}')" @keydown.enter="$wire.set('periodo', '{{ $pp['chave'] }}')" @endif
                     class="grid w-full grid-cols-[1fr_auto_auto_auto] gap-x-3 border-b border-stone-100 px-4 py-2 text-left text-sm tabular-nums sm:grid-cols-[1fr_auto_auto_auto_auto] sm:gap-x-6 {{ $clicavel ? 'cursor-pointer hover:bg-stone-50' : '' }}">
                    <span class="text-stone-700">{{ $pp['rotulo'] }}</span>
                    <span class="text-right text-emerald-700">{{ $pp['entradas'] > 0 ? $brl($pp['entradas']) : '—' }}</span>
                    <span class="text-right text-red-700">{{ $pp['saidas'] > 0 ? $brl($pp['saidas']) : '—' }}</span>
                    <span class="hidden text-right sm:block {{ $pp['liquido'] < 0 ? 'text-red-700' : '' }}">{{ $brl($pp['liquido']) }}</span>
                    <span class="text-right font-medium {{ $pp['saldo'] < 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $brl($pp['saldo']) }}</span>
                </div>
            @endforeach
        </section>

        @if ($r['grupos'])
            <section class="card mt-4 p-4">
                <h2 class="text-sm font-semibold text-stone-900">Por grupo do plano de contas</h2>
                <dl class="mt-2 space-y-1 text-sm">
                    @foreach ($r['grupos'] as $g)
                        <div class="flex justify-between gap-3">
                            <dt class="text-stone-600">{{ $g['tipo'] === 'entrada' ? '+' : '−' }} {{ $g['rotulo'] }}</dt>
                            <dd class="tabular-nums {{ $g['tipo'] === 'entrada' ? 'text-emerald-700' : 'text-red-700' }}">{{ $brl($g['total']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
        <p class="hint mt-2">Pela data em que o dinheiro entrou ou saiu (regime de caixa), já com juros, descontos e taxa da maquininha. Transferências entre contas não entram.</p>
    @endif
</div>
