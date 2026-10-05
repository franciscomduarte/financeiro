@php
    $fuso = config('clinica.fuso_horario');
    $iniciais = fn (string $nome) => mb_strtoupper(collect(explode(' ', trim($nome)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '?';
    $setaOrdem = fn (string $campo) => ltrim($ordem, '-') === $campo ? (str_starts_with($ordem, '-') ? '↓' : '↑') : '';
    $idsPagina = $lista ? $lista->pluck('id')->all() : [];
    $paginaToda = $idsPagina !== [] && array_diff($idsPagina, $selecionados) === [];
@endphp
<div>
    <x-ui.page-header titulo="Notificações" subtitulo="Tudo o que a clínica enviou aos pacientes por WhatsApp e e-mail, e os lembretes que ainda vão sair." />

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
    <div class="mb-5 flex gap-1 overflow-x-auto border-b border-stone-200" role="tablist">
        @foreach (array_filter(['historico' => 'Histórico', 'agendadas' => 'Agendadas', 'configurar' => $this->podeConfigurar() ? 'Configurar avisos' : null]) as $chave => $titulo)
            <button type="button" wire:click="trocarAba('{{ $chave }}')" role="tab" aria-selected="{{ $aba === $chave ? 'true' : 'false' }}"
                    class="-mb-px inline-flex min-h-[44px] shrink-0 items-center gap-2 border-b-2 px-4 text-sm font-medium transition-colors {{ $aba === $chave ? 'border-rose-600 text-rose-700' : 'border-transparent text-stone-500 hover:text-stone-700' }}">
                {{ $titulo }}
                @if ($chave === 'agendadas' && $qtdAgendadas)
                    <span class="badge bg-sky-50 text-sky-700 tabular-nums">{{ $qtdAgendadas }}</span>
                @endif
            </button>
        @endforeach
    </div>

    @if ($aba !== 'configurar')
        {{-- Busca, filtros e ações em lote --}}
        <div class="mb-4 space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="flex flex-1 gap-2">
                    <input type="search" wire:model.live.debounce.400ms="busca" placeholder="Buscar por paciente ou assunto" class="input flex-1" aria-label="Buscar">
                    <button type="button" wire:click="$toggle('mostrarFiltros')" class="btn-secondary shrink-0" aria-expanded="{{ $mostrarFiltros ? 'true' : 'false' }}">
                        Filtros @if ($this->filtrosAtivos) <span class="badge bg-rose-50 text-rose-700">{{ $this->filtrosAtivos }}</span> @endif
                    </button>
                </div>
                @if ($selecionados)
                    <div class="flex flex-wrap items-center gap-2" x-data="{ aberto: false }">
                        <span class="text-sm text-stone-500 tabular-nums">{{ count($selecionados) }} selecionada(s)</span>
                        @if ($aba === 'agendadas')
                            <button type="button" wire:click="emLote('enviarAgora')" wire:loading.attr="disabled" class="btn-secondary">Enviar agora</button>
                            <button type="button" wire:click="emLote('cancelar')" wire:confirm="Cancelar as notificações selecionadas? Elas não serão enviadas." wire:loading.attr="disabled" class="btn-ghost text-red-600">Cancelar envio</button>
                        @else
                            <button type="button" wire:click="emLote('reenviar')" wire:confirm="Reenviar as notificações selecionadas?" wire:loading.attr="disabled" class="btn-secondary">Reenviar</button>
                        @endif
                        <button type="button" wire:click="$set('selecionados', [])" class="btn-ghost">Limpar</button>
                    </div>
                @endif
            </div>

            @if ($mostrarFiltros)
                <div class="card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="f-canal" class="label">Canal</label>
                        <select id="f-canal" wire:model.live="filtroCanal" class="input">
                            <option value="">Todos</option>
                            @foreach ($canais as $c) <option value="{{ $c->value }}">{{ $c->label() }}</option> @endforeach
                        </select>
                    </div>
                    @if ($aba === 'historico')
                        <div>
                            <label for="f-status" class="label">Situação</label>
                            <select id="f-status" wire:model.live="filtroStatus" class="input">
                                <option value="">Todas</option>
                                @foreach ($statusHistorico as $s) <option value="{{ $s->value }}">{{ $s->label() }}</option> @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label for="f-tipo" class="label">Tipo</label>
                        <select id="f-tipo" wire:model.live="filtroTipo" class="input">
                            <option value="">Todos</option>
                            @foreach ($tipos as $t) <option value="{{ $t->value }}">{{ $t->label() }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="f-de" class="label">De</label>
                        <input id="f-de" type="date" wire:model.live="de" class="input">
                    </div>
                    <div>
                        <label for="f-ate" class="label">Até</label>
                        <input id="f-ate" type="date" wire:model.live="ate" class="input">
                    </div>
                    <div class="flex items-end sm:col-span-2 lg:col-span-5">
                        <button type="button" wire:click="limparFiltros" class="btn-ghost">Limpar filtros</button>
                    </div>
                </div>
            @endif
        </div>

        <p class="mb-2 text-sm text-stone-500 tabular-nums">{{ $lista->total() }} {{ $lista->total() === 1 ? 'registro' : 'registros' }}</p>

        @if ($lista->isEmpty())
            <div class="card">
                @if ($busca !== '' || $this->filtrosAtivos)
                    <x-ui.empty-state titulo="Nada encontrado" texto="Mude a busca ou os filtros." />
                @elseif ($aba === 'agendadas')
                    <x-ui.empty-state titulo="Nenhum lembrete na fila"
                        texto="Os lembretes de consulta aparecem aqui assim que um horário é marcado na agenda."
                        icone="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <x-ui.empty-state titulo="Nenhuma notificação enviada ainda"
                        texto="Confirmações, lembretes, cobranças e mensagens de relacionamento enviadas aos pacientes aparecem aqui."
                        icone="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                @endif
            </div>
        @else
            {{-- Celular: cartões --}}
            <div class="card divide-y divide-stone-100 md:hidden">
                @foreach ($lista as $n)
                    <div class="flex gap-3 p-4" wire:key="m-{{ $n->id }}">
                        <input type="checkbox" value="{{ $n->id }}" wire:model.live="selecionados" class="mt-1 h-5 w-5 shrink-0 rounded border-stone-300 text-rose-600 focus:ring-rose-300" aria-label="Selecionar">
                        <button type="button" wire:click="$set('verId', '{{ $n->id }}')" class="min-w-0 flex-1 text-left">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-medium text-stone-900">{{ $n->destinatario_nome }}</p>
                                <span class="badge shrink-0 {{ $n->status->badge() }}">{{ $n->status->label() }}</span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-stone-600">{{ $n->assunto }}</p>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-stone-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $n->canal->icone() }}" /></svg>
                                {{ $n->canal->label() }} ·
                                {{ ($aba === 'agendadas' ? $n->agendada_para : $n->created_at)?->timezone($fuso)->format('d/m/Y H:i') }}
                            </p>
                        </button>
                    </div>
                @endforeach
            </div>

            {{-- Computador: tabela --}}
            <div class="card hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-stone-100 text-stone-600">
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <input type="checkbox" @checked($paginaToda) wire:click="alternarPagina(@js($idsPagina))" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300" aria-label="Selecionar a página">
                            </th>
                            @foreach (['data' => $aba === 'agendadas' ? 'Sai em' : 'Data', 'destinatario' => 'Destinatário', 'canal' => 'Canal'] as $campo => $rotulo)
                                <th class="px-3 py-3 font-medium">
                                    <button type="button" wire:click="ordenar('{{ $campo }}')" class="inline-flex min-h-[44px] items-center gap-1 hover:text-stone-900">{{ $rotulo }} <span aria-hidden="true">{{ $setaOrdem($campo) }}</span></button>
                                </th>
                            @endforeach
                            <th class="px-3 py-3 font-medium">Assunto</th>
                            <th class="px-3 py-3 font-medium">
                                <button type="button" wire:click="ordenar('status')" class="inline-flex min-h-[44px] items-center gap-1 hover:text-stone-900">Situação <span aria-hidden="true">{{ $setaOrdem('status') }}</span></button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($lista as $n)
                            <tr class="cursor-pointer hover:bg-stone-50" wire:key="d-{{ $n->id }}" wire:click="$set('verId', '{{ $n->id }}')">
                                <td class="px-4 py-3" wire:click.stop>
                                    <input type="checkbox" value="{{ $n->id }}" wire:model.live="selecionados" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300" aria-label="Selecionar">
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 tabular-nums text-stone-700">{{ ($aba === 'agendadas' ? $n->agendada_para : $n->created_at)?->timezone($fuso)->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-50 text-xs font-semibold text-rose-700">{{ $iniciais($n->destinatario_nome) }}</span>
                                        <span class="max-w-[14rem] truncate text-stone-900">{{ $n->destinatario_nome }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-stone-700">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $n->canal->icone() }}" /></svg>
                                        {{ $n->canal->label() }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate px-3 py-3 text-stone-700" title="{{ $n->assunto }}">{{ $n->assunto }}</td>
                                <td class="px-3 py-3"><span class="badge {{ $n->status->badge() }}">{{ $n->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $lista->links() }}</div>
        @endif
    @else
        {{-- Configurar avisos automáticos --}}
        <p class="mb-4 text-sm text-stone-500">Os avisos da agenda saem sozinhos. Escolha os canais, quando os lembretes saem e o texto de cada um.</p>
        <div class="card divide-y divide-stone-100">
            @foreach ($configuracoes as $c)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="cfg-{{ $c->tipo->value }}">
                    <div class="min-w-0">
                        <p class="font-medium text-stone-900">{{ $c->tipo->label() }}</p>
                        <p class="mt-0.5 text-sm text-stone-500">
                            {{ $c->tipo->descricao() }}
                            @if ($c->tipo->lembrete()) Sai {{ \App\Livewire\NotificacaoIndex::ANTECEDENCIAS[$c->antecedenciaEfetiva()] ?? $c->antecedenciaEfetiva() . ' min' }} antes. @endif
                        </p>
                        <p class="mt-1 flex flex-wrap gap-1.5">
                            <span class="badge {{ $c->whatsapp ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">WhatsApp {{ $c->whatsapp ? 'ligado' : 'desligado' }}</span>
                            <span class="badge {{ $c->email ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">E-mail {{ $c->email ? 'ligado' : 'desligado' }}</span>
                            @if (filled($c->texto) || filled($c->assunto)) <span class="badge bg-violet-50 text-violet-700">Texto personalizado</span> @endif
                        </p>
                    </div>
                    <button type="button" wire:click="editar('{{ $c->tipo->value }}')" class="btn-secondary shrink-0">Editar</button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Detalhe --}}
    @if ($this->detalhe)
        @php $d = $this->detalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('verId', null)"></div>
            <div class="relative z-10 flex max-h-[96vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-stone-900">{{ $d->tipo->label() }}</h2>
                        <p class="text-sm text-stone-500">{{ $d->destinatario_nome }} · {{ $d->canal->label() }} @if ($d->destino) ({{ $d->destino }}) @endif</p>
                    </div>
                    <button type="button" wire:click="$set('verId', null)" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4 text-sm sm:px-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="badge {{ $d->status->badge() }}">{{ $d->status->label() }}</span>
                        @if ($d->agendada_para && $d->status === \App\Enums\StatusNotificacao::Agendada) <span class="text-stone-500">sai em {{ $d->agendada_para->timezone($fuso)->format('d/m/Y H:i') }}</span> @endif
                    </div>
                    <dl class="grid grid-cols-2 gap-3 text-stone-600">
                        <div><dt class="text-xs text-stone-500">Criada</dt><dd class="tabular-nums">{{ $d->created_at->timezone($fuso)->format('d/m/Y H:i') }}</dd></div>
                        @if ($d->enviada_em) <div><dt class="text-xs text-stone-500">Enviada</dt><dd class="tabular-nums">{{ $d->enviada_em->timezone($fuso)->format('d/m/Y H:i') }}</dd></div> @endif
                        @if ($d->entregue_em) <div><dt class="text-xs text-stone-500">Entregue</dt><dd class="tabular-nums">{{ $d->entregue_em->timezone($fuso)->format('d/m/Y H:i') }}</dd></div> @endif
                        @if ($d->lida_em) <div><dt class="text-xs text-stone-500">{{ $d->canal === \App\Enums\CanalNotificacao::Email ? 'Aberta' : 'Lida' }}</dt><dd class="tabular-nums">{{ $d->lida_em->timezone($fuso)->format('d/m/Y H:i') }}</dd></div> @endif
                    </dl>
                    @if ($d->erro)
                        <p class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-800">{{ $d->erro }}</p>
                    @endif
                    <div>
                        <p class="label">{{ $d->assunto }}</p>
                        @if ($d->conteudo)
                            <div class="rounded-xl bg-stone-50 px-4 py-3 leading-relaxed text-stone-800">{{ \App\Mail\NotificacaoMail::formatar($d->conteudo) }}</div>
                        @else
                            <p class="hint">O texto é montado na hora do envio, com os dados mais recentes do agendamento.</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap justify-end gap-2 border-t border-stone-100 px-5 py-4 sm:px-6">
                    @if ($d->status === \App\Enums\StatusNotificacao::Agendada)
                        <button type="button" wire:click="acao('cancelar', '{{ $d->id }}')" wire:confirm="Cancelar esta notificação?" class="btn-ghost text-red-600">Cancelar envio</button>
                        <button type="button" wire:click="acao('enviarAgora', '{{ $d->id }}')" class="btn-primary">Enviar agora</button>
                    @elseif ($d->status !== \App\Enums\StatusNotificacao::Enviando)
                        <button type="button" wire:click="acao('reenviar', '{{ $d->id }}')" wire:confirm="Reenviar esta notificação?" class="btn-primary">Reenviar</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Editar aviso --}}
    @if ($editandoTipo !== '')
        @php $tipoEd = \App\Enums\TipoNotificacao::from($editandoTipo); @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="$set('editandoTipo', '')"></div>
            <form wire:submit="salvarConfiguracao" class="relative z-10 flex max-h-[96vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">{{ $tipoEd->label() }}</h2>
                        <p class="text-sm text-stone-500">{{ $tipoEd->descricao() }}</p>
                    </div>
                    <button type="button" wire:click="$set('editandoTipo', '')" aria-label="Fechar" class="-mr-2 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100">✕</button>
                </div>
                <div class="space-y-4 overflow-y-auto px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                            <input type="checkbox" wire:model="cfgWhatsapp" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300"> Enviar por WhatsApp
                        </label>
                        <label class="flex min-h-11 items-center gap-3 text-sm text-stone-700">
                            <input type="checkbox" wire:model="cfgEmail" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300"> Enviar por e-mail
                        </label>
                    </div>
                    @if ($tipoEd->lembrete())
                        <div>
                            <label for="cfg-antecedencia" class="label">Quando sai</label>
                            <select id="cfg-antecedencia" wire:model="cfgAntecedencia" class="input sm:max-w-xs">
                                @foreach (\App\Livewire\NotificacaoIndex::ANTECEDENCIAS as $min => $rotulo)
                                    <option value="{{ $min }}">{{ $rotulo }} antes</option>
                                @endforeach
                            </select>
                            @error('cfgAntecedencia') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <label for="cfg-assunto" class="label">Assunto do e-mail</label>
                        <input id="cfg-assunto" type="text" wire:model="cfgAssunto" maxlength="200" class="input">
                        @error('cfgAssunto') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cfg-texto" class="label">Mensagem</label>
                        <textarea id="cfg-texto" wire:model="cfgTexto" rows="9" maxlength="2000" class="input font-mono text-sm"></textarea>
                        @error('cfgTexto') <p class="field-error">{{ $message }}</p> @enderror
                        <p class="hint">Use *asteriscos* para negrito. A mesma mensagem vai no WhatsApp e no corpo do e-mail.</p>
                    </div>
                    <details class="rounded-xl bg-stone-50 px-4 py-3 text-sm">
                        <summary class="min-h-[44px] cursor-pointer content-center font-medium text-stone-700">Variáveis que você pode usar</summary>
                        <ul class="mt-2 grid gap-1 sm:grid-cols-2">
                            @foreach (\App\Enums\TipoNotificacao::variaveis() as $var => $explicacao)
                                <li><code class="text-rose-700">{{ $var }}</code> <span class="text-stone-500">{{ $explicacao }}</span></li>
                            @endforeach
                        </ul>
                    </details>
                </div>
                <div class="flex flex-wrap justify-between gap-2 border-t border-stone-100 px-5 py-4 sm:px-6">
                    <button type="button" wire:click="restaurarPadrao" class="btn-ghost">Restaurar texto padrão</button>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('editandoTipo', '')" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
