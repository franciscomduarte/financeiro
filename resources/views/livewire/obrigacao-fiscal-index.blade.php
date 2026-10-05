<div>
    {{-- ── Avisos (toast) ───────────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             role="status"
             class="fixed top-4 left-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg sm:left-auto sm:max-w-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             role="alert"
             class="fixed top-4 left-4 right-4 z-50 flex items-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg sm:left-auto sm:max-w-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- ── Cabeçalho ────────────────────────────────────────────────── --}}
    <x-ui.page-header titulo="Obrigações fiscais" subtitulo="Acompanhe as guias de DAS, DARF, INSS, FGTS e ISS e pague tudo em dia.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova obrigação
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- ── Indicadores ──────────────────────────────────────────────── --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-stone-500">A pagar</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-stone-500">guias pendentes e vencidas</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Pago este mês</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums">R$ {{ number_format($totalPagoMes, 2, ',', '.') }}</p>
            <p class="mt-1 text-xs text-stone-500">referente a {{ now()->format('m/Y') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Guias vencidas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $totalVencidas > 0 ? 'text-red-700' : 'text-stone-900' }}">{{ $totalVencidas }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ $totalVencidas > 0 ? 'pague logo para reduzir multa e juros' : 'tudo em dia' }}</p>
        </div>
    </div>

    @php
        $guiaBadge = [
            'pendente'  => 'bg-amber-50 text-amber-700',
            'pago'      => 'bg-emerald-50 text-emerald-700',
            'vencido'   => 'bg-red-50 text-red-700',
            'parcelado' => 'bg-blue-50 text-blue-700',
            'cancelado' => 'bg-stone-100 text-stone-600',
        ];
        $corMap = [
            'das_simples' => 'bg-blue-50 text-blue-700',
            'darf'        => 'bg-red-50 text-red-700',
            'gps_inss'    => 'bg-emerald-50 text-emerald-700',
            'fgts'        => 'bg-teal-50 text-teal-700',
            'iss'         => 'bg-violet-50 text-violet-700',
            'irrf'        => 'bg-orange-50 text-orange-700',
            'outro'       => 'bg-stone-100 text-stone-600',
        ];
        $iconeRecibo = 'M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z';
    @endphp

    {{-- ── Obrigações cadastradas ───────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="mb-3 text-base font-semibold text-stone-900">Suas obrigações</h2>
        @if ($obrigacoes->isEmpty())
            <div class="card">
                <x-ui.empty-state
                    titulo="Nenhuma obrigação cadastrada ainda"
                    texto="Cadastre os tributos que a clínica paga, como DAS e FGTS, para lançar as guias e não perder prazos."
                    :icone="$iconeRecibo">
                    <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar obrigação</button>
                </x-ui.empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($obrigacoes as $ob)
                    @php
                        $cor        = $corMap[$ob->tipo_tributo->value] ?? $corMap['outro'];
                        $ultimoLanc = $ob->ultimoLancamento->first();
                    @endphp
                    <div wire:key="ob-{{ $ob->id }}" class="card flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $cor }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconeRecibo }}"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-stone-900">{{ $ob->descricao }}</p>
                                    <p class="text-xs text-stone-500">{{ $ob->tipo_tributo->label() }}</p>
                                </div>
                            </div>
                            <span class="badge shrink-0 {{ $ob->status === 'ativo' ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                {{ $ob->status === 'ativo' ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-stone-100 pt-4 text-sm">
                            <div>
                                <dt class="text-xs text-stone-500">Período</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ $ob->periodicidade->label() }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-stone-500">Vence dia</dt>
                                <dd class="mt-0.5 font-medium text-stone-800 tabular-nums">{{ $ob->dia_vencimento ?: '—' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-stone-500">Cód. receita</dt>
                                <dd class="mt-0.5 truncate font-medium text-stone-800 tabular-nums">{{ $ob->codigo_receita ?: '—' }}</dd>
                            </div>
                        </dl>

                        @if ($ultimoLanc)
                            <div class="mt-3 flex items-center gap-1.5 rounded-xl bg-stone-50 px-3 py-2 text-xs">
                                <span class="text-stone-500">Última guia:</span>
                                <span class="font-medium text-stone-700">{{ $ultimoLanc->competenciaFormatada() }}</span>
                                <span class="badge {{ $guiaBadge[$ultimoLanc->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $ultimoLanc->status->label() }}</span>
                                <span class="ml-auto font-semibold text-stone-800 tabular-nums">R$ {{ number_format($ultimoLanc->valorTotal(), 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="mt-auto flex gap-2 pt-4">
                            <button type="button" wire:click="abrirModalLancar('{{ $ob->id }}')" class="btn-secondary flex-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Lançar guia
                            </button>
                            <button type="button" wire:click="abrirModalEditar('{{ $ob->id }}')" class="btn-ghost">
                                Editar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── Guias ────────────────────────────────────────────────────── --}}
    <section>
        <h2 class="mb-3 text-base font-semibold text-stone-900">Guias</h2>

        @php $temFiltro = $filtroStatus !== '' || $filtroObrigacaoId !== ''; @endphp

        <div class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:w-48">
                <option value="">Todos os status</option>
                <option value="pendente">Pendente</option>
                <option value="pago">Pago</option>
                <option value="vencido">Vencido</option>
                <option value="parcelado">Parcelado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <select wire:model.live="filtroObrigacaoId" aria-label="Filtrar por obrigação" class="input sm:w-60">
                <option value="">Todas as obrigações</option>
                @foreach ($obrigacoes as $ob)
                    <option value="{{ $ob->id }}">{{ $ob->descricao }}</option>
                @endforeach
            </select>
            @if ($temFiltro)
                <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroObrigacaoId', '')" class="btn-ghost">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar filtros
                </button>
            @endif
        </div>

        <div class="card overflow-hidden">
            @if ($lancamentos->isEmpty())
                @if ($temFiltro)
                    <x-ui.empty-state
                        titulo="Nada encontrado com esses filtros"
                        texto="Nenhuma guia bate com o que você escolheu. Limpe os filtros para ver todas."
                        icone="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z">
                        <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroObrigacaoId', '')" class="btn-secondary">Limpar filtros</button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state
                        titulo="Nenhuma guia lançada"
                        texto="{{ $obrigacoes->isEmpty() ? 'Cadastre uma obrigação primeiro. Depois é só lançar a guia de cada período.' : 'Use “Lançar guia” no cartão da obrigação quando a guia do período sair.' }}"
                        :icone="$iconeRecibo">
                        @if ($obrigacoes->isEmpty())
                            <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar obrigação</button>
                        @endif
                    </x-ui.empty-state>
                @endif
            @else
                {{-- Celular: cartões --}}
                <ul class="divide-y divide-stone-100 md:hidden">
                    @foreach ($lancamentos as $lanc)
                        @php
                            $vencida = $lanc->status->value !== 'pago'
                                && $lanc->status->value !== 'cancelado'
                                && $lanc->data_vencimento->isPast();
                        @endphp
                        <li wire:key="guia-m-{{ $lanc->id }}" class="p-4 {{ $vencida ? 'bg-red-50/40' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-stone-900">{{ $lanc->obrigacaoFiscal->descricao }}</p>
                                    <p class="text-xs text-stone-500">
                                        {{ $lanc->competenciaFormatada() }} ·
                                        <span class="{{ $vencida ? 'font-semibold text-red-700' : '' }}">vence {{ $lanc->data_vencimento->format('d/m/Y') }}</span>
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-semibold text-stone-900 tabular-nums">R$ {{ number_format($lanc->valorTotal(), 2, ',', '.') }}</p>
                                    <span class="badge mt-1 {{ $guiaBadge[$lanc->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $lanc->status->label() }}</span>
                                </div>
                            </div>
                            @if ($lanc->temMultaOuJuros())
                                <p class="mt-1 text-xs text-red-700 tabular-nums">Inclui R$ {{ number_format((float) $lanc->valor_multa + (float) $lanc->valor_juros, 2, ',', '.') }} de multa e juros</p>
                            @endif
                            @if ($lanc->numero_autenticacao)
                                <p class="mt-1 truncate text-xs text-stone-500">Autenticação: <span class="tabular-nums">{{ $lanc->numero_autenticacao }}</span></p>
                            @endif
                            <div class="mt-3 flex items-center gap-2">
                                @if ($lanc->status->podePagar())
                                    <button type="button" wire:click="abrirModalPagar('{{ $lanc->id }}')" class="btn-secondary flex-1">Pagar guia</button>
                                @elseif ($lanc->transacao_id)
                                    <span class="flex-1 text-xs text-stone-500">Já está nos lançamentos</span>
                                @else
                                    <span class="flex-1"></span>
                                @endif
                                @if ($lanc->arquivo_path)
                                    <a href="{{ route('lancamentos-fiscais.arquivo.download', $lanc->id) }}" target="_blank" class="btn-ghost">Ver arquivo</a>
                                @else
                                    <button type="button" wire:click="abrirModalUploadGuia('{{ $lanc->id }}')" class="btn-ghost">Anexar arquivo</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: tabela --}}
                <table class="hidden w-full text-sm md:table">
                    <thead>
                        <tr class="border-b border-stone-100 bg-stone-50 text-left text-xs font-medium text-stone-500">
                            <th class="px-4 py-3 font-medium">Obrigação</th>
                            <th class="px-4 py-3 font-medium">Referência</th>
                            <th class="px-4 py-3 font-medium">Vencimento</th>
                            <th class="hidden px-4 py-3 text-right font-medium xl:table-cell">Principal</th>
                            <th class="hidden px-4 py-3 text-right font-medium xl:table-cell">Multa e juros</th>
                            <th class="px-4 py-3 text-right font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="hidden px-4 py-3 font-medium lg:table-cell">Autenticação</th>
                            <th class="px-4 py-3 text-right font-medium"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($lancamentos as $lanc)
                            @php
                                $vencida = $lanc->status->value !== 'pago'
                                    && $lanc->status->value !== 'cancelado'
                                    && $lanc->data_vencimento->isPast();
                            @endphp
                            <tr wire:key="guia-d-{{ $lanc->id }}" class="transition-colors hover:bg-stone-50 {{ $vencida ? 'bg-red-50/40' : '' }}">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-900">{{ $lanc->obrigacaoFiscal->descricao }}</p>
                                    <p class="text-xs text-stone-500">{{ $lanc->obrigacaoFiscal->tipo_tributo->label() }}</p>
                                </td>
                                <td class="px-4 py-3 text-stone-700">{{ $lanc->competenciaFormatada() }}</td>
                                <td class="px-4 py-3 tabular-nums {{ $vencida ? 'font-semibold text-red-700' : 'text-stone-700' }}">
                                    {{ $lanc->data_vencimento->format('d/m/Y') }}
                                    @if ($vencida)
                                        <div class="text-xs font-normal text-red-700">{{ $lanc->data_vencimento->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums text-stone-700 xl:table-cell">
                                    R$ {{ number_format((float) $lanc->valor_principal, 2, ',', '.') }}
                                </td>
                                <td class="hidden px-4 py-3 text-right tabular-nums xl:table-cell">
                                    @if ($lanc->temMultaOuJuros())
                                        <span class="text-red-700">
                                            R$ {{ number_format((float) $lanc->valor_multa + (float) $lanc->valor_juros, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-stone-900">
                                    R$ {{ number_format($lanc->valorTotal(), 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $guiaBadge[$lanc->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $lanc->status->label() }}</span>
                                </td>
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    @if ($lanc->numero_autenticacao)
                                        <span class="text-xs text-stone-600 tabular-nums">{{ $lanc->numero_autenticacao }}</span>
                                    @else
                                        <span class="text-xs text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($lanc->status->podePagar())
                                            <button type="button" wire:click="abrirModalPagar('{{ $lanc->id }}')"
                                                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50">
                                                Pagar
                                            </button>
                                        @elseif ($lanc->transacao_id)
                                            <span class="inline-flex items-center gap-1 px-2 text-xs text-stone-500" title="Já está nos lançamentos">
                                                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                Lançada
                                            </span>
                                        @endif

                                        @if ($lanc->arquivo_path)
                                            <a href="{{ route('lancamentos-fiscais.arquivo.download', $lanc->id) }}"
                                               target="_blank"
                                               class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                                               title="Baixar arquivo" aria-label="Baixar arquivo">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                        @else
                                            <button type="button" wire:click="abrirModalUploadGuia('{{ $lanc->id }}')"
                                                    class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                                                    title="Anexar arquivo" aria-label="Anexar arquivo">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($lancamentos->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">
                        {{ $lancamentos->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Nova / editar obrigação
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-ob"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-ob" class="text-lg font-semibold text-stone-900">
                        {{ $modalCriar ? 'Nova obrigação fiscal' : 'Editar obrigação' }}
                    </h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="{{ $modalCriar ? 'salvar' : 'atualizar' }}">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ob-tipo" class="label">Tipo de tributo <span class="text-red-600">*</span></label>
                                <select id="ob-tipo" wire:model.live="tipoTributo" class="input @error('tipoTributo') border-red-300 @enderror">
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                                @error('tipoTributo') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ob-periodicidade" class="label">Periodicidade <span class="text-red-600">*</span></label>
                                <select id="ob-periodicidade" wire:model="periodicidade" class="input">
                                    @foreach ($periodicidades as $p)
                                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                    @endforeach
                                </select>
                                @error('periodicidade') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="ob-descricao" class="label">Descrição <span class="text-red-600">*</span></label>
                            <input id="ob-descricao" wire:model="descricao" type="text" placeholder="Ex.: Simples Nacional, DARF IRPJ"
                                   class="input @error('descricao') border-red-300 @enderror">
                            @error('descricao') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ob-codigo" class="label">
                                    Código de receita
                                    @if (in_array($tipoTributo, ['darf', 'irrf']))
                                        <span class="text-red-600">*</span>
                                    @endif
                                </label>
                                <input id="ob-codigo" wire:model="codigoReceita" type="text" maxlength="10" inputmode="numeric" placeholder="Ex.: 6912"
                                       class="input tabular-nums @error('codigoReceita') border-red-300 @enderror">
                                @error('codigoReceita')
                                    <p class="field-error">{{ $message }}</p>
                                @else
                                    <p class="hint">Código de 4 dígitos que vem no DARF.</p>
                                @enderror
                            </div>
                            <div>
                                <label for="ob-dia" class="label">Dia do vencimento</label>
                                <input id="ob-dia" wire:model="diaVencimento" type="number" min="1" max="31" inputmode="numeric" placeholder="Ex.: 20"
                                       class="input @error('diaVencimento') border-red-300 @enderror">
                                @error('diaVencimento') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="ob-status" class="label">Situação</label>
                            <select id="ob-status" wire:model="statusOb" class="input">
                                <option value="ativo">Ativa</option>
                                <option value="inativo">Inativa</option>
                            </select>
                        </div>

                        <div>
                            <label for="ob-obs" class="label">Observações</label>
                            <textarea id="ob-obs" wire:model="observacoes" rows="2" placeholder="Ex.: Guia enviada pelo contador todo dia 10"
                                      class="input resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            {{ $modalCriar ? 'Salvar obrigação' : 'Salvar alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Lançar guia
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalLancar)
        @php $obLancar = $obrigacoes->firstWhere('id', $obrigacaoLancarId); @endphp
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-lancar"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-lancar" class="text-lg font-semibold text-stone-900">Lançar guia</h2>
                        @if ($obLancar)
                            <p class="truncate text-sm text-stone-500">{{ $obLancar->descricao }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="lancarGuia">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="lg-competencia" class="label">Mês de referência <span class="text-red-600">*</span></label>
                                <input id="lg-competencia" wire:model="competencia" type="month" class="input @error('competencia') border-red-300 @enderror">
                                @error('competencia') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="lg-vencimento" class="label">Vencimento <span class="text-red-600">*</span></label>
                                <input id="lg-vencimento" wire:model="dataVencimento" type="date" class="input @error('dataVencimento') border-red-300 @enderror">
                                @error('dataVencimento') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="lg-principal" class="label">Valor principal (R$) <span class="text-red-600">*</span></label>
                            <input id="lg-principal" wire:model="valorPrincipal" type="number" step="0.01" min="0.01" inputmode="decimal" placeholder="Ex.: 1.250,00"
                                   class="input tabular-nums @error('valorPrincipal') border-red-300 @enderror">
                            @error('valorPrincipal') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="lg-multa" class="label">Multa (R$)</label>
                                <input id="lg-multa" wire:model="valorMulta" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 0,00"
                                       class="input tabular-nums @error('valorMulta') border-red-300 @enderror">
                                @error('valorMulta') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="lg-juros" class="label">Juros (R$)</label>
                                <input id="lg-juros" wire:model="valorJuros" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 0,00"
                                       class="input tabular-nums @error('valorJuros') border-red-300 @enderror">
                                @error('valorJuros') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="lg-barras" class="label">Código de barras</label>
                            <input id="lg-barras" wire:model="codigoBarras" type="text" inputmode="numeric" placeholder="Cole aqui a linha digitável da guia"
                                   class="input tabular-nums">
                        </div>

                        <div>
                            <label for="lg-obs" class="label">Observações</label>
                            <textarea id="lg-obs" wire:model="lancarObs" rows="2" placeholder="Ex.: Guia recalculada pelo contador"
                                      class="input resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">Lançar guia</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Registrar pagamento
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalPagar && $lancamentoParaPagar)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-pagar"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-sm sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-pagar" class="text-lg font-semibold text-stone-900">Registrar pagamento</h2>
                        <p class="truncate text-sm text-stone-500">
                            {{ $lancamentoParaPagar->obrigacaoFiscal->descricao }} · {{ $lancamentoParaPagar->competenciaFormatada() }}
                        </p>
                    </div>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="pagar">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                        {{-- Resumo da guia --}}
                        <dl class="space-y-1 rounded-xl bg-stone-50 p-4 text-sm">
                            <div class="flex justify-between text-stone-500">
                                <dt>Principal</dt>
                                <dd class="font-medium text-stone-800 tabular-nums">R$ {{ number_format((float) $lancamentoParaPagar->valor_principal, 2, ',', '.') }}</dd>
                            </div>
                            @if ($lancamentoParaPagar->temMultaOuJuros())
                                <div class="flex justify-between text-stone-500">
                                    <dt>Multa e juros</dt>
                                    <dd class="font-medium text-red-700 tabular-nums">R$ {{ number_format((float) $lancamentoParaPagar->valor_multa + (float) $lancamentoParaPagar->valor_juros, 2, ',', '.') }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between border-t border-stone-200 pt-2 font-semibold text-stone-900">
                                <dt>Total a pagar</dt>
                                <dd class="tabular-nums">R$ {{ number_format($lancamentoParaPagar->valorTotal(), 2, ',', '.') }}</dd>
                            </div>
                        </dl>

                        {{-- Multa/juros, se a guia ainda não tiver --}}
                        @if (!$lancamentoParaPagar->temMultaOuJuros())
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="pg-multa" class="label">Multa (R$)</label>
                                    <input id="pg-multa" wire:model="pagarValorMulta" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 0,00"
                                           class="input tabular-nums @error('pagarValorMulta') border-red-300 @enderror">
                                    @error('pagarValorMulta') <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="pg-juros" class="label">Juros (R$)</label>
                                    <input id="pg-juros" wire:model="pagarValorJuros" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 0,00"
                                           class="input tabular-nums @error('pagarValorJuros') border-red-300 @enderror">
                                    @error('pagarValorJuros') <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        <div>
                            <label for="pg-forma" class="label">Forma de pagamento <span class="text-red-600">*</span></label>
                            <select id="pg-forma" wire:model="formaPagamento" class="input">
                                <option value="pix">Pix ou TED</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito em conta</option>
                                <option value="dinheiro">Dinheiro</option>
                            </select>
                            @error('formaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pg-data" class="label">Data do pagamento <span class="text-red-600">*</span></label>
                            <input id="pg-data" wire:model="dataPagamento" type="date" class="input @error('dataPagamento') border-red-300 @enderror">
                            @error('dataPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pg-autenticacao" class="label">Número de autenticação</label>
                            <input id="pg-autenticacao" wire:model="numeroAutenticacao" type="text" maxlength="50" placeholder="Ex.: código do comprovante do banco"
                                   class="input tabular-nums @error('numeroAutenticacao') border-red-300 @enderror">
                            @error('numeroAutenticacao')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="mt-1 text-xs font-medium text-amber-700">Guarde este número: ele é pedido em auditorias.</p>
                            @enderror
                        </div>

                        <p class="rounded-xl bg-stone-50 px-4 py-3 text-xs text-stone-500">
                            Ao confirmar, criamos uma saída nos lançamentos automaticamente.
                        </p>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">Registrar pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Anexar arquivo da guia
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalUploadGuia)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-upload"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-upload" class="text-lg font-semibold text-stone-900">Anexar arquivo da guia</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    <label for="lg-arquivo" class="label">Arquivo <span class="text-red-600">*</span></label>
                    <input id="lg-arquivo" wire:model="arquivoGuia" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx"
                           class="input file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    @error('arquivoGuia')
                        <p class="field-error">{{ $message }}</p>
                    @else
                        <p class="hint">PDF, JPG, PNG ou DOCX, com até 10 MB.</p>
                    @enderror
                    <p wire:loading wire:target="arquivoGuia" class="mt-2 text-xs text-stone-500">Carregando arquivo...</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="uploadArquivoGuia" wire:loading.attr="disabled" wire:target="uploadArquivoGuia, arquivoGuia" class="btn-primary">
                        <span wire:loading.remove wire:target="uploadArquivoGuia">Enviar arquivo</span>
                        <span wire:loading wire:target="uploadArquivoGuia">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
