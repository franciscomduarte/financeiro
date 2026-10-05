<div>
    {{-- Classes das mensagens: border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
    @php
        $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
        $situacao = fn ($r) => match (true) {
            $r->encerrada() => ['Encerrada', 'bg-stone-100 text-stone-600'],
            ! $r->ativa     => ['Pausada', 'bg-amber-50 text-amber-700'],
            default         => ['Ativa', 'bg-emerald-50 text-emerald-700'],
        };
    @endphp

    <x-ui.page-header titulo="Lançamentos" subtitulo="Contas fixas que se repetem. O sistema cria os lançamentos de cada mês sozinho.">
        <x-slot:acoes>
            <a href="{{ route('transacoes.index', ['novo' => 'recorrente']) }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nova recorrência
            </a>
        </x-slot:acoes>
    </x-ui.page-header>
    <x-ui.abas-lancamentos />

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    {{-- Indicadores --}}
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Recorrências ativas</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalAtivas }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Saídas fixas por mês</p>
            <p class="mt-1 text-2xl font-semibold text-red-600 tabular-nums">{{ $brl($saidasMensais) }}</p>
            <p class="mt-0.5 text-xs text-stone-500">anuais e semestrais divididas por mês</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Entradas fixas por mês</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-600 tabular-nums">{{ $brl($entradasMensais) }}</p>
        </div>
    </div>

    {{-- Filtro --}}
    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="Filtrar recorrências">
        @foreach (['ativas' => 'Ativas', 'pausadas' => 'Pausadas', 'encerradas' => 'Encerradas', 'todas' => 'Todas'] as $valor => $rotulo)
            <button type="button" wire:click="$set('filtro', '{{ $valor }}')" aria-pressed="{{ $filtro === $valor ? 'true' : 'false' }}"
                    class="min-h-[40px] rounded-full px-4 text-sm font-medium border transition-colors
                           {{ $filtro === $valor ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-surface border-stone-200 text-stone-600 hover:border-stone-300' }}">
                {{ $rotulo }}
            </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        @if ($recorrencias->isEmpty())
            @if ($filtro === 'ativas' || $filtro === 'todas')
                <x-ui.empty-state titulo="Nenhuma conta fixa ainda"
                                  texto="Cadastre aluguel, salários, internet e outras contas que se repetem. O sistema cria os lançamentos todo mês para você."
                                  icone="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99">
                    <a href="{{ route('transacoes.index', ['novo' => 'recorrente']) }}" class="btn-primary">Cadastrar conta fixa</a>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state titulo="Nada por aqui" texto="Nenhuma recorrência {{ $filtro === 'pausadas' ? 'pausada' : 'encerrada' }}." />
            @endif
        @else
            <ul class="divide-y divide-stone-100">
                @foreach ($recorrencias as $r)
                    @php([$rotuloSit, $corSit] = $situacao($r))
                    <li wire:key="rec-{{ $r->id }}" class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium text-stone-900 truncate">{{ $r->descricao }}</p>
                                <span class="badge {{ $corSit }}">{{ $rotuloSit }}</span>
                            </div>
                            <p class="mt-0.5 text-sm text-stone-500">
                                {{ $r->categoria }} · {{ $r->frequencia->label() }}, dia {{ $r->dia_vencimento }} · {{ $r->forma_pagamento->label() }}
                                @if ($r->lancar_como_pago) · entra como pago @endif
                            </p>
                            <p class="mt-0.5 text-xs text-stone-500">
                                @if ($r->ativa)
                                    Próximo: {{ $r->proxima_data->format('d/m/Y') }}
                                @elseif ($r->encerrada())
                                    Encerrada em {{ $r->encerrada_em->format('d/m/Y') }}
                                @else
                                    Pausada
                                @endif
                                @if ($r->data_fim) · até {{ $r->data_fim->format('d/m/Y') }} @endif
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <p class="font-semibold tabular-nums {{ $r->tipo->value === 'saida' ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ $r->tipo->value === 'saida' ? '−' : '+' }} {{ $brl($r->valor_bruto) }}
                            </p>
                            @unless ($r->encerrada())
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="editar('{{ $r->id }}')" class="btn-ghost min-h-[40px] px-3">Editar</button>
                                    @if ($r->ativa)
                                        <button type="button" wire:click="pausar('{{ $r->id }}')" class="btn-ghost min-h-[40px] px-3">Pausar</button>
                                    @else
                                        <button type="button" wire:click="retomar('{{ $r->id }}')" class="btn-ghost min-h-[40px] px-3">Retomar</button>
                                    @endif
                                    <button type="button" wire:click="encerrar('{{ $r->id }}')"
                                            wire:confirm="Encerrar “{{ $r->descricao }}”? Não serão criados novos lançamentos. Os já criados continuam na lista."
                                            class="btn-ghost min-h-[40px] px-3 text-red-600">Encerrar</button>
                                </div>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="mt-4">{{ $recorrencias->links() }}</div>

    {{-- Edição --}}
    @if ($editandoId)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="titulo-editar-rec"
             x-data @keydown.escape.window="$wire.fecharEdicao()">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharEdicao"></div>
            <form wire:submit="salvar" class="relative w-full sm:max-w-lg max-h-[92dvh] overflow-y-auto bg-surface rounded-t-2xl sm:rounded-2xl shadow-xl">
                <div class="px-5 pt-5 pb-3 border-b border-stone-200/70">
                    <h2 id="titulo-editar-rec" class="text-lg font-semibold text-stone-900">Editar recorrência</h2>
                    <p class="mt-1 text-sm text-stone-500">Vale dos próximos lançamentos em diante. Os já criados não mudam.</p>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label for="rec-desc" class="label">Descrição</label>
                        <input id="rec-desc" type="text" wire:model="descricao" class="input" maxlength="255">
                        @error('descricao') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rec-cat" class="label">Categoria</label>
                            <select id="rec-cat" wire:model="categoria" class="input">
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                                @unless (in_array($categoria, $categorias, true))
                                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                                @endunless
                            </select>
                            @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rec-valor" class="label">Valor</label>
                            <input id="rec-valor" type="text" inputmode="decimal" wire:model="valorBruto" class="input" placeholder="Ex.: 1500,00">
                            @error('valorBruto') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="rec-forma" class="label">Forma de pagamento</label>
                            <select id="rec-forma" wire:model="formaPagamento" class="input">
                                @foreach ($formasPagamento as $f)
                                    <option value="{{ $f->value }}">{{ $f->label() }}</option>
                                @endforeach
                            </select>
                            @error('formaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="rec-fim" class="label">Repetir até <span class="font-normal text-stone-500">(opcional)</span></label>
                            <input id="rec-fim" type="date" wire:model="dataFim" class="input">
                            @error('dataFim') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <label class="flex min-h-[44px] items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="lancarComoPago" class="mt-1 w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                        <span class="text-sm text-stone-700">Lançar já como pago <span class="block text-xs text-stone-500">Para débito automático.</span></span>
                    </label>
                    <div>
                        <label for="rec-obs" class="label">Observações</label>
                        <textarea id="rec-obs" wire:model="observacoes" rows="2" class="input resize-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-5 py-4 border-t border-stone-200/70">
                    <button type="button" wire:click="fecharEdicao" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled">Salvar alterações</button>
                </div>
            </form>
        </div>
    @endif
</div>
