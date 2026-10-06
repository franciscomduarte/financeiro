@php
    $a        = $this->atendimento;
    $editavel = $this->editavel;
    $fuso     = config('clinica.fuso_horario');
    $brl      = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $secoes   = $this->secoes;
@endphp
<div>
    <a href="{{ route('pacientes.prontuario', $a->paciente_id) }}" wire:navigate
       class="mb-3 inline-flex min-h-11 items-center gap-1.5 text-sm text-stone-500 hover:text-stone-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Prontuário
    </a>

    <x-ui.page-header titulo="Atendimento"
        :subtitulo="$a->paciente->nome . ($a->paciente->data_nascimento ? ' · ' . $a->paciente->data_nascimento->age . ' anos' : '') . ($a->agendamento ? ' · ' . ($a->agendamento->procedimento?->nome ?? 'Agendamento') . ' às ' . $a->agendamento->inicio_em->format('H:i') : '')">
        <x-slot:acoes>
            <span class="badge {{ $a->emAndamento() ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $a->status->label() }}</span>
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

    @if (! $a->emAndamento())
        <div class="mb-4 rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-600">
            Finalizado em {{ $a->finalizado_em->timezone($fuso)->format('d/m/Y H:i') }}{{ $a->duracaoTexto() ? ' · duração ' . $a->duracaoTexto() : '' }}.
            O registro não muda mais; para corrigir, registre uma evolução no prontuário.
        </div>
    @elseif (! $editavel)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Só quem iniciou o atendimento pode preenchê-lo.</div>
    @endif

    <div class="grid gap-4 md:grid-cols-[14rem_minmax(0,1fr)] lg:grid-cols-[16rem_minmax(0,1fr)]">
        {{-- Seções --}}
        <div class="md:hidden">
            <label for="secao-cel" class="sr-only">Seção</label>
            <select id="secao-cel" class="input" x-on:change="$wire.irPara($event.target.value)">
                @foreach ($secoes as $chave => $rotulo)
                    <option value="{{ $chave }}" @selected($atual === $chave)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </div>
        <nav class="card hidden self-start p-2 md:sticky md:top-4 md:block" aria-label="Seções do atendimento">
            @foreach ($secoes as $chave => $rotulo)
                <button type="button" wire:click="irPara('{{ $chave }}')"
                        @class(['flex min-h-11 w-full items-center rounded-xl px-3 text-left text-sm font-medium transition-colors',
                                'bg-rose-600 text-white' => $atual === $chave, 'text-stone-700 hover:bg-stone-100' => $atual !== $chave])>
                    {{ $rotulo }}
                </button>
            @endforeach
        </nav>

        <div class="min-w-0">
            {{-- ═══════════ Ficha ═══════════ --}}
            @if (str_starts_with($atual, 'ficha:'))
                @php $ficha = $this->fichas->firstWhere('id', substr($atual, 6)); @endphp
                <div class="card divide-y divide-stone-100" wire:key="ficha-{{ $ficha['id'] }}">
                    @foreach ($ficha['campos'] as $campo)
                        @php
                            $tipo  = \App\Enums\TipoCampoFicha::from($campo['tipo']);
                            $valor = $respostas[$ficha['id']][$campo['id']] ?? ($tipo === \App\Enums\TipoCampoFicha::Multipla ? [] : '');
                            $modelo = "respostas.{$ficha['id']}.{$campo['id']}";
                            $idHtml = 'c-' . $campo['id'];
                        @endphp
                        <div class="p-4 sm:p-5" wire:key="campo-{{ $ficha['id'] }}-{{ $campo['id'] }}">
                            @if ($tipo === \App\Enums\TipoCampoFicha::Titulo)
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">{{ $campo['rotulo'] }}</h3>
                            @elseif (! $editavel)
                                <p class="text-sm font-medium text-stone-500">{{ $campo['rotulo'] }}</p>
                                <div class="mt-1 text-stone-800">
                                    @if ($tipo === \App\Enums\TipoCampoFicha::TextoRico)
                                        @if (filled($valor)) <div class="texto-rico">{!! \App\Support\HtmlSeguro::limpar($valor) !!}</div> @else <span class="text-stone-400">—</span> @endif
                                    @elseif ($tipo === \App\Enums\TipoCampoFicha::Multipla)
                                        {{ $valor ? implode(', ', $valor) : '—' }}
                                    @elseif ($tipo === \App\Enums\TipoCampoFicha::SimNao)
                                        {{ ['sim' => 'Sim', 'nao' => 'Não'][$valor] ?? '—' }}
                                    @elseif ($tipo === \App\Enums\TipoCampoFicha::Data)
                                        {{ $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : '—' }}
                                    @else
                                        {{ filled($valor) ? $valor : '—' }}
                                    @endif
                                </div>
                            @elseif ($tipo === \App\Enums\TipoCampoFicha::TextoRico)
                                <p class="mb-2 text-base font-semibold text-stone-900">{{ $campo['rotulo'] }}</p>
                                @include('livewire.atendimento.editor-rico', ['modeloId' => $ficha['id'], 'campoId' => $campo['id'], 'valor' => (string) $valor, 'rotulo' => $campo['rotulo']])
                            @elseif ($tipo === \App\Enums\TipoCampoFicha::Texto)
                                <label for="{{ $idHtml }}" class="label">{{ $campo['rotulo'] }}</label>
                                <input id="{{ $idHtml }}" type="text" maxlength="1000" wire:model.live.debounce.800ms="{{ $modelo }}" class="input">
                            @elseif ($tipo === \App\Enums\TipoCampoFicha::Numero)
                                <label for="{{ $idHtml }}" class="label">{{ $campo['rotulo'] }}</label>
                                <input id="{{ $idHtml }}" type="text" inputmode="decimal" wire:model.live.debounce.800ms="{{ $modelo }}" class="input max-w-[12rem] tabular-nums">
                            @elseif ($tipo === \App\Enums\TipoCampoFicha::Data)
                                <label for="{{ $idHtml }}" class="label">{{ $campo['rotulo'] }}</label>
                                <input id="{{ $idHtml }}" type="date" wire:model.live="{{ $modelo }}" class="input max-w-[12rem]">
                            @else
                                <fieldset>
                                    <legend class="label">{{ $campo['rotulo'] }}</legend>
                                    <div class="flex flex-wrap gap-2">
                                        @php
                                            $opcoes = $tipo === \App\Enums\TipoCampoFicha::SimNao ? ['sim' => 'Sim', 'nao' => 'Não'] : array_combine($campo['opcoes'], $campo['opcoes']);
                                            $multipla = $tipo === \App\Enums\TipoCampoFicha::Multipla;
                                        @endphp
                                        @foreach ($opcoes as $valorOpcao => $rotuloOpcao)
                                            @php $marcada = $multipla ? in_array($valorOpcao, (array) $valor, true) : $valor === $valorOpcao; @endphp
                                            <label @class(['relative inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border px-3.5 text-sm transition-colors',
                                                           'border-rose-300 bg-rose-50 text-rose-800' => $marcada, 'border-stone-200 text-stone-700 hover:bg-stone-50' => ! $marcada])>
                                                <input type="{{ $multipla ? 'checkbox' : 'radio' }}" value="{{ $valorOpcao }}" wire:model.live="{{ $modelo }}" class="sr-only">
                                                {{ $rotuloOpcao }}
                                            </label>
                                        @endforeach
                                        @if (! $multipla && filled($valor))
                                            <button type="button" wire:click="$set('{{ $modelo }}', '')" class="btn-ghost min-h-11 px-3 text-sm">Limpar</button>
                                        @endif
                                    </div>
                                </fieldset>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ═══════════ Fotos ═══════════ --}}
            @if ($atual === 'fotos')
                <div class="card space-y-4 p-4 sm:p-5">
                    @if ($editavel)
                        <form wire:submit="adicionarFotos" class="space-y-3">
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="sm:col-span-3">
                                    <label for="at-fotos" class="label">Fotos (até 10 por vez)</label>
                                    <input id="at-fotos" type="file" multiple accept="image/jpeg,image/png,image/webp" capture="environment" wire:model="fotos" class="input">
                                    @error('fotos') <p class="field-error">{{ $message }}</p> @enderror
                                    @error('fotos.*') <p class="field-error">{{ $message }}</p> @enderror
                                    <p wire:loading wire:target="fotos" class="hint">Carregando fotos…</p>
                                </div>
                                <div>
                                    <label for="at-momento" class="label">Momento</label>
                                    <select id="at-momento" wire:model="fotoMomento" class="input">
                                        @foreach (\App\Enums\MomentoFoto::cases() as $m) <option value="{{ $m->value }}">{{ $m->label() }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="at-regiao" class="label">Região (opcional)</label>
                                    <input id="at-regiao" type="text" maxlength="80" wire:model="fotoRegiao" class="input" placeholder="Ex.: rosto, abdômen">
                                </div>
                            </div>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="fotos,adicionarFotos">Salvar fotos</button>
                        </form>
                    @endif
                    @if ($this->fotosDoAtendimento->isEmpty())
                        <p class="text-sm text-stone-500">Nenhuma foto neste atendimento.</p>
                    @else
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                            @foreach ($this->fotosDoAtendimento as $f)
                                <a href="{{ route('prontuario.foto', $f->id) }}" target="_blank" rel="noopener" class="group relative block aspect-square overflow-hidden rounded-xl bg-stone-100">
                                    <img src="{{ route('prontuario.foto', [$f->id, 'mini']) }}" alt="Foto {{ $f->momento->label() }}" loading="lazy" class="h-full w-full object-cover">
                                    <span class="absolute bottom-1 left-1 rounded-md bg-black/60 px-1.5 py-0.5 text-[11px] text-white">{{ $f->momento->label() }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <p class="hint">As fotos ficam no prontuário do paciente, na aba Fotos.</p>
                </div>
            @endif

            {{-- ═══════════ Injetáveis ═══════════ --}}
            @if ($atual === 'injetaveis')
                <div class="card space-y-4 p-4 sm:p-5">
                    @if ($editavel)
                        <form wire:submit="adicionarInjetavel" class="grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="inj-produto" class="label">Produto do estoque</label>
                                <select id="inj-produto" wire:model.live="injProduto" class="input">
                                    <option value="">Escolha…</option>
                                    @foreach ($this->produtos as $p) <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit_type->abbreviation() }})</option> @endforeach
                                </select>
                                @error('injProduto') <p class="field-error">{{ $message }}</p> @enderror
                                @if ($this->produtos->isEmpty()) <p class="hint">Cadastre os produtos em Estoque › Produtos.</p> @endif
                            </div>
                            <div>
                                <label for="inj-lote" class="label">Lote</label>
                                <select id="inj-lote" wire:model="injLote" class="input" @disabled($injProduto === '')>
                                    <option value="">Automático (vence primeiro)</option>
                                    @foreach ($this->lotes as $l)
                                        <option value="{{ $l->id }}">{{ $l->lot_number }} · vence {{ $l->expires_at?->format('d/m/Y') }} · {{ rtrim(rtrim(number_format((float) $l->quantity_available, 3, ',', '.'), '0'), ',') }} disp.</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="inj-qtd" class="label">Quantidade aplicada</label>
                                <input id="inj-qtd" type="text" inputmode="decimal" wire:model="injQuantidade" class="input tabular-nums" placeholder="Ex.: 20">
                                @error('injQuantidade') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="inj-regiao" class="label">Região</label>
                                <input id="inj-regiao" type="text" maxlength="120" wire:model="injRegiao" class="input" placeholder="Ex.: glabela, testa">
                            </div>
                            <div>
                                <label for="inj-obs" class="label">Observação</label>
                                <input id="inj-obs" type="text" maxlength="255" wire:model="injObservacao" class="input">
                            </div>
                            <div class="sm:col-span-2"><button type="submit" class="btn-primary">Adicionar</button></div>
                        </form>
                    @endif

                    @if ($this->injetaveis->isEmpty())
                        <p class="text-sm text-stone-500">Nenhum produto aplicado.</p>
                    @else
                        <ul class="divide-y divide-stone-100 rounded-xl border border-stone-100">
                            @foreach ($this->injetaveis as $i)
                                <li class="flex items-start justify-between gap-3 p-3" wire:key="inj-{{ $i->id }}">
                                    <div class="min-w-0 text-sm">
                                        <p class="font-medium text-stone-900">{{ $i->produto?->name }} · <span class="tabular-nums">{{ rtrim(rtrim(number_format((float) $i->quantidade, 3, ',', '.'), '0'), ',') }} {{ $i->produto?->unit_type->abbreviation() }}</span></p>
                                        <p class="text-stone-500">
                                            {{ collect([$i->regiao, $i->lote ? 'lote ' . $i->lote->lot_number : null, $i->lotes_baixados ? 'baixa: ' . $i->lotes_baixados : null, $i->observacao])->filter()->implode(' · ') ?: 'Lote automático' }}
                                        </p>
                                    </div>
                                    @if ($editavel)
                                        <button type="button" wire:click="removerInjetavel('{{ $i->id }}')" class="btn-ghost min-h-10 px-3 text-sm text-red-600">Remover</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($a->emAndamento()) <p class="hint">A baixa no estoque acontece quando você finaliza o atendimento.</p> @endif
                    @endif
                </div>
            @endif

            {{-- ═══════════ Orçamento ═══════════ --}}
            @if ($atual === 'orcamento')
                <div class="card space-y-4 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-stone-600">Orçamentos recentes do paciente.</p>
                        @if (auth()->user()->pode(\App\Enums\Modulo::Cobrancas))
                            <a href="{{ route('orcamentos.index', ['paciente' => $a->paciente_id]) }}" target="_blank" rel="noopener" class="btn-secondary">+ Novo orçamento</a>
                        @endif
                    </div>
                    @forelse ($this->orcamentos as $o)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-stone-100 p-3 text-sm">
                            <span>Nº {{ $o->numero }} · {{ $o->created_at->timezone($fuso)->format('d/m/Y') }}</span>
                            <span class="flex items-center gap-2"><span class="tabular-nums">{{ $brl($o->total) }}</span> <span class="badge bg-stone-100 text-stone-600">{{ $o->status->label() }}</span></span>
                        </div>
                    @empty
                        <p class="text-sm text-stone-500">Nenhum orçamento ainda. Monte o plano de tratamento e gere o orçamento por lá.</p>
                    @endforelse
                </div>
            @endif

            {{-- ═══════════ Plano de tratamento ═══════════ --}}
            @if ($atual === 'plano')
                @php $plano = $this->plano; $travado = $plano?->orcamento_id !== null; @endphp
                <div class="card space-y-4 p-4 sm:p-5">
                    @if ($travado)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            Este plano virou o orçamento nº {{ $plano->orcamento?->numero }} ({{ $plano->orcamento?->status->label() }}).
                        </div>
                    @endif

                    @if ($editavel && ! $travado)
                        @foreach ($planoItens as $i => $item)
                            <div class="grid gap-3 rounded-xl border border-stone-100 p-3 sm:grid-cols-12" wire:key="plano-{{ $i }}">
                                <div class="sm:col-span-5">
                                    <label class="label" for="pl-proc-{{ $i }}">Procedimento</label>
                                    <select id="pl-proc-{{ $i }}" wire:model.live="planoItens.{{ $i }}.procedimento_id" class="input">
                                        <option value="">Outro (escrever)</option>
                                        @foreach ($this->procedimentos as $p) <option value="{{ $p->id }}">{{ $p->nome }}</option> @endforeach
                                    </select>
                                    <input type="text" maxlength="150" wire:model="planoItens.{{ $i }}.descricao" class="input mt-2" aria-label="Descrição" placeholder="Descrição">
                                    @error("planoItens.$i.descricao") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label" for="pl-ses-{{ $i }}">Sessões</label>
                                    <input id="pl-ses-{{ $i }}" type="number" min="1" max="100" wire:model="planoItens.{{ $i }}.sessoes" class="input tabular-nums">
                                    @error("planoItens.$i.sessoes") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label" for="pl-int-{{ $i }}">A cada (dias)</label>
                                    <input id="pl-int-{{ $i }}" type="number" min="1" max="365" wire:model="planoItens.{{ $i }}.intervalo_dias" class="input tabular-nums">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label" for="pl-val-{{ $i }}">Valor/sessão</label>
                                    <input id="pl-val-{{ $i }}" type="text" inputmode="decimal" wire:model="planoItens.{{ $i }}.valor_unitario" class="input tabular-nums">
                                    @error("planoItens.$i.valor_unitario") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end sm:col-span-1">
                                    <button type="button" wire:click="removerItemPlano({{ $i }})" class="btn-ghost w-full px-2 text-red-600" aria-label="Remover item">✕</button>
                                </div>
                            </div>
                        @endforeach
                        <button type="button" wire:click="adicionarItemPlano" class="btn-secondary">+ Procedimento</button>
                        <div>
                            <label for="pl-obs" class="label">Observações</label>
                            <textarea id="pl-obs" rows="3" maxlength="2000" wire:model="planoObservacoes" class="input"></textarea>
                        </div>
                        @php $totalPlano = collect($planoItens)->sum(fn ($it) => max(1, (int) $it['sessoes']) * (float) str_replace(',', '.', (string) $it['valor_unitario'])); @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4">
                            <p class="text-sm text-stone-600">Total: <strong class="tabular-nums text-stone-900">{{ $brl($totalPlano) }}</strong></p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="salvarPlano" class="btn-secondary">Salvar plano</button>
                                @if (auth()->user()->pode(\App\Enums\Modulo::Cobrancas) || auth()->user()->role === \App\Enums\RoleUsuario::Profissional)
                                    <button type="button" wire:click="gerarOrcamento" wire:confirm="Criar um orçamento com estes itens?" class="btn-primary" @disabled($planoItens === [])>Gerar orçamento</button>
                                @endif
                            </div>
                        </div>
                    @elseif ($plano && $plano->itens->isNotEmpty())
                        <ul class="divide-y divide-stone-100 text-sm">
                            @foreach ($plano->itens as $it)
                                <li class="flex justify-between gap-3 py-2">
                                    <span>{{ $it->descricao }} · {{ $it->sessoes }} {{ $it->sessoes === 1 ? 'sessão' : 'sessões' }}{{ $it->intervalo_dias ? ', a cada ' . $it->intervalo_dias . ' dias' : '' }}</span>
                                    <span class="tabular-nums">{{ $brl($it->sessoes * (float) $it->valor_unitario) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-sm">Total: <strong class="tabular-nums">{{ $brl($plano->total()) }}</strong></p>
                        @if ($plano->observacoes) <p class="text-sm whitespace-pre-line text-stone-600">{{ $plano->observacoes }}</p> @endif
                        @if (! $travado && $a->user_id === auth()->id() || (! $travado && auth()->user()->role === \App\Enums\RoleUsuario::Admin))
                            <button type="button" wire:click="gerarOrcamento" wire:confirm="Criar um orçamento com estes itens?" class="btn-primary">Gerar orçamento</button>
                        @endif
                    @else
                        <p class="text-sm text-stone-500">Nenhum plano de tratamento.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Barra inferior: cronômetro, privacidade, cancelar e finalizar --}}
    <div class="sticky bottom-0 z-30 -mx-4 -mb-6 mt-6 border-t lg:-mb-8 border-stone-200 bg-surface/95 backdrop-blur sm:-mx-6 lg:-mx-8">
        <div class="flex flex-wrap items-center gap-2 px-4 py-3 sm:gap-3 sm:px-6 lg:px-8">
            @if ($a->emAndamento())
                <div class="flex items-center gap-2 text-lg font-semibold tabular-nums text-stone-900" aria-label="Tempo de atendimento"
                     x-data="{ inicio: {{ $a->iniciado_em->getTimestampMs() }}, agora: Date.now() }" x-init="setInterval(() => agora = Date.now(), 1000)">
                    <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="(() => { const s = Math.max(0, Math.floor((agora - inicio) / 1000)); return [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map(n => String(n).padStart(2, '0')).join(':'); })()">00:00:00</span>
                </div>
            @else
                <span class="text-sm text-stone-600">Duração: <strong>{{ $a->duracaoTexto() ?? '—' }}</strong></span>
            @endif

            <label class="sr-only" for="visibilidade">Quem vê</label>
            <select id="visibilidade" class="input w-auto min-w-[8.5rem]" @disabled(! ($a->user_id === auth()->id() || auth()->user()->role === \App\Enums\RoleUsuario::Admin))
                    x-on:change="$wire.alterarVisibilidade($event.target.value)" title="{{ $a->visibilidade->descricao() }}">
                @foreach (\App\Enums\VisibilidadeAtendimento::cases() as $v)
                    <option value="{{ $v->value }}" @selected($a->visibilidade === $v)>🔒 {{ $v->label() }}</option>
                @endforeach
            </select>

            @if ($editavel)
                <div class="ml-auto flex gap-2">
                    <button type="button" wire:click="cancelar" wire:confirm="Cancelar o atendimento? As respostas e os injetáveis serão descartados (as fotos ficam no prontuário)." class="btn-ghost">Cancelar</button>
                    <button type="button" wire:click="$set('modalFinalizar', true)" class="btn-primary">Finalizar atendimento</button>
                </div>
            @endif
        </div>
    </div>

    @if ($modalFinalizar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalFinalizar', false)"></div>
            <div class="relative z-10 w-full max-w-md rounded-t-2xl bg-surface p-5 shadow-xl animate-modal-in sm:rounded-2xl sm:p-6" role="dialog" aria-modal="true">
                <h2 class="text-lg font-semibold text-stone-900">Finalizar atendimento?</h2>
                <ul class="mt-3 space-y-1 text-sm text-stone-600">
                    <li>• O registro vai para o prontuário e não poderá mais ser editado.</li>
                    @if ($this->injetaveis->isNotEmpty())
                        <li>• Baixa no estoque de {{ $this->injetaveis->count() }} {{ $this->injetaveis->count() === 1 ? 'produto' : 'produtos' }}.</li>
                    @endif
                    @if ($a->agendamento?->status?->isPendente())
                        <li>• Em seguida você conclui o agendamento (receita ou pacote).</li>
                    @endif
                </ul>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="$set('modalFinalizar', false)" class="btn-secondary">Voltar</button>
                    <button type="button" wire:click="finalizar" wire:loading.attr="disabled" class="btn-primary">Finalizar</button>
                </div>
            </div>
        </div>
    @endif
</div>
