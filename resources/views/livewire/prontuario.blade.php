@php
    $p = $this->paciente;
    $fuso = config('clinica.fuso_horario');
    $abas = [
        'atendimentos' => 'Atendimentos',
        'evolucoes'   => 'Evoluções',
        'fotos'       => 'Fotos',
        'termos'      => 'Termos',
        'orientacoes' => 'Orientações',
    ];
    $rotuloAtendimento = fn ($a) => $a->inicio_em->format('d/m/Y H:i') . ' · ' . ($a->procedimento?->nome ?? 'Atendimento') . ($a->profissional ? ' · ' . $a->profissional->nome : '');
@endphp

<div>
    <a href="{{ route('pacientes.index') }}" wire:navigate
       class="mb-3 inline-flex min-h-11 items-center gap-1.5 text-sm text-stone-500 hover:text-stone-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Pacientes
    </a>

    <x-ui.page-header titulo="Prontuário" :subtitulo="$p->nome . ($p->data_nascimento ? ' · ' . $p->data_nascimento->age . ' anos' : '')">
        <x-slot:acoes>
            @if ($aba === 'atendimentos' && ! $p->anonimizado())
                <button wire:click="novoAtendimento" wire:loading.attr="disabled" class="btn-primary">+ Novo atendimento</button>
            @elseif ($aba === 'termos')
                <button wire:click="abrirTermo" class="btn-primary">+ Novo termo</button>
            @elseif ($aba === 'orientacoes')
                <button wire:click="abrirOrientacao" class="btn-primary">+ Novas orientações</button>
            @endif
        </x-slot:acoes>
    </x-ui.page-header>

    @if ($p->anonimizado())
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Paciente anonimizado. O prontuário fica guardado pelo prazo legal, mas não recebe novos registros.
        </div>
    @endif

    @if ($p->anamnese)
        <details class="card mb-4 px-4 py-3">
            <summary class="flex min-h-11 cursor-pointer items-center text-sm font-medium text-stone-700">Anamnese</summary>
            <p class="pb-2 text-sm whitespace-pre-line text-stone-600">{{ $p->anamnese }}</p>
        </details>
    @endif

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    {{-- Abas --}}
    <div class="mb-5 -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
        <nav class="flex w-max gap-1 rounded-xl bg-stone-100 p-1 sm:w-auto sm:inline-flex" aria-label="Seções do prontuário">
            @foreach ($abas as $chave => $rotulo)
                <button type="button" wire:click="$set('aba', '{{ $chave }}')"
                        @class([
                            'min-h-11 rounded-lg px-4 text-sm font-medium transition-colors whitespace-nowrap',
                            'bg-surface text-stone-900 shadow-sm' => $aba === $chave,
                            'text-stone-500 hover:text-stone-800' => $aba !== $chave,
                        ])>
                    {{ $rotulo }}
                    <span class="ml-1 text-xs text-stone-400 tabular-nums">{{ $this->contagens[$chave] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══════════════ ATENDIMENTOS ═══════════════ --}}
    @if ($aba === 'atendimentos')
        @if ($this->registrosAtendimento->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhum atendimento registrado"
                    texto="Inicie pela agenda (detalhe do horário) ou aqui, com “Novo atendimento”. Fichas, fotos, injetáveis e plano ficam juntos."
                    icone="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->registrosAtendimento as $at)
                    @php $preenchidas = $at->fichas->filter->preenchida(); @endphp
                    <article class="card p-4 sm:p-5" wire:key="at-{{ $at->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900">
                                    {{ $at->iniciado_em->timezone($fuso)->format('d/m/Y H:i') }}
                                    @if ($at->agendamento?->procedimento) · {{ $at->agendamento->procedimento->nome }} @endif
                                </p>
                                <p class="mt-0.5 text-sm text-stone-500">
                                    {{ $at->profissional?->nome ?? $at->autor?->name ?? '—' }}
                                    @if ($at->duracaoTexto()) · {{ $at->duracaoTexto() }} @endif
                                    · 🔒 {{ $at->visibilidade->label() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge {{ $at->emAndamento() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $at->status->label() }}</span>
                                <a href="{{ route('atendimentos.show', $at->id) }}" wire:navigate class="btn-secondary min-h-10 px-3 text-sm">{{ $at->emAndamento() ? 'Continuar' : 'Abrir' }}</a>
                            </div>
                        </div>
                        @if (! $at->emAndamento())
                            @foreach ($preenchidas as $ficha)
                                <details class="mt-3 rounded-xl bg-stone-50 px-4 py-2">
                                    <summary class="flex min-h-11 cursor-pointer items-center text-sm font-medium text-stone-700">{{ $ficha->titulo }}</summary>
                                    <div class="pb-3">@include('livewire.atendimento.respostas', ['ficha' => $ficha])</div>
                                </details>
                            @endforeach
                            @if ($at->injetaveis->isNotEmpty())
                                <p class="mt-3 text-sm text-stone-600"><span class="font-medium text-stone-700">Injetáveis:</span>
                                    {{ $at->injetaveis->map(fn ($i) => $i->produto?->name . ' ' . rtrim(rtrim(number_format((float) $i->quantidade, 3, ',', '.'), '0'), ',') . ' ' . $i->produto?->unit_type->abbreviation() . ($i->regiao ? ' (' . $i->regiao . ')' : '') . ($i->lotes_baixados ? ' · lote ' . $i->lotes_baixados : ''))->implode('; ') }}
                                </p>
                            @endif
                            @if ($at->anexos->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-x-4">
                                    @foreach ($at->anexos as $anexo)
                                        <a href="{{ route('prontuario.anexo', $anexo->id) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-rose-700 hover:text-rose-800">
                                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                            {{ $anexo->nome }} <span class="text-xs font-normal text-stone-400">PDF · {{ $anexo->tamanhoTexto() }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            @if ($at->plano?->itens?->isNotEmpty())
                                <p class="mt-2 text-sm text-stone-600"><span class="font-medium text-stone-700">Plano:</span>
                                    {{ $at->plano->itens->map(fn ($i) => $i->descricao . ' × ' . $i->sessoes)->implode(', ') }}
                                </p>
                            @endif
                        @endif
                    </article>
                @endforeach
            </div>
            @if ($this->registrosAtendimento->count() >= $limite)
                <div class="mt-4 text-center"><button type="button" wire:click="verMais" class="btn-secondary">Ver mais</button></div>
            @endif
        @endif
    @endif

    {{-- ═══════════════ EVOLUÇÕES ═══════════════ --}}
    @if ($aba === 'evolucoes')
        @unless ($p->anonimizado())
            <form wire:submit="registrarEvolucao" class="card mb-5 space-y-4 p-4 sm:p-5">
                <div>
                    <label for="evolucaoTexto" class="label">Nova evolução</label>
                    <textarea id="evolucaoTexto" wire:model="evolucaoTexto" rows="4" class="input"
                              placeholder="Ex.: Aplicação de toxina em glabela, 20U. Paciente sem intercorrências. Retorno em 15 dias."></textarea>
                    @error('evolucaoTexto') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label for="evolucaoAgendamentoId" class="label">Atendimento (opcional)</label>
                        <select id="evolucaoAgendamentoId" wire:model="evolucaoAgendamentoId" class="input">
                            <option value="">Sem atendimento ligado</option>
                            @foreach ($this->atendimentos as $a)
                                <option value="{{ $a->id }}">{{ $rotuloAtendimento($a) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="registrarEvolucao">
                        <span wire:loading.remove wire:target="registrarEvolucao">Registrar evolução</span>
                        <span wire:loading wire:target="registrarEvolucao">Salvando...</span>
                    </button>
                </div>
                <p class="hint">Depois de registrada, a evolução não pode ser alterada nem apagada. Para corrigir, registre uma nova.</p>
            </form>
        @endunless

        @if ($this->evolucoes->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhuma evolução ainda"
                    texto="Registre aqui como foi cada atendimento: o que foi feito, produtos, doses e próximos passos."
                    icone="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </div>
        @else
            <ol class="relative space-y-4 border-l border-stone-200 pl-5 sm:ml-2">
                @foreach ($this->evolucoes as $ev)
                    <li class="relative" wire:key="ev-{{ $ev->id }}">
                        <span class="absolute -left-[27px] top-5 h-3 w-3 rounded-full border-2 border-rose-500 bg-surface" aria-hidden="true"></span>
                        <article class="card p-4 sm:p-5">
                            <header class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-stone-500">
                                <time class="font-medium text-stone-700" datetime="{{ $ev->created_at->toIso8601String() }}">
                                    {{ $ev->created_at->timezone($fuso)->format('d/m/Y \à\s H:i') }}
                                </time>
                                <span>· {{ $ev->profissional?->nome ?? $ev->autor?->name ?? 'Usuário removido' }}</span>
                                @if ($ev->agendamento)
                                    <span class="badge bg-rose-50 text-rose-700">
                                        {{ $ev->agendamento->procedimento?->nome ?? 'Atendimento' }} · {{ $ev->agendamento->inicio_em->format('d/m') }}
                                    </span>
                                @endif
                            </header>
                            <p class="text-sm whitespace-pre-line text-stone-800">{{ $ev->texto }}</p>
                        </article>
                    </li>
                @endforeach
            </ol>
            @if ($this->evolucoes->count() >= $limite)
                <div class="mt-4 text-center"><button wire:click="verMais" class="btn-ghost">Ver mais antigas</button></div>
            @endif
        @endif
    @endif

    {{-- ═══════════════ FOTOS ═══════════════ --}}
    @if ($aba === 'fotos')
        @if ($this->anexos->isNotEmpty())
            <div class="card mb-5 p-4 sm:p-5">
                <h2 class="text-sm font-semibold text-stone-900">Anexos (PDF)</h2>
                <ul class="mt-2 divide-y divide-stone-100">
                    @foreach ($this->anexos as $anexo)
                        <li class="flex flex-wrap items-center justify-between gap-2" wire:key="pa-{{ $anexo->id }}">
                            <a href="{{ route('prontuario.anexo', $anexo->id) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-rose-700 hover:text-rose-800">
                                <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                {{ $anexo->nome }} <span class="text-xs font-normal text-stone-400">PDF · {{ $anexo->tamanhoTexto() }}</span>
                            </a>
                            <span class="text-xs text-stone-400 tabular-nums">{{ $anexo->created_at->timezone($fuso)->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @unless ($p->anonimizado())
            <form wire:submit="adicionarFotos" class="card mb-5 space-y-4 p-4 sm:p-5">
                <div>
                    <label class="label" for="fotos">Adicionar fotos</label>
                    <label for="fotos"
                           class="flex min-h-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-stone-200 px-4 py-5 text-center text-sm text-stone-500 hover:border-rose-300 hover:bg-rose-50">
                        <svg class="h-7 w-7 text-rose-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                        <span wire:loading.remove wire:target="fotos">
                            @if (count($fotos))
                                <strong class="text-stone-800">{{ count($fotos) }} {{ count($fotos) === 1 ? 'foto escolhida' : 'fotos escolhidas' }}</strong> · toque para trocar
                            @else
                                Tire uma foto ou escolha da galeria (até 10, 10 MB cada)
                            @endif
                        </span>
                        <span wire:loading wire:target="fotos">Carregando fotos...</span>
                    </label>
                    <input id="fotos" type="file" wire:model="fotos" accept="image/*" multiple class="sr-only">
                    @error('fotos') <p class="field-error">{{ $message }}</p> @enderror
                    @error('fotos.*') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <fieldset>
                        <legend class="label">Momento</legend>
                        <div class="flex gap-1 rounded-xl bg-stone-100 p-1">
                            @foreach ($momentos as $m)
                                <label @class([
                                    'flex min-h-10 flex-1 cursor-pointer items-center justify-center rounded-lg px-2 text-sm font-medium',
                                    'bg-surface text-stone-900 shadow-sm' => $fotoMomento === $m->value,
                                    'text-stone-500' => $fotoMomento !== $m->value,
                                ])>
                                    <input type="radio" wire:model.live="fotoMomento" value="{{ $m->value }}" class="sr-only">
                                    {{ $m === \App\Enums\MomentoFoto::Acompanhamento ? 'Acompanh.' : $m->label() }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div>
                        <label for="fotoTiradaEm" class="label">Data da foto</label>
                        <input id="fotoTiradaEm" type="date" wire:model="fotoTiradaEm" max="{{ now()->toDateString() }}" class="input">
                        @error('fotoTiradaEm') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="fotoRegiao" class="label">Região (opcional)</label>
                        <input id="fotoRegiao" type="text" wire:model="fotoRegiao" maxlength="80" class="input" placeholder="Ex.: Testa">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="fotoDescricao" class="label">Observação (opcional)</label>
                        <input id="fotoDescricao" type="text" wire:model="fotoDescricao" maxlength="255" class="input" placeholder="Ex.: Frontal, sem maquiagem">
                    </div>
                    <div>
                        <label for="fotoAgendamentoId" class="label">Atendimento (opcional)</label>
                        <select id="fotoAgendamentoId" wire:model="fotoAgendamentoId" class="input">
                            <option value="">Sem atendimento ligado</option>
                            @foreach ($this->atendimentos as $a)
                                <option value="{{ $a->id }}">{{ $rotuloAtendimento($a) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="adicionarFotos,fotos">
                        <span wire:loading.remove wire:target="adicionarFotos">Guardar fotos</span>
                        <span wire:loading wire:target="adicionarFotos">Guardando...</span>
                    </button>
                </div>
            </form>
        @endunless

        @if ($this->fotosSalvas->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhuma foto ainda"
                    texto="Guarde fotos de antes e depois para acompanhar a evolução do tratamento. Elas ficam privadas, só no prontuário."
                    icone="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </div>
        @else
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm text-stone-500">
                    @if (count($comparar) === 0)
                        Marque duas fotos para comparar lado a lado.
                    @elseif (count($comparar) === 1)
                        Marque mais uma foto para comparar.
                    @else
                        Duas fotos marcadas.
                    @endif
                </p>
                <button wire:click="abrirComparacao" class="btn-secondary" @disabled(count($comparar) !== 2)>Comparar</button>
            </div>

            <div x-data="{ aberta: null }">
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($this->fotosSalvas as $foto)
                        @php $marcada = in_array($foto->id, $comparar, true); @endphp
                        <li wire:key="foto-{{ $foto->id }}" @class(['card overflow-hidden', 'ring-2 ring-rose-500' => $marcada])>
                            <button type="button" class="block aspect-square w-full bg-stone-100"
                                    @click="aberta = { url: '{{ route('prontuario.foto', $foto->id) }}', id: '{{ $foto->id }}', legenda: @js($foto->momento->label() . ' · ' . $foto->tirada_em->format('d/m/Y') . ($foto->regiao ? ' · ' . $foto->regiao : '')) }">
                                <img src="{{ route('prontuario.foto', [$foto->id, 'mini']) }}" alt="Foto {{ $foto->momento->label() }} de {{ $foto->tirada_em->format('d/m/Y') }}"
                                     loading="lazy" class="h-full w-full object-cover">
                            </button>
                            <div class="flex items-start gap-2 p-2.5">
                                <div class="min-w-0 flex-1">
                                    <span class="badge {{ $foto->momento->badge() }}">{{ $foto->momento->label() }}</span>
                                    <p class="mt-1 truncate text-xs text-stone-500">
                                        {{ $foto->tirada_em->format('d/m/Y') }}@if ($foto->regiao) · {{ $foto->regiao }}@endif
                                    </p>
                                    @if ($foto->descricao)
                                        <p class="truncate text-xs text-stone-400" title="{{ $foto->descricao }}">{{ $foto->descricao }}</p>
                                    @endif
                                </div>
                                <label class="-m-2 flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center" title="Marcar para comparar">
                                    <input type="checkbox" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-500"
                                           @checked($marcada) wire:click="alternarComparacao('{{ $foto->id }}')">
                                    <span class="sr-only">Comparar esta foto</span>
                                </label>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Foto ampliada --}}
                <template x-if="aberta">
                    <div class="fixed inset-0 z-50 flex flex-col bg-black/90" @keydown.escape.window="aberta = null">
                        <div class="flex items-center justify-between gap-2 px-4 py-2 text-white">
                            <p class="truncate text-sm" x-text="aberta.legenda"></p>
                            <div class="flex shrink-0 items-center gap-1">
                                @if ($ehAdmin && ! $p->anonimizado())
                                    <button type="button" class="min-h-11 rounded-lg px-3 text-sm text-red-300 hover:bg-white/10"
                                            @click="if (confirm('Remover esta foto do prontuário? Essa ação não pode ser desfeita.')) { $wire.removerFoto(aberta.id); aberta = null }">
                                        Remover
                                    </button>
                                @endif
                                <a :href="aberta.url" target="_blank" rel="noopener" class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-white/10">Abrir original</a>
                                <button type="button" @click="aberta = null" aria-label="Fechar"
                                        class="flex h-11 w-11 items-center justify-center rounded-lg hover:bg-white/10">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-1 items-center justify-center p-2" @click.self="aberta = null">
                            <img :src="aberta.url" alt="" class="max-h-full max-w-full object-contain">
                        </div>
                    </div>
                </template>
            </div>

            @if ($this->fotosSalvas->count() >= $limite)
                <div class="mt-4 text-center"><button wire:click="verMais" class="btn-ghost">Ver mais fotos</button></div>
            @endif
        @endif

        {{-- Comparação lado a lado --}}
        @if ($modalComparar && $this->fotosComparadas->count() === 2)
            <div class="fixed inset-0 z-50 flex flex-col bg-black/90" x-data @keydown.escape.window="$wire.set('modalComparar', false)">
                <div class="flex items-center justify-between px-4 py-2 text-white">
                    <p class="text-sm font-medium">Comparação</p>
                    <button type="button" wire:click="$set('modalComparar', false)" aria-label="Fechar"
                            class="flex h-11 w-11 items-center justify-center rounded-lg hover:bg-white/10">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="grid flex-1 grid-rows-2 gap-2 overflow-hidden p-2 md:grid-cols-2 md:grid-rows-1">
                    @foreach ($this->fotosComparadas as $foto)
                        <figure class="flex min-h-0 flex-col">
                            <div class="flex min-h-0 flex-1 items-center justify-center">
                                <img src="{{ route('prontuario.foto', $foto->id) }}" alt="" class="max-h-full max-w-full object-contain">
                            </div>
                            <figcaption class="pt-1 text-center text-sm text-white">
                                {{ $foto->momento->label() }} · {{ $foto->tirada_em->format('d/m/Y') }}@if ($foto->regiao) · {{ $foto->regiao }}@endif
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- ═══════════════ TERMOS ═══════════════ --}}
    @if ($aba === 'termos')
        @if ($this->termos->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhum termo assinado"
                    texto="O paciente lê o termo e assina com o dedo na tela. O termo vira PDF e fica guardado aqui."
                    icone="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10">
                    @unless ($p->anonimizado())
                        <button wire:click="abrirTermo" class="btn-primary">Colher assinatura</button>
                    @endunless
                </x-ui.empty-state>
            </div>
        @else
            <div class="card divide-y divide-stone-100">
                @foreach ($this->termos as $termo)
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="termo-{{ $termo->id }}">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $termo->titulo }}</p>
                            <p class="text-sm text-stone-500">
                                Assinado por {{ $termo->assinante_nome }} em {{ $termo->assinado_em->timezone($fuso)->format('d/m/Y \à\s H:i') }}
                                · colhido por {{ $termo->autor?->name ?? 'usuário removido' }}
                            </p>
                            <p class="mt-0.5 truncate font-mono text-[11px] text-stone-400" title="Código de verificação">{{ $termo->hash }}</p>
                        </div>
                        <a href="{{ route('prontuario.termo.pdf', $termo->id) }}" target="_blank" rel="noopener" class="btn-secondary shrink-0">Abrir PDF</a>
                    </div>
                @endforeach
            </div>
            @if ($this->termos->count() >= $limite)
                <div class="mt-4 text-center"><button wire:click="verMais" class="btn-ghost">Ver mais</button></div>
            @endif
        @endif
    @endif

    {{-- ═══════════════ ORIENTAÇÕES ═══════════════ --}}
    @if ($aba === 'orientacoes')
        @if ($this->orientacoes->isEmpty())
            <div class="card">
                <x-ui.empty-state titulo="Nenhuma orientação ainda"
                    texto="Entregue cuidados pós-procedimento e prescrições em PDF ou direto no WhatsApp do paciente."
                    icone="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z">
                    @unless ($p->anonimizado())
                        <button wire:click="abrirOrientacao" class="btn-primary">Escrever orientações</button>
                    @endunless
                </x-ui.empty-state>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->orientacoes as $o)
                    <article class="card p-4 sm:p-5" wire:key="ori-{{ $o->id }}" x-data="{ aberto: false }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-medium text-stone-900">{{ $o->titulo }}</p>
                                <p class="text-sm text-stone-500">
                                    {{ $o->created_at->timezone($fuso)->format('d/m/Y') }} · {{ $o->autor?->name ?? 'Usuário removido' }}
                                    @if ($o->enviada_whatsapp_em)
                                        <span class="badge ml-1 bg-emerald-50 text-emerald-700">Enviada no WhatsApp</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <button type="button" class="btn-ghost" @click="aberto = !aberto" x-text="aberto ? 'Esconder' : 'Ver texto'"></button>
                                <a href="{{ route('prontuario.orientacao.pdf', $o->id) }}" target="_blank" rel="noopener" class="btn-secondary">PDF</a>
                                @if ($p->telefone && ! $p->anonimizado())
                                    <button type="button" class="btn-secondary" wire:click="enviarOrientacao('{{ $o->id }}')"
                                            wire:loading.attr="disabled" wire:target="enviarOrientacao('{{ $o->id }}')">
                                        {{ $o->enviada_whatsapp_em ? 'Reenviar' : 'Enviar' }} no WhatsApp
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p x-show="aberto" x-cloak class="mt-3 border-t border-stone-100 pt-3 text-sm whitespace-pre-line text-stone-700">{{ $o->texto }}</p>
                    </article>
                @endforeach
            </div>
            @if ($this->orientacoes->count() >= $limite)
                <div class="mt-4 text-center"><button wire:click="verMais" class="btn-ghost">Ver mais</button></div>
            @endif
        @endif
    @endif

    {{-- ═══════════════ MODAL: NOVO TERMO ═══════════════ --}}
    @if ($modalTermo)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalTermo', false)"></div>
            <div class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl"
                 x-data="assinaturaTermo()">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">Termo de consentimento</h2>
                        <p class="text-sm text-stone-500">Escolha o modelo, revise o texto e entregue a tela ao paciente para ler e assinar.</p>
                    </div>
                    <button wire:click="$set('modalTermo', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="termoModeloId" class="label">Modelo</label>
                            <select id="termoModeloId" wire:model.live="termoModeloId" class="input">
                                <option value="">Escolha um modelo</option>
                                @foreach ($this->modelos->where('tipo', \App\Enums\TipoModeloProntuario::Termo) as $m)
                                    <option value="{{ $m->id }}">{{ $m->titulo }}</option>
                                @endforeach
                            </select>
                            @if ($this->modelos->where('tipo', \App\Enums\TipoModeloProntuario::Termo)->isEmpty())
                                <p class="hint">Nenhum modelo ainda. <a href="{{ route('prontuario.modelos') }}" class="text-rose-600 underline">Cadastre ou use os modelos prontos</a>.</p>
                            @endif
                        </div>
                        <div>
                            <label for="termoAgendamentoId" class="label">Atendimento (opcional)</label>
                            <select id="termoAgendamentoId" wire:model.live="termoAgendamentoId" class="input">
                                <option value="">Sem atendimento ligado</option>
                                @foreach ($this->atendimentos as $a)
                                    <option value="{{ $a->id }}">{{ $rotuloAtendimento($a) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="termoTitulo" class="label">Título</label>
                        <input id="termoTitulo" type="text" wire:model="termoTitulo" maxlength="150" class="input" placeholder="Ex.: Termo de consentimento para toxina botulínica">
                        @error('termoTitulo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="termoConteudo" class="label">Texto do termo</label>
                        <textarea id="termoConteudo" wire:model="termoConteudo" rows="10" class="input text-sm leading-relaxed"></textarea>
                        @error('termoConteudo') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="termoAssinante" class="label">Nome de quem assina</label>
                        <input id="termoAssinante" type="text" wire:model="termoAssinante" maxlength="150" class="input" autocomplete="off">
                        <p class="hint">Se for o responsável legal, escreva o nome dele.</p>
                        @error('termoAssinante') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div wire:ignore>
                        <div class="mb-1.5 flex items-center justify-between">
                            <span class="label mb-0">Assinatura</span>
                            <button type="button" class="btn-ghost min-h-9 px-3 text-sm" @click="limpar()">Limpar</button>
                        </div>
                        <canvas x-ref="quadro" class="h-44 w-full touch-none rounded-xl border-2 border-dashed border-stone-300 bg-white"
                                @pointerdown="iniciar($event)" @pointermove="desenhar($event)" @pointerup="parar()" @pointerleave="parar()"
                                aria-label="Quadro para assinar com o dedo ou o mouse"></canvas>
                        <p class="hint">Assine com o dedo ou com o mouse dentro do quadro.</p>
                    </div>
                    <p class="field-error" x-show="semAssinatura" x-cloak>Peça para o paciente assinar no quadro antes de salvar.</p>
                    @error('assinatura') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="$set('modalTermo', false)" class="btn-secondary">Cancelar</button>
                    <button type="button" class="btn-primary" @click="salvar()" wire:loading.attr="disabled" wire:target="assinarTermo">
                        <span wire:loading.remove wire:target="assinarTermo">Salvar termo assinado</span>
                        <span wire:loading wire:target="assinarTermo">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════ MODAL: NOVAS ORIENTAÇÕES ═══════════════ --}}
    @if ($modalOrientacao)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalOrientacao', false)"></div>
            <div class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Orientações ao paciente</h2>
                    <button wire:click="$set('modalOrientacao', false)" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="salvarOrientacao" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="orientacaoModeloId" class="label">Modelo</label>
                                <select id="orientacaoModeloId" wire:model.live="orientacaoModeloId" class="input">
                                    <option value="">Escrever do zero</option>
                                    @foreach ($this->modelos->where('tipo', \App\Enums\TipoModeloProntuario::Orientacao) as $m)
                                        <option value="{{ $m->id }}">{{ $m->titulo }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="orientacaoAgendamentoId" class="label">Atendimento (opcional)</label>
                                <select id="orientacaoAgendamentoId" wire:model.live="orientacaoAgendamentoId" class="input">
                                    <option value="">Sem atendimento ligado</option>
                                    @foreach ($this->atendimentos as $a)
                                        <option value="{{ $a->id }}">{{ $rotuloAtendimento($a) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="orientacaoTitulo" class="label">Título</label>
                            <input id="orientacaoTitulo" type="text" wire:model="orientacaoTitulo" maxlength="150" class="input" placeholder="Ex.: Cuidados após o peeling">
                            @error('orientacaoTitulo') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="orientacaoTexto" class="label">Orientações</label>
                            <textarea id="orientacaoTexto" wire:model="orientacaoTexto" rows="10" class="input text-sm leading-relaxed"
                                      placeholder="Ex.: Evite sol por 7 dias e use protetor FPS 50."></textarea>
                            @error('orientacaoTexto') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        @if ($p->telefone)
                            <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                                <input type="checkbox" wire:model="orientacaoWhatsapp" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-500">
                                Enviar também no WhatsApp do paciente ({{ $p->telefone }})
                            </label>
                        @else
                            <p class="hint">O paciente não tem telefone cadastrado, então as orientações não vão por WhatsApp. Você pode abrir o PDF e imprimir.</p>
                        @endif
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="$set('modalOrientacao', false)" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvarOrientacao">
                            <span wire:loading.remove wire:target="salvarOrientacao">Salvar orientações</span>
                            <span wire:loading wire:target="salvarOrientacao">Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Quadro de assinatura: desenha com dedo/mouse e envia o PNG ao salvar
    Alpine.data('assinaturaTermo', () => ({
        ctx: null,
        desenhando: false,
        assinou: false,
        semAssinatura: false,

        init() {
            this.$nextTick(() => this.preparar());
        },

        preparar() {
            const c = this.$refs.quadro;
            const escala = window.devicePixelRatio || 1;
            c.width = c.offsetWidth * escala;
            c.height = c.offsetHeight * escala;
            this.ctx = c.getContext('2d');
            this.ctx.scale(escala, escala);
            this.ctx.lineWidth = 2.2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.strokeStyle = '#1c1917'; // tinta escura também no modo escuro: vai para o PDF em fundo branco
        },

        ponto(e) {
            const r = this.$refs.quadro.getBoundingClientRect();
            return { x: e.clientX - r.left, y: e.clientY - r.top };
        },

        iniciar(e) {
            this.desenhando = true;
            this.$refs.quadro.setPointerCapture?.(e.pointerId);
            const p = this.ponto(e);
            this.ctx.beginPath();
            this.ctx.moveTo(p.x, p.y);
        },

        desenhar(e) {
            if (!this.desenhando) return;
            const p = this.ponto(e);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
            this.assinou = true;
            this.semAssinatura = false;
        },

        parar() {
            this.desenhando = false;
        },

        limpar() {
            const c = this.$refs.quadro;
            this.ctx.clearRect(0, 0, c.width, c.height);
            this.assinou = false;
        },

        salvar() {
            if (!this.assinou) {
                this.semAssinatura = true;
                return;
            }
            $wire.assinarTermo(this.$refs.quadro.toDataURL('image/png'));
        },
    }));
</script>
@endscript
