<div>
    {{-- ── Flash messages ──────────────────────────────────────────── --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- ── Cabeçalho ────────────────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Contas de Consumo</h1>
            <p class="mt-0.5 text-sm text-slate-500">Água, luz, telefone e outros serviços recorrentes</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Conta
        </button>
    </div>

    {{-- ── Stats ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">A Pagar (pendente)</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 tabular-nums">
                R$ {{ number_format($totalPendente, 2, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">faturas recebidas/pendentes</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pago este mês</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 tabular-nums">
                R$ {{ number_format($totalPagoMes, 2, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">competência {{ now()->format('m/Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Faturas Vencidas</p>
            <p class="mt-1 text-2xl font-bold {{ $totalVencidas > 0 ? 'text-red-600' : 'text-slate-400' }}">
                {{ $totalVencidas }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">requer atenção</p>
        </div>
    </div>

    {{-- ── Contas cadastradas ────────────────────────────────────────── --}}
    <div class="mb-6">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Contas cadastradas</h2>

        @if ($contas->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-3 text-sm text-slate-500">Nenhuma conta cadastrada ainda.</p>
                <button wire:click="abrirModalCriar" class="mt-3 text-sm font-medium text-rose-600 hover:text-rose-700">Cadastrar primeira conta →</button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($contas as $conta)
                    @php
                        $corMap = [
                            'agua'     => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'ring' => 'ring-blue-200'],
                            'luz'      => ['bg' => 'bg-amber-100',  'text' => 'text-amber-600',  'ring' => 'ring-amber-200'],
                            'gas'      => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'ring' => 'ring-orange-200'],
                            'telefone' => ['bg' => 'bg-violet-100', 'text' => 'text-violet-600', 'ring' => 'ring-violet-200'],
                            'internet' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'ring' => 'ring-indigo-200'],
                            'outro'    => ['bg' => 'bg-slate-100',  'text' => 'text-slate-600',  'ring' => 'ring-slate-200'],
                        ];
                        $cor = $corMap[$conta->tipo->value] ?? $corMap['outro'];
                        $ultimaFatura = $conta->ultimaFatura->first();
                    @endphp
                    <div class="flex flex-col rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $cor['bg'] }} ring-1 {{ $cor['ring'] }}">
                                    @if ($conta->tipo->value === 'agua')
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18a8 8 0 110-16 8 8 0 010 16zm-1-11h2v6h-2zm0-4h2v2h-2z"/></svg>
                                    @elseif ($conta->tipo->value === 'luz')
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @elseif ($conta->tipo->value === 'gas')
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                    @elseif ($conta->tipo->value === 'telefone')
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    @elseif ($conta->tipo->value === 'internet')
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                                    @else
                                        <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $conta->descricao }}</p>
                                    <p class="text-xs text-slate-400">{{ $conta->tipo->label() }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $conta->status === 'ativo' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $conta->status === 'ativo' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">VENCE DIA</p>
                                <p class="text-xs font-semibold text-slate-700">{{ $conta->dia_vencimento }}</p>
                            </div>
                            @if ($conta->fornecedor)
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5 min-w-0">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">FORNECEDOR</p>
                                <p class="text-xs font-medium text-slate-700 truncate max-w-28">{{ $conta->fornecedor->nome_fantasia }}</p>
                            </div>
                            @endif
                            @if ($conta->valor_estimado)
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">ESTIMADO</p>
                                <p class="text-xs font-medium text-slate-700">R$ {{ number_format((float)$conta->valor_estimado, 2, ',', '.') }}</p>
                            </div>
                            @endif
                        </div>

                        @if ($ultimaFatura)
                            <div class="mt-3 flex items-center gap-1.5 text-xs">
                                <span class="text-slate-400">Última:</span>
                                <span class="font-medium text-slate-600">{{ $ultimaFatura->competenciaFormatada() }}</span>
                                @php
                                    $statusBadge = [
                                        'pendente'  => 'bg-amber-50 text-amber-700',
                                        'recebida'  => 'bg-blue-50 text-blue-700',
                                        'paga'      => 'bg-emerald-50 text-emerald-700',
                                        'vencida'   => 'bg-red-50 text-red-700',
                                        'cancelada' => 'bg-slate-100 text-slate-500',
                                    ][$ultimaFatura->status->value] ?? 'bg-slate-100 text-slate-500';
                                @endphp
                                <span class="rounded-full px-1.5 py-0.5 {{ $statusBadge }}">{{ $ultimaFatura->status->label() }}</span>
                                @if ($ultimaFatura->valor)
                                    <span class="ml-auto font-semibold text-slate-700">R$ {{ number_format((float)$ultimaFatura->valor, 2, ',', '.') }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-3 flex gap-2 border-t border-slate-50 pt-3">
                            <button wire:click="abrirModalFatura('{{ $conta->id }}')"
                                    class="flex flex-1 items-center justify-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Lançar Fatura
                            </button>
                            <button wire:click="abrirModalEditar('{{ $conta->id }}')"
                                    class="flex items-center justify-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Editar
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Faturas ───────────────────────────────────────────────────── --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Faturas</h2>

        {{-- Filtros --}}
        <div class="mb-3 flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os status</option>
                <option value="pendente">Pendente</option>
                <option value="recebida">Recebida</option>
                <option value="paga">Paga</option>
                <option value="vencida">Vencida</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <select wire:model.live="filtroContaId"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todas as contas</option>
                @foreach ($contas as $c)
                    <option value="{{ $c->id }}">{{ $c->descricao }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tabela --}}
        <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
            @if ($faturas->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">Nenhuma fatura encontrada.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="px-4 py-3 text-left">Conta</th>
                                <th class="px-4 py-3 text-left">Competência</th>
                                <th class="px-4 py-3 text-left">Vencimento</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                                <th class="px-4 py-3 text-left">Consumo</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($faturas as $fatura)
                                @php
                                    $statusBadge = [
                                        'pendente'  => 'bg-amber-50 text-amber-700',
                                        'recebida'  => 'bg-blue-50 text-blue-700',
                                        'paga'      => 'bg-emerald-50 text-emerald-700',
                                        'vencida'   => 'bg-red-50 text-red-700',
                                        'cancelada' => 'bg-slate-100 text-slate-500',
                                    ][$fatura->status->value] ?? 'bg-slate-100 text-slate-500';
                                    $vencida = $fatura->status->value !== 'paga'
                                        && $fatura->status->value !== 'cancelada'
                                        && $fatura->data_vencimento->isPast();
                                @endphp
                                <tr class="group transition hover:bg-slate-50/60">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-800">{{ $fatura->contaConsumo->descricao }}</p>
                                        <p class="text-xs text-slate-400">{{ $fatura->contaConsumo->tipo->label() }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $fatura->competenciaFormatada() }}</td>
                                    <td class="px-4 py-3 {{ $vencida ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                        {{ $fatura->data_vencimento->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        @if ($fatura->valor)
                                            <span class="font-semibold text-slate-800">R$ {{ number_format((float)$fatura->valor, 2, ',', '.') }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-500">
                                        {{ $fatura->consumoFormatado() ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $statusBadge }}">
                                            {{ $fatura->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($fatura->status->podeSerPaga())
                                                <button wire:click="abrirModalPagar('{{ $fatura->id }}')"
                                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Pagar
                                                </button>
                                            @elseif ($fatura->transacao_id)
                                                <span class="text-xs text-slate-400">Lançado ✓</span>
                                            @else
                                                <span class="text-xs text-slate-300">—</span>
                                            @endif

                                            @if ($fatura->arquivo_path)
                                                <a href="{{ route('faturas.arquivo.download', $fatura->id) }}"
                                                   target="_blank"
                                                   class="rounded-md p-1.5 text-slate-400 hover:bg-blue-50 hover:text-blue-600"
                                                   title="Baixar arquivo">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                </a>
                                            @else
                                                <button wire:click="abrirModalUploadFatura('{{ $fatura->id }}')"
                                                        class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                                        title="Enviar arquivo">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $faturas->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Criar Conta                                             --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl" x-trap.noscroll="true">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">
                        {{ $modalCriar ? 'Nova Conta de Consumo' : 'Editar Conta' }}
                    </h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="{{ $modalCriar ? 'salvar' : 'atualizar' }}" class="divide-y divide-slate-50">
                    <div class="space-y-4 px-6 py-5">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Tipo <span class="text-red-500">*</span></label>
                                <select wire:model="tipo" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Dia de Vencimento <span class="text-red-500">*</span></label>
                                <input wire:model="diaVencimento" type="number" min="1" max="31" placeholder="Ex: 10"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('diaVencimento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Descrição <span class="text-red-500">*</span></label>
                            <input wire:model="descricao" type="text" placeholder="Ex: Água Caesb, Luz CEB, Vivo Fibra..."
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('descricao') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Fornecedor</label>
                                <select wire:model="fornecedorId" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    <option value="">Nenhum</option>
                                    @foreach ($fornecedoresAtivos as $f)
                                        <option value="{{ $f->id }}">{{ $f->nome_fantasia }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Valor Estimado (R$)</label>
                                <input wire:model="valorEstimado" type="number" step="0.01" min="0" placeholder="0,00"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('valorEstimado') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                            <select wire:model="statusConta" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Observações</label>
                            <textarea wire:model="observacoes" rows="2" placeholder="Informações adicionais..."
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                            {{ $modalCriar ? 'Cadastrar' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Lançar Fatura                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalFatura)
        @php $contaFatura = $contas->firstWhere('id', $contaFaturaId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Lançar Fatura</h2>
                        @if ($contaFatura)
                            <p class="text-xs text-slate-500">{{ $contaFatura->descricao }}</p>
                        @endif
                    </div>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="lancarFatura" class="divide-y divide-slate-50">
                    <div class="space-y-4 px-6 py-5">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Competência <span class="text-red-500">*</span></label>
                                <input wire:model="competencia" type="month"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('competencia') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Data de Vencimento <span class="text-red-500">*</span></label>
                                <input wire:model="dataVencimento" type="date"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('dataVencimento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Valor da Fatura (R$)</label>
                            <input wire:model="valor" type="number" step="0.01" min="0" placeholder="Deixe em branco se ainda não chegou"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('valor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">Sem valor → status <em>Pendente</em>. Com valor → status <em>Recebida</em>.</p>
                        </div>

                        @if ($contaFatura && $contaFatura->tipo->consumoLabel())
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">{{ $contaFatura->tipo->consumoLabel() }}</label>
                                <input wire:model="consumoValor" type="number" step="0.01" min="0" placeholder="Ex: 210"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('consumoValor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Observações</label>
                            <textarea wire:model="faturaObs" rows="2" placeholder="Notas sobre esta fatura..."
                                      class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                            Lançar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Pagar Fatura                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalPagar && $faturaParaPagar)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Registrar Pagamento</h2>
                        <p class="text-xs text-slate-500">
                            {{ $faturaParaPagar->contaConsumo->descricao }} — {{ $faturaParaPagar->competenciaFormatada() }}
                        </p>
                    </div>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="pagar" class="divide-y divide-slate-50">
                    <div class="space-y-4 px-6 py-5">

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Valor Pago (R$) <span class="text-red-500">*</span></label>
                            <input wire:model="valorPagamento" type="number" step="0.01" min="0.01" placeholder="0,00"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('valorPagamento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Forma de Pagamento <span class="text-red-500">*</span></label>
                            <select wire:model="formaPagamento" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="pix">Pix</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="credito_1x">Crédito 1x</option>
                                <option value="credito_2x">Crédito 2x</option>
                                <option value="credito_3x">Crédito 3x</option>
                            </select>
                            @error('formaPagamento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Data do Pagamento <span class="text-red-500">*</span></label>
                            <input wire:model="dataPagamento" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('dataPagamento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-500">
                            Uma transação de saída será criada automaticamente no módulo financeiro.
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Confirmar Pagamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- MODAL: Upload Arquivo de Fatura                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @if ($modalUploadFatura)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Enviar Arquivo da Fatura</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-700">
                        Formatos aceitos: PDF, JPG, JPEG, PNG, DOCX. Tamanho máximo: 10 MB.
                    </div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Arquivo <span class="text-red-500">*</span></label>
                    <input wire:model="arquivoFatura" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    @error('arquivoFatura') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="arquivoFatura" class="mt-2 text-xs text-slate-500">Carregando arquivo...</div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="uploadArquivoFatura" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        <span wire:loading.remove wire:target="uploadArquivoFatura">Enviar</span>
                        <span wire:loading wire:target="uploadArquivoFatura">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
