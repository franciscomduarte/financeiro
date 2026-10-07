<div>
    <x-ui.page-header titulo="Assistente do WhatsApp" subtitulo="Responde os leads com o que vocês ensinarem aqui e agenda a avaliação. Passa para a equipe quando precisa.">
        <x-slot:acoes>
            <a href="{{ route('leads.index') }}" wire:navigate class="btn-secondary">Voltar aos leads</a>
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

    @if ($this->pendencias)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-medium">Antes de ligar:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($this->pendencias as $p) <li>{{ $p }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
        <div class="space-y-4">
            {{-- Configuração --}}
            <form wire:submit="salvar" class="card space-y-4 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-stone-900">Funcionamento</h2>
                        <p class="text-xs text-stone-500">
                            {{ $this->config->respostasNoMes() }} de {{ $this->config->limite_respostas_mes }} respostas usadas este mês.
                        </p>
                    </div>
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium text-stone-800">
                        <input type="checkbox" wire:model.live="ativo" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        {{ $ativo ? 'Ligado' : 'Desligado' }}
                    </label>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="a-nome" class="label">Nome do assistente</label>
                        <input id="a-nome" type="text" wire:model="nome" maxlength="60" class="input" placeholder="Ex.: Bia">
                        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="a-limite" class="label">Limite de respostas por mês</label>
                        <input id="a-limite" type="number" inputmode="numeric" min="0" wire:model="limiteRespostasMes" class="input">
                        <p class="hint">Ao chegar no limite, ele para até o mês seguinte.</p>
                        @error('limiteRespostasMes') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="a-instrucoes" class="label">Jeito de falar e regras da clínica (opcional)</label>
                    <textarea id="a-instrucoes" wire:model="instrucoes" rows="3" maxlength="5000" class="input"
                              placeholder="Ex.: Trate por você. Não ofereça desconto. Sempre lembre que a avaliação é gratuita."></textarea>
                    @error('instrucoes') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-stone-800">
                        <input type="checkbox" wire:model="informarPrecos" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Informar os preços cadastrados nos procedimentos
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-stone-800">
                        <input type="checkbox" wire:model.live="podeAgendar" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Agendar a avaliação sozinho, nos horários livres da agenda
                    </label>
                </div>

                @if ($podeAgendar)
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="a-proc" class="label">Procedimento da avaliação</label>
                            <select id="a-proc" wire:model="procedimentoAvaliacaoId" class="input">
                                <option value="">Escolha…</option>
                                @foreach ($this->procedimentos as $p)
                                    <option value="{{ $p->id }}">{{ $p->nome }} ({{ $p->duracao_minutos }} min)</option>
                                @endforeach
                            </select>
                            @error('procedimentoAvaliacaoId') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="a-prof" class="label">Profissional</label>
                            <select id="a-prof" wire:model="profissionalId" class="input">
                                <option value="">Qualquer uma com horário livre</option>
                                @foreach ($this->profissionais as $p)
                                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>

            {{-- Treinamento --}}
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-semibold text-stone-900">Treinamento</h2>
                        <p class="text-xs text-stone-500">Tudo o que o assistente pode dizer. Os procedimentos, preços e dados da clínica cadastrados no sistema já entram sozinhos.</p>
                    </div>
                    <button type="button" wire:click="novoConhecimento" class="btn-secondary shrink-0">+ Item</button>
                </div>
                <div class="divide-y divide-stone-100">
                    @forelse ($this->conhecimentos as $c)
                        <div class="flex items-start gap-3 px-4 py-3 {{ $c->ativo ? '' : 'opacity-60' }}" wire:key="k-{{ $c->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-stone-900">{{ $c->titulo }} @unless ($c->ativo) <span class="badge bg-stone-100 text-stone-600">desativado</span> @endunless</p>
                                <p class="line-clamp-2 text-xs text-stone-500">{{ $c->conteudo }}</p>
                            </div>
                            <button type="button" wire:click="editarConhecimento('{{ $c->id }}')" class="btn-ghost min-h-[44px] px-3 text-sm">Editar</button>
                            <button type="button" wire:click="alternarConhecimento('{{ $c->id }}')" class="btn-ghost min-h-[44px] px-3 text-sm">{{ $c->ativo ? 'Desativar' : 'Ativar' }}</button>
                            <button type="button" wire:click="excluirConhecimento('{{ $c->id }}')" wire:confirm="Excluir “{{ $c->titulo }}”?" class="btn-ghost min-h-[44px] px-3 text-sm text-red-700">Excluir</button>
                        </div>
                    @empty
                        <x-ui.empty-state titulo="Nada ensinado ainda" texto="Comece pelas dúvidas mais comuns: como funciona a avaliação, cuidados antes e depois, formas de pagamento, endereço e estacionamento." />
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Teste --}}
        <section class="card flex flex-col overflow-hidden lg:sticky lg:top-4 lg:self-start" aria-label="Testar o assistente">
            <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-3">
                <div>
                    <h2 class="text-sm font-semibold text-stone-900">Testar</h2>
                    <p class="text-xs text-stone-500">Converse como se fosse um lead. Nada é enviado nem agendado.</p>
                </div>
                @if ($teste)
                    <button type="button" wire:click="limparTeste" class="btn-ghost min-h-[44px] shrink-0 text-sm">Limpar</button>
                @endif
            </div>
            <div class="max-h-96 min-h-40 space-y-2 overflow-y-auto bg-stone-50/60 px-3 py-3" wire:key="teste-{{ count($teste) }}" x-data x-init="$el.scrollTop = $el.scrollHeight">
                @forelse ($teste as $m)
                    @php $doAssistente = $m['role'] === 'assistant'; @endphp
                    <div class="flex {{ $doAssistente ? 'justify-end' : 'justify-start' }}">
                        <p class="max-w-[85%] whitespace-pre-line break-words rounded-2xl px-3 py-2 text-sm shadow-sm {{ $doAssistente ? 'rounded-br-sm bg-emerald-100 text-emerald-950' : 'rounded-bl-sm bg-surface text-stone-800' }}">{{ $m['content'] }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs text-stone-400">Ex.: “Oi, quanto custa o botox?” ou “Tem horário sexta de manhã?”</p>
                @endforelse
                <div wire:loading.flex wire:target="testar" class="justify-end"><p class="rounded-2xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800">digitando…</p></div>
            </div>
            <form wire:submit="testar" class="flex items-end gap-2 border-t border-stone-100 p-2">
                <textarea wire:model="perguntaTeste" rows="2" maxlength="1000" class="input min-h-11 flex-1 resize-none" aria-label="Mensagem de teste" placeholder="Escreva como um lead…"
                          x-on:keydown.enter="if (! $event.shiftKey && window.matchMedia('(min-width: 768px)').matches) { $event.preventDefault(); $wire.testar() }"></textarea>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="testar">Enviar</button>
            </form>
            @error('perguntaTeste') <p class="field-error px-3 pb-2">{{ $message }}</p> @enderror
        </section>
    </div>

    {{-- Item de treinamento --}}
    @if ($modalConhecimento)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalConhecimento', false)"></div>
            <form wire:submit="salvarConhecimento" class="relative flex max-h-[92dvh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $conhecimentoId ? 'Editar item' : 'Novo item de treinamento' }}</h2>
                    <button type="button" wire:click="$set('modalConhecimento', false)" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="space-y-3 overflow-y-auto px-5 py-4 sm:px-6">
                    <div>
                        <label for="k-titulo" class="label">Título</label>
                        <input id="k-titulo" type="text" wire:model="titulo" maxlength="120" class="input" placeholder="Ex.: Preenchimento labial">
                        @error('titulo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="k-conteudo" class="label">O que o assistente deve saber</label>
                        <textarea id="k-conteudo" wire:model="conteudo" rows="10" maxlength="8000" class="input"
                                  placeholder="Escreva como explicaria a uma pessoa nova na recepção: o que é, para quem é indicado, quanto dura, cuidados, valores e condições."></textarea>
                        @error('conteudo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('modalConhecimento', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    @endif
</div>
