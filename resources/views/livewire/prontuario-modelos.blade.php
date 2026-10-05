<div>
    <x-ui.page-header titulo="Modelos de termos e orientações" subtitulo="Textos prontos para colher assinaturas e orientar pacientes, com os dados de cada um preenchidos sozinhos.">
        <x-slot:acoes>
            <button wire:click="novo('termo')" class="btn-primary">+ Novo modelo</button>
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

    @if ($modelos->isEmpty())
        <div class="card">
            <x-ui.empty-state titulo="Nenhum modelo ainda"
                texto="Comece com os nossos modelos de termo de consentimento e de cuidados pós-procedimento e ajuste o texto à sua clínica."
                icone="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z">
                <button wire:click="usarModelosProntos" class="btn-primary">Usar modelos prontos</button>
                <button wire:click="novo('termo')" class="btn-secondary">Escrever do zero</button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($tipos as $t)
                <section>
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-stone-700">{{ $t->label() }}</h2>
                        <button wire:click="novo('{{ $t->value }}')" class="btn-ghost min-h-9 px-3 text-sm">+ Adicionar</button>
                    </div>
                    <div class="card divide-y divide-stone-100">
                        @forelse ($modelos->get($t->value, collect()) as $m)
                            <button type="button" wire:click="editar('{{ $m->id }}')" wire:key="modelo-{{ $m->id }}"
                                    class="flex min-h-14 w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-stone-50">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-stone-900">{{ $m->titulo }}</span>
                                    <span class="text-xs text-stone-500">Alterado em {{ $m->updated_at->timezone(config('clinica.fuso_horario'))->format('d/m/Y') }}</span>
                                </span>
                                @unless ($m->ativo)
                                    <span class="badge bg-stone-100 text-stone-600">Inativo</span>
                                @endunless
                            </button>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-stone-500">Nenhum modelo deste tipo.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    @if ($modal)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modal', false)"></div>
            <form wire:submit="salvar" class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $editandoId ? 'Editar modelo' : 'Novo modelo' }}</h2>
                    <button type="button" wire:click="$set('modal', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="tipo" class="label">Tipo</label>
                            <select id="tipo" wire:model="tipo" class="input">
                                @foreach ($tipos as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="titulo" class="label">Título</label>
                            <input id="titulo" type="text" wire:model="titulo" maxlength="150" class="input" placeholder="Ex.: Termo para preenchimento labial">
                            @error('titulo') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="conteudo" class="label">Texto</label>
                        <textarea id="conteudo" wire:model="conteudo" rows="14" class="input text-sm leading-relaxed"></textarea>
                        @error('conteudo') <p class="field-error">{{ $message }}</p> @enderror
                        <p class="hint">Campos preenchidos sozinhos: {{ implode(', ', $campos) }}.</p>
                    </div>
                    <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                        <input type="checkbox" wire:model="ativo" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-500">
                        Modelo ativo (aparece na hora de colher assinatura ou orientar)
                    </label>
                    @if ($editandoId)
                        <p class="hint">Mudar o modelo não altera os termos já assinados: cada termo guarda o próprio texto.</p>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('modal', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvar">
                        <span wire:loading.remove wire:target="salvar">Salvar modelo</span>
                        <span wire:loading wire:target="salvar">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
