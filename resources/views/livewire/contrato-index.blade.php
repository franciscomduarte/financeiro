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
            <h1 class="text-xl font-bold text-slate-800">Contratos</h1>
            <p class="mt-0.5 text-sm text-slate-500">Gerencie contratos com fornecedores</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Contrato
        </button>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Contratos Ativos</p>
            <p class="mt-1 text-2xl font-bold text-slate-800">{{ $ativos }}</p>
            <p class="mt-0.5 text-xs text-slate-500">em vigência</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Valor Mensal Total</p>
            <p class="mt-1 text-2xl font-bold text-rose-600 tabular-nums">
                R$ {{ number_format($valorTotal, 2, ',', '.') }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">contratos ativos</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Alertas</p>
            <p class="mt-1 text-2xl font-bold {{ $alertasCount > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                {{ $alertasCount }}
            </p>
            <p class="mt-0.5 text-xs text-slate-500">vencimento/reajuste próximos</p>
        </div>
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────────── --}}
    <div class="mb-4 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="em_negociacao">Em Negociação</option>
                <option value="encerrado">Encerrado</option>
                <option value="suspenso">Suspenso</option>
            </select>
            <select wire:model.live="filtroRisco"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os riscos</option>
                <option value="baixo">Baixo</option>
                <option value="medio">Médio</option>
                <option value="alto">Alto</option>
            </select>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 {{ $alertas ? 'border-amber-300 bg-amber-50 text-amber-700' : '' }}">
                <input wire:model.live="alertas" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-400">
                <svg class="h-4 w-4 {{ $alertas ? 'text-amber-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Apenas alertas
            </label>
            @if ($filtroStatus !== '' || $filtroRisco !== '' || $alertas)
                <button wire:click="$set('filtroStatus', ''); $set('filtroRisco', ''); $set('alertas', false)"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-500 hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar
                </button>
            @endif
        </div>
    </div>

    {{-- ── Tabela ───────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-4 py-3">Fornecedor</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Valor / Início</th>
                        <th class="hidden px-4 py-3 md:table-cell">Vencimento</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="hidden px-4 py-3 lg:table-cell">Risco</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($contratos as $contrato)
                        @php
                            $venceEm60      = $contrato->data_fim && $contrato->data_fim->lte(now()->addDays(60));
                            $reajusteEm30   = $contrato->data_proximo_reajuste && $contrato->data_proximo_reajuste->lte(now()->addDays(30));
                            $diasVenc       = $contrato->diasParaVencimento();
                            $pagamentoEm5   = $diasVenc !== null && $diasVenc <= 5;
                            $temAlerta      = $venceEm60 || $reajusteEm30 || $pagamentoEm5;
                        @endphp
                        <tr class="group hover:bg-slate-50/50 transition-colors {{ $temAlerta ? 'bg-amber-50/30' : '' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($temAlerta)
                                        <svg class="h-4 w-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                        </svg>
                                    @endif
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $contrato->fornecedor?->nome_fantasia ?? '—' }}</div>
                                        @if ($contrato->fornecedor?->categoria)
                                            <div class="text-xs text-slate-400">{{ $contrato->fornecedor->categoria }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <div class="font-semibold tabular-nums text-slate-800">
                                    R$ {{ number_format((float) $contrato->getRawOriginal('valor_mensal'), 2, ',', '.') }}
                                </div>
                                @if ($contrato->data_inicio)
                                    <div class="text-xs text-slate-400">desde {{ $contrato->data_inicio->format('d/m/Y') }}</div>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                @if ($contrato->data_fim)
                                    <div class="{{ $venceEm60 ? 'font-semibold text-amber-700' : 'text-slate-700' }}">
                                        {{ $contrato->data_fim->format('d/m/Y') }}
                                    </div>
                                    @if ($venceEm60)
                                        <div class="text-xs text-amber-600">em {{ $contrato->data_fim->diffForHumans() }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if ($contrato->dia_vencimento)
                                    @php $proximo = $contrato->proximoVencimento(); @endphp
                                    <div class="mt-1 flex items-center gap-1 text-xs {{ $pagamentoEm5 ? 'font-semibold text-rose-600' : 'text-slate-400' }}">
                                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @if ($pagamentoEm5)
                                            Pgto {{ $proximo?->format('d/m') }} ({{ $diasVenc === 0 ? 'hoje' : 'em ' . $diasVenc . 'd' }})
                                        @else
                                            Pgto mensal dia {{ $contrato->dia_vencimento }}
                                        @endif
                                    </div>
                                    @if ($contrato->pagamentos_max_competencia)
                                        @php
                                            [$pAno, $pMes] = explode('-', $contrato->pagamentos_max_competencia);
                                            $pMeses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
                                            $pLabel = $pMeses[(int)$pMes - 1] . '/' . $pAno;
                                        @endphp
                                        <div class="mt-0.5 flex items-center gap-1 text-xs text-emerald-600">
                                            <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Pago {{ $pLabel }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusCor = match($contrato->status->value) {
                                        'ativo'         => 'bg-emerald-50 text-emerald-700',
                                        'em_negociacao' => 'bg-blue-50 text-blue-700',
                                        'suspenso'      => 'bg-amber-50 text-amber-700',
                                        default         => 'bg-slate-100 text-slate-500',
                                    };
                                    $statusDot = match($contrato->status->value) {
                                        'ativo'         => 'bg-emerald-500',
                                        'em_negociacao' => 'bg-blue-500',
                                        'suspenso'      => 'bg-amber-500',
                                        default         => 'bg-slate-400',
                                    };
                                    $statusLabel = match($contrato->status->value) {
                                        'ativo'         => 'Ativo',
                                        'em_negociacao' => 'Em Negoc.',
                                        'suspenso'      => 'Suspenso',
                                        default         => 'Encerrado',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium {{ $statusCor }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                @php
                                    $riscoCor = match($contrato->risco->value) {
                                        'alto'  => 'bg-red-50 text-red-700',
                                        'medio' => 'bg-amber-50 text-amber-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <span class="inline-block rounded-md px-2 py-0.5 text-xs font-medium capitalize {{ $riscoCor }}">
                                    {{ ucfirst($contrato->risco->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button wire:click="abrirDetalhe('{{ $contrato->id }}')"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Detalhes">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="abrirModalEditar('{{ $contrato->id }}')"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @if ($contrato->status->value === 'ativo' && $contrato->dia_vencimento !== null)
                                        <button wire:click="abrirModalPagarContrato('{{ $contrato->id }}')"
                                                class="rounded-md p-1.5 text-slate-400 hover:bg-emerald-50 hover:text-emerald-600" title="Registrar Pagamento">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </button>
                                    @endif
                                    <button wire:click="abrirModalReajuste('{{ $contrato->id }}')"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-blue-50 hover:text-blue-600" title="Registrar Reajuste">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    </button>
                                    <button wire:click="abrirModalArquivo('{{ $contrato->id }}')"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-violet-50 hover:text-violet-600" title="Enviar Arquivo">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    </button>
                                    <button wire:click="abrirModalExcluir('{{ $contrato->id }}')"
                                            class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-medium">Nenhum contrato encontrado</p>
                                    @if ($filtroStatus !== '' || $filtroRisco !== '' || $alertas)
                                        <p class="text-xs">Tente ajustar os filtros</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($contratos->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $contratos->links() }}
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: CRIAR / EDITAR CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">
                        {{ $modalCriar ? 'Novo Contrato' : 'Editar Contrato' }}
                    </h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6 space-y-4">
                    {{-- Fornecedor + Status + Risco --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Fornecedor</label>
                            <select wire:model="fornecedorId"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="">Selecione um fornecedor</option>
                                @foreach ($fornecedoresAtivos as $f)
                                    <option value="{{ $f->id }}">{{ $f->nome_fantasia }}</option>
                                @endforeach
                            </select>
                            @error('fornecedorId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                            <select wire:model="status"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="ativo">Ativo</option>
                                <option value="em_negociacao">Em Negociação</option>
                                <option value="suspenso">Suspenso</option>
                                <option value="encerrado">Encerrado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Risco</label>
                            <select wire:model="risco"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="baixo">Baixo</option>
                                <option value="medio">Médio</option>
                                <option value="alto">Alto</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Valor Mensal (R$) <span class="text-red-500">*</span></label>
                            <input wire:model="valorMensal" type="number" step="0.01" min="0"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('valorMensal') border-red-400 @enderror"
                                   placeholder="0,00">
                            @error('valorMensal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Data de Início</label>
                            <input wire:model="dataInicio" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Data de Encerramento</label>
                            <input wire:model="dataFim" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('dataFim') border-red-400 @enderror">
                            @error('dataFim') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Dia do Pagamento Mensal</label>
                            <input wire:model="diaVencimento" type="number" min="1" max="31" placeholder="Ex: 10"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('diaVencimento') border-red-400 @enderror">
                            @error('diaVencimento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            <p class="mt-1 text-xs text-slate-400">Deixe vazio se não houver pagamento mensal.</p>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Reajuste</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Periodicidade</label>
                            <select wire:model="periodicidadeReajuste"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="">Sem reajuste</option>
                                <option value="anual">Anual</option>
                                <option value="semestral">Semestral</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Índice</label>
                            <select wire:model="indiceReajuste"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="">Não definido</option>
                                <option value="igpm">IGPM</option>
                                <option value="ipca">IPCA</option>
                                <option value="inpc">INPC</option>
                                <option value="fixo">Fixo</option>
                                <option value="livre">Livre</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Próximo Reajuste</label>
                            <input wire:model="dataProximoReajuste" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Rescisão</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Multa (R$)</label>
                            <input wire:model="multaRescisaoValor" type="number" step="0.01" min="0"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                   placeholder="0,00">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Multa (%)</label>
                            <input wire:model="multaRescisaoPercentual" type="number" step="0.01" min="0" max="100"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                   placeholder="0,00">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Aviso Prévio (dias)</label>
                            <input wire:model="avisoPrevioDias" type="number" min="0"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                   placeholder="30">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Link do Contrato (URL)</label>
                        <input wire:model="linkContrato" type="url"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('linkContrato') border-red-400 @enderror"
                               placeholder="https://drive.google.com/...">
                        @error('linkContrato') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Observações</label>
                        <textarea wire:model="observacoes" rows="2"
                                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                  placeholder="Notas sobre este contrato..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    @if ($modalCriar)
                        <button wire:click="salvar" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                            <span wire:loading.remove wire:target="salvar">Salvar</span>
                            <span wire:loading wire:target="salvar">Salvando...</span>
                        </button>
                    @else
                        <button wire:click="atualizar" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                            <span wire:loading.remove wire:target="atualizar">Salvar alterações</span>
                            <span wire:loading wire:target="atualizar">Salvando...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHE CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $contratoDetalhe)
        @php $c = $contratoDetalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ $c->fornecedor?->nome_fantasia ?? 'Contrato' }}</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-400">Status</p>
                            <p class="font-medium text-slate-700">{{ ucfirst(str_replace('_', ' ', $c->status->value)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Risco</p>
                            <p class="font-medium text-slate-700">{{ ucfirst($c->risco->value) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400">Valor Mensal</p>
                            <p class="font-semibold tabular-nums text-slate-800">R$ {{ number_format((float) $c->getRawOriginal('valor_mensal'), 2, ',', '.') }}</p>
                        </div>
                        @if ($c->data_inicio)
                            <div>
                                <p class="text-xs text-slate-400">Início</p>
                                <p class="font-medium text-slate-700">{{ $c->data_inicio->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if ($c->data_fim)
                            <div>
                                <p class="text-xs text-slate-400">Vencimento</p>
                                <p class="font-medium {{ $c->data_fim->lte(now()->addDays(60)) ? 'text-amber-700' : 'text-slate-700' }}">
                                    {{ $c->data_fim->format('d/m/Y') }}
                                </p>
                            </div>
                        @endif
                        @if ($c->indice_reajuste)
                            <div>
                                <p class="text-xs text-slate-400">Índice de Reajuste</p>
                                <p class="font-medium text-slate-700">{{ strtoupper($c->indice_reajuste->value) }}</p>
                            </div>
                        @endif
                        @if ($c->data_proximo_reajuste)
                            <div>
                                <p class="text-xs text-slate-400">Próximo Reajuste</p>
                                <p class="font-medium {{ $c->data_proximo_reajuste->lte(now()->addDays(30)) ? 'text-amber-700' : 'text-slate-700' }}">
                                    {{ $c->data_proximo_reajuste->format('d/m/Y') }}
                                </p>
                            </div>
                        @endif
                        @if ($c->aviso_previo_dias)
                            <div>
                                <p class="text-xs text-slate-400">Aviso Prévio</p>
                                <p class="font-medium text-slate-700">{{ $c->aviso_previo_dias }} dias</p>
                            </div>
                        @endif
                        @if ($c->temArquivo())
                            <div class="col-span-2">
                                <p class="text-xs text-slate-400">Arquivo</p>
                                <p class="font-medium text-slate-700">{{ $c->arquivo_contrato_nome }}</p>
                            </div>
                        @endif
                        @if ($c->link_contrato)
                            <div class="col-span-2">
                                <p class="text-xs text-slate-400">Link do Contrato</p>
                                <a href="{{ $c->link_contrato }}" target="_blank" rel="noopener noreferrer"
                                   class="font-medium text-rose-600 hover:underline break-all">{{ $c->link_contrato }}</a>
                            </div>
                        @endif
                    </div>

                    @if ($c->pagamentos->count() > 0)
                        <div class="border-t border-slate-100 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Histórico de Pagamentos</p>
                            <div class="space-y-2">
                                @foreach ($c->pagamentos->take(6) as $pgto)
                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                        <div>
                                            <span class="font-medium text-slate-700">{{ $pgto->competenciaFormatada() }}</span>
                                            <span class="mx-1.5 text-slate-300">·</span>
                                            <span class="text-xs text-slate-500">{{ $pgto->data_pagamento->format('d/m/Y') }}</span>
                                        </div>
                                        <span class="font-semibold tabular-nums text-slate-800">
                                            R$ {{ number_format((float) $pgto->getRawOriginal('valor'), 2, ',', '.') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($c->reajustes->count() > 0)
                        <div class="border-t border-slate-100 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Histórico de Reajustes</p>
                            <div class="space-y-2">
                                @foreach ($c->reajustes as $r)
                                    <div class="rounded-lg bg-slate-50 p-2.5 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($r->data_reajuste)->format('d/m/Y') }}</span>
                                            <span class="font-semibold {{ $r->percentual_efetivo >= 0 ? 'text-emerald-600' : 'text-red-600' }} tabular-nums">
                                                {{ $r->percentual_efetivo >= 0 ? '+' : '' }}{{ number_format($r->percentual_efetivo, 2) }}%
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500 tabular-nums">
                                            R$ {{ number_format($r->valor_anterior, 2, ',', '.') }} → R$ {{ number_format($r->valor_novo, 2, ',', '.') }}
                                            @if ($r->indice) · {{ strtoupper($r->indice) }} @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($c->observacoes)
                        <div>
                            <p class="mb-1 text-xs text-slate-400">Observações</p>
                            <p class="text-sm text-slate-600">{{ $c->observacoes }}</p>
                        </div>
                    @endif
                </div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Fechar</button>
                    @if ($c->status->value === 'ativo' && $c->dia_vencimento !== null)
                        <button wire:click="abrirModalPagarContrato('{{ $c->id }}')" class="rounded-lg border border-emerald-200 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Registrar Pagamento</button>
                    @endif
                    <button wire:click="abrirModalReajuste('{{ $c->id }}')" class="rounded-lg border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">Reajuste</button>
                    <button wire:click="abrirModalEditar('{{ $c->id }}')" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">Editar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REAJUSTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalReajuste)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Registrar Reajuste</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Data do Reajuste <span class="text-red-500">*</span></label>
                            <input wire:model="dataReajuste" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('dataReajuste') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Índice</label>
                            <select wire:model="indiceReajusteOp"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="">Livre</option>
                                <option value="igpm">IGPM</option>
                                <option value="ipca">IPCA</option>
                                <option value="inpc">INPC</option>
                                <option value="fixo">Fixo</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Novo Valor Mensal (R$) <span class="text-red-500">*</span></label>
                        <input wire:model="valorNovo" type="number" step="0.01" min="0.01"
                               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('valorNovo') border-red-400 @enderror"
                               placeholder="0,00">
                        @error('valorNovo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Observações</label>
                        <textarea wire:model="observacoesReajuste" rows="2"
                                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                  placeholder="Justificativa do reajuste..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="registrarReajuste" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        <span wire:loading.remove wire:target="registrarReajuste">Confirmar Reajuste</span>
                        <span wire:loading wire:target="registrarReajuste">Registrando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REGISTRAR PAGAMENTO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalPagarContrato)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Registrar Pagamento</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Competência <span class="text-red-500">*</span></label>
                            <input wire:model="pgCompetencia" type="month"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('pgCompetencia') border-red-400 @enderror">
                            @error('pgCompetencia') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Data do Pagamento <span class="text-red-500">*</span></label>
                            <input wire:model="pgDataPagamento" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('pgDataPagamento') border-red-400 @enderror">
                            @error('pgDataPagamento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Valor (R$) <span class="text-red-500">*</span></label>
                            <input wire:model="pgValor" type="number" step="0.01" min="0.01"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('pgValor') border-red-400 @enderror"
                                   placeholder="0,00">
                            @error('pgValor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Forma de Pagamento <span class="text-red-500">*</span></label>
                            <select wire:model="pgFormaPagamento"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="pix">PIX</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito</option>
                                <option value="credito_1x">Crédito 1x</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="credito_2x">Crédito 2x</option>
                                <option value="credito_3x">Crédito 3x</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Observações</label>
                        <textarea wire:model="pgObservacoes" rows="2"
                                  class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                                  placeholder="Notas sobre este pagamento..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="pagarContrato" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        <span wire:loading.remove wire:target="pagarContrato">Confirmar Pagamento</span>
                        <span wire:loading wire:target="pagarContrato">Registrando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: EXCLUIR CONTRATO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalExcluir)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-sm rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Excluir contrato</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Tem certeza que deseja excluir o contrato com
                                <span class="font-medium text-slate-700">{{ $contratoExcluirNome }}</span>?
                                Todos os pagamentos e reajustes vinculados também serão removidos.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="excluir" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                        <span wire:loading.remove wire:target="excluir">Excluir</span>
                        <span wire:loading wire:target="excluir">Excluindo...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: UPLOAD ARQUIVO
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalArquivo)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Enviar Arquivo do Contrato</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-3 rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs text-blue-700">
                        Formatos aceitos: PDF, JPG, JPEG, PNG, DOCX. Tamanho máximo: 10 MB.
                    </div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Arquivo <span class="text-red-500">*</span></label>
                    <input wire:model="arquivoContrato" type="file" accept=".pdf,.jpg,.jpeg,.png,.docx"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
                    @error('arquivoContrato') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="arquivoContrato" class="mt-2 text-xs text-slate-500">Carregando arquivo...</div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="uploadArquivo" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        <span wire:loading.remove wire:target="uploadArquivo">Enviar</span>
                        <span wire:loading wire:target="uploadArquivo">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
