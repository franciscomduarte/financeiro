@php $brl = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.'); @endphp
<div @if ($resumo['processando'] && ! $modalEmitir && ! $cancelandoId) wire:poll.15s.visible @endif>
    <x-ui.page-header titulo="Notas fiscais" subtitulo="Emita a NFS-e das receitas e acompanhe a autorização da prefeitura.">
        <x-slot:acoes>
            <button wire:click="abrirEmissao" class="btn-primary" @disabled($pendencias)>+ Emitir nota</button>
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

    @if ($pendencias)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Para emitir notas, complete em
            @if (auth()->user()->pode(\App\Enums\Modulo::DadosClinica))
                <a href="{{ route('admin.clinica') }}" wire:navigate class="font-medium underline underline-offset-2">Dados da clínica › Nota fiscal</a>:
            @else
                Dados da clínica › Nota fiscal (peça a um administrador):
            @endif
            {{ implode(', ', $pendencias) }}.
        </div>
    @elseif ($homologacao)
        <div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
            Ambiente de testes (homologação): as notas não têm valor fiscal.
        </div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Emitido no mês</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">{{ $brl($resumo['emitidas_mes']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Notas no mês</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">{{ $resumo['qtd_mes'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Processando</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-stone-900">{{ $resumo['processando'] }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Com erro (30 dias)</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $resumo['erros'] ? 'text-red-700' : 'text-stone-900' }}">{{ $resumo['erros'] }}</p>
        </div>
    </div>

    <div class="mb-4">
        <select wire:model.live="filtroStatus" class="input sm:max-w-[12rem]" aria-label="Situação">
            <option value="">Todas as situações</option>
            @foreach ($status as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    @if ($notas->isEmpty())
        <div class="card">
            @if ($filtroStatus !== '')
                <x-ui.empty-state titulo="Nada encontrado com esse filtro" texto="Escolha outra situação." />
            @else
                <x-ui.empty-state titulo="Nenhuma nota emitida ainda"
                    texto="Emita a NFS-e de uma receita aqui ou pelo detalhe do lançamento. A nota vai por e-mail ao paciente quando ele tem e-mail cadastrado."
                    icone="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z">
                    @unless ($pendencias)
                        <button wire:click="abrirEmissao" class="btn-primary">Emitir nota</button>
                    @endunless
                </x-ui.empty-state>
            @endif
        </div>
    @else
        <div class="card divide-y divide-stone-100">
            @foreach ($notas as $n)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="nf-{{ $n->id }}">
                    <div class="min-w-0">
                        <p class="font-medium text-stone-900">
                            {{ $n->tomador_nome }}
                            <span class="ml-1 tabular-nums text-stone-500">{{ $brl($n->valor) }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-stone-500">
                            <span class="badge {{ $n->status->badge() }}">{{ $n->status->label() }}</span>
                            @if ($n->numero) · nº {{ $n->numero }} @endif
                            · {{ $n->created_at->timezone(config('clinica.fuso_horario'))->format('d/m/Y H:i') }}
                            @if ($n->homologacao) · <span class="text-xs">teste</span> @endif
                        </p>
                        @if ($n->status === \App\Enums\StatusNotaFiscal::Erro && $n->mensagem_erro)
                            <p class="mt-1 text-sm text-red-700">{{ $n->mensagem_erro }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($n->status === \App\Enums\StatusNotaFiscal::Autorizada)
                            @if ($n->url) <a href="{{ $n->url }}" target="_blank" rel="noopener" class="btn-secondary min-h-10 px-3 text-sm">Ver nota</a> @endif
                            @if ($n->url_xml) <a href="{{ $n->url_xml }}" target="_blank" rel="noopener" class="btn-ghost min-h-10 px-3 text-sm">XML</a> @endif
                            <button type="button" wire:click="abrirCancelamento('{{ $n->id }}')" class="btn-ghost min-h-10 px-3 text-sm text-red-600">Cancelar</button>
                        @elseif ($n->status === \App\Enums\StatusNotaFiscal::Processando)
                            <button type="button" wire:click="atualizar('{{ $n->id }}')" wire:loading.attr="disabled" wire:target="atualizar" class="btn-secondary min-h-10 px-3 text-sm">Atualizar situação</button>
                        @elseif ($n->status === \App\Enums\StatusNotaFiscal::Erro)
                            <button type="button" wire:click="reemitir('{{ $n->transacao_id }}')" class="btn-secondary min-h-10 px-3 text-sm">Corrigir e emitir de novo</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $notas->links() }}</div>
    @endif

    {{-- Emitir --}}
    @if ($modalEmitir)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalEmitir', false)"></div>
            <form wire:submit="emitir" class="relative z-10 flex max-h-[96vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Emitir nota fiscal</h2>
                    <button type="button" wire:click="$set('modalEmitir', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    @if ($this->receitaEscolhida)
                        <div class="flex items-start justify-between gap-3 rounded-xl bg-stone-50 px-4 py-3">
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-stone-900">{{ $this->receitaEscolhida->descricao }}</p>
                                <p class="text-stone-500 tabular-nums">{{ $this->receitaEscolhida->data_competencia->format('d/m/Y') }} · {{ $brl($this->receitaEscolhida->valor_bruto) }}</p>
                            </div>
                            <button type="button" wire:click="$set('transacaoId', '')" class="btn-ghost min-h-9 px-2 text-sm">Trocar</button>
                        </div>
                    @else
                        <div>
                            <label for="nf-busca" class="label">Receita</label>
                            <input id="nf-busca" type="search" wire:model.live.debounce.300ms="buscaReceita" class="input" placeholder="Busque pela descrição ou cliente">
                            @error('transacaoId') <p class="field-error">{{ $message }}</p> @enderror
                            <ul class="mt-2 max-h-64 divide-y divide-stone-100 overflow-y-auto rounded-xl border border-stone-200">
                                @forelse ($this->receitas as $r)
                                    <li>
                                        <button type="button" wire:click="escolherReceita('{{ $r->id }}')" class="flex min-h-11 w-full items-center justify-between gap-3 px-4 py-2 text-left text-sm hover:bg-stone-50">
                                            <span class="min-w-0">
                                                <span class="block truncate text-stone-800">{{ $r->descricao }}</span>
                                                <span class="text-xs text-stone-500">{{ $r->cliente }} · {{ $r->data_competencia->format('d/m/Y') }}</span>
                                            </span>
                                            <span class="shrink-0 tabular-nums text-stone-700">{{ $brl($r->valor_bruto) }}</span>
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-4 py-6 text-center text-sm text-stone-500">Nenhuma receita sem nota nos últimos 4 meses.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endif

                    @if ($transacaoId !== '')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="nf-nome" class="label">Tomador (quem recebe a nota)</label>
                                <input id="nf-nome" type="text" wire:model="tomadorNome" maxlength="150" class="input" placeholder="Ex.: Maria Silva">
                                @error('tomadorNome') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="nf-cpf" class="label">CPF ou CNPJ</label>
                                <input id="nf-cpf" type="text" inputmode="numeric" wire:model="tomadorCpf" maxlength="18" class="input tabular-nums" placeholder="Ex.: 123.456.789-00">
                                <p class="hint">Opcional em muitas cidades, mas recomendado.</p>
                            </div>
                            <div>
                                <label for="nf-email" class="label">E-mail (recebe a nota)</label>
                                <input id="nf-email" type="email" wire:model="tomadorEmail" maxlength="150" class="input" placeholder="Ex.: maria@email.com">
                                @error('tomadorEmail') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="nf-desc" class="label">Descrição do serviço</label>
                                <textarea id="nf-desc" wire:model="discriminacao" rows="4" maxlength="2000" class="input"></textarea>
                                @error('discriminacao') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('modalEmitir', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="emitir" @disabled($transacaoId === '')>
                        <span wire:loading.remove wire:target="emitir">Emitir nota</span>
                        <span wire:loading wire:target="emitir">Enviando...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Cancelar --}}
    @if ($cancelandoId)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('cancelandoId', null)"></div>
            <form wire:submit="cancelar" class="relative z-10 w-full max-w-md overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Cancelar nota fiscal</h2>
                    <p class="text-sm text-stone-500">O cancelamento vai para a prefeitura e não tem volta.</p>
                </div>
                <div class="px-5 py-5 sm:px-6">
                    <label for="nf-just" class="label">Motivo</label>
                    <textarea id="nf-just" wire:model="justificativa" rows="3" maxlength="255" class="input" placeholder="Ex.: Nota emitida com valor errado; será emitida outra."></textarea>
                    @error('justificativa') <p class="field-error">{{ $message }}</p> @enderror
                    <p class="hint">Pelo menos 15 caracteres.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="$set('cancelandoId', null)" class="btn-secondary">Voltar</button>
                    <button type="submit" class="btn-danger" wire:loading.attr="disabled" wire:target="cancelar">Cancelar nota</button>
                </div>
            </form>
        </div>
    @endif
</div>
