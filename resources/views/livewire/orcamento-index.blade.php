@php $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
<div>
    <x-ui.page-header titulo="Orçamentos" subtitulo="Monte orçamentos, envie ao paciente e, aprovados, transforme em pacotes de sessões.">
        <x-slot:acoes>
            <button wire:click="novo" class="btn-primary">+ Novo orçamento</button>
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

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Em aberto</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $resumo['abertos'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Valor em aberto</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $brl($resumo['valor_aberto']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Aprovado no mês</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $brl($resumo['aprovados_mes']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Taxa de aprovação no mês</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">
                {{ $resumo['decididos_mes'] ? round($resumo['aprovados_qtd'] / $resumo['decididos_mes'] * 100) . '%' : '—' }}
            </p>
        </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row">
        <input type="search" wire:model.live.debounce.400ms="busca" class="input sm:max-w-xs" placeholder="Buscar paciente" aria-label="Buscar paciente">
        <select wire:model.live="filtroStatus" class="input sm:max-w-[12rem]" aria-label="Situação">
            <option value="">Todas as situações</option>
            @foreach ($status as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($orcamentos->isEmpty())
        <div class="card">
            @if (trim($busca) !== '' || $filtroStatus !== '')
                <x-ui.empty-state titulo="Nada encontrado com esses filtros" texto="Tente outro nome ou outra situação." />
            @else
                <x-ui.empty-state titulo="Nenhum orçamento ainda"
                    texto="Monte o orçamento com os procedimentos e as sessões, envie em PDF ou WhatsApp e aprove quando o paciente fechar."
                    icone="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z">
                    <button wire:click="novo" class="btn-primary">Fazer orçamento</button>
                </x-ui.empty-state>
            @endif
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="hidden w-full text-sm md:table">
                <thead class="bg-stone-50 text-left text-xs font-medium text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Número</th>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Emitido</th>
                        <th class="px-4 py-3">Validade</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Situação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($orcamentos as $o)
                        @php [$rotulo, $cor] = $o->situacao(); @endphp
                        <tr wire:key="o-{{ $o->id }}" class="cursor-pointer hover:bg-stone-50" wire:click="$set('detalheId', '{{ $o->id }}')">
                            <td class="px-4 py-3 font-medium tabular-nums text-stone-900">{{ $o->codigo() }}</td>
                            <td class="px-4 py-3 text-stone-800">{{ $o->paciente?->nome ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums text-stone-600">{{ $o->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 tabular-nums text-stone-600">{{ $o->validade->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-stone-800">{{ $brl($o->total) }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $cor }}">{{ $rotulo }}</span>
                                @if ($o->enviado_em && $o->status === \App\Enums\StatusOrcamento::Aberto)
                                    <span class="ml-1 text-xs text-stone-400">enviado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($orcamentos as $o)
                    @php [$rotulo, $cor] = $o->situacao(); @endphp
                    <li wire:key="om-{{ $o->id }}">
                        <button type="button" wire:click="$set('detalheId', '{{ $o->id }}')" class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-stone-900">{{ $o->paciente?->nome ?? '—' }}</span>
                                <span class="block text-sm tabular-nums text-stone-500">{{ $o->codigo() }} · {{ $brl($o->total) }}</span>
                                <span class="block text-xs text-stone-400">Válido até {{ $o->validade->format('d/m/Y') }}</span>
                            </span>
                            <span class="badge shrink-0 {{ $cor }}">{{ $rotulo }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-4">{{ $orcamentos->links() }}</div>
    @endif

    {{-- Detalhe --}}
    @if ($this->detalhe && ! $aprovandoId)
        @php $d = $this->detalhe; [$rotulo, $cor] = $d->situacao(); $aberto = $d->status === \App\Enums\StatusOrcamento::Aberto; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('detalheId', null)"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <span class="badge {{ $cor }}">{{ $rotulo }}</span>
                        <h2 class="mt-2 text-lg font-semibold text-stone-900">Orçamento {{ $d->codigo() }}</h2>
                        <p class="text-sm text-stone-500">{{ $d->paciente?->nome }} · válido até {{ $d->validade->format('d/m/Y') }}</p>
                    </div>
                    <button wire:click="$set('detalheId', null)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="divide-y divide-stone-100 rounded-xl border border-stone-200">
                        @foreach ($d->itens as $item)
                            <div class="flex items-start justify-between gap-3 px-4 py-3 text-sm">
                                <span class="min-w-0">
                                    <span class="block text-stone-800">{{ $item->descricao }}</span>
                                    <span class="text-xs text-stone-500 tabular-nums">{{ $item->quantidade }} × {{ $brl($item->valor_unitario) }}</span>
                                </span>
                                <span class="tabular-nums text-stone-800">{{ $brl($item->subtotal) }}</span>
                            </div>
                        @endforeach
                        @if ((float) $d->desconto > 0)
                            <div class="flex justify-between px-4 py-2 text-sm text-stone-600"><span>Desconto</span><span class="tabular-nums">− {{ $brl($d->desconto) }}</span></div>
                        @endif
                        <div class="flex justify-between px-4 py-3 font-semibold text-stone-900"><span>Total</span><span class="tabular-nums">{{ $brl($d->total) }}</span></div>
                    </div>
                    @if ($d->forma_pagamento)
                        <p class="text-sm text-stone-600">Pagamento: {{ $d->forma_pagamento->label() }}</p>
                    @endif
                    @if ($d->observacoes)
                        <p class="whitespace-pre-line text-sm text-stone-600">{{ $d->observacoes }}</p>
                    @endif
                    @if ($d->pacotes->isNotEmpty())
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-stone-700">Pacotes gerados</h3>
                            @foreach ($d->pacotes as $pc)
                                <p class="flex justify-between py-1 text-sm">
                                    <span class="text-stone-800">{{ $pc->nome }}</span>
                                    <span class="tabular-nums text-stone-500">saldo {{ $pc->saldo() }} de {{ $pc->sessoes_total }}</span>
                                </p>
                            @endforeach
                        </div>
                    @endif
                    <p class="text-xs text-stone-400">
                        Feito por {{ $d->autor?->name ?? 'usuário removido' }} em {{ $d->created_at->format('d/m/Y') }}
                        @if ($d->enviado_em) · enviado por WhatsApp em {{ $d->enviado_em->format('d/m/Y') }} @endif
                    </p>
                </div>
                <div class="flex flex-col gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:flex-wrap sm:justify-end sm:px-6">
                    <a href="{{ route('orcamentos.pdf', $d->id) }}" target="_blank" rel="noopener" class="btn-secondary">PDF</a>
                    @if ($aberto)
                        @if ($d->paciente?->telefone)
                            <button type="button" wire:click="enviarWhatsapp('{{ $d->id }}')" wire:loading.attr="disabled" wire:target="enviarWhatsapp" class="btn-secondary">
                                {{ $d->enviado_em ? 'Reenviar' : 'Enviar' }} no WhatsApp
                            </button>
                        @endif
                        <button type="button" wire:click="editar('{{ $d->id }}')" class="btn-secondary">Editar</button>
                        <button type="button" wire:click="recusar('{{ $d->id }}')" wire:confirm="Marcar o orçamento {{ $d->codigo() }} como recusado?" class="btn-ghost text-red-600">Recusado</button>
                        <button type="button" wire:click="abrirAprovacao('{{ $d->id }}')" class="btn-primary">Aprovar</button>
                    @else
                        <button wire:click="$set('detalheId', null)" class="btn-primary">Fechar</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Aprovar --}}
    @if ($aprovandoId)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('aprovandoId', null)"></div>
            <form wire:submit="aprovar" class="relative z-10 w-full max-w-md overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Aprovar orçamento</h2>
                    <p class="text-sm text-stone-500">A receita é lançada com o total e cada item vira um pacote de sessões do paciente.</p>
                </div>
                <div class="space-y-4 px-5 py-5 sm:px-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="aprovar-forma" class="label">Pagamento</label>
                            <select id="aprovar-forma" wire:model="aprovarForma" class="input">
                                @foreach ($formas as $f)
                                    <option value="{{ $f->value }}">{{ $f->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="aprovar-categoria" class="label">Categoria da receita</label>
                            <select id="aprovar-categoria" wire:model="aprovarCategoria" class="input">
                                <option value="">Selecione</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('aprovarCategoria') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="aprovar-validade" class="label">Validade dos pacotes (opcional)</label>
                        <input id="aprovar-validade" type="date" wire:model="aprovarValidade" min="{{ now()->toDateString() }}" class="input">
                        @error('aprovarValidade') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 rounded-xl bg-stone-100 p-1">
                        <button type="button" wire:click="$set('aprovarPago', true)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ $aprovarPago ? 'bg-surface text-emerald-700 shadow-sm' : 'text-stone-500' }}">Pago agora</button>
                        <button type="button" wire:click="$set('aprovarPago', false)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ ! $aprovarPago ? 'bg-surface text-amber-700 shadow-sm' : 'text-stone-500' }}">A receber</button>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('aprovandoId', null)" class="btn-secondary">Voltar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="aprovar">Aprovar e lançar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Formulário --}}
    @if ($modal)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modal', false)"></div>
            <form wire:submit="salvar" class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $editandoId ? 'Editar orçamento' : 'Novo orçamento' }}</h2>
                    <button type="button" wire:click="$set('modal', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                    @include('livewire.partials.escolhe-paciente', ['idCampo' => 'orcamento-paciente'])

                    <fieldset class="space-y-3">
                        <legend class="label">Itens</legend>
                        @foreach ($itens as $i => $item)
                            <div class="rounded-xl border border-stone-200 p-3" wire:key="item-{{ $i }}">
                                <div class="grid gap-3 sm:grid-cols-12">
                                    <div class="sm:col-span-5">
                                        <label class="sr-only" for="item-proc-{{ $i }}">Procedimento</label>
                                        <select id="item-proc-{{ $i }}" wire:model.live="itens.{{ $i }}.procedimento_id" class="input">
                                            <option value="">Outro (descrever)</option>
                                            @foreach ($this->procedimentos as $proc)
                                                <option value="{{ $proc->id }}">{{ $proc->nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sm:col-span-7">
                                        <label class="sr-only" for="item-desc-{{ $i }}">Descrição</label>
                                        <input id="item-desc-{{ $i }}" type="text" wire:model="itens.{{ $i }}.descricao" maxlength="150" class="input" placeholder="Ex.: Limpeza de pele">
                                        @error("itens.$i.descricao") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="text-xs text-stone-500" for="item-qtd-{{ $i }}">Sessões</label>
                                        <input id="item-qtd-{{ $i }}" type="number" inputmode="numeric" min="1" max="200" wire:model.live.debounce.400ms="itens.{{ $i }}.quantidade" class="input tabular-nums">
                                        @error("itens.$i.quantidade") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-4">
                                        <label class="text-xs text-stone-500" for="item-valor-{{ $i }}">Valor por sessão (R$)</label>
                                        <input id="item-valor-{{ $i }}" type="number" inputmode="decimal" step="0.01" min="0" wire:model.live.debounce.400ms="itens.{{ $i }}.valor_unitario" class="input tabular-nums">
                                        @error("itens.$i.valor_unitario") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex items-end justify-between gap-2 sm:col-span-5">
                                        <p class="pb-3 text-sm tabular-nums text-stone-700">
                                            {{ $brl(max(0, (int) $item['quantidade']) * (float) $item['valor_unitario']) }}
                                        </p>
                                        <button type="button" wire:click="removerItem({{ $i }})" class="btn-ghost text-red-600" aria-label="Remover item">Remover</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button type="button" wire:click="adicionarItem" class="btn-secondary w-full sm:w-auto">+ Adicionar item</button>
                    </fieldset>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="orc-desconto" class="label">Desconto (R$)</label>
                            <input id="orc-desconto" type="number" inputmode="decimal" step="0.01" min="0" wire:model.live.debounce.400ms="desconto" class="input tabular-nums">
                            @error('desconto') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="orc-validade" class="label">Válido até</label>
                            <input id="orc-validade" type="date" wire:model="validade" min="{{ now()->toDateString() }}" class="input">
                            @error('validade') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="orc-forma" class="label">Pagamento (opcional)</label>
                            <select id="orc-forma" wire:model="formaPagamento" class="input">
                                <option value="">A combinar</option>
                                @foreach ($formas as $f)
                                    <option value="{{ $f->value }}">{{ $f->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="orc-obs" class="label">Observações (opcional)</label>
                        <textarea id="orc-obs" wire:model="observacoes" rows="2" class="input" placeholder="Ex.: Intervalo de 30 dias entre as sessões."></textarea>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-stone-50 px-4 py-3">
                        <span class="text-sm text-stone-600">Total</span>
                        <span class="text-xl font-semibold tabular-nums text-stone-900">{{ $brl($this->totais['total']) }}</span>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('modal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvar">
                        <span wire:loading.remove wire:target="salvar">Salvar orçamento</span>
                        <span wire:loading wire:target="salvar">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
