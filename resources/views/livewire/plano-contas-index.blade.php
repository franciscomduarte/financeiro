<div>
    <x-ui.page-header titulo="Plano de contas" subtitulo="As categorias de receitas e despesas, organizadas nos grupos que formam a DRE." />

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    <div class="mb-5 flex gap-1 border-b border-stone-200" role="tablist">
        @foreach (['saida' => 'Despesas', 'entrada' => 'Receitas'] as $chave => $titulo)
            <button type="button" wire:click="$set('tipo', '{{ $chave }}')" role="tab" aria-selected="{{ $tipo === $chave ? 'true' : 'false' }}"
                    class="-mb-px inline-flex min-h-[44px] items-center border-b-2 px-4 text-sm font-medium {{ $tipo === $chave ? 'border-rose-600 text-rose-700' : 'border-transparent text-stone-500 hover:text-stone-700' }}">{{ $titulo }}</button>
        @endforeach
    </div>

    <div class="space-y-4">
        @foreach ($this->grupos as $grupo => $contas)
            @php $g = \App\Enums\GrupoPlanoContas::from($grupo); @endphp
            <section class="card overflow-hidden" wire:key="g-{{ $grupo }}">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-semibold text-stone-900">{{ $g->label() }}</h2>
                        @if ($g->foraDoResultado())
                            <p class="text-xs text-stone-500">Mexe no caixa, mas não entra no resultado (lucro).</p>
                        @elseif ($g->fixa())
                            <p class="text-xs text-stone-500">Despesa fixa: existe mesmo sem atendimentos.</p>
                        @elseif ($g === \App\Enums\GrupoPlanoContas::CustosVariaveis)
                            <p class="text-xs text-stone-500">Cresce junto com os atendimentos.</p>
                        @endif
                    </div>
                    <button type="button" wire:click="nova('{{ $grupo }}')" class="btn-ghost min-h-[44px] shrink-0 text-sm">+ Categoria</button>
                </div>
                <div class="divide-y divide-stone-100">
                    @forelse ($contas as $c)
                        @php $usos = $this->usos[$c->id] ?? 0; @endphp
                        <div class="flex items-center gap-3 px-4 py-2 {{ $c->ativa ? '' : 'opacity-60' }}" wire:key="c-{{ $c->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-stone-900">{{ $c->nome }} @unless ($c->ativa) <span class="badge bg-stone-100 text-stone-600">inativa</span> @endunless</p>
                                <p class="text-xs text-stone-400">{{ $usos }} {{ $usos === 1 ? 'lançamento' : 'lançamentos' }}</p>
                            </div>
                            <button type="button" wire:click="editar('{{ $c->id }}')" class="btn-ghost min-h-[44px] px-3 text-sm">Editar</button>
                            @if ($usos === 0)
                                <button type="button" wire:click="excluir('{{ $c->id }}')" wire:confirm="Excluir a categoria {{ $c->nome }}?" class="btn-ghost min-h-[44px] px-3 text-sm text-red-700">Excluir</button>
                            @else
                                <button type="button" wire:click="alternar('{{ $c->id }}')" class="btn-ghost min-h-[44px] px-3 text-sm">{{ $c->ativa ? 'Desativar' : 'Reativar' }}</button>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-3 text-sm text-stone-500">Nenhuma categoria neste grupo.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if ($modal)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modal', false)"></div>
            <form wire:submit="salvar" class="relative flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <h2 class="border-b border-stone-100 px-5 py-4 text-lg font-semibold text-stone-900">{{ $editandoId ? 'Editar categoria' : 'Nova categoria' }}</h2>
                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="label" for="pc-nome">Nome</label>
                        <input id="pc-nome" type="text" wire:model="nome" class="input" maxlength="100">
                        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="pc-grupo">Grupo</label>
                        <select id="pc-grupo" wire:model="grupo" class="input">
                            @foreach ($gruposDoTipo as $g) <option value="{{ $g->value }}">{{ $g->label() }}</option> @endforeach
                        </select>
                        @error('grupo') <p class="field-error">{{ $message }}</p> @enderror
                        @if ($editandoId) <p class="hint">Mudar o nome ou o grupo também atualiza os lançamentos já feitos.</p> @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4">
                    <button type="button" wire:click="$set('modal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    @endif
</div>
