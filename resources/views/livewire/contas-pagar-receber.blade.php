@php
    $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $verbo = $aPagar ? 'Pagar' : 'Receber';
    $nomeDe = fn ($t) => $t?->fornecedor?->nome ?? $t?->paciente?->nome ?? $t?->cliente;
    $r = $this->resumo;
@endphp
<div>
    <x-ui.page-header :titulo="$aPagar ? 'Contas a pagar' : 'Contas a receber'"
                      :subtitulo="$aPagar ? 'Despesas em aberto por vencimento. Registre o pagamento quando o dinheiro sair.' : 'O que pacientes e clientes ainda vão pagar. Registre o recebimento quando o dinheiro entrar.'">
        <x-slot:acoes>
            <a href="{{ route('transacoes.index', ['novo' => $aPagar ? 'saida' : 'entrada']) }}" wire:navigate class="btn-primary">
                + {{ $aPagar ? 'Nova conta a pagar' : 'Nova conta a receber' }}
            </a>
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

    {{-- Resumo: cada cartão filtra a lista --}}
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['vencidos', 'Vencidos', $r['vencidos'], $r['vencidos'][0] ? 'text-red-700' : 'text-stone-900'],
            ['hoje', 'Vencem hoje', $r['hoje'], $r['hoje'][0] ? 'text-amber-700' : 'text-stone-900'],
            ['semana', 'Próximos 7 dias', $r['semana'], 'text-stone-900'],
            ['abertos', 'Total em aberto', $r['abertos'], 'text-stone-900'],
        ] as [$chave, $rotulo, [$qtd, $valor], $cor])
            <button type="button" wire:click="$set('filtro', '{{ $chave }}')"
                    class="card p-4 text-left transition hover:border-rose-200 {{ $filtro === $chave ? 'ring-2 ring-rose-500/40' : '' }}">
                <p class="text-sm text-stone-500">{{ $rotulo }}</p>
                <p class="mt-1 whitespace-nowrap text-lg font-semibold tabular-nums sm:text-xl {{ $cor }}">{{ $brl($valor) }}</p>
                <p class="text-xs text-stone-400">{{ $qtd }} {{ $qtd === 1 ? 'conta' : 'contas' }}</p>
            </button>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="-mx-4 flex gap-1 overflow-x-auto px-4 sm:mx-0 sm:px-0" role="tablist" aria-label="Filtro">
            @foreach (\App\Livewire\ContasPagarReceber::FILTROS as $chave => $rotulo)
                @php $rotulo = $chave === 'quitados' ? ($aPagar ? 'Pagos no mês' : 'Recebidos no mês') : $rotulo; @endphp
                <button type="button" wire:click="$set('filtro', '{{ $chave }}')" role="tab" aria-selected="{{ $filtro === $chave ? 'true' : 'false' }}"
                        class="inline-flex min-h-[44px] shrink-0 items-center rounded-full px-4 text-sm font-medium {{ $filtro === $chave ? 'bg-rose-600 text-white' : 'bg-surface text-stone-600 border border-stone-200 hover:bg-stone-50' }}">
                    {{ $rotulo }}
                </button>
            @endforeach
        </div>
        <div class="flex gap-2 sm:ml-auto">
            @if (in_array($filtro, ['mes', 'quitados'], true))
                <input type="month" wire:model.live="mes" value="{{ $mesRef->format('Y-m') }}" class="input w-auto" aria-label="Mês">
            @endif
            <input type="search" wire:model.live.debounce.400ms="busca" placeholder="{{ $aPagar ? 'Buscar despesa ou fornecedor' : 'Buscar paciente ou descrição' }}" class="input sm:w-64" aria-label="Buscar">
        </div>
    </div>

    @if ($quitados)
        <p class="mb-3 text-sm text-stone-500">
            {{ $aPagar ? 'Saiu das contas' : 'Entrou nas contas' }} em {{ $mesRef->translatedFormat('F/Y') }}:
            <span class="font-semibold tabular-nums text-stone-900">{{ $brl($this->totalQuitadoMes) }}</span>
        </p>
    @endif

    @if ($lista->isEmpty())
        <div class="card">
            <x-ui.empty-state
                :titulo="$quitados ? ($aPagar ? 'Nenhum pagamento neste mês' : 'Nenhum recebimento neste mês') : 'Nada em aberto aqui'"
                :texto="$quitados ? 'Os pagamentos registrados aparecem aqui.' : ($aPagar ? 'Nenhuma conta a pagar neste filtro.' : 'Nenhuma conta a receber neste filtro.')"
                icone="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </div>
    @elseif ($quitados)
        {{-- Pagos/recebidos no mês --}}
        <div class="card divide-y divide-stone-100">
            @foreach ($lista as $b)
                <div class="flex items-center gap-3 px-4 py-3" wire:key="b-{{ $b->id }}">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-stone-900">{{ $b->transacao?->descricao }}</p>
                        <p class="truncate text-xs text-stone-500">
                            {{ $b->data->format('d/m/Y') }} · {{ $b->conta?->nome }}
                            @if ($n = $nomeDe($b->transacao)) · {{ $n }} @endif
                            @if ((float) $b->juros + (float) $b->multa > 0) · encargos {{ $brl((float) $b->juros + (float) $b->multa) }} @endif
                            @if ((float) $b->desconto > 0) · desconto {{ $brl($b->desconto) }} @endif
                            @if ((float) $b->taxa > 0) · taxa {{ $brl($b->taxa) }} @endif
                        </p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold tabular-nums {{ $aPagar ? 'text-stone-900' : 'text-emerald-700' }}">{{ $brl($b->valor_movimentado) }}</p>
                    <button type="button" wire:click="abrirDetalhe('{{ $b->transacao_id }}')" class="btn-ghost min-h-[44px] px-3 text-sm">Ver</button>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $lista->links() }}</div>
    @else
        {{-- Em aberto --}}
        <div class="card divide-y divide-stone-100">
            @foreach ($lista as $t)
                @php
                    $atraso = $t->diasAtraso();
                    $aberto = $t->valorAberto();
                    $venceHoje = $t->data_vencimento?->isToday();
                @endphp
                <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:gap-4" wire:key="t-{{ $t->id }}">
                    <button type="button" wire:click="abrirDetalhe('{{ $t->id }}')" class="min-w-0 flex-1 text-left">
                        <p class="truncate text-sm font-medium text-stone-900">{{ $t->descricao }}</p>
                        <p class="truncate text-xs text-stone-500">
                            {{ $t->categoria }}
                            @if ($n = $nomeDe($t)) · {{ $n }} @endif
                            @if ($t->status === \App\Enums\StatusTransacao::Parcial) · pago {{ $brl($t->valor_pago) }} de {{ $brl($t->valor_bruto) }} @endif
                        </p>
                    </button>
                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <div class="text-left sm:text-right">
                            <p class="text-sm font-semibold tabular-nums text-stone-900">{{ $brl($aberto) }}</p>
                            <p class="text-xs tabular-nums {{ $atraso ? 'font-medium text-red-700' : ($venceHoje ? 'font-medium text-amber-700' : 'text-stone-500') }}">
                                @if ($atraso)
                                    venceu há {{ $atraso }} {{ $atraso === 1 ? 'dia' : 'dias' }}
                                @elseif ($venceHoje)
                                    vence hoje
                                @else
                                    vence {{ $t->data_vencimento?->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>
                        <button type="button" wire:click="abrirBaixa('{{ $t->id }}')" class="btn-primary min-h-[44px] shrink-0">
                            {{ $verbo }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $lista->links() }}</div>
    @endif

    {{-- ═══ Modal: registrar pagamento/recebimento ═══ --}}
    @if ($baixaId && ($tb = $this->transacaoBaixa))
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharBaixa"></div>
            <form wire:submit="confirmarBaixa" class="relative flex max-h-[92dvh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="border-b border-stone-100 px-5 py-4">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $aPagar ? 'Registrar pagamento' : 'Registrar recebimento' }}</h2>
                    <p class="mt-0.5 truncate text-sm text-stone-500">{{ $tb->descricao }} · falta {{ $brl($tb->valorAberto()) }}</p>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="baixa-valor">Valor (R$)</label>
                            <input id="baixa-valor" type="text" inputmode="decimal" wire:model.blur="baixaValor" class="input tabular-nums" autocomplete="off">
                        </div>
                        <div>
                            <label class="label" for="baixa-data">Data</label>
                            <input id="baixa-data" type="date" wire:model="baixaData" class="input">
                        </div>
                    </div>
                    @error('baixaValor') <p class="field-error -mt-2">{{ $message }}</p> @enderror
                    @error('baixaData') <p class="field-error -mt-2">{{ $message }}</p> @enderror
                    <p class="hint -mt-2">Pagando menos que o total, o restante continua em aberto.</p>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label" for="baixa-forma">Forma</label>
                            <select id="baixa-forma" wire:model.live="baixaForma" class="input">
                                @foreach ($formas as $f) <option value="{{ $f->value }}">{{ $f->label() }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label" for="baixa-conta">{{ $aPagar ? 'Saiu de' : 'Entrou em' }}</label>
                            <select id="baixa-conta" wire:model="baixaConta" class="input">
                                @foreach ($this->contas as $c) <option value="{{ $c->id }}">{{ $c->nome }}</option> @endforeach
                            </select>
                            @error('baixaConta') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if (! $baixaEncargos)
                        <button type="button" wire:click="$set('baixaEncargos', true)" class="text-sm font-medium text-rose-600 hover:text-rose-700 min-h-[44px]">
                            + Juros, multa ou desconto
                        </button>
                    @else
                        <div class="grid grid-cols-3 gap-3">
                            @foreach (['baixaJuros' => 'Juros', 'baixaMulta' => 'Multa', 'baixaDesconto' => 'Desconto'] as $campo => $rotulo)
                                <div>
                                    <label class="label" for="{{ $campo }}">{{ $rotulo }}</label>
                                    <input id="{{ $campo }}" type="text" inputmode="decimal" wire:model.blur="{{ $campo }}" class="input tabular-nums" placeholder="0,00" autocomplete="off">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="label" for="baixa-obs">Observação <span class="font-normal text-stone-400">(opcional)</span></label>
                        <input id="baixa-obs" type="text" wire:model="baixaObs" class="input" maxlength="255">
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-stone-50 px-4 py-3 text-sm">
                        <span class="text-stone-600">{{ $aPagar ? 'Total que sai da conta' : 'Total recebido' }}</span>
                        <span class="font-semibold tabular-nums text-stone-900">{{ $brl($this->totalBaixa()) }}</span>
                    </div>
                    @if (! $aPagar && (float) $tb->taxa_operacional > 0)
                        <p class="hint -mt-2">A taxa da maquininha ({{ $brl(\App\Actions\Financeiro\BaixarTransacaoAction::taxaProporcional($tb, \App\Support\Dinheiro::numero($baixaValor))) }}) é descontada do que entra na conta.</p>
                    @endif
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" wire:click="fecharBaixa" class="btn-secondary">Voltar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- ═══ Modal: detalhe e histórico ═══ --}}
    @if ($detalheId && ($d = $this->detalhe))
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharDetalhe"></div>
            <div class="relative flex max-h-[92dvh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-stone-100 px-5 py-4">
                    <div class="min-w-0">
                        <x-transacao.status-badge :status="$d->status" />
                        <h2 class="mt-2 truncate text-lg font-semibold text-stone-900">{{ $d->descricao }}</h2>
                        <p class="text-sm text-stone-500">{{ $d->categoria }} @if ($n = $nomeDe($d)) · {{ $n }} @endif</p>
                    </div>
                    <button type="button" wire:click="fecharDetalhe" aria-label="Fechar" class="-mr-2 flex h-11 w-11 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-xs text-stone-500">Valor</dt><dd class="font-medium tabular-nums">{{ $brl($d->valor_bruto) }}</dd></div>
                        <div><dt class="text-xs text-stone-500">{{ $aPagar ? 'Pago' : 'Recebido' }}</dt><dd class="font-medium tabular-nums">{{ $brl($d->valor_pago) }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Falta</dt><dd class="font-medium tabular-nums">{{ $brl($d->valorAberto()) }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Vencimento</dt><dd class="tabular-nums">{{ $d->data_vencimento?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Competência</dt><dd class="tabular-nums">{{ $d->data_competencia?->format('m/Y') }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Forma</dt><dd>{{ $d->forma_pagamento?->label() }}</dd></div>
                    </dl>

                    <h3 class="mt-5 text-sm font-semibold text-stone-900">{{ $aPagar ? 'Pagamentos' : 'Recebimentos' }}</h3>
                    @forelse ($d->baixas as $b)
                        <div class="mt-2 flex items-center gap-3 rounded-xl border border-stone-100 px-3 py-2" wire:key="db-{{ $b->id }}">
                            <div class="min-w-0 flex-1 text-sm">
                                <p class="tabular-nums"><span class="font-medium">{{ $brl($b->valor_movimentado) }}</span> em {{ $b->data->format('d/m/Y') }}</p>
                                <p class="truncate text-xs text-stone-500">
                                    {{ $b->conta?->nome }}
                                    @if ((float) $b->juros + (float) $b->multa > 0) · juros/multa {{ $brl((float) $b->juros + (float) $b->multa) }} @endif
                                    @if ((float) $b->desconto > 0) · desconto {{ $brl($b->desconto) }} @endif
                                    @if ((float) $b->taxa > 0) · taxa {{ $brl($b->taxa) }} @endif
                                    @if ($b->usuario) · por {{ $b->usuario->name }} @endif
                                </p>
                            </div>
                            <button type="button" wire:click="estornar('{{ $b->id }}')"
                                    wire:confirm="Desfazer este {{ $aPagar ? 'pagamento' : 'recebimento' }}? O valor volta a ficar em aberto."
                                    class="btn-ghost min-h-[44px] px-3 text-sm text-red-700">Desfazer</button>
                        </div>
                    @empty
                        <p class="mt-2 text-sm text-stone-500">Nada {{ $aPagar ? 'pago' : 'recebido' }} ainda.</p>
                    @endforelse
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <a href="{{ route('transacoes.index', ['editar' => $d->id]) }}" wire:navigate class="btn-secondary">Editar lançamento</a>
                    @if ($d->status->emAberto())
                        <button type="button" wire:click="abrirBaixa('{{ $d->id }}')" class="btn-primary">{{ $verbo }}</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
