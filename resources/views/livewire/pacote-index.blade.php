@php $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
<div>
    <x-ui.page-header titulo="Pacotes" subtitulo="Venda pacotes de sessões e acompanhe o saldo de cada paciente.">
        <x-slot:acoes>
            <button wire:click="novo" class="btn-primary">+ Vender pacote</button>
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
            <p class="text-sm text-stone-500">Pacotes ativos</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $resumo['ativos'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Sessões a realizar</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $resumo['sessoes_a_usar'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Vendido no mês</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $brl($resumo['vendido_no_mes']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Sessões usadas no mês</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $resumo['sessoes_no_mes'] }}</p>
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

    @if ($pacotes->isEmpty())
        <div class="card">
            @if (trim($busca) !== '' || $filtroStatus !== 'ativo')
                <x-ui.empty-state titulo="Nada encontrado com esses filtros" texto="Tente outro nome ou outra situação." />
            @else
                <x-ui.empty-state titulo="Nenhum pacote ativo"
                    texto="Venda pacotes de sessões (ex.: 10 sessões de laser). A receita entra na venda e cada atendimento desconta uma sessão."
                    icone="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9">
                    <button wire:click="novo" class="btn-primary">Vender pacote</button>
                </x-ui.empty-state>
            @endif
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="hidden w-full text-sm md:table">
                <thead class="bg-stone-50 text-left text-xs font-medium text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Pacote</th>
                        <th class="px-4 py-3">Saldo</th>
                        <th class="px-4 py-3">Validade</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                        <th class="px-4 py-3">Situação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($pacotes as $pc)
                        <tr wire:key="pc-{{ $pc->id }}" class="cursor-pointer hover:bg-stone-50" wire:click="$set('detalheId', '{{ $pc->id }}')">
                            <td class="px-4 py-3 font-medium text-stone-900">{{ $pc->paciente?->nome ?? '—' }}</td>
                            <td class="px-4 py-3 text-stone-700">{{ $pc->nome }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-1.5 w-20 overflow-hidden rounded-full bg-stone-100">
                                        <div class="h-full rounded-full bg-rose-500" style="width: {{ (int) round($pc->sessoes_usadas / max(1, $pc->sessoes_total) * 100) }}%"></div>
                                    </div>
                                    <span class="tabular-nums text-stone-700">{{ $pc->saldo() }} de {{ $pc->sessoes_total }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 tabular-nums {{ $pc->vencido() && $pc->status === \App\Enums\StatusPacote::Ativo ? 'text-red-700' : 'text-stone-600' }}">
                                {{ $pc->validade?->format('d/m/Y') ?? 'Sem validade' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-stone-800">{{ $brl($pc->valor_total) }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $pc->status->badge() }}">{{ $pc->status->label() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($pacotes as $pc)
                    <li wire:key="pcm-{{ $pc->id }}">
                        <button type="button" wire:click="$set('detalheId', '{{ $pc->id }}')" class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left">
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-stone-900">{{ $pc->paciente?->nome ?? '—' }}</span>
                                <span class="block truncate text-sm text-stone-500">{{ $pc->nome }}</span>
                                <span class="mt-1 block text-xs text-stone-500 tabular-nums">Saldo {{ $pc->saldo() }} de {{ $pc->sessoes_total }} · {{ $brl($pc->valor_total) }}</span>
                            </span>
                            <span class="badge shrink-0 {{ $pc->status->badge() }}">{{ $pc->status->label() }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-4">{{ $pacotes->links() }}</div>
    @endif

    {{-- Detalhe --}}
    @if ($this->detalhe)
        @php $d = $this->detalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('detalheId', null)"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <span class="badge {{ $d->status->badge() }}">{{ $d->status->label() }}</span>
                        <h2 class="mt-2 text-lg font-semibold text-stone-900">{{ $d->nome }}</h2>
                        <p class="text-sm text-stone-500">{{ $d->paciente?->nome }}</p>
                    </div>
                    <button wire:click="$set('detalheId', null)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                    <dl class="grid grid-cols-2 gap-4 rounded-xl bg-stone-50 p-4 text-sm">
                        <div><dt class="text-xs text-stone-500">Saldo</dt><dd class="font-medium tabular-nums text-stone-800">{{ $d->saldo() }} de {{ $d->sessoes_total }} sessões</dd></div>
                        <div><dt class="text-xs text-stone-500">Valor</dt><dd class="font-medium tabular-nums text-stone-800">{{ $brl($d->valor_total) }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Validade</dt><dd class="text-stone-800">{{ $d->validade?->format('d/m/Y') ?? 'Sem validade' }}</dd></div>
                        <div><dt class="text-xs text-stone-500">Pagamento</dt>
                            <dd class="text-stone-800">
                                {{ $d->transacao?->forma_pagamento?->label() ?? '—' }} ·
                                {{ $d->transacao?->status === \App\Enums\StatusTransacao::Pago ? 'pago' : 'a receber' }}
                            </dd>
                        </div>
                    </dl>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-stone-700">Sessões usadas</h3>
                        @forelse ($d->sessoes as $s)
                            <p class="flex justify-between border-b border-stone-100 py-2 text-sm last:border-0">
                                <span class="tabular-nums text-stone-800">{{ $s->usada_em->format('d/m/Y') }}</span>
                                <span class="text-stone-500">{{ $s->profissional?->nome ?? '—' }}</span>
                            </p>
                        @empty
                            <p class="text-sm text-stone-500">Nenhuma sessão usada ainda. Ao concluir um atendimento na Agenda, escolha usar a sessão do pacote.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    @if ($d->status === \App\Enums\StatusPacote::Ativo)
                        <button type="button" wire:click="cancelar('{{ $d->id }}')"
                                wire:confirm="Cancelar o saldo de {{ $d->saldo() }} sessões deste pacote? A receita não muda; se houver devolução, registre o estorno em Lançamentos."
                                class="btn-ghost text-red-600 sm:mr-auto">Cancelar pacote</button>
                    @endif
                    <button wire:click="$set('detalheId', null)" class="btn-secondary">Fechar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Venda --}}
    @if ($modal)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modal', false)"></div>
            <form wire:submit="vender" class="relative z-10 flex max-h-[96vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Vender pacote</h2>
                    <button type="button" wire:click="$set('modal', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    @include('livewire.partials.escolhe-paciente', ['idCampo' => 'pacote-paciente'])
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label for="pacote-procedimento" class="label">Procedimento</label>
                            <select id="pacote-procedimento" wire:model.live="procedimentoId" class="input">
                                <option value="">Vários ou outro</option>
                                @foreach ($this->procedimentos as $proc)
                                    <option value="{{ $proc->id }}">{{ $proc->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="pacote-sessoes" class="label">Sessões</label>
                            <input id="pacote-sessoes" type="number" inputmode="numeric" min="1" max="200" wire:model.live.debounce.400ms="sessoes" class="input tabular-nums">
                            @error('sessoes') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="pacote-nome" class="label">Nome do pacote</label>
                        <input id="pacote-nome" type="text" wire:model="nome" maxlength="150" class="input" placeholder="Ex.: 10 sessões de laser">
                        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pacote-valor" class="label">Valor total (R$)</label>
                            <input id="pacote-valor" type="number" inputmode="decimal" step="0.01" min="0" wire:model="valorTotal" class="input tabular-nums" placeholder="Ex.: 1.500,00">
                            @error('valorTotal') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pacote-validade" class="label">Validade (opcional)</label>
                            <input id="pacote-validade" type="date" wire:model="validade" min="{{ now()->toDateString() }}" class="input">
                            @error('validade') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="pacote-forma" class="label">Pagamento</label>
                            <select id="pacote-forma" wire:model="formaPagamento" class="input">
                                @foreach ($formas as $f)
                                    <option value="{{ $f->value }}">{{ $f->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="pacote-categoria" class="label">Categoria da receita</label>
                            <select id="pacote-categoria" wire:model="categoria" class="input">
                                <option value="">Selecione</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 rounded-xl bg-stone-100 p-1">
                        <button type="button" wire:click="$set('pago', true)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ $pago ? 'bg-surface text-emerald-700 shadow-sm' : 'text-stone-500' }}">Pago agora</button>
                        <button type="button" wire:click="$set('pago', false)"
                                class="min-h-[40px] rounded-lg text-sm font-medium transition-colors {{ ! $pago ? 'bg-surface text-amber-700 shadow-sm' : 'text-stone-500' }}">A receber</button>
                    </div>
                    <p class="hint">A receita do pacote entra agora em Lançamentos. Nos atendimentos, a sessão é descontada sem lançar receita de novo.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('modal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="vender">
                        <span wire:loading.remove wire:target="vender">Vender pacote</span>
                        <span wire:loading wire:target="vender">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
