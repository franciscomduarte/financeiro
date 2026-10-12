@php
    $meses = ['1' => 'Janeiro', '2' => 'Fevereiro', '3' => 'Março', '4' => 'Abril', '5' => 'Maio', '6' => 'Junho', '7' => 'Julho', '8' => 'Agosto', '9' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'];
    $tempo = fn (int $min) => $min < 60 ? "{$min} min" : intdiv($min, 60) . 'h' . ($min % 60 ? str_pad((string) ($min % 60), 2, '0', STR_PAD_LEFT) : '');
@endphp
<div>
    <a href="{{ route('campanhas.index') }}" wire:navigate class="mb-3 inline-flex min-h-11 items-center gap-1 text-sm text-stone-500 hover:text-stone-800">← Campanhas</a>

    <x-ui.page-header :titulo="$campanha->nome" :subtitulo="$campanha->status->editavel() ? 'Escreva a mensagem, escolha os pacientes e inicie o envio.' : 'Acompanhe o envio. As mensagens saem aos poucos, em horário comercial.'">
        <x-slot:acoes>
            <span class="badge {{ $campanha->status->badge() }}">{{ $campanha->status->label() }}</span>
        </x-slot:acoes>
    </x-ui.page-header>

    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            {{-- border-emerald-200 bg-emerald-50 text-emerald-800 border-red-200 bg-red-50 text-red-800 --}}
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="-my-2 -mr-2 min-h-11 min-w-11" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    @if ($campanha->status->editavel())
        <div class="grid gap-5 lg:grid-cols-5">
            {{-- 1. Mensagem --}}
            <section class="card p-4 sm:p-5 lg:col-span-2">
                <h2 class="text-base font-semibold text-stone-900">1. Mensagem</h2>
                <form wire:submit="salvar" class="mt-4 space-y-4">
                    <div>
                        <label for="camp-nome" class="label">Nome da campanha (só para você)</label>
                        <input id="camp-nome" type="text" wire:model="nome" maxlength="120" class="input">
                        @error('nome') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="camp-msg" class="label">Texto</label>
                        <textarea id="camp-msg" wire:model="mensagem" rows="8" maxlength="3000" class="input"></textarea>
                        <p class="mt-1 text-xs text-stone-500">Use <code>{primeiro_nome}</code> ou <code>{nome}</code> para personalizar. Mensagens personalizadas têm menos chance de bloqueio.</p>
                        @error('mensagem') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <span class="label">Imagem (opcional)</span>
                        @if ($imagem)
                            <img src="{{ $imagem->temporaryUrl() }}" alt="Prévia da imagem" class="mt-1 max-h-48 rounded-xl border border-stone-200">
                        @elseif ($campanha->imagem_path)
                            <img src="{{ route('campanhas.imagem', $campanha->id) }}" alt="Imagem da campanha" loading="lazy" class="mt-1 max-h-48 rounded-xl border border-stone-200">
                            <button type="button" wire:click="removerImagem" class="btn-ghost mt-1 text-red-600">Remover imagem</button>
                        @endif
                        <input type="file" wire:model="imagem" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-stone-600 file:mr-3 file:min-h-11 file:rounded-lg file:border-0 file:bg-stone-100 file:px-4 file:text-sm file:font-medium">
                        <p class="mt-1 text-xs text-stone-500">JPG, PNG ou WebP até 5 MB. Com imagem, o texto vai como legenda.</p>
                        @error('imagem') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Salvar mensagem</button>
                        <button type="button" wire:click="enviarTeste" wire:loading.attr="disabled" class="btn-secondary">Enviar teste para a gestão</button>
                    </div>
                </form>

                <div class="mt-5 rounded-xl bg-emerald-50/60 p-3">
                    <p class="text-xs font-medium text-stone-500">Prévia (como a Maria Silva vai receber)</p>
                    <p class="mt-1 whitespace-pre-line break-words text-sm text-stone-800">{{ $previa }}</p>
                </div>
            </section>

            {{-- 2. Pacientes --}}
            <section class="card p-4 sm:p-5 lg:col-span-3">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-base font-semibold text-stone-900">2. Quem recebe</h2>
                    <p class="text-sm text-stone-600"><span class="font-semibold tabular-nums text-rose-700">{{ $selecionados }}</span> {{ $selecionados === 1 ? 'paciente marcado' : 'pacientes marcados' }}</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="camp-busca" class="label">Buscar por nome</label>
                        <input id="camp-busca" type="search" wire:model.live.debounce.400ms="busca" class="input" placeholder="Nome do paciente">
                    </div>
                    <div>
                        <label for="camp-mes" class="label">Aniversariantes de</label>
                        <select id="camp-mes" wire:model.live="aniversarioMes" class="input">
                            <option value="">Qualquer mês</option>
                            @foreach ($meses as $n => $m) <option value="{{ $n }}">{{ $m }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="camp-proc" class="label">Já fez o procedimento</label>
                        <select id="camp-proc" wire:model.live="procedimentoId" class="input">
                            <option value="">Qualquer um</option>
                            @foreach ($procedimentos as $p) <option value="{{ $p->id }}">{{ $p->nome }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="camp-sumido" class="label">Sem vir há</label>
                        <select id="camp-sumido" wire:model.live="semVirDias" class="input">
                            <option value="">Qualquer tempo</option>
                            <option value="30">30 dias ou mais</option>
                            <option value="60">60 dias ou mais</option>
                            <option value="90">90 dias ou mais</option>
                            <option value="180">6 meses ou mais</option>
                            <option value="365">1 ano ou mais</option>
                        </select>
                    </div>
                    <div>
                        <label for="camp-origem" class="label">Como conheceu</label>
                        <select id="camp-origem" wire:model.live="origem" class="input">
                            <option value="">Qualquer origem</option>
                            @foreach ($origens as $o) <option value="{{ $o }}">{{ $o }}</option> @endforeach
                        </select>
                    </div>
                    <label class="flex min-h-11 items-center gap-2 text-sm text-stone-700">
                        <input type="checkbox" wire:model.live="soAutorizados" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Só quem autorizou marketing
                    </label>
                    <label class="flex min-h-11 items-center gap-2 text-sm text-stone-700">
                        <input type="checkbox" wire:model.live="soSelecionados" class="h-5 w-5 rounded border-stone-300 text-rose-600">
                        Ver só os marcados
                    </label>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-stone-100 pt-3">
                    <span class="text-sm text-stone-500 tabular-nums">{{ $pacientes->total() }} no filtro</span>
                    <button type="button" wire:click="marcarTodos" wire:loading.attr="disabled" class="btn-secondary" @disabled($pacientes->total() === 0)>Marcar todos do filtro</button>
                    <button type="button" wire:click="desmarcarTodos" wire:loading.attr="disabled" class="btn-ghost">Desmarcar do filtro</button>
                    <button type="button" wire:click="limparFiltros" class="btn-ghost">Limpar filtros</button>
                </div>

                @if ($pacientes->isEmpty())
                    <p class="py-8 text-center text-sm text-stone-500">Nenhum paciente com celular neste filtro.</p>
                @else
                    <ul class="mt-2 divide-y divide-stone-100">
                        @foreach ($pacientes as $p)
                            @php $marcado = $marcados->has($p->id); @endphp
                            <li wire:key="pac-{{ $p->id }}">
                                <label class="flex min-h-12 cursor-pointer items-center gap-3 py-2">
                                    <input type="checkbox" @checked($marcado) wire:click="alternar('{{ $p->id }}')" class="h-5 w-5 shrink-0 rounded border-stone-300 text-rose-600">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-stone-900">{{ $p->nome }}</span>
                                        <span class="block text-xs text-stone-500 tabular-nums">
                                            {{ $p->telefone }}
                                            @if ($p->data_nascimento) · 🎂 {{ $p->data_nascimento->format('d/m') }} @endif
                                            @if (! $p->aceita_whatsapp_marketing) · <span class="text-amber-700">sem autorização de marketing</span> @endif
                                        </span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3">{{ $pacientes->links() }}</div>
                @endif
            </section>
        </div>

        {{-- 3. Iniciar --}}
        <section class="card mt-5 p-4 sm:p-5">
            <h2 class="text-base font-semibold text-stone-900">3. Iniciar o envio</h2>
            <p class="mt-1 text-sm text-stone-600">🛡️ {{ $regras }}</p>
            @if ($selecionados > 0)
                <p class="mt-1 text-sm text-stone-600">Para {{ $selecionados }} {{ $selecionados === 1 ? 'paciente' : 'pacientes' }}, leva cerca de <strong>{{ $tempo($estimativa) }}</strong> de envio (fora do horário, o envio espera e continua no dia seguinte).</p>
            @endif
            <div class="mt-4 flex flex-wrap gap-2">
                @podeEditar
                    <button type="button" wire:click="iniciar" wire:loading.attr="disabled" @disabled($selecionados === 0)
                            wire:confirm="Iniciar a campanha para {{ $selecionados }} paciente(s)? Depois de iniciada, a mensagem e a lista não podem ser alteradas. Salve a mensagem antes."
                            class="btn-primary">Iniciar envio</button>
                    <button type="button" wire:click="excluir" wire:confirm="Excluir este rascunho?" class="btn-ghost text-red-600">Excluir rascunho</button>
                @endpodeEditar
            </div>
        </section>
    @else
        @php
            $total = max(1, $campanha->total);
            $feitos = $campanha->total - $pendentes;
        @endphp
        <div wire:poll.15s class="space-y-5">
            <section class="card p-4 sm:p-5">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div><p class="text-xs text-stone-500">Pacientes</p><p class="text-xl font-semibold tabular-nums">{{ $campanha->total }}</p></div>
                    <div><p class="text-xs text-stone-500">Enviadas</p><p class="text-xl font-semibold tabular-nums text-emerald-700">{{ $campanha->enviados }}</p></div>
                    <div><p class="text-xs text-stone-500">Falhas / puladas</p><p class="text-xl font-semibold tabular-nums text-red-700">{{ $campanha->falhas }}</p></div>
                    <div><p class="text-xs text-stone-500">Na fila</p><p class="text-xl font-semibold tabular-nums">{{ $pendentes }}</p></div>
                </div>
                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $feitos }}" aria-valuemax="{{ $campanha->total }}">
                    <div class="h-full rounded-full bg-sky-500 transition-all" style="width: {{ min(100, (int) round($feitos / $total * 100)) }}%"></div>
                </div>
                <p class="mt-3 text-sm text-stone-600">
                    @if ($campanha->status === \App\Enums\StatusCampanha::Enviando)
                        @if (! $noHorario)
                            ⏸️ Fora do horário de envio: continua no próximo horário permitido.
                        @elseif ($enviadasHoje >= $limite)
                            ⏸️ Limite de hoje atingido ({{ $limite }} mensagens): continua amanhã.
                        @else
                            📤 Enviando… faltam cerca de {{ $tempo($estimativa) }}. Hoje: {{ $enviadasHoje }} de {{ $limite }}.
                        @endif
                    @elseif ($campanha->status === \App\Enums\StatusCampanha::Pausada)
                        ⏸️ Pausada. Se pausou sozinha, confira a conexão do WhatsApp em Dados da clínica antes de retomar.
                    @elseif ($campanha->status === \App\Enums\StatusCampanha::Concluida)
                        ✅ Concluída em {{ $campanha->concluida_em?->format('d/m/Y H:i') }}.
                    @else
                        Cancelada em {{ $campanha->concluida_em?->format('d/m/Y H:i') }}.
                    @endif
                </p>
                @podeEditar
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($campanha->status === \App\Enums\StatusCampanha::Enviando)
                            <button type="button" wire:click="pausar" wire:loading.attr="disabled" class="btn-secondary">Pausar</button>
                        @elseif ($campanha->status === \App\Enums\StatusCampanha::Pausada)
                            <button type="button" wire:click="retomar" wire:loading.attr="disabled" class="btn-primary">Retomar</button>
                        @endif
                        @if (in_array($campanha->status, [\App\Enums\StatusCampanha::Enviando, \App\Enums\StatusCampanha::Pausada], true))
                            <button type="button" wire:click="cancelar" wire:confirm="Cancelar a campanha? Quem ainda não recebeu não vai receber." class="btn-ghost text-red-600">Cancelar campanha</button>
                        @endif
                    </div>
                @endpodeEditar
            </section>

            <section class="card p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-stone-900">Pacientes</h2>
                    <select wire:model.live="filtroStatus" class="input w-auto" aria-label="Filtrar por situação">
                        <option value="">Todas as situações</option>
                        @foreach (\App\Enums\StatusDestinatarioCampanha::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }} ({{ (int) ($contagem[$s->value] ?? 0) }})</option>
                        @endforeach
                    </select>
                </div>
                <ul class="mt-2 divide-y divide-stone-100">
                    @foreach ($destinatarios as $d)
                        <li wire:key="dest-{{ $d->id }}" class="flex min-h-12 items-center gap-3 py-2">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $d->paciente?->nome ?? '—' }}</p>
                                @if ($d->erro) <p class="truncate text-xs text-stone-500">{{ $d->erro }}</p> @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="badge {{ $d->status->badge() }}">{{ $d->status->label() }}</span>
                                @if ($d->enviado_em) <p class="mt-0.5 text-xs text-stone-400 tabular-nums">{{ $d->enviado_em->format('d/m H:i') }}</p> @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3">{{ $destinatarios->links() }}</div>
            </section>

            <section class="card p-4 sm:p-5">
                <h2 class="text-base font-semibold text-stone-900">Mensagem enviada</h2>
                @if ($campanha->imagem_path)
                    <img src="{{ route('campanhas.imagem', $campanha->id) }}" alt="Imagem da campanha" loading="lazy" class="mt-3 max-h-48 rounded-xl border border-stone-200">
                @endif
                <p class="mt-3 whitespace-pre-line break-words text-sm text-stone-700">{{ $campanha->mensagem }}</p>
            </section>
        </div>
    @endif
</div>
