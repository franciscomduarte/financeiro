@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',') . '%';
@endphp
<div>
    <x-ui.page-header titulo="Comissões" subtitulo="Percentual de cada profissional sobre o que a clínica recebeu, já sem a taxa do cartão." />

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-1">
            <button type="button" wire:click="mesAnterior" class="btn-ghost h-11 w-11 px-0" aria-label="Mês anterior"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg></button>
            <p class="min-w-40 text-center text-base font-semibold text-stone-900">{{ $tituloMes }}</p>
            <button type="button" wire:click="mesSeguinte" class="btn-ghost h-11 w-11 px-0" aria-label="Próximo mês"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg></button>
        </div>
        <div class="flex gap-6 text-sm">
            <p class="text-stone-500">Recebido <span class="ml-1 font-semibold tabular-nums text-stone-900">{{ $brl($totais['base']) }}</span></p>
            <p class="text-stone-500">Comissões <span class="ml-1 font-semibold tabular-nums text-stone-900">{{ $brl($totais['valor']) }}</span></p>
        </div>
    </div>

    @if ($linhas->isEmpty())
        <div class="card">
            <x-ui.empty-state titulo="Nenhum profissional ativo"
                texto="Cadastre os profissionais em Profissionais e horários para calcular as comissões." />
        </div>
    @else
        <div class="space-y-3">
            @foreach ($linhas as $l)
                <div class="card p-4 sm:p-5" wire:key="com-{{ $l['profissional_id'] }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="min-w-0 lg:w-56">
                            <p class="font-medium text-stone-900">{{ $l['nome'] }}</p>
                            @if ($l['fechamento'])
                                <span class="badge mt-1 bg-emerald-50 text-emerald-700">Fechada em {{ $l['fechamento']->created_at->format('d/m') }}</span>
                            @endif
                        </div>
                        <dl class="grid flex-1 grid-cols-2 gap-x-6 gap-y-3 text-sm lg:grid-cols-[1fr_1fr_minmax(12rem,auto)_1fr]">
                            <div>
                                <dt class="text-xs text-stone-500">Atendimentos</dt>
                                <dd class="tabular-nums text-stone-800">{{ $brl($l['base_atendimentos']) }} <span class="text-xs text-stone-400">({{ $l['qtd_atendimentos'] }})</span></dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500">Sessões de pacote</dt>
                                <dd class="tabular-nums text-stone-800">{{ $brl($l['base_pacotes']) }} <span class="text-xs text-stone-400">({{ $l['qtd_sessoes'] }})</span></dd>
                                @if ($l['sessoes_a_receber'])
                                    <dd class="text-xs text-amber-700">{{ $l['sessoes_a_receber'] }} de pacote ainda não pago</dd>
                                @endif
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500">Percentual</dt>
                                <dd>
                                    @if ($l['fechamento'])
                                        <span class="tabular-nums text-stone-800">{{ $pct($l['percentual']) }}</span>
                                    @else
                                        <form wire:submit="salvarPercentual('{{ $l['profissional_id'] }}')" class="flex items-center gap-1">
                                            <label class="sr-only" for="pct-{{ $l['profissional_id'] }}">Percentual de {{ $l['nome'] }}</label>
                                            <input id="pct-{{ $l['profissional_id'] }}" type="number" inputmode="decimal" step="0.5" min="0" max="100"
                                                   wire:model="percentuais.{{ $l['profissional_id'] }}" placeholder="{{ rtrim(rtrim(number_format($l['percentual'], 2, '.', ''), '0'), '.') }}"
                                                   class="input h-10 min-h-0 w-20 py-1 tabular-nums">
                                            <span class="text-stone-500">%</span>
                                            <button type="submit" class="btn-ghost min-h-10 px-2 text-sm">Salvar</button>
                                        </form>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500">Comissão</dt>
                                <dd class="text-lg font-semibold tabular-nums text-stone-900">{{ $brl($l['valor']) }}</dd>
                            </div>
                        </dl>
                        <div class="lg:w-40 lg:text-right">
                            @if ($l['fechamento'])
                                <a href="{{ route('transacoes.index') }}" wire:navigate class="btn-ghost text-sm">Ver em Lançamentos</a>
                            @elseif ($mesEncerrado && $l['valor'] > 0)
                                <button type="button" wire:click="fechar('{{ $l['profissional_id'] }}')"
                                        wire:confirm="Fechar a comissão de {{ $l['nome'] }} em {{ $brl($l['valor']) }}? Ela entra como despesa a pagar em Lançamentos."
                                        class="btn-primary w-full lg:w-auto">Fechar e lançar</button>
                            @elseif (! $mesEncerrado)
                                <p class="text-xs text-stone-500">Prévia: fecha quando o mês terminar.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="hint mt-4">
            Entram as receitas pagas no mês ligadas a um atendimento do profissional e as sessões de pacote feitas no mês (valor de uma sessão, se o pacote já foi pago).
            Mudar o percentual não altera meses já fechados.
        </p>
    @endif
</div>
