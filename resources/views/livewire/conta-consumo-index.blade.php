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
    <x-ui.page-header titulo="Contas de consumo" subtitulo="Controle as contas de água, luz, telefone e internet da clínica.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nova conta
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- ── Indicadores ──────────────────────────────────────────────── --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-stone-500">A pagar</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">
                R$ {{ number_format($totalPendente, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-stone-500">faturas pendentes e recebidas</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Pago este mês</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums">
                R$ {{ number_format($totalPagoMes, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-stone-500">referente a {{ now()->format('m/Y') }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Faturas vencidas</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $totalVencidas > 0 ? 'text-red-700' : 'text-stone-900' }}">
                {{ $totalVencidas }}
            </p>
            <p class="mt-1 text-xs text-stone-500">{{ $totalVencidas > 0 ? 'pague para evitar juros' : 'tudo em dia' }}</p>
        </div>
    </div>

    @php
        $faturaBadge = [
            'pendente'  => 'bg-amber-50 text-amber-700',
            'recebida'  => 'bg-blue-50 text-blue-700',
            'paga'      => 'bg-emerald-50 text-emerald-700',
            'vencida'   => 'bg-red-50 text-red-700',
            'cancelada' => 'bg-stone-100 text-stone-600',
        ];
        $corMap = [
            'agua'     => 'bg-blue-50 text-blue-700',
            'luz'      => 'bg-amber-50 text-amber-700',
            'gas'      => 'bg-orange-50 text-orange-700',
            'telefone' => 'bg-violet-50 text-violet-700',
            'internet' => 'bg-indigo-50 text-indigo-700',
            'outro'    => 'bg-stone-100 text-stone-600',
        ];
        $iconeMap = [
            'agua'     => 'M12 21a7.5 7.5 0 007.5-7.5c0-4.5-7.5-11.25-7.5-11.25S4.5 9 4.5 13.5A7.5 7.5 0 0012 21z',
            'luz'      => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z',
            'gas'      => 'M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z',
            'telefone' => 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z',
            'internet' => 'M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z',
            'outro'    => 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
        ];
    @endphp

    {{-- ── Contas cadastradas ───────────────────────────────────────── --}}
    <section class="mb-8">
        <h2 class="mb-3 text-base font-semibold text-stone-900">Suas contas</h2>

        @if ($contas->isEmpty())
            <div class="card">
                <x-ui.empty-state
                    titulo="Nenhuma conta cadastrada ainda"
                    texto="Cadastre água, luz, telefone e internet para lançar as faturas todo mês e não perder vencimentos."
                    icone="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z">
                    <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar conta</button>
                </x-ui.empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($contas as $conta)
                    @php
                        $cor = $corMap[$conta->tipo->value] ?? $corMap['outro'];
                        $icone = $iconeMap[$conta->tipo->value] ?? $iconeMap['outro'];
                        $ultimaFatura = $conta->ultimaFatura->first();
                    @endphp
                    <div wire:key="conta-{{ $conta->id }}" class="card flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $cor }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-stone-900">{{ $conta->descricao }}</p>
                                    <p class="text-xs text-stone-500">{{ $conta->tipo->label() }}</p>
                                </div>
                            </div>
                            <span class="badge shrink-0 {{ $conta->status === 'ativo' ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">
                                {{ $conta->status === 'ativo' ? 'Ativa' : 'Inativa' }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-stone-100 pt-4 text-sm">
                            <div>
                                <dt class="text-xs text-stone-500">Vence dia</dt>
                                <dd class="mt-0.5 font-medium text-stone-800 tabular-nums">{{ $conta->dia_vencimento }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-stone-500">Estimado</dt>
                                <dd class="mt-0.5 font-medium text-stone-800 tabular-nums">
                                    {{ $conta->valor_estimado ? 'R$ ' . number_format((float) $conta->valor_estimado, 2, ',', '.') : '—' }}
                                </dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="text-xs text-stone-500">Fornecedor</dt>
                                <dd class="mt-0.5 truncate font-medium text-stone-800">{{ $conta->fornecedor?->nome_fantasia ?? '—' }}</dd>
                            </div>
                        </dl>

                        @if ($ultimaFatura)
                            <div class="mt-3 flex items-center gap-1.5 rounded-xl bg-stone-50 px-3 py-2 text-xs">
                                <span class="text-stone-500">Última fatura:</span>
                                <span class="font-medium text-stone-700">{{ $ultimaFatura->competenciaFormatada() }}</span>
                                <span class="badge {{ $faturaBadge[$ultimaFatura->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $ultimaFatura->status->label() }}</span>
                                @if ($ultimaFatura->valor)
                                    <span class="ml-auto font-semibold text-stone-800 tabular-nums">R$ {{ number_format((float) $ultimaFatura->valor, 2, ',', '.') }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-auto flex gap-2 pt-4">
                            <button type="button" wire:click="abrirModalFatura('{{ $conta->id }}')" class="btn-secondary flex-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Lançar fatura
                            </button>
                            <button type="button" wire:click="abrirModalEditar('{{ $conta->id }}')" class="btn-ghost">
                                Editar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ── Faturas ──────────────────────────────────────────────────── --}}
    <section>
        <h2 class="mb-3 text-base font-semibold text-stone-900">Faturas</h2>

        @php $temFiltro = $filtroStatus !== '' || $filtroContaId !== ''; @endphp

        {{-- Filtros --}}
        <div class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:w-48">
                <option value="">Todos os status</option>
                <option value="pendente">Pendente</option>
                <option value="recebida">Recebida</option>
                <option value="paga">Paga</option>
                <option value="vencida">Vencida</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <select wire:model.live="filtroContaId" aria-label="Filtrar por conta" class="input sm:w-60">
                <option value="">Todas as contas</option>
                @foreach ($contas as $c)
                    <option value="{{ $c->id }}">{{ $c->descricao }}</option>
                @endforeach
            </select>
            @if ($temFiltro)
                <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroContaId', '')" class="btn-ghost">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar filtros
                </button>
            @endif
        </div>

        <div class="card overflow-hidden">
            @if ($faturas->isEmpty())
                @if ($temFiltro)
                    <x-ui.empty-state
                        titulo="Nada encontrado com esses filtros"
                        texto="Nenhuma fatura bate com o que você escolheu. Limpe os filtros para ver todas."
                        icone="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z">
                        <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroContaId', '')" class="btn-secondary">Limpar filtros</button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state
                        titulo="Nenhuma fatura lançada"
                        texto="{{ $contas->isEmpty() ? 'Cadastre uma conta primeiro. Depois é só lançar a fatura de cada mês.' : 'Use “Lançar fatura” no cartão da conta quando a fatura do mês chegar.' }}"
                        icone="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z">
                        @if ($contas->isEmpty())
                            <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar conta</button>
                        @endif
                    </x-ui.empty-state>
                @endif
            @else
                {{-- Celular: cartões --}}
                <ul class="divide-y divide-stone-100 md:hidden">
                    @foreach ($faturas as $fatura)
                        @php
                            $vencida = $fatura->status->value !== 'paga'
                                && $fatura->status->value !== 'cancelada'
                                && $fatura->data_vencimento->isPast();
                        @endphp
                        <li wire:key="fatura-m-{{ $fatura->id }}" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-stone-900">{{ $fatura->contaConsumo->descricao }}</p>
                                    <p class="text-xs text-stone-500">
                                        {{ $fatura->competenciaFormatada() }} ·
                                        <span class="{{ $vencida ? 'font-semibold text-red-700' : '' }}">vence {{ $fatura->data_vencimento->format('d/m/Y') }}</span>
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-semibold text-stone-900 tabular-nums">
                                        {{ $fatura->valor ? 'R$ ' . number_format((float) $fatura->valor, 2, ',', '.') : '—' }}
                                    </p>
                                    <span class="badge mt-1 {{ $faturaBadge[$fatura->status->value] ?? 'bg-stone-100 text-stone-600' }}">{{ $fatura->status->label() }}</span>
                                </div>
                            </div>
                            @if ($fatura->consumoFormatado())
                                <p class="mt-1 text-xs text-stone-500">Consumo: {{ $fatura->consumoFormatado() }}</p>
                            @endif
                            <div class="mt-3 flex items-center gap-2">
                                @if ($fatura->status->podeSerPaga())
                                    <button type="button" wire:click="abrirModalPagar('{{ $fatura->id }}')" class="btn-secondary flex-1">Pagar fatura</button>
                                @elseif ($fatura->transacao_id)
                                    <span class="flex-1 text-xs text-stone-500">Já está nos lançamentos</span>
                                @else
                                    <span class="flex-1"></span>
                                @endif
                                @if ($fatura->arquivo_path)
                                    <a href="{{ route('faturas.arquivo.download', $fatura->id) }}" target="_blank" class="btn-ghost">Ver arquivo</a>
                                @else
                                    <button type="button" wire:click="abrirModalUploadFatura('{{ $fatura->id }}')" class="btn-ghost">Anexar arquivo</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Desktop: tabela --}}
                <table class="hidden w-full text-sm md:table">
                    <thead>
                        <tr class="border-b border-stone-100 bg-stone-50 text-left text-xs font-medium text-stone-500">
                            <th class="px-4 py-3 font-medium">Conta</th>
                            <th class="px-4 py-3 font-medium">Referência</th>
                            <th class="px-4 py-3 font-medium">Vencimento</th>
                            <th class="px-4 py-3 text-right font-medium">Valor</th>
                            <th class="hidden px-4 py-3 text-right font-medium lg:table-cell">Consumo</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium"><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($faturas as $fatura)
                            @php
                                $vencida = $fatura->status->value !== 'paga'
                                    && $fatura->status->value !== 'cancelada'
                                    && $fatura->data_vencimento->isPast();
                            @endphp
                            <tr wire:key="fatura-d-{{ $fatura->id }}" class="transition-colors hover:bg-stone-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-900">{{ $fatura->contaConsumo->descricao }}</p>
                                    <p class="text-xs text-stone-500">{{ $fatura->contaConsumo->tipo->label() }}</p>
                                </td>
                                <td class="px-4 py-3 text-stone-700">{{ $fatura->competenciaFormatada() }}</td>
                                <td class="px-4 py-3 tabular-nums {{ $vencida ? 'font-semibold text-red-700' : 'text-stone-700' }}">
                                    {{ $fatura->data_vencimento->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    @if ($fatura->valor)
                                        <span class="font-semibold text-stone-900">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-stone-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden px-4 py-3 text-right text-xs text-stone-500 tabular-nums lg:table-cell">
                                    {{ $fatura->consumoFormatado() ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $faturaBadge[$fatura->status->value] ?? 'bg-stone-100 text-stone-600' }}">
                                        {{ $fatura->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($fatura->status->podeSerPaga())
                                            <button type="button" wire:click="abrirModalPagar('{{ $fatura->id }}')"
                                                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50">
                                                Pagar
                                            </button>
                                        @elseif ($fatura->transacao_id)
                                            <span class="inline-flex items-center gap-1 px-2 text-xs text-stone-500" title="Já está nos lançamentos">
                                                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                Lançada
                                            </span>
                                        @endif

                                        @if ($fatura->arquivo_path)
                                            <a href="{{ route('faturas.arquivo.download', $fatura->id) }}"
                                               target="_blank"
                                               class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                                               title="Baixar arquivo" aria-label="Baixar arquivo">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                        @else
                                            <button type="button" wire:click="abrirModalUploadFatura('{{ $fatura->id }}')"
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
                @if ($faturas->hasPages())
                    <div class="border-t border-stone-100 px-4 py-3">
                        {{ $faturas->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Nova / editar conta                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-conta"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-lg sm:rounded-2xl" x-trap.noscroll="true">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-conta" class="text-lg font-semibold text-stone-900">
                        {{ $modalCriar ? 'Nova conta de consumo' : 'Editar conta' }}
                    </h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="{{ $modalCriar ? 'salvar' : 'atualizar' }}">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cc-tipo" class="label">Tipo <span class="text-red-600">*</span></label>
                                <select id="cc-tipo" wire:model="tipo" class="input @error('tipo') border-red-300 @enderror">
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cc-dia" class="label">Dia do vencimento <span class="text-red-600">*</span></label>
                                <input id="cc-dia" wire:model="diaVencimento" type="number" min="1" max="31" inputmode="numeric" placeholder="Ex.: 10"
                                       class="input @error('diaVencimento') border-red-300 @enderror">
                                @error('diaVencimento') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="cc-descricao" class="label">Descrição <span class="text-red-600">*</span></label>
                            <input id="cc-descricao" wire:model="descricao" type="text" placeholder="Ex.: Luz da recepção, Vivo Fibra"
                                   class="input @error('descricao') border-red-300 @enderror">
                            @error('descricao') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="cc-fornecedor" class="label">Fornecedor</label>
                                <select id="cc-fornecedor" wire:model="fornecedorId" class="input">
                                    <option value="">Nenhum</option>
                                    @foreach ($fornecedoresAtivos as $f)
                                        <option value="{{ $f->id }}">{{ $f->nome_fantasia }}</option>
                                    @endforeach
                                </select>
                                @error('fornecedorId') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="cc-estimado" class="label">Valor estimado (R$)</label>
                                <input id="cc-estimado" wire:model="valorEstimado" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 350,00"
                                       class="input tabular-nums @error('valorEstimado') border-red-300 @enderror">
                                @error('valorEstimado') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="cc-status" class="label">Situação</label>
                            <select id="cc-status" wire:model="statusConta" class="input">
                                <option value="ativo">Ativa</option>
                                <option value="inativo">Inativa</option>
                            </select>
                        </div>

                        <div>
                            <label for="cc-obs" class="label">Observações</label>
                            <textarea id="cc-obs" wire:model="observacoes" rows="2" placeholder="Ex.: Número do cliente 123456"
                                      class="input resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">
                            {{ $modalCriar ? 'Salvar conta' : 'Salvar alterações' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Lançar fatura                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalFatura)
        @php $contaFatura = $contas->firstWhere('id', $contaFaturaId); @endphp
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-fatura"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-fatura" class="text-lg font-semibold text-stone-900">Lançar fatura</h2>
                        @if ($contaFatura)
                            <p class="truncate text-sm text-stone-500">{{ $contaFatura->descricao }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="lancarFatura">
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="ft-competencia" class="label">Mês de referência <span class="text-red-600">*</span></label>
                                <input id="ft-competencia" wire:model="competencia" type="month" class="input @error('competencia') border-red-300 @enderror">
                                @error('competencia') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ft-vencimento" class="label">Vencimento <span class="text-red-600">*</span></label>
                                <input id="ft-vencimento" wire:model="dataVencimento" type="date" class="input @error('dataVencimento') border-red-300 @enderror">
                                @error('dataVencimento') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="ft-valor" class="label">Valor da fatura (R$)</label>
                            <input id="ft-valor" wire:model="valor" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 287,40"
                                   class="input tabular-nums @error('valor') border-red-300 @enderror">
                            @error('valor')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="hint">Ainda não chegou? Deixe em branco e a fatura fica pendente. Com valor, ela fica como recebida.</p>
                            @enderror
                        </div>

                        @if ($contaFatura && $contaFatura->tipo->consumoLabel())
                            <div>
                                <label for="ft-consumo" class="label">{{ $contaFatura->tipo->consumoLabel() }}</label>
                                <input id="ft-consumo" wire:model="consumoValor" type="number" step="0.01" min="0" inputmode="decimal" placeholder="Ex.: 210"
                                       class="input tabular-nums @error('consumoValor') border-red-300 @enderror">
                                @error('consumoValor') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label for="ft-obs" class="label">Observações</label>
                            <textarea id="ft-obs" wire:model="faturaObs" rows="2" placeholder="Ex.: Leitura estimada pela concessionária"
                                      class="input resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                        <button type="submit" wire:loading.attr="disabled" class="btn-primary">Lançar fatura</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Pagar fatura                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalPagar && $faturaParaPagar)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-pagar"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-sm sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h2 id="titulo-modal-pagar" class="text-lg font-semibold text-stone-900">Registrar pagamento</h2>
                        <p class="truncate text-sm text-stone-500">
                            {{ $faturaParaPagar->contaConsumo->descricao }} · {{ $faturaParaPagar->competenciaFormatada() }}
                        </p>
                    </div>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="pagar">
                    <div class="space-y-4 p-5 sm:p-6">
                        <div>
                            <label for="pf-valor" class="label">Valor pago (R$) <span class="text-red-600">*</span></label>
                            <input id="pf-valor" wire:model="valorPagamento" type="number" step="0.01" min="0.01" inputmode="decimal" placeholder="Ex.: 287,40"
                                   class="input tabular-nums @error('valorPagamento') border-red-300 @enderror">
                            @error('valorPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pf-forma" class="label">Forma de pagamento <span class="text-red-600">*</span></label>
                            <select id="pf-forma" wire:model="formaPagamento" class="input">
                                <option value="pix">Pix</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="credito_1x">Crédito 1x</option>
                                <option value="credito_2x">Crédito 2x</option>
                                <option value="credito_3x">Crédito 3x</option>
                            </select>
                            @error('formaPagamento') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="pf-data" class="label">Data do pagamento <span class="text-red-600">*</span></label>
                            <input id="pf-data" wire:model="dataPagamento" type="date" class="input @error('dataPagamento') border-red-300 @enderror">
                            @error('dataPagamento') <p class="field-error">{{ $message }}</p> @enderror
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

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Anexar arquivo da fatura                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalUploadFatura)
        <div class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-upload"
                 class="relative w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-md sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-upload" class="text-lg font-semibold text-stone-900">Anexar arquivo da fatura</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    <label for="ft-arquivo" class="label">Arquivo <span class="text-red-600">*</span></label>
                    <input id="ft-arquivo" wire:model="arquivoFatura" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx"
                           class="input file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    @error('arquivoFatura')
                        <p class="field-error">{{ $message }}</p>
                    @else
                        <p class="hint">PDF, JPG, PNG ou DOCX, com até 100 MB.</p>
                    @enderror
                    <p wire:loading wire:target="arquivoFatura" class="mt-2 text-xs text-stone-500">Carregando arquivo...</p>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="uploadArquivoFatura" wire:loading.attr="disabled" wire:target="uploadArquivoFatura, arquivoFatura" class="btn-primary">
                        <span wire:loading.remove wire:target="uploadArquivoFatura">Enviar arquivo</span>
                        <span wire:loading wire:target="uploadArquivoFatura">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
