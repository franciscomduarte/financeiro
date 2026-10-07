@php
    $brl = fn ($v) => ((float) $v < 0 ? '− ' : '') . 'R$ ' . number_format(abs((float) $v), 2, ',', '.');
    $ext = $contaId !== '' ? $this->extrato : null;
@endphp
<div>
    <x-ui.page-header titulo="Caixa e bancos" subtitulo="Quanto a clínica tem em cada conta, com o extrato de entradas, saídas e transferências.">
        <x-slot:acoes>
            <button type="button" wire:click="novaTransferencia" class="btn-secondary">Transferir</button>
            <button type="button" wire:click="novaConta" class="btn-primary">+ Nova conta</button>
        </x-slot:acoes>
    </x-ui.page-header>

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    <div class="card mb-4 flex items-center justify-between gap-3 p-4">
        <div>
            <p class="text-sm text-stone-500">Saldo total hoje</p>
            <p class="text-2xl font-semibold tabular-nums {{ $totalGeral < 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $brl($totalGeral) }}</p>
        </div>
        <p class="hint max-w-xs text-right">Para o saldo bater com o banco, use "Ajustar saldo" uma vez em cada conta.</p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($this->contas as $c)
            @php $saldo = (float) ($this->saldos[$c->id] ?? 0); @endphp
            <div class="card flex flex-col p-4 {{ $c->ativa ? '' : 'opacity-60' }} {{ $contaId === $c->id ? 'ring-2 ring-rose-500/40' : '' }}" wire:key="c-{{ $c->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-stone-900">{{ $c->nome }}</p>
                        <p class="truncate text-xs text-stone-500">
                            {{ $c->tipo->label() }}@if ($c->banco) · {{ $c->banco }}@endif
                            @if ($c->padrao) · <span class="text-rose-600">padrão</span>@endif
                            @if (! $c->ativa) · inativa @endif
                        </p>
                    </div>
                    <button type="button" wire:click="editarConta('{{ $c->id }}')" class="btn-ghost -mr-2 -mt-1 min-h-[44px] px-3 text-sm">Editar</button>
                </div>
                <p class="mt-2 text-xl font-semibold tabular-nums {{ $saldo < 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $brl($saldo) }}</p>
                @if ($lib = $this->aLiberar[$c->id] ?? null)
                    <p class="text-xs text-sky-700">{{ $brl($lib['valor']) }} a liberar · próxima em {{ \Carbon\Carbon::parse($lib['proxima'])->format('d/m') }}</p>
                @endif
                @if ($c->saldo_inicial_em)
                    <p class="text-xs text-stone-400">saldo conferido em {{ $c->saldo_inicial_em->format('d/m/Y') }}</p>
                @else
                    <p class="text-xs text-amber-700">saldo ainda não conferido</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" wire:click="verExtrato('{{ $c->id }}')" class="btn-secondary min-h-[44px] flex-1 text-sm">Extrato</button>
                    <button type="button" wire:click="abrirAjuste('{{ $c->id }}')" class="btn-secondary min-h-[44px] flex-1 text-sm">Ajustar saldo</button>
                    @if ($c->ativa)
                        <button type="button" wire:click="novaTransferencia('{{ $c->id }}')" class="btn-ghost min-h-[44px] text-sm">Transferir</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══ Extrato ═══ --}}
    @if ($ext && $ext['conta'])
        <section class="card mt-6 overflow-hidden" aria-label="Extrato">
            <div class="flex flex-col gap-3 border-b border-stone-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-stone-900">Extrato · {{ $ext['conta']->nome }}</h2>
                    <p class="text-xs text-stone-500">
                        Entradas <span class="tabular-nums text-emerald-700">{{ $brl($ext['entradas']) }}</span> ·
                        Saídas <span class="tabular-nums text-red-700">{{ $brl($ext['saidas']) }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="month" wire:model.live="mes" value="{{ $mesRef->format('Y-m') }}" class="input w-auto" aria-label="Mês do extrato">
                    <button type="button" wire:click="fecharExtrato" class="btn-ghost min-h-[44px]" aria-label="Fechar extrato">✕</button>
                </div>
            </div>
            @if ($ext['conta']->tipo === \App\Enums\TipoContaFinanceira::Maquininha && $this->agendaCartao->isNotEmpty())
                <div class="border-b border-stone-100 bg-sky-50/50 px-4 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-stone-900">A liberar para o banco</p>
                        <button type="button" wire:click="liberarAgora" class="btn-ghost min-h-[44px] text-sm">Liberar vencidos</button>
                    </div>
                    <div class="mt-1 divide-y divide-sky-100 text-xs">
                        @foreach ($this->agendaCartao as $r)
                            <div class="flex items-center gap-3 py-1.5">
                                <span class="w-12 shrink-0 tabular-nums {{ $r->data_prevista->lte(today()) ? 'font-medium text-amber-700' : 'text-stone-500' }}">{{ $r->data_prevista->format('d/m') }}</span>
                                <span class="min-w-0 flex-1 truncate text-stone-700">{{ $r->transacao?->descricao }} · {{ $r->antecipado ? 'antecipado' : "parcela {$r->parcela}/{$r->total_parcelas}" }}</span>
                                <span class="shrink-0 tabular-nums text-stone-900">{{ $brl($r->valor_liquido) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="flex items-center justify-between bg-stone-50 px-4 py-2 text-sm">
                <span class="text-stone-500">Saldo anterior</span>
                <span class="font-medium tabular-nums">{{ $brl($ext['anterior']) }}</span>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($ext['linhas'] as $l)
                    <div class="flex items-center gap-3 px-4 py-2.5">
                        <span class="w-12 shrink-0 text-xs tabular-nums text-stone-500">{{ $l['data']->format('d/m') }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-stone-900">{{ $l['descricao'] }}</p>
                            @if ($l['detalhe']) <p class="truncate text-xs text-stone-500">{{ $l['detalhe'] }}</p> @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-medium tabular-nums {{ $l['valor'] < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $l['valor'] < 0 ? '− ' : '+ ' }}{{ 'R$ ' . number_format(abs($l['valor']), 2, ',', '.') }}</p>
                            <p class="text-xs tabular-nums text-stone-400">{{ $brl($l['saldo']) }}</p>
                        </div>
                        @if ($l['transferencia_id'])
                            <button type="button" wire:click="excluirTransferencia('{{ $l['transferencia_id'] }}')" wire:confirm="Excluir esta transferência?"
                                    class="btn-ghost min-h-[44px] px-2 text-xs text-red-700" aria-label="Excluir transferência">✕</button>
                        @endif
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-stone-500">Nenhum movimento em {{ $mesRef->translatedFormat('F/Y') }}.</p>
                @endforelse
            </div>
            <div class="flex items-center justify-between border-t border-stone-100 bg-stone-50 px-4 py-2 text-sm">
                <span class="text-stone-500">Saldo no fim do período</span>
                <span class="font-semibold tabular-nums">{{ $brl($ext['final']) }}</span>
            </div>
        </section>
    @endif

    {{-- ═══ Modal: conta ═══ --}}
    @if ($modalConta)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalConta', false)"></div>
            <form wire:submit="salvarConta" class="relative flex max-h-[92dvh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <h2 class="border-b border-stone-100 px-5 py-4 text-lg font-semibold text-stone-900">{{ $editandoId ? 'Editar conta' : 'Nova conta' }}</h2>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div>
                        <label class="label" for="conta-nome">Nome</label>
                        <input id="conta-nome" type="text" wire:model="nome" class="input" maxlength="80" placeholder="Ex.: Banco Inter, Caixa da recepção">
                        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="conta-tipo">Tipo</label>
                        <select id="conta-tipo" wire:model.live="tipoConta" class="input">
                            @foreach ($tipos as $t) <option value="{{ $t->value }}">{{ $t->label() }}</option> @endforeach
                        </select>
                    </div>
                    @if ($tipoConta === 'banco')
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-3 sm:col-span-1"><label class="label" for="conta-banco">Banco</label><input id="conta-banco" type="text" wire:model="banco" class="input" maxlength="80"></div>
                            <div><label class="label" for="conta-ag">Agência</label><input id="conta-ag" type="text" inputmode="numeric" wire:model="agencia" class="input" maxlength="20"></div>
                            <div><label class="label" for="conta-num">Conta</label><input id="conta-num" type="text" inputmode="numeric" wire:model="numero" class="input" maxlength="30"></div>
                        </div>
                    @endif
                    @if ($tipoConta === 'maquininha')
                        <fieldset class="space-y-3 rounded-xl border border-stone-200 p-3">
                            <legend class="px-1 text-sm font-medium text-stone-700">Recebimento do cartão</legend>
                            <div class="grid grid-cols-3 gap-3">
                                <div><label class="label" for="mq-credito">Crédito (dias)</label><input id="mq-credito" type="number" inputmode="numeric" min="0" wire:model="prazoCredito" class="input"></div>
                                <div><label class="label" for="mq-debito">Débito (dias)</label><input id="mq-debito" type="number" inputmode="numeric" min="0" wire:model="prazoDebito" class="input"></div>
                                <div><label class="label" for="mq-antecipa">Antecipado (dias)</label><input id="mq-antecipa" type="number" inputmode="numeric" min="0" wire:model="antecipacaoDias" class="input"></div>
                            </div>
                            <p class="hint -mt-1">Crédito parcelado: cada parcela cai no prazo × número da parcela (30, 60, 90 dias...).</p>
                            @error('prazoCredito') <p class="field-error">{{ $message }}</p> @enderror
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="label" for="mq-taxa">Antecipação (% ao mês)</label>
                                    <input id="mq-taxa" type="text" inputmode="decimal" wire:model="taxaAntecipacao" class="input tabular-nums" placeholder="1,99">
                                    @error('taxaAntecipacao') <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label" for="mq-liq">Libera em</label>
                                    <select id="mq-liq" wire:model="contaLiquidacao" class="input">
                                        <option value="">Conta padrão</option>
                                        @foreach ($this->contas->where('ativa', true)->where('id', '!=', $editandoId) as $cl) <option value="{{ $cl->id }}">{{ $cl->nome }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <label class="relative flex min-h-[44px] items-center gap-3 text-sm text-stone-700">
                                <input type="checkbox" wire:model="anteciparPadrao" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                                Antecipar as vendas no crédito por padrão
                            </label>
                        </fieldset>
                    @endif
                    <label class="relative flex min-h-[44px] items-center gap-3 text-sm text-stone-700">
                        <input type="checkbox" wire:model="padrao" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Conta padrão (sugerida para PIX, boleto e transferências)
                    </label>
                    <label class="relative flex min-h-[44px] items-center gap-3 text-sm text-stone-700">
                        <input type="checkbox" wire:model="ativa" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Ativa
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" wire:click="$set('modalConta', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ═══ Modal: transferência ═══ --}}
    @if ($modalTransferencia)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalTransferencia', false)"></div>
            <form wire:submit="confirmarTransferencia" class="relative flex max-h-[92dvh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="border-b border-stone-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-stone-900">Transferir entre contas</h2>
                    <p class="text-sm text-stone-500">Ex.: repasse da maquininha para o banco, depósito do caixa. Não conta como receita nem despesa.</p>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['trOrigem' => 'De', 'trDestino' => 'Para'] as $campo => $rotulo)
                            <div>
                                <label class="label" for="{{ $campo }}">{{ $rotulo }}</label>
                                <select id="{{ $campo }}" wire:model="{{ $campo }}" class="input">
                                    @foreach ($this->contas->where('ativa', true) as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                                </select>
                                @error($campo) <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="tr-valor">Valor (R$)</label>
                            <input id="tr-valor" type="text" inputmode="decimal" wire:model="trValor" class="input tabular-nums" autocomplete="off">
                        </div>
                        <div>
                            <label class="label" for="tr-data">Data</label>
                            <input id="tr-data" type="date" wire:model="trData" class="input">
                        </div>
                    </div>
                    @error('trValor') <p class="field-error -mt-2">{{ $message }}</p> @enderror
                    <div>
                        <label class="label" for="tr-desc">Descrição <span class="font-normal text-stone-400">(opcional)</span></label>
                        <input id="tr-desc" type="text" wire:model="trDescricao" class="input" maxlength="255">
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" wire:click="$set('modalTransferencia', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Transferir</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ═══ Modal: ajustar saldo ═══ --}}
    @if ($ajusteId)
        @php $contaAjuste = $this->contas->firstWhere('id', $ajusteId); @endphp
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('ajusteId', null)"></div>
            <form wire:submit="confirmarAjuste" class="relative flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="border-b border-stone-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-stone-900">Ajustar saldo · {{ $contaAjuste?->nome }}</h2>
                    <p class="text-sm text-stone-500">Informe o saldo real (do extrato do banco ou contado no caixa) ao fim do dia escolhido.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 px-5 py-4">
                    <div>
                        <label class="label" for="aj-saldo">Saldo (R$)</label>
                        <input id="aj-saldo" type="text" inputmode="decimal" wire:model="ajusteSaldo" class="input tabular-nums" autocomplete="off">
                    </div>
                    <div>
                        <label class="label" for="aj-data">No fim do dia</label>
                        <input id="aj-data" type="date" wire:model="ajusteData" class="input" max="{{ today()->toDateString() }}">
                    </div>
                    @error('ajusteSaldo') <p class="field-error col-span-2">{{ $message }}</p> @enderror
                    @error('ajusteData') <p class="field-error col-span-2">{{ $message }}</p> @enderror
                    <p class="hint col-span-2">Pagamentos e recebimentos até esse dia já estão dentro desse saldo; os posteriores são somados a ele.</p>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" wire:click="$set('ajusteId', null)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar saldo</button>
                </div>
            </form>
        </div>
    @endif
</div>
