<div>
    {{-- Classes das mensagens: border-emerald-200 bg-emerald-50 text-emerald-800 text-emerald-600 border-red-200 bg-red-50 text-red-800 text-red-600 --}}
    @php
        $corSituacao = [
            'teste'     => 'bg-amber-50 text-amber-700',
            'ativa'     => 'bg-emerald-50 text-emerald-700',
            'bloqueada' => 'bg-red-50 text-red-700',
        ];
        $prazoTeste = function ($c) {
            if ($c->status->value !== 'teste' || ! $c->teste_ate) return null;
            $dias = (int) today()->diffInDays($c->teste_ate, false);
            return match (true) {
                $dias < 0   => 'acabou em ' . $c->teste_ate->format('d/m'),
                $dias === 0 => 'último dia hoje',
                $dias === 1 => 'acaba amanhã',
                default     => "faltam " . ($dias + 1) . " dias",
            };
        };
    @endphp

    <x-ui.page-header titulo="Clínicas" subtitulo="Acompanhe quem está testando, quem assinou e dê suporte quando precisar." />

    {{-- Mensagens --}}
    @foreach (['flashSucesso' => 'emerald', 'flashErro' => 'red'] as $prop => $cor)
        @if ($this->$prop)
            <div class="mb-4 flex items-start gap-3 rounded-xl border border-{{ $cor }}-200 bg-{{ $cor }}-50 px-4 py-3 text-sm text-{{ $cor }}-800" role="{{ $cor === 'red' ? 'alert' : 'status' }}">
                <span class="flex-1">{{ $this->$prop }}</span>
                <button type="button" wire:click="$set('{{ $prop }}', null)" class="text-{{ $cor }}-600" aria-label="Fechar">✕</button>
            </div>
        @endif
    @endforeach

    {{-- Saúde do sistema --}}
    @php
        $filaOk = $saude['batimento_minutos'] !== null && $saude['batimento_minutos'] < \App\Services\SaudeSistemaService::FILA_PARADA_MINUTOS;
        $itensSaude = [
            ['Fila de tarefas', $saude['batimento_minutos'] === null ? 'sem sinal ainda' : ($filaOk ? 'rodando (há ' . $saude['batimento_minutos'] . ' min)' : 'parada há ' . $saude['batimento_minutos'] . ' min'), $filaOk],
            ['Tarefas atrasadas', (string) $saude['jobs_atrasados'], $saude['jobs_atrasados'] === 0],
            ['Falhas (24h)', (string) $saude['falhas_24h'], $saude['falhas_24h'] === 0],
            ['Erros (1h)', (string) $saude['erros_hora'], $saude['erros_hora'] < \App\Services\SaudeSistemaService::LIMITE_ERROS_HORA],
            ['Notificações com falha (1h)', (string) $saude['notificacoes_falhas_hora'], $saude['notificacoes_falhas_hora'] < \App\Services\SaudeSistemaService::LIMITE_NOTIFICACOES_HORA],
        ];
    @endphp
    <section class="card mb-6 p-4 sm:p-5" aria-label="Saúde do sistema">
        <h2 class="text-sm font-semibold text-stone-900">Saúde do sistema</h2>
        <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($itensSaude as [$rotulo, $valor, $ok])
                <div class="rounded-xl px-3 py-2 {{ $ok ? 'bg-emerald-50' : 'bg-red-50' }}">
                    <dt class="text-xs {{ $ok ? 'text-emerald-700' : 'text-red-700' }}">{{ $rotulo }}</dt>
                    <dd class="text-sm font-semibold tabular-nums {{ $ok ? 'text-emerald-900' : 'text-red-900' }}">{{ $valor }}</dd>
                </div>
            @endforeach
        </dl>
        @if ($saude['ultimas_falhas'])
            <ul class="mt-3 space-y-1 text-xs text-red-700">
                @foreach ($saude['ultimas_falhas'] as $falha) <li class="break-words">{{ $falha }}</li> @endforeach
            </ul>
        @endif
        <p class="hint mt-2">Alertas vão por e-mail para os super admins a cada problema novo (no máximo um por hora) e quando normaliza.</p>
    </section>

    {{-- Indicadores --}}
    <div class="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 mb-6">
        @foreach ([
            ['Clínicas', $indicadores['total'], null, 'text-stone-900'],
            ['Em teste', $indicadores['teste'], $indicadores['testes_acabando'] . ' acabam em 7 dias', 'text-amber-600'],
            ['Assinantes', $indicadores['ativas'], null, 'text-emerald-600'],
            ['Bloqueadas', $indicadores['bloqueadas'], null, 'text-red-600'],
            ['Cadastros no mês', $indicadores['cadastros_mes'], today()->translatedFormat('F'), 'text-stone-900'],
            ['Conversão', $indicadores['conversao'] === null ? '—' : number_format($indicadores['conversao'], 1, ',', '.') . '%', 'do teste para assinatura', 'text-rose-600'],
        ] as [$rotulo, $valor, $nota, $cor])
            <div class="card p-5">
                <p class="text-sm text-stone-500">{{ $rotulo }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $cor }}">{{ $valor }}</p>
                @if ($nota)
                    <p class="mt-0.5 text-xs text-stone-500">{{ $nota }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="card p-4 mb-4 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
        <label class="sr-only" for="busca-clinica">Buscar</label>
        <input id="busca-clinica" type="search" wire:model.live.debounce.400ms="busca" class="input" placeholder="Buscar por clínica ou e-mail">
        <label class="sr-only" for="filtro-situacao">Situação</label>
        <select id="filtro-situacao" wire:model.live="situacao" class="input sm:w-44">
            <option value="">Todas as situações</option>
            @foreach ($situacoes as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        <label class="sr-only" for="ordem">Ordenar</label>
        <select id="ordem" wire:model.live="ordem" class="input sm:w-52">
            <option value="recentes">Cadastro mais recente</option>
            <option value="acesso">Último acesso</option>
            <option value="teste">Fim do teste mais próximo</option>
            <option value="nome">Nome</option>
        </select>
    </div>

    {{-- Lista --}}
    <div class="card overflow-hidden">
        @if ($clinicas->isEmpty())
            @if ($busca !== '' || $situacao !== '')
                <x-ui.empty-state titulo="Nada encontrado com esses filtros" texto="Tente outro nome ou situação.">
                    <button type="button" wire:click="limparFiltros" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state titulo="Nenhuma clínica ainda" texto="As clínicas aparecem aqui assim que se cadastram pelo “Assine já”." />
            @endif
        @else
            <table class="hidden md:table w-full text-sm">
                <thead class="bg-stone-50 text-xs font-medium text-stone-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Clínica</th>
                        <th class="px-4 py-3 text-left">Responsável</th>
                        <th class="px-4 py-3 text-left">Situação</th>
                        <th class="px-4 py-3 text-left">Cadastro</th>
                        <th class="px-4 py-3 text-left">Último acesso</th>
                        <th class="px-4 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($clinicas as $c)
                        @php($resp = $c->usuarios->first())
                        <tr wire:key="clinica-{{ $c->id }}" class="hover:bg-stone-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $c->nome }}</p>
                                <p class="text-xs text-stone-500">{{ $c->telefone ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-stone-800">{{ $resp?->name ?? '—' }}</p>
                                <p class="text-xs text-stone-500">{{ $resp?->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $corSituacao[$c->status->value] }}">{{ $c->status->label() }}</span>
                                @if ($prazo = $prazoTeste($c))
                                    <p class="mt-1 text-xs text-stone-500">{{ $prazo }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-stone-600 tabular-nums">{{ $c->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ $c->ultimo_acesso_em?->diffForHumans() ?? 'nunca' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="abrir('{{ $c->id }}')" class="btn-secondary min-h-[36px] px-3">Gerenciar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ul class="md:hidden divide-y divide-stone-100">
                @foreach ($clinicas as $c)
                    @php($resp = $c->usuarios->first())
                    <li wire:key="clinica-m-{{ $c->id }}">
                        <button type="button" wire:click="abrir('{{ $c->id }}')" class="w-full text-left px-4 py-3 hover:bg-stone-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-stone-900 truncate">{{ $c->nome }}</p>
                                    <p class="text-xs text-stone-500 truncate">{{ $resp?->name }} · {{ $resp?->email }}</p>
                                </div>
                                <span class="badge shrink-0 {{ $corSituacao[$c->status->value] }}">{{ $c->status->label() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-stone-500">
                                {{ $prazoTeste($c) ?? 'Desde ' . $c->created_at->format('d/m/Y') }} · acesso {{ $c->ultimo_acesso_em?->diffForHumans() ?? 'nunca' }}
                            </p>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="mt-4">{{ $clinicas->links() }}</div>

    {{-- Detalhe da clínica --}}
    @if ($aberta)
        <div class="fixed inset-0 z-50 flex items-end sm:items-stretch sm:justify-end" role="dialog" aria-modal="true" aria-labelledby="clinica-titulo"
             x-data @keydown.escape.window="$wire.fechar()">
            <div class="absolute inset-0 bg-black/40" wire:click="fechar"></div>
            <div class="relative w-full sm:max-w-lg max-h-[92dvh] sm:max-h-none overflow-y-auto bg-surface rounded-t-2xl sm:rounded-none shadow-xl">
                <div class="sticky top-0 z-10 bg-surface border-b border-stone-200/70 px-5 py-4 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="clinica-titulo" class="text-lg font-semibold text-stone-900 truncate">{{ $aberta->nome }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-stone-500">
                            <span class="badge {{ $corSituacao[$aberta->status->value] }}">{{ $aberta->status->label() }}</span>
                            {{ $prazoTeste($aberta) }}
                        </p>
                    </div>
                    <button type="button" wire:click="fechar" class="w-11 h-11 -mr-2 flex items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100" aria-label="Fechar">✕</button>
                </div>

                <div class="p-5 space-y-6">
                    {{-- Resumo --}}
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-stone-500">Cadastro</dt><dd class="text-stone-900">{{ $aberta->created_at->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-stone-500">Último acesso</dt><dd class="text-stone-900">{{ $aberta->ultimo_acesso_em?->format('d/m/Y H:i') ?? 'nunca' }}</dd></div>
                        <div><dt class="text-stone-500">Telefone</dt><dd class="text-stone-900">{{ $aberta->telefone ?: '—' }}</dd></div>
                        <div><dt class="text-stone-500">Assinante desde</dt><dd class="text-stone-900">{{ $aberta->ativada_em?->format('d/m/Y') ?? '—' }}</dd></div>
                        <div><dt class="text-stone-500">Pacientes</dt><dd class="text-stone-900 tabular-nums">{{ $uso['pacientes'] }}</dd></div>
                        <div><dt class="text-stone-500">Agendamentos</dt><dd class="text-stone-900 tabular-nums">{{ $uso['agendamentos'] }}</dd></div>
                        <div><dt class="text-stone-500">Lançamentos</dt><dd class="text-stone-900 tabular-nums">{{ $uso['lancamentos'] }}</dd></div>
                    </dl>

                    {{-- Ações --}}
                    <section aria-labelledby="acoes-titulo" class="space-y-3">
                        <h3 id="acoes-titulo" class="text-sm font-semibold text-stone-900">Ações</h3>

                        @if ($aberta->status->value !== 'ativa')
                            <button type="button" wire:click="ativar" wire:confirm="Ativar a assinatura de {{ $aberta->nome }}? A clínica sai do teste e volta a gravar normalmente."
                                    class="btn-primary w-full">Ativar assinatura</button>
                        @endif

                        @if ($aberta->status->value !== 'ativa')
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label for="dias-extensao" class="label">Dar mais dias de teste</label>
                                    <input id="dias-extensao" type="number" inputmode="numeric" min="1" max="90" wire:model="diasExtensao" class="input">
                                </div>
                                <button type="button" wire:click="estenderTeste" class="btn-secondary">Estender</button>
                            </div>
                            @error('diasExtensao') <p class="field-error">{{ $message }}</p> @enderror
                        @endif

                        @if ($aberta->estaBloqueada())
                            <button type="button" wire:click="desbloquear" class="btn-secondary w-full">Desbloquear</button>
                        @else
                            <div x-data="{ aberto: false }">
                                <button type="button" x-show="!aberto" @click="aberto = true" class="btn-ghost w-full text-red-600">Bloquear clínica…</button>
                                <div x-show="aberto" x-cloak class="space-y-2 rounded-xl border border-red-200 p-3">
                                    <label for="motivo-bloqueio" class="label">Motivo (opcional, só você vê)</label>
                                    <input id="motivo-bloqueio" type="text" wire:model="motivoBloqueio" class="input" placeholder="Ex.: assinatura não paga">
                                    <div class="flex gap-2">
                                        <button type="button" @click="aberto = false" class="btn-secondary flex-1">Cancelar</button>
                                        <button type="button" wire:click="bloquear" class="btn-danger flex-1">Bloquear</button>
                                    </div>
                                    <p class="hint">Ninguém da clínica consegue entrar até você desbloquear. Os dados continuam guardados.</p>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('plataforma.suporte.entrar', $aberta->id) }}">
                            @csrf
                            <button type="submit" class="btn-secondary w-full">Entrar como a clínica (só leitura)</button>
                        </form>
                    </section>

                    {{-- Equipe --}}
                    <section aria-labelledby="equipe-titulo">
                        <h3 id="equipe-titulo" class="text-sm font-semibold text-stone-900 mb-2">Equipe</h3>
                        <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200/70">
                            @forelse ($aberta->usuarios as $u)
                                <li class="flex items-center justify-between gap-3 px-3 py-2.5 text-sm">
                                    <div class="min-w-0">
                                        <p class="text-stone-900 truncate">{{ $u->name }}</p>
                                        <p class="text-xs text-stone-500 truncate">{{ $u->email }}</p>
                                    </div>
                                    <span class="badge {{ $u->pivot->papel === 'admin' ? 'bg-rose-50 text-rose-700' : 'bg-stone-100 text-stone-600' }}">
                                        {{ \App\Enums\RoleUsuario::tryFrom($u->pivot->papel)?->label() ?? $u->pivot->papel }}
                                    </span>
                                </li>
                            @empty
                                <li class="px-3 py-2.5 text-sm text-stone-500">Ninguém vinculado.</li>
                            @endforelse
                        </ul>
                    </section>

                    {{-- Histórico --}}
                    <section aria-labelledby="historico-titulo">
                        <h3 id="historico-titulo" class="text-sm font-semibold text-stone-900 mb-2">Histórico</h3>
                        @if ($aberta->eventos->isEmpty())
                            <p class="text-sm text-stone-500">Nenhum evento registrado.</p>
                        @else
                            <ol class="space-y-3">
                                @foreach ($aberta->eventos as $ev)
                                    <li class="text-sm">
                                        <p class="text-stone-900">
                                            {{ $ev->acao->label() }}
                                            @if (isset($ev->detalhes['dias'])) (+{{ $ev->detalhes['dias'] }} dias, até {{ \Carbon\Carbon::parse($ev->detalhes['teste_ate'])->format('d/m/Y') }}) @endif
                                        </p>
                                        <p class="text-xs text-stone-500">
                                            {{ $ev->created_at->format('d/m/Y H:i') }} · {{ $ev->user?->name ?? 'sistema' }}
                                            @if (! empty($ev->detalhes['motivo'])) · “{{ $ev->detalhes['motivo'] }}” @endif
                                        </p>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    @endif
</div>
