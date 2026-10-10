@php
    $fuso = config('clinica.fuso_horario');
    $iniciais = fn (string $nome) => mb_strtoupper(collect(explode(' ', trim($nome)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '?';
    $quando = function ($data) use ($fuso) {
        if ($data === null) return null;
        $d = $data->copy()->timezone($fuso);
        return $d->isToday() ? 'hoje ' . $d->format('H:i') : ($d->isTomorrow() ? 'amanhã ' . $d->format('H:i') : $d->format('d/m H:i'));
    };
@endphp
<div>
    <x-ui.page-header titulo="Leads" subtitulo="Quem demonstrou interesse e ainda não é paciente. Responda rápido e acompanhe até fechar.">
        <x-slot:acoes>
            @if ($this->podeRelatorio())
                <a href="{{ route('leads.assistente') }}" wire:navigate class="btn-secondary">Assistente</a>
            @endif
            <button type="button" wire:click="$set('modalLinks', true)" class="btn-secondary">Links do formulário</button>
            @podeEditar<button type="button" wire:click="novo" class="btn-primary">+ Novo lead</button>@endpodeEditar
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

    {{-- Abas --}}
    @if ($this->podeRelatorio())
        <div class="mb-5 flex gap-1 border-b border-stone-200" role="tablist">
            @foreach (['funil' => 'Funil', 'relatorio' => 'Relatório'] as $chave => $titulo)
                <button type="button" wire:click="$set('aba', '{{ $chave }}')" role="tab" aria-selected="{{ $aba === $chave ? 'true' : 'false' }}"
                        class="-mb-px inline-flex min-h-[44px] items-center border-b-2 px-4 text-sm font-medium {{ $aba === $chave ? 'border-rose-600 text-rose-700' : 'border-transparent text-stone-500 hover:text-stone-700' }}">{{ $titulo }}</button>
            @endforeach
        </div>
    @endif

    @if ($aba === 'funil')
        @php $r = $this->resumo; @endphp
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Sem resposta', $r['sem_resposta'], $r['sem_resposta'] ? 'text-red-700' : 'text-stone-900', 'leads novos que ninguém contatou'],
                ['Contato atrasado', $r['atrasados'], $r['atrasados'] ? 'text-amber-700' : 'text-stone-900', 'próximo contato já passou'],
                ['Em negociação', $r['abertos'], 'text-stone-900', 'leads em aberto'],
                ['Viraram pacientes', $r['fechados_mes'], 'text-emerald-700', 'neste mês'],
            ] as [$rotulo, $valor, $cor, $hint])
                <div class="card p-4">
                    <p class="text-sm text-stone-500">{{ $rotulo }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums {{ $cor }}">{{ $valor }}</p>
                    <p class="text-xs text-stone-400">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar nome, telefone ou e-mail" class="input sm:max-w-xs" aria-label="Buscar">
            <select wire:model.live="filtroOrigem" class="input sm:w-auto" aria-label="Origem">
                <option value="">Todas as origens</option>
                @foreach ($origens as $o) <option value="{{ $o->value }}">{{ $o->label() }}</option> @endforeach
            </select>
            <select wire:model.live="filtroResponsavel" class="input sm:w-auto" aria-label="Responsável">
                <option value="">Todos os responsáveis</option>
                <option value="nenhum">Sem responsável</option>
                @foreach ($this->equipe as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
            </select>
            <label class="flex min-h-11 items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" wire:model.live="soAtencao" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300"> Só os que precisam de atenção
            </label>
        </div>

        {{-- Celular e tablet: escolhe a etapa e vê a lista --}}
        <div class="lg:hidden">
            <select wire:model.live="etapaCelular" class="input mb-3" aria-label="Etapa">
                @foreach ($etapas as $e) <option value="{{ $e->value }}">{{ $e->label() }} ({{ $this->colunas[$e->value]->count() }})</option> @endforeach
            </select>
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse ($this->colunas[$etapaCelular] ?? [] as $l)
                    @include('livewire.leads.cartao', ['l' => $l])
                @empty
                    <p class="card p-4 text-sm text-stone-500 sm:col-span-2">Nenhum lead nesta etapa.</p>
                @endforelse
            </div>
        </div>

        {{-- Computador: etapas em aberto lado a lado (cabem na tela) e as encerradas embaixo; arrastar e soltar entre elas --}}
        <div class="hidden space-y-4 lg:block" x-data="{ arrastando: null, sobre: null }">
            @foreach ([['abertas', \App\Enums\EtapaLead::abertas(), 'grid-cols-4'], ['encerradas', \App\Enums\EtapaLead::encerradas(), 'grid-cols-3']] as [$grupo, $lista, $grade])
                @if ($grupo === 'encerradas')
                    <h2 class="px-1 text-sm font-semibold text-stone-700">Encerrados <span class="font-normal text-stone-400">· últimos 30 dias</span></h2>
                @endif
                <div class="grid {{ $grade }} items-start gap-3">
                    @foreach ($lista as $e)
                        @php $leads = $this->colunas[$e->value]; @endphp
                        <section class="flex min-w-0 flex-col rounded-2xl bg-stone-100/70 p-2 dark:bg-stone-800/40" aria-label="{{ $e->label() }}"
                                 x-on:dragover.prevent="sobre = '{{ $e->value }}'" x-on:dragleave="sobre = null"
                                 x-on:drop.prevent="if (arrastando) { $wire.mover(arrastando, '{{ $e->value }}') } arrastando = null; sobre = null"
                                 :class="sobre === '{{ $e->value }}' && 'ring-2 ring-rose-300'">
                            <header class="flex items-center justify-between gap-2 px-2 py-2">
                                <span class="badge {{ $e->badge() }} truncate">{{ $e->label() }}</span>
                                <span class="text-xs tabular-nums text-stone-500">{{ $leads->count() }}{{ $leads->count() >= 50 ? '+' : '' }}</span>
                            </header>
                            <div @class(['flex flex-col gap-2', 'min-h-24' => $grupo === 'abertas', 'min-h-14 max-h-80 overflow-y-auto' => $grupo === 'encerradas'])>
                                @forelse ($leads as $l)
                                    <div draggable="true" x-on:dragstart="arrastando = '{{ $l->id }}'" x-on:dragend="arrastando = null" wire:key="col-{{ $l->id }}">
                                        @include('livewire.leads.cartao', ['l' => $l])
                                    </div>
                                @empty
                                    <p class="px-2 py-3 text-center text-xs text-stone-400">
                                        {{ $e === \App\Enums\EtapaLead::JaPaciente ? 'Arraste aqui quem já tem ficha' : 'Nenhum lead' }}
                                    </p>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if ($aba === 'relatorio' && $relatorio)
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div><label for="r-de" class="label">De</label><input id="r-de" type="date" wire:model.live="de" class="input"></div>
            <div><label for="r-ate" class="label">Até</label><input id="r-ate" type="date" wire:model.live="ate" class="input"></div>
        </div>
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="card p-4"><p class="text-sm text-stone-500">Leads no período</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $relatorio['total'] }}</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Viraram pacientes</p><p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{{ $relatorio['convertidos'] }}</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Taxa de conversão</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format($relatorio['taxa'], 1, ',', '.') }}%</p></div>
            <div class="card p-4"><p class="text-sm text-stone-500">Tempo até o 1º contato</p><p class="mt-1 text-2xl font-semibold tabular-nums">{{ $relatorio['horas_primeiro_contato'] !== null ? number_format($relatorio['horas_primeiro_contato'], 1, ',', '.') . ' h' : '—' }}</p></div>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="card overflow-x-auto p-4">
                <h2 class="mb-2 text-sm font-semibold text-stone-900">Por origem</h2>
                @if ($relatorio['origens'])
                    <table class="w-full text-sm">
                        <thead class="text-left text-stone-500"><tr><th class="py-2 font-medium">Origem</th><th class="py-2 text-right font-medium">Leads</th><th class="py-2 text-right font-medium">Pacientes</th><th class="py-2 text-right font-medium">Conversão</th><th class="py-2 text-right font-medium">Faturou</th></tr></thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($relatorio['origens'] as $o)
                                <tr>
                                    <td class="py-2">{{ $o['origem']->label() }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ $o['total'] }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ $o['convertidos'] }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($o['taxa'], 1, ',', '.') }}%</td>
                                    <td class="py-2 text-right tabular-nums">R$ {{ number_format($o['faturamento'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="hint">"Faturou" soma as receitas pagas dos pacientes depois que viraram pacientes.</p>
                @else
                    <p class="text-sm text-stone-500">Nenhum lead no período.</p>
                @endif
            </section>
            <section class="card p-4">
                <h2 class="mb-2 text-sm font-semibold text-stone-900">Onde estão os leads do período</h2>
                <ul class="space-y-1 text-sm">
                    @foreach ($etapas as $e)
                        <li class="flex justify-between"><span class="badge {{ $e->badge() }}">{{ $e->label() }}</span><span class="tabular-nums">{{ $relatorio['etapas'][$e->value] ?? 0 }}</span></li>
                    @endforeach
                </ul>
                <h2 class="mb-2 mt-4 text-sm font-semibold text-stone-900">Por que perdemos</h2>
                @forelse ($relatorio['perdas'] as $motivo => $qtd)
                    <p class="flex justify-between text-sm"><span>{{ $motivo }}</span><span class="tabular-nums">{{ $qtd }}</span></p>
                @empty
                    <p class="text-sm text-stone-500">Nenhuma perda registrada.</p>
                @endforelse
            </section>
        </div>
    @endif

    {{-- Ficha do lead --}}
    @if ($this->lead && ! $modalForm)
        @php $l = $this->lead; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="fechar"></div>
            <div class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-stone-900">{{ $l->nome }}</h2>
                        <p class="text-sm text-stone-500">
                            {{ $l->origem->label() }} · chegou {{ $l->created_at->timezone($fuso)->format('d/m/Y H:i') }}
                            @if ($l->responsavel) · {{ $l->responsavel->name }} @endif
                        </p>
                    </div>
                    <button type="button" wire:click="fechar" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="space-y-5 overflow-y-auto px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @if ($l->telefone) <span class="text-stone-700">📱 {{ $l->telefone }}</span> @endif
                        @if ($l->email) <span class="text-stone-700">✉️ {{ $l->email }}</span> @endif
                        @if ($l->procedimento || $l->interesse) <span class="text-stone-700">✨ {{ $l->procedimento?->nome ?? $l->interesse }}</span> @endif
                    </div>
                    @if ($l->observacoes) <p class="whitespace-pre-line rounded-xl bg-stone-50 px-3 py-2 text-sm text-stone-700">{{ $l->observacoes }}</p> @endif

                    <div class="flex flex-wrap gap-2">
                        @if ($l->whatsappLink())
                            {{-- Celular abre o app; computador abre o WhatsApp Web direto na conversa --}}
                            <a href="{{ $l->whatsappLink($this->textoWhatsApp($l)) }}" target="_blank" rel="noopener" wire:click="whatsappAberto" class="btn bg-emerald-600 text-white hover:bg-emerald-700 md:hidden"><x-icone.whatsapp class="h-4 w-4" /> Abrir no WhatsApp</a>
                            <a href="{{ $l->whatsappWebLink($this->textoWhatsApp($l)) }}" target="_blank" rel="noopener" wire:click="whatsappAberto" class="btn hidden bg-emerald-600 text-white hover:bg-emerald-700 md:inline-flex"><x-icone.whatsapp class="h-4 w-4" /> Abrir no WhatsApp Web</a>
                        @endif
                        @if ($l->telefone)
                            <a href="tel:+55{{ \App\Support\Telefone::nacional($l->telefone) }}" class="btn-secondary">Ligar</a>
                        @endif
                        <button type="button" wire:click="editar" class="btn-secondary">Editar</button>
                        @if (in_array($l->etapa, [\App\Enums\EtapaLead::Fechado, \App\Enums\EtapaLead::JaPaciente], true) && $l->paciente)
                            <a href="{{ route('pacientes.index', ['q' => $l->paciente->nome]) }}" wire:navigate class="btn-secondary">Paciente: {{ $l->paciente->nome }}</a>
                        @elseif ($l->etapa !== \App\Enums\EtapaLead::Perdido)
                            @podeEditar
                                <button type="button" wire:click="abrirVinculo('{{ $l->id }}')" class="btn-secondary">Já é paciente</button>
                                <button type="button" wire:click="converter" wire:confirm="Converter {{ $l->nome }} em paciente e agendar a avaliação?" class="btn-primary">Converter em paciente</button>
                            @endpodeEditar
                        @endif
                    </div>

                    @if ($l->etapa !== \App\Enums\EtapaLead::Fechado)
                        <div>
                            <label for="etapa-lead" class="label">Etapa</label>
                            <select id="etapa-lead" class="input sm:max-w-xs" x-on:change="$wire.mover(@js($l->id), $event.target.value)">
                                @foreach ($etapas as $e)
                                    @continue($e === \App\Enums\EtapaLead::Fechado)
                                    <option value="{{ $e->value }}" @selected($l->etapa === $e)>{{ $e->label() }}</option>
                                @endforeach
                            </select>
                            @if ($l->motivo_perda) <p class="hint">Motivo da perda: {{ $l->motivo_perda }}</p> @endif
                        </div>
                    @endif

                    @php
                        $temWhatsApp = $l->whatsappLink() !== null || filled($l->whatsapp_lid);
                        $conversa = $temWhatsApp ? $l->interacoes->filter->ehMensagemWhatsApp()->take(50)->reverse() : collect();
                    @endphp
                    @if ($temWhatsApp)
                        <section class="rounded-xl border border-stone-200" aria-label="Conversa no WhatsApp" wire:poll.15s>
                            <div class="flex flex-wrap items-center gap-2 border-b border-stone-100 px-3 py-2">
                                <p class="flex flex-1 items-center gap-2 text-sm font-semibold text-stone-900">
                                    <x-icone.whatsapp class="h-4 w-4 text-emerald-600" /> Conversa no WhatsApp
                                </p>
                                @if ($this->assistenteAtivo && $l->etapa->aberta())
                                    @if ($l->assistente_pausado_em)
                                        <span class="text-xs text-stone-500" title="{{ $l->assistente_motivo }}">🤖 Assistente pausado</span>
                                        <button type="button" wire:click="retomarAssistente" class="btn-ghost min-h-[44px] px-3 text-xs">Retomar</button>
                                    @else
                                        <span class="text-xs text-emerald-700">🤖 Assistente respondendo</span>
                                        <button type="button" wire:click="pausarAssistente" class="btn-ghost min-h-[44px] px-3 text-xs">Pausar</button>
                                    @endif
                                @endif
                            </div>
                            @if ($this->assistenteAtivo && $l->assistente_pausado_em && $l->assistente_motivo)
                                <p class="border-b border-stone-100 bg-amber-50 px-3 py-1.5 text-xs text-amber-800">{{ $l->assistente_motivo }}</p>
                            @endif
                            <div class="max-h-72 space-y-2 overflow-y-auto bg-stone-50/60 px-3 py-3" wire:key="conversa-{{ $l->id }}-{{ $conversa->count() }}" x-data x-init="$el.scrollTop = $el.scrollHeight">
                                @forelse ($conversa as $m)
                                    @php
                                        $doAssistente = $m->tipo === \App\Enums\TipoInteracaoLead::WhatsAppAssistente;
                                        $minha = $doAssistente || $m->tipo === \App\Enums\TipoInteracaoLead::WhatsAppEnviado;
                                    @endphp
                                    <div class="flex {{ $minha ? 'justify-end' : 'justify-start' }}" wire:key="msg-{{ $m->id }}">
                                        <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm {{ $minha ? 'rounded-br-sm bg-emerald-100 text-emerald-950' : 'rounded-bl-sm bg-surface text-stone-800' }}">
                                            <p class="whitespace-pre-line break-words">{{ $m->texto }}</p>
                                            <p class="mt-0.5 text-right text-[11px] {{ $minha ? 'text-emerald-800/70' : 'text-stone-400' }}">
                                                {{ $doAssistente ? '🤖 Assistente · ' : ($minha ? ($m->autor ? explode(' ', $m->autor->name)[0] . ' · ' : 'Celular da clínica · ') : '') }}{{ $quando($m->created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="py-4 text-center text-xs text-stone-400">Nenhuma mensagem ainda. As mensagens trocadas com o WhatsApp da clínica aparecem aqui.</p>
                                @endforelse
                            </div>
                            @if ($this->whatsappConectado)
                                <form wire:submit="enviarWhatsApp" class="flex items-end gap-2 border-t border-stone-100 p-2">
                                    <textarea wire:model="resposta" rows="2" maxlength="2000" class="input min-h-11 flex-1 resize-none" aria-label="Mensagem para {{ $l->nome }}"
                                              placeholder="Escreva a resposta…" x-on:keydown.enter="if (! $event.shiftKey && window.matchMedia('(min-width: 768px)').matches) { $event.preventDefault(); $wire.enviarWhatsApp() }"></textarea>
                                    <button type="submit" class="btn bg-emerald-600 text-white hover:bg-emerald-700" wire:loading.attr="disabled" wire:target="enviarWhatsApp">
                                        <span wire:loading.remove wire:target="enviarWhatsApp">Enviar</span><span wire:loading wire:target="enviarWhatsApp">Enviando…</span>
                                    </button>
                                </form>
                                @error('resposta') <p class="field-error px-3 pb-2">{{ $message }}</p> @enderror
                                @if ($flashErro) <p class="px-3 pb-2 text-sm text-red-700" role="alert">{{ $flashErro }}</p> @endif
                            @else
                                <p class="hint border-t border-stone-100 px-3 py-2">Para responder por aqui, conecte o WhatsApp da clínica em Dados da clínica.</p>
                            @endif
                        </section>
                    @endif

                    @if ($l->etapa->aberta())
                        <form wire:submit="registrarContato" class="space-y-3 rounded-xl border border-stone-200 p-3">
                            <p class="text-sm font-semibold text-stone-900">Registrar contato</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['ligacao' => 'Ligação', 'whatsapp_enviado' => 'WhatsApp', 'nota' => 'Anotação'] as $v => $r)
                                    <label class="relative inline-flex min-h-11 cursor-pointer items-center rounded-xl border px-3.5 text-sm {{ $contatoTipo === $v ? 'border-rose-300 bg-rose-50 text-rose-800' : 'border-stone-200 text-stone-700' }}">
                                        <input type="radio" wire:model.live="contatoTipo" value="{{ $v }}" class="sr-only"> {{ $r }}
                                    </label>
                                @endforeach
                            </div>
                            <textarea wire:model="contatoTexto" rows="2" maxlength="2000" class="input" aria-label="Como foi" placeholder="Ex.: Pediu valores do botox, vai pensar até sexta."></textarea>
                            @error('contatoTexto') <p class="field-error">{{ $message }}</p> @enderror
                            <div class="flex flex-wrap items-end gap-3">
                                <div>
                                    <label for="proximo" class="label">Próximo contato (opcional)</label>
                                    <input id="proximo" type="datetime-local" wire:model="contatoProximo" class="input">
                                </div>
                                <button type="submit" class="btn-primary">Salvar</button>
                            </div>
                            @if ($l->proximo_contato_em)
                                <p class="hint {{ $l->contatoAtrasado() ? 'text-red-600' : '' }}">Próximo contato marcado: {{ $quando($l->proximo_contato_em) }}</p>
                            @endif
                        </form>
                    @endif

                    <div>
                        <p class="mb-2 text-sm font-semibold text-stone-900">Histórico</p>
                        <ol class="space-y-3 border-l border-stone-200 pl-4">
                            @foreach ($l->interacoes as $i)
                                @continue($temWhatsApp && $i->ehMensagemWhatsApp())
                                <li class="relative">
                                    <span class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full bg-rose-400" aria-hidden="true"></span>
                                    <p class="text-xs text-stone-500">{{ $i->tipo->label() }} · {{ $i->created_at->timezone($fuso)->format('d/m/Y H:i') }}{{ $i->autor ? ' · ' . $i->autor->name : '' }}</p>
                                    @if ($i->texto) <p class="whitespace-pre-line text-sm text-stone-800">{{ $i->texto }}</p> @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Novo / editar --}}
    @if ($modalForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalForm', false)"></div>
            <form wire:submit="salvar" class="relative z-10 flex max-h-[96vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">{{ $leadId ? 'Editar lead' : 'Novo lead' }}</h2>
                    <button type="button" wire:click="$set('modalForm', false)" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="grid gap-3 overflow-y-auto px-5 py-4 sm:grid-cols-2 sm:px-6">
                    <div class="sm:col-span-2">
                        <label for="l-nome" class="label">Nome</label>
                        <input id="l-nome" type="text" wire:model="nome" maxlength="150" class="input">
                        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="l-tel" class="label">WhatsApp / telefone</label>
                        <input id="l-tel" type="tel" inputmode="tel" wire:model.live.debounce.600ms="telefone" maxlength="20" class="input" placeholder="(61) 99999-0000">
                        @error('telefone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="l-email" class="label">E-mail</label>
                        <input id="l-email" type="email" inputmode="email" wire:model.live.debounce.600ms="email" maxlength="150" class="input">
                        @error('email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    @if ($this->pacienteExistente)
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 sm:col-span-2">Este contato já é do paciente <strong>{{ $this->pacienteExistente }}</strong>.</p>
                    @endif
                    <div>
                        <label for="l-origem" class="label">Origem</label>
                        <select id="l-origem" wire:model="origem" class="input">
                            @foreach ($origens as $o) <option value="{{ $o->value }}">{{ $o->label() }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="l-resp" class="label">Responsável</label>
                        <select id="l-resp" wire:model="responsavelId" class="input">
                            <option value="">Ninguém</option>
                            @foreach ($this->equipe as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="l-proc" class="label">Procedimento de interesse</label>
                        <select id="l-proc" wire:model="procedimentoId" class="input">
                            <option value="">—</option>
                            @foreach ($this->procedimentos as $p) <option value="{{ $p->id }}">{{ $p->nome }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="l-int" class="label">Ou descreva</label>
                        <input id="l-int" type="text" wire:model="interesse" maxlength="255" class="input" placeholder="Ex.: manchas no rosto">
                    </div>
                    @unless ($leadId)
                        <div class="sm:col-span-2">
                            <label for="l-prox" class="label">Próximo contato (opcional)</label>
                            <input id="l-prox" type="datetime-local" wire:model="proximoContato" class="input sm:max-w-xs">
                        </div>
                    @endunless
                    <div class="sm:col-span-2">
                        <label for="l-obs" class="label">Observações</label>
                        <textarea id="l-obs" wire:model="observacoes" rows="3" maxlength="5000" class="input"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-stone-100 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="$set('modalForm', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Motivo da perda --}}
    @if ($perdendoId)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('perdendoId', null)"></div>
            <form wire:submit="confirmarPerda" class="relative z-10 w-full max-w-md space-y-4 rounded-t-2xl bg-surface p-5 shadow-xl sm:rounded-2xl sm:p-6">
                <h2 class="text-lg font-semibold text-stone-900">Por que não fechou?</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Enums\EtapaLead::motivosPerda() as $m)
                        <label class="relative inline-flex min-h-11 cursor-pointer items-center rounded-xl border px-3.5 text-sm {{ $motivoPerda === $m ? 'border-rose-300 bg-rose-50 text-rose-800' : 'border-stone-200 text-stone-700' }}">
                            <input type="radio" wire:model.live="motivoPerda" value="{{ $m }}" class="sr-only"> {{ $m }}
                        </label>
                    @endforeach
                </div>
                @if ($motivoPerda === 'Outro')
                    <input type="text" wire:model="motivoOutro" maxlength="150" class="input" placeholder="Qual?" aria-label="Outro motivo">
                @endif
                @error('motivoPerda') <p class="field-error">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('perdendoId', null)" class="btn-secondary">Voltar</button>
                    <button type="submit" class="btn-primary">Marcar como perdido</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Já é paciente: escolher a ficha --}}
    @if ($vinculandoId)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('vinculandoId', null)"></div>
            <form wire:submit="confirmarVinculo" class="relative z-10 max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-t-2xl bg-surface p-5 shadow-xl sm:rounded-2xl sm:p-6" role="dialog" aria-modal="true" aria-labelledby="titulo-vinculo">
                <div>
                    <h2 id="titulo-vinculo" class="text-lg font-semibold text-stone-900">Já é paciente</h2>
                    <p class="mt-1 text-sm text-stone-500">Escolha a ficha de quem entrou em contato. O lead sai do funil e não conta como conversão.</p>
                </div>
                @include('livewire.partials.escolhe-paciente', ['idCampo' => 'vinculo-paciente', 'listaNoFluxo' => true])
                @if ($pacienteId !== '')
                    <p class="rounded-xl bg-teal-50 px-3 py-2 text-sm text-teal-800">Ficha escolhida: <strong>{{ $pacienteNome }}</strong>. Novas mensagens deste número não viram lead de novo.</p>
                @endif
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('vinculandoId', null)" class="btn-secondary">Voltar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Links do formulário --}}
    @if ($modalLinks)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('modalLinks', false)"></div>
            <div class="relative z-10 w-full max-w-lg space-y-4 rounded-t-2xl bg-surface p-5 shadow-xl sm:rounded-2xl sm:p-6" role="dialog" aria-modal="true">
                <h2 class="text-lg font-semibold text-stone-900">Links do formulário</h2>
                <p class="text-sm text-stone-500">Quem preencher vira lead aqui, já com a origem certa, e a equipe recebe um aviso no WhatsApp da gestão.</p>
                @foreach (\App\Enums\OrigemLead::doFormulario() as $o)
                    @php $url = $linkBase . ($o === \App\Enums\OrigemLead::Formulario ? '' : '?origem=' . $o->value); @endphp
                    <div x-data="{ copiado: false }">
                        <p class="label">{{ $o === \App\Enums\OrigemLead::Formulario ? 'Geral' : $o->label() }}{{ $o === \App\Enums\OrigemLead::Instagram ? ' (bio e stories)' : '' }}</p>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $url }}" class="input text-xs" aria-label="Link {{ $o->label() }}" onclick="this.select()">
                            <button type="button" class="btn-secondary shrink-0" x-on:click="navigator.clipboard.writeText(@js($url)); copiado = true; setTimeout(() => copiado = false, 2000)" x-text="copiado ? 'Copiado!' : 'Copiar'">Copiar</button>
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-end"><button type="button" wire:click="$set('modalLinks', false)" class="btn-secondary">Fechar</button></div>
            </div>
        </div>
    @endif
</div>
