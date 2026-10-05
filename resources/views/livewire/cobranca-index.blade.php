<div class="space-y-6">

    {{-- Aviso: sucesso --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 left-4 z-[60] sm:left-auto sm:max-w-sm" role="status">
            <div class="flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-surface px-4 py-3 text-sm text-emerald-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                    <svg class="h-3 w-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                {{ $flashSucesso }}
            </div>
        </div>
    @endif

    {{-- Aviso: erro --}}
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 left-4 z-[60] sm:left-auto sm:max-w-sm" role="alert">
            <div class="flex items-center gap-2.5 rounded-xl border border-red-200 bg-surface px-4 py-3 text-sm text-red-800 shadow-lg">
                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-3 w-3 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- Cabeçalho --}}
    <x-ui.page-header titulo="Cobranças" subtitulo="Envie mensalidades e parcelas por PIX e acompanhe quem já pagou.">
        <x-slot:acoes>
            @if ($abaAtiva === 'mensalidades')
                @if ($pacientesAtivos > 0)
                    <button wire:click="abrirModalDispararTodas" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                        Enviar cobranças do mês
                    </button>
                @endif
            @else
                <button wire:click="abrirModalNovoParcelamento" class="btn-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Novo parcelamento
                </button>
            @endif
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- Aviso: ninguém apto a receber cobrança --}}
    @if ($abaAtiva === 'mensalidades' && $pacientesAtivos === 0)
        <div class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between">
            <p class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                Nenhum paciente ativo tem mensalidade por PIX. Para cobrar por aqui, informe o valor e escolha PIX no cadastro do paciente.
            </p>
            <a href="{{ route('pacientes.index') }}" class="btn-secondary shrink-0">Ir para Pacientes</a>
        </div>
    @endif

    {{-- Indicadores do mês (só na aba mensalidades) --}}
    @if ($abaAtiva === 'mensalidades')
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Total no mês</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalMes }}</p>
            <p class="mt-0.5 text-xs text-stone-400 first-letter:uppercase">{{ Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Pagas</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums">{{ $pagosMes }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Pendentes</p>
            <p class="mt-1 text-2xl font-semibold text-amber-700 tabular-nums">{{ $pendentesMes }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Vencidas</p>
            <p class="mt-1 text-2xl font-semibold text-red-700 tabular-nums">{{ $vencidosMes }}</p>
        </div>
        <div class="card col-span-2 p-5 sm:col-span-1">
            <p class="text-sm text-stone-500">Prontos para cobrar</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $pacientesAtivos }}</p>
            <p class="mt-0.5 text-xs text-stone-400">pacientes ativos com PIX</p>
        </div>
    </div>
    @endif

    {{-- Abas --}}
    <div class="flex w-full gap-1 rounded-xl bg-stone-100 p-1 sm:w-fit">
        <button wire:click="$set('abaAtiva', 'mensalidades')"
                class="{{ $abaAtiva === 'mensalidades' ? 'bg-surface shadow-sm text-stone-900' : 'text-stone-500 hover:text-stone-800' }} min-h-[40px] flex-1 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none">
            Mensalidades
        </button>
        <button wire:click="$set('abaAtiva', 'parcelamentos')"
                class="{{ $abaAtiva === 'parcelamentos' ? 'bg-surface shadow-sm text-stone-900' : 'text-stone-500 hover:text-stone-800' }} flex min-h-[40px] flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 text-sm font-medium transition-colors sm:flex-none">
            Parcelamentos
            @php $ativos = $parcelamentos->where('status', 'ativo')->count(); @endphp
            @if ($ativos > 0)
                <span class="badge bg-rose-50 text-rose-700 !px-1.5 tabular-nums">{{ $ativos }}</span>
            @endif
        </button>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         ABA: MENSALIDADES
    ════════════════════════════════════════════════════════════ --}}
    @if ($abaAtiva === 'mensalidades')

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input wire:model.live.debounce.400ms="busca"
                       type="search"
                       aria-label="Buscar paciente"
                       placeholder="Buscar pelo nome do paciente"
                       class="input !pl-10">
            </div>
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:!w-44">
                <option value="">Todos os status</option>
                <option value="PENDING">Pendentes</option>
                <option value="RECEIVED">Pagas</option>
                <option value="OVERDUE">Vencidas</option>
                <option value="REFUNDED">Estornadas</option>
            </select>
            <input wire:model.live="filtroMes" type="month" aria-label="Mês de referência"
                   class="input sm:!w-44"
                   value="{{ $mesAtual }}">
            @if ($busca || $filtroStatus || ($filtroMes && $filtroMes !== $mesAtual))
                <button wire:click="$set('busca', ''); $set('filtroStatus', ''); $set('filtroMes', '')" class="btn-ghost whitespace-nowrap">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Lista de cobranças --}}
    <div class="card overflow-hidden">
        @if ($cobrancas->isEmpty())
            @if ($busca || $filtroStatus || ($filtroMes && $filtroMes !== $mesAtual))
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Tente outro mês ou status, ou limpe os filtros para ver as cobranças deste mês."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button wire:click="$set('busca', ''); $set('filtroStatus', ''); $set('filtroMes', '')" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhuma cobrança neste mês"
                    texto="Envie as mensalidades por PIX e acompanhe aqui quem já pagou."
                    icone="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z">
                    @if ($pacientesAtivos > 0)
                        <button wire:click="abrirModalDispararTodas" class="btn-primary">Enviar cobranças do mês</button>
                    @else
                        <a href="{{ route('pacientes.index') }}" class="btn-secondary">Ir para Pacientes</a>
                    @endif
                </x-ui.empty-state>
            @endif
        @else
            @php
                $statusMap = [
                    'PENDING'  => ['Pendente',  'bg-amber-50 text-amber-700'],
                    'RECEIVED' => ['Paga',      'bg-emerald-50 text-emerald-700'],
                    'OVERDUE'  => ['Vencida',   'bg-red-50 text-red-700'],
                    'REFUNDED' => ['Estornada', 'bg-stone-100 text-stone-600'],
                    'DELETED'  => ['Excluída',  'bg-stone-100 text-stone-500'],
                ];
            @endphp

            {{-- Celular: lista de cartões --}}
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($cobrancas as $cobranca)
                    @php [$statusLabel, $statusCls] = $statusMap[$cobranca->status] ?? ['Desconhecido', 'bg-stone-100 text-stone-500']; @endphp
                    <li class="flex items-center gap-3 px-4 py-3" wire:key="cob-cel-{{ $cobranca->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-stone-900">{{ $cobranca->paciente?->nome ?? '—' }}</p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-stone-500">
                                @if ($cobranca->pago_em)
                                    <span class="text-emerald-700">Paga em {{ $cobranca->pago_em->format('d/m') }}</span>
                                @else
                                    <span class="{{ $cobranca->vencimento->isPast() && $cobranca->status === 'PENDING' ? 'font-medium text-red-700' : '' }}">Vence {{ $cobranca->vencimento->format('d/m') }}</span>
                                @endif
                                @if ($cobranca->numero_parcela && $cobranca->parcelamento)
                                    <span>· Parcela {{ $cobranca->numero_parcela }}/{{ $cobranca->parcelamento->total_parcelas }}</span>
                                @endif
                                @if ($cobranca->link_fatura)
                                    <a href="{{ $cobranca->link_fatura }}" target="_blank" rel="noopener" class="font-medium text-rose-700">Ver fatura ↗</a>
                                @endif
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-stone-900 tabular-nums">R$ {{ number_format((float) $cobranca->valor, 2, ',', '.') }}</p>
                            <span class="badge mt-0.5 {{ $statusCls }}">{{ $statusLabel }}</span>
                        </div>
                        @if ($cobranca->status !== 'RECEIVED' && $cobranca->status !== 'DELETED')
                            <button wire:click="abrirModalReenviar({{ $cobranca->id }})"
                                    title="Reenviar cobrança" aria-label="Reenviar cobrança"
                                    class="-mr-2 flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-700 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            </button>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: tabela --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 bg-stone-50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Paciente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Mês</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-stone-500">Valor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Vencimento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Status</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-medium text-stone-500 lg:table-cell">Tipo</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-medium text-stone-500 lg:table-cell">Enviada por</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-stone-500"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($cobrancas as $cobranca)
                            @php [$statusLabel, $statusCls] = $statusMap[$cobranca->status] ?? ['Desconhecido', 'bg-stone-100 text-stone-500']; @endphp
                            <tr class="hover:bg-stone-50 transition-colors">
                                {{-- Paciente --}}
                                <td class="px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-stone-900 truncate">{{ $cobranca->paciente?->nome ?? '—' }}</p>
                                        @if ($cobranca->link_fatura)
                                            <a href="{{ $cobranca->link_fatura }}" target="_blank" rel="noopener"
                                               class="text-xs font-medium text-rose-700 hover:underline">Ver fatura ↗</a>
                                        @endif
                                    </div>
                                </td>
                                {{-- Mês de referência --}}
                                <td class="px-4 py-3 text-stone-600 first-letter:uppercase">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $cobranca->mes_referencia)->translatedFormat('M/Y') }}
                                </td>
                                {{-- Valor --}}
                                <td class="px-4 py-3 text-right font-semibold text-stone-900 tabular-nums">
                                    R$ {{ number_format((float) $cobranca->valor, 2, ',', '.') }}
                                </td>
                                {{-- Vencimento --}}
                                <td class="px-4 py-3 tabular-nums">
                                    @if ($cobranca->pago_em)
                                        <span class="text-xs font-medium text-emerald-700">Paga em {{ $cobranca->pago_em->format('d/m/Y') }}</span>
                                    @else
                                        <span class="{{ $cobranca->vencimento->isPast() && $cobranca->status === 'PENDING' ? 'font-medium text-red-700' : 'text-stone-600' }}">
                                            {{ $cobranca->vencimento->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    <span class="badge {{ $statusCls }}">{{ $statusLabel }}</span>
                                </td>
                                {{-- Tipo --}}
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    @if ($cobranca->numero_parcela && $cobranca->parcelamento)
                                        <span class="badge bg-violet-50 text-violet-700 tabular-nums">
                                            Parcela {{ $cobranca->numero_parcela }}/{{ $cobranca->parcelamento->total_parcelas }}
                                        </span>
                                    @else
                                        <span class="text-xs text-stone-500">Mensalidade</span>
                                    @endif
                                </td>
                                {{-- Envios --}}
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <div class="flex items-center gap-1.5">
                                        @if ($cobranca->whatsapp_enviado_em)
                                            <span title="WhatsApp enviado em {{ $cobranca->whatsapp_enviado_em->format('d/m H:i') }}"
                                                  class="badge bg-emerald-50 text-emerald-700">
                                                WhatsApp
                                            </span>
                                        @endif
                                        @if ($cobranca->email_enviado_em)
                                            <span title="E-mail enviado em {{ $cobranca->email_enviado_em->format('d/m H:i') }}"
                                                  class="badge bg-blue-50 text-blue-700">
                                                E-mail
                                            </span>
                                        @endif
                                        @if (!$cobranca->whatsapp_enviado_em && !$cobranca->email_enviado_em)
                                            <span class="text-xs text-stone-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                {{-- Ações --}}
                                <td class="px-4 py-3 text-right">
                                    @if ($cobranca->status !== 'RECEIVED' && $cobranca->status !== 'DELETED')
                                        <button wire:click="abrirModalReenviar({{ $cobranca->id }})"
                                                class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-700 transition-colors"
                                                title="Reenviar cobrança" aria-label="Reenviar cobrança">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($cobrancas->hasPages())
                <div class="border-t border-stone-100 px-4 py-3">
                    {{ $cobrancas->links() }}
                </div>
            @endif
        @endif
    </div>

    @endif {{-- fim aba mensalidades --}}

    {{-- ════════════════════════════════════════════════════════════
         ABA: PARCELAMENTOS
    ════════════════════════════════════════════════════════════ --}}
    @if ($abaAtiva === 'parcelamentos')

    @if ($parcelamentos->isEmpty())
        <div class="card">
            <x-ui.empty-state
                titulo="Nenhum parcelamento ainda"
                texto="Divida um tratamento em parcelas mensais e envie cada uma por PIX."
                icone="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z">
                <button wire:click="abrirModalNovoParcelamento" class="btn-primary">Novo parcelamento</button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($parcelamentos as $parcelamento)
                @php
                    $enviadas  = $parcelamento->cobrancas->count();
                    $recebidas = $parcelamento->cobrancas->where('status', 'RECEIVED')->count();
                    $total     = $parcelamento->total_parcelas;
                    $pctEnv    = $total > 0 ? round($enviadas / $total * 100) : 0;
                    $pctRec    = $total > 0 ? round($recebidas / $total * 100) : 0;
                    $podeDisp  = $parcelamento->status === 'ativo' && ($enviadas + 1) <= $total;

                    $statusBadge = match($parcelamento->status) {
                        'ativo'     => 'bg-emerald-50 text-emerald-700',
                        'concluido' => 'bg-blue-50 text-blue-700',
                        default     => 'bg-stone-100 text-stone-600',
                    };
                    $statusLabel = match($parcelamento->status) {
                        'ativo'     => 'Ativo',
                        'concluido' => 'Concluído',
                        default     => 'Cancelado',
                    };
                @endphp
                <div class="card space-y-4 p-5" wire:key="parc-{{ $parcelamento->id }}">

                    {{-- Cabeçalho do cartão --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-stone-900">{{ $parcelamento->paciente?->nome ?? '—' }}</p>
                            <p class="truncate text-sm text-stone-500">{{ $parcelamento->descricao }}</p>
                        </div>
                        <span class="badge shrink-0 {{ $statusBadge }}">{{ $statusLabel }}</span>
                    </div>

                    {{-- Valores --}}
                    <div class="flex items-baseline justify-between gap-2 text-sm">
                        <p class="tabular-nums">
                            <span class="font-semibold text-stone-900">{{ $total }}× R$ {{ number_format((float) $parcelamento->valor_parcela, 2, ',', '.') }}</span>
                        </p>
                        <p class="text-right text-stone-500 tabular-nums">
                            Total <span class="font-medium text-stone-700">R$ {{ number_format((float) $parcelamento->valor_total, 2, ',', '.') }}</span>
                        </p>
                    </div>

                    {{-- Progresso --}}
                    <div class="space-y-2">
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-stone-500">
                                <span>Enviadas</span>
                                <span class="font-medium text-stone-700 tabular-nums">{{ $enviadas }}/{{ $total }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-stone-100">
                                <div class="h-2 rounded-full bg-rose-500 transition-all" style="width: {{ $pctEnv }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-stone-500">
                                <span>Pagas</span>
                                <span class="font-medium text-emerald-700 tabular-nums">{{ $recebidas }}/{{ $total }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-stone-100">
                                <div class="h-2 rounded-full bg-emerald-500 transition-all" style="width: {{ $pctRec }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Próximo envio --}}
                    <p class="text-xs text-stone-500">
                        Cobrança todo dia <span class="font-medium text-stone-700 tabular-nums">{{ $parcelamento->dia_cobranca }}</span>
                        · Início em <span class="tabular-nums">{{ $parcelamento->data_inicio->format('m/Y') }}</span>
                    </p>

                    {{-- Ações --}}
                    @if ($parcelamento->status === 'ativo')
                        <div class="flex items-center gap-2 border-t border-stone-100 pt-4">
                            <button wire:click="abrirModalDispararParcela({{ $parcelamento->id }})"
                                    @disabled(! $podeDisp)
                                    class="flex-1 {{ $podeDisp ? 'btn-primary' : 'btn bg-stone-100 text-stone-500' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                                @if ($podeDisp)
                                    Enviar parcela {{ $enviadas + 1 }}/{{ $total }}
                                @else
                                    Todas enviadas
                                @endif
                            </button>
                            <button wire:click="abrirModalCancelarParcelamento({{ $parcelamento->id }})"
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 text-stone-400 hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition-colors"
                                    title="Cancelar parcelamento" aria-label="Cancelar parcelamento">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @endif

    @endif {{-- fim aba parcelamentos --}}

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ENVIAR COBRANÇAS DO MÊS
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDispararTodas)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Enviar cobranças do mês</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-3 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-sm text-stone-600">
                        Vamos gerar as cobranças PIX pelo Asaas e enviar por WhatsApp e e-mail para
                        <span class="font-semibold text-stone-900">{{ $pacientesAtivos }} paciente(s) ativo(s)</span>.
                    </p>
                    <p class="hint">Quem já tem cobrança neste mês fica de fora.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="dispararTodas" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="dispararTodas">Enviar cobranças</span>
                        <span wire:loading wire:target="dispararTodas">Agendando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ENVIAR COBRANÇA (mensalidade avulsa)
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDisparar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Enviar cobrança</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-sm text-stone-600">Gerar e enviar a cobrança PIX para <span class="font-semibold text-stone-900">{{ $pacienteDispararNome }}</span>.</p>
                    <div class="space-y-1">
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="dispararWhatsapp" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                            <span class="text-sm text-stone-700">Enviar por WhatsApp</span>
                        </label>
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="dispararEmail" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                            <span class="text-sm text-stone-700">Enviar por e-mail</span>
                        </label>
                    </div>
                    <p class="hint">Se já houver cobrança neste mês, nada é enviado de novo.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="confirmarDisparar" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="confirmarDisparar">Enviar cobrança</span>
                        <span wire:loading wire:target="confirmarDisparar">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REENVIAR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalReenviar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Reenviar cobrança</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-sm text-stone-600">Mande de novo o aviso desta cobrança para o paciente.</p>
                    <div class="space-y-1">
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="reenviarWhatsapp" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                            <span class="text-sm text-stone-700">Reenviar por WhatsApp</span>
                        </label>
                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3">
                            <input type="checkbox" wire:model="reenviarEmail" class="h-5 w-5 rounded border-stone-300 text-rose-600 focus:ring-rose-100">
                            <span class="text-sm text-stone-700">Reenviar por e-mail</span>
                        </label>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="confirmarReenviar" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="confirmarReenviar">Reenviar cobrança</span>
                        <span wire:loading wire:target="confirmarReenviar">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: NOVO PARCELAMENTO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalNovoParcelamento)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">Novo parcelamento</h2>
                        <p class="mt-0.5 text-sm text-stone-500">Cada parcela vira uma cobrança PIX no dia escolhido.</p>
                    </div>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="salvarParcelamento" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5 sm:px-6">

                        {{-- Paciente --}}
                        <div>
                            <label for="parc-paciente" class="label">Paciente</label>
                            <select id="parc-paciente" wire:model="formPacienteId"
                                    class="input @error('formPacienteId') !border-red-300 @enderror">
                                <option value="">Selecione o paciente</option>
                                @foreach ($pacientesSelect as $p)
                                    <option value="{{ $p->id }}">{{ $p->nome }}</option>
                                @endforeach
                            </select>
                            @error('formPacienteId') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Descrição --}}
                        <div>
                            <label for="parc-descricao" class="label">Descrição</label>
                            <input id="parc-descricao" type="text" wire:model="formDescricao" placeholder="Ex.: Tratamento a laser"
                                   class="input @error('formDescricao') !border-red-300 @enderror">
                            @error('formDescricao') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Valores --}}
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label for="parc-total" class="label">Valor total (R$)</label>
                                <input id="parc-total" type="number" inputmode="decimal" wire:model="formValorTotal" step="0.01" min="0.01" placeholder="Ex.: 1000,00"
                                       class="input tabular-nums @error('formValorTotal') !border-red-300 @enderror">
                                @error('formValorTotal') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="parc-qtd" class="label">Nº de parcelas</label>
                                <input id="parc-qtd" type="number" inputmode="numeric" wire:model="formTotalParcelas" min="2" max="999" placeholder="Ex.: 10"
                                       class="input tabular-nums @error('formTotalParcelas') !border-red-300 @enderror">
                                @error('formTotalParcelas') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="parc-valor" class="label">Valor da parcela (R$)</label>
                                <input id="parc-valor" type="number" inputmode="decimal" wire:model="formValorParcela" step="0.01" min="0.01" placeholder="Ex.: 100,00"
                                       class="input tabular-nums @error('formValorParcela') !border-red-300 @enderror">
                                @error('formValorParcela') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Dia e data de início --}}
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="parc-dia" class="label">Dia da cobrança</label>
                                <input id="parc-dia" type="number" inputmode="numeric" wire:model="formDiaCobranca" min="1" max="28" placeholder="Ex.: 5"
                                       class="input tabular-nums @error('formDiaCobranca') !border-red-300 @enderror">
                                @error('formDiaCobranca')
                                    <p class="field-error">{{ $message }}</p>
                                @else
                                    <p class="hint">De 1 a 28, para valer em todos os meses.</p>
                                @enderror
                            </div>
                            <div>
                                <label for="parc-inicio" class="label">Data de início</label>
                                <input id="parc-inicio" type="date" wire:model="formDataInicio"
                                       class="input @error('formDataInicio') !border-red-300 @enderror">
                                @error('formDataInicio') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            <span wire:loading.remove wire:target="salvarParcelamento">Salvar parcelamento</span>
                            <span wire:loading wire:target="salvarParcelamento">Salvando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: CANCELAR PARCELAMENTO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCancelarParcelamento)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Cancelar parcelamento</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-3 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-sm text-stone-600">Cancelar este parcelamento? As próximas parcelas não serão enviadas. Essa ação não pode ser desfeita.</p>
                    <p class="rounded-xl bg-stone-50 px-4 py-3 text-sm font-medium text-stone-800">{{ $parcelamentoCancelarNome }}</p>
                    <p class="hint">As cobranças já enviadas continuam valendo.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Voltar</button>
                    <button wire:click="confirmarCancelarParcelamento" wire:loading.attr="disabled" class="btn-danger">
                        <span wire:loading.remove wire:target="confirmarCancelarParcelamento">Cancelar parcelamento</span>
                        <span wire:loading wire:target="confirmarCancelarParcelamento">Cancelando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: ENVIAR PARCELA
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDispararParcela)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Enviar parcela</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-3 overflow-y-auto px-5 py-5 sm:px-6">
                    <p class="text-sm text-stone-600">Gerar e enviar a cobrança PIX de:</p>
                    <p class="rounded-xl bg-stone-50 px-4 py-3 text-sm font-medium text-stone-800">{{ $parcelamentoDispararNome }}</p>
                    <p class="hint">A cobrança é gerada no Asaas e enviada por WhatsApp.</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="confirmarDispararParcela" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="confirmarDispararParcela">Enviar parcela</span>
                        <span wire:loading wire:target="confirmarDispararParcela">Agendando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
