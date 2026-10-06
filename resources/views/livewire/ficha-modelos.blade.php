<div>
    <x-ui.page-header titulo="Fichas de atendimento" subtitulo="As fichas que aparecem no atendimento. Edite as perguntas, a ordem e quais ficam visíveis.">
        <x-slot:acoes>
            <button type="button" wire:click="novo" class="btn-primary">+ Nova ficha</button>
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

    <div class="card divide-y divide-stone-100">
        @foreach ($this->modelos as $m)
            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="fm-{{ $m->id }}">
                <div class="min-w-0">
                    <p class="font-medium text-stone-900">
                        {{ $m->nome }}
                        @unless ($m->ativo) <span class="badge ml-1 bg-stone-100 text-stone-500">Oculta</span> @endunless
                    </p>
                    <p class="mt-0.5 text-sm text-stone-500">{{ count($m->campos) }} {{ count($m->campos) === 1 ? 'pergunta' : 'perguntas' }}{{ $m->descricao ? ' · ' . $m->descricao : '' }}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button type="button" wire:click="mover('{{ $m->id }}', -1)" class="btn-ghost min-h-11 w-11 px-0" aria-label="Subir {{ $m->nome }}" @disabled($loop->first)>↑</button>
                    <button type="button" wire:click="mover('{{ $m->id }}', 1)" class="btn-ghost min-h-11 w-11 px-0" aria-label="Descer {{ $m->nome }}" @disabled($loop->last)>↓</button>
                    <button type="button" wire:click="editar('{{ $m->id }}')" class="btn-secondary">Editar</button>
                </div>
            </div>
        @endforeach
    </div>
    <p class="hint mt-3">Mudar uma ficha não altera os atendimentos já feitos: cada um guarda as perguntas como estavam.</p>

    @if ($modal)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modal', false)"></div>
            <form wire:submit="salvar" class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $editandoId ? 'Editar ficha' : 'Nova ficha' }}</h2>
                    <button type="button" wire:click="$set('modal', false)" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="fm-nome" class="label">Nome</label>
                            <input id="fm-nome" type="text" maxlength="100" wire:model="nome" class="input" placeholder="Ex.: Anamnese">
                            @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="fm-desc" class="label">Descrição (opcional)</label>
                            <input id="fm-desc" type="text" maxlength="255" wire:model="descricao" class="input">
                        </div>
                    </div>
                    <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                        <input type="checkbox" wire:model="ativo" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                        Mostrar esta ficha no atendimento
                    </label>

                    <div>
                        <p class="label">Perguntas</p>
                        @error('campos') <p class="field-error">{{ $message }}</p> @enderror
                        <div class="space-y-3">
                            @foreach ($campos as $i => $c)
                                <div class="rounded-xl border border-stone-200 p-3" wire:key="campo-{{ $i }}-{{ $c['id'] }}">
                                    <div class="grid gap-2 sm:grid-cols-[12rem_minmax(0,1fr)]">
                                        <select wire:model.live="campos.{{ $i }}.tipo" class="input" aria-label="Tipo da pergunta {{ $i + 1 }}">
                                            @foreach ($tipos as $t) <option value="{{ $t->value }}">{{ $t->label() }}</option> @endforeach
                                        </select>
                                        <div>
                                            <input type="text" maxlength="150" wire:model="campos.{{ $i }}.rotulo" class="input" aria-label="Texto da pergunta {{ $i + 1 }}"
                                                   placeholder="{{ $c['tipo'] === 'titulo' ? 'Ex.: Medidas' : 'Ex.: Queixa principal' }}">
                                            @error("campos.$i.rotulo") <p class="field-error">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    @if (\App\Enums\TipoCampoFicha::tryFrom($c['tipo'])?->temOpcoes())
                                        <textarea wire:model="campos.{{ $i }}.opcoes" rows="3" class="input mt-2" aria-label="Opções da pergunta {{ $i + 1 }}" placeholder="Uma opção por linha"></textarea>
                                    @endif
                                    <div class="mt-2 flex justify-end gap-1">
                                        <button type="button" wire:click="moverCampo({{ $i }}, -1)" class="btn-ghost min-h-10 w-10 px-0" aria-label="Subir pergunta" @disabled($loop->first)>↑</button>
                                        <button type="button" wire:click="moverCampo({{ $i }}, 1)" class="btn-ghost min-h-10 w-10 px-0" aria-label="Descer pergunta" @disabled($loop->last)>↓</button>
                                        <button type="button" wire:click="removerCampo({{ $i }})" class="btn-ghost min-h-10 px-3 text-sm text-red-600">Remover</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="adicionarCampo" class="btn-secondary mt-3">+ Pergunta</button>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="$set('modal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    @endif
</div>
