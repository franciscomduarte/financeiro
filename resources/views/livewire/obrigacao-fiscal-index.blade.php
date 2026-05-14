<div>
    {{-- ── Flash ─────────────────────────────────────────────────────── --}}
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
            <h1 class="text-xl font-bold text-slate-800">Obrigações Fiscais</h1>
            <p class="mt-0.5 text-sm text-slate-500">DARF, DAS Simples, GPS, FGTS, ISS e outros tributos</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Obrigação
        </button>
    </div>

    {{-- ── Stats ─────────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">A Pagar (pendente/vencido)</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 tabular-nums">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">guias em aberto</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pago este mês</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 tabular-nums">R$ {{ number_format($totalPagoMes, 2, ',', '.') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">competência {{ now()->format('m/Y') }}</p>
        </div>
        <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Guias Vencidas</p>
            <p class="mt-1 text-2xl font-bold {{ $totalVencidas > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $totalVencidas }}</p>
            <p class="mt-0.5 text-xs text-slate-500">requer atenção imediata</p>
        </div>
    </div>

    {{-- ── Obrigações cadastradas ───────────────────────────────────── --}}
    <div class="mb-6">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Obrigações cadastradas</h2>
        @if ($obrigacoes->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                <p class="mt-3 text-sm text-slate-500">Nenhuma obrigação fiscal cadastrada.</p>
                <button wire:click="abrirModalCriar" class="mt-3 text-sm font-medium text-rose-600 hover:text-rose-700">Cadastrar primeira obrigação →</button>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($obrigacoes as $ob)
                    @php
                        $corMap = [
                            'das_simples' => ['bg' => 'bg-blue-100',    'text' => 'text-blue-600',    'ring' => 'ring-blue-200'],
                            'darf'        => ['bg' => 'bg-red-100',     'text' => 'text-red-600',     'ring' => 'ring-red-200'],
                            'gps_inss'    => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'ring' => 'ring-emerald-200'],
                            'fgts'        => ['bg' => 'bg-teal-100',    'text' => 'text-teal-600',    'ring' => 'ring-teal-200'],
                            'iss'         => ['bg' => 'bg-violet-100',  'text' => 'text-violet-600',  'ring' => 'ring-violet-200'],
                            'irrf'        => ['bg' => 'bg-orange-100',  'text' => 'text-orange-600',  'ring' => 'ring-orange-200'],
                            'outro'       => ['bg' => 'bg-slate-100',   'text' => 'text-slate-600',   'ring' => 'ring-slate-200'],
                        ];
                        $cor            = $corMap[$ob->tipo_tributo->value] ?? $corMap['outro'];
                        $ultimoLanc     = $ob->ultimoLancamento->first();
                    @endphp
                    <div class="flex flex-col rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $cor['bg'] }} ring-1 {{ $cor['ring'] }}">
                                    <svg class="h-5 w-5 {{ $cor['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ $ob->descricao }}</p>
                                    <p class="text-xs text-slate-400">{{ $ob->tipo_tributo->label() }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $ob->status === 'ativo' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $ob->status === 'ativo' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">PERÍODO</p>
                                <p class="text-xs font-medium text-slate-700">{{ $ob->periodicidade->label() }}</p>
                            </div>
                            @if ($ob->dia_vencimento)
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">VENCE DIA</p>
                                <p class="text-xs font-semibold text-slate-700">{{ $ob->dia_vencimento }}</p>
                            </div>
                            @endif
                            @if ($ob->codigo_receita)
                            <div class="rounded-md bg-slate-50 px-2.5 py-1.5">
                                <p class="text-slate-400 leading-none mb-0.5" style="font-size:10px">CÓD. RECEITA</p>
                                <p class="text-xs font-medium font-mono text-slate-700">{{ $ob->codigo_receita }}</p>
                            </div>
                            @endif
                        </div>

                        @if ($ultimoLanc)
                            @php
                                $statusBadge = [
                                    'pendente'  => 'bg-amber-50 text-amber-700',
                                    'pago'      => 'bg-emerald-50 text-emerald-700',
                                    'vencido'   => 'bg-red-50 text-red-700',
                                    'parcelado' => 'bg-blue-50 text-blue-700',
                                    'cancelado' => 'bg-slate-100 text-slate-500',
                                ][$ultimoLanc->status->value] ?? 'bg-slate-100 text-slate-500';
                            @endphp
                            <div class="mt-3 flex items-center gap-1.5 text-xs">
                                <span class="text-slate-400">Última:</span>
                                <span class="font-medium text-slate-600">{{ $ultimoLanc->competenciaFormatada() }}</span>
                                <span class="rounded-full px-1.5 py-0.5 {{ $statusBadge }}">{{ $ultimoLanc->status->label() }}</span>
                                <span class="ml-auto font-semibold text-slate-700">R$ {{ number_format($ultimoLanc->valorTotal(), 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="mt-3 flex gap-2 border-t border-slate-50 pt-3">
                            <button wire:click="abrirModalLancar('{{ $ob->id }}')"
                                    class="flex flex-1 items-center justify-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Lançar Guia
                            </button>
                            <button wire:click="abrirModalEditar('{{ $ob->id }}')"
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

    {{-- ── Lançamentos ──────────────────────────────────────────────── --}}
    <div>
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">Histórico de Guias</h2>

        <div class="mb-3 flex flex-col gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row sm:flex-wrap sm:items-center">
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os status</option>
                <option value="pendente">Pendente</option>
                <option value="pago">Pago</option>
                <option value="vencido">Vencido</option>
                <option value="parcelado">Parcelado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <select wire:model.live="filtroObrigacaoId"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todas as obrigações</option>
                @foreach ($obrigacoes as $ob)
                    <option value="{{ $ob->id }}">{{ $ob->descricao }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
            @if ($lancamentos->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">Nenhuma guia encontrada.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="px-4 py-3 text-left">Obrigação</th>
                                <th class="px-4 py-3 text-left">Competência</th>
                                <th class="px-4 py-3 text-left">Vencimento</th>
                                <th class="px-4 py-3 text-right">Principal</th>
                                <th class="px-4 py-3 text-right">Multa/Juros</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Autenticação</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($lancamentos as $lanc)
                                @php
                                    $statusBadge = [
                                        'pendente'  => 'bg-amber-50 text-amber-700',
                                        'pago'      => 'bg-emerald-50 text-emerald-700',
                                        'vencido'   => 'bg-red-50 text-red-700',
                                        'parcelado' => 'bg-blue-50 text-blue-700',
                                        'cancelado' => 'bg-slate-100 text-slate-500',
                                    ][$lanc->status->value] ?? 'bg-slate-100 text-slate-500';
                                    $vencida = $lanc->status->value !== 'pago'
                                        && $lanc->status->value !== 'cancelado'
                                        && $lanc->data_vencimento->isPast();
                                @endphp
                                <tr class="group transition hover:bg-slate-50/60 {{ $vencida ? 'bg-red-50/20' : '' }}">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-800">{{ $lanc->obrigacaoFiscal->descricao }}</p>
                                        <p class="text-xs text-slate-400">{{ $lanc->obrigacaoFiscal->tipo_tributo->label() }}</p>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ $lanc->competenciaFormatada() }}</td>
                                    <td class="px-4 py-3 {{ $vencida ? 'font-semibold text-red-600' : 'text-slate-600' }}">
                                        {{ $lanc->data_vencimento->format('d/m/Y') }}
                                        @if ($vencida)
                                            <div class="text-xs text-red-500">{{ $lanc->data_vencimento->diffForHumans() }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums text-slate-700">
                                        R$ {{ number_format((float)$lanc->valor_principal, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">
                                        @if ($lanc->temMultaOuJuros())
                                            <span class="text-red-600">
                                                R$ {{ number_format((float)$lanc->valor_multa + (float)$lanc->valor_juros, 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-800">
                                        R$ {{ number_format($lanc->valorTotal(), 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $statusBadge }}">{{ $lanc->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($lanc->numero_autenticacao)
                                            <span class="font-mono text-xs text-slate-600">{{ $lanc->numero_autenticacao }}</span>
                                        @else
                                            <span class="text-xs text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($lanc->status->podePagar())
                                            <button wire:click="abrirModalPagar('{{ $lanc->id }}')"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Pagar
                                            </button>
                                        @elseif ($lanc->transacao_id)
                                            <span class="text-xs text-slate-400">Lançado ✓</span>
                                        @else
                                            <span class="text-xs text-slate-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    {{ $lancamentos->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Criar / Editar Obrigação
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalCriar || $modalEditar)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">
                        {{ $modalCriar ? 'Nova Obrigação Fiscal' : 'Editar Obrigação' }}
                    </h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="{{ $modalCriar ? 'salvar' : 'atualizar' }}" class="divide-y divide-slate-50">
                    <div class="space-y-4 px-6 py-5">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Tipo de Tributo <span class="text-red-500">*</span></label>
                                <select wire:model.live="tipoTributo"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                                @error('tipoTributo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Periodicidade <span class="text-red-500">*</span></label>
                                <select wire:model="periodicidade"
                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                    @foreach ($periodicidades as $p)
                                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Descrição <span class="text-red-500">*</span></label>
                            <input wire:model="descricao" type="text" placeholder="Ex: SIMPLES NACIONAL, DARF - IRPJ..."
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('descricao') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">
                                    Código de Receita
                                    @if (in_array($tipoTributo, ['darf', 'irrf']))
                                        <span class="text-red-500">*</span>
                                    @endif
                                </label>
                                <input wire:model="codigoReceita" type="text" maxlength="10" placeholder="Ex: 6912"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <p class="mt-1 text-xs text-slate-400">Código de 4 dígitos do DARF.</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Dia de Vencimento</label>
                                <input wire:model="diaVencimento" type="number" min="1" max="31" placeholder="Ex: 20"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                @error('diaVencimento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                            <select wire:model="statusOb"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Observações</label>
                            <textarea wire:model="observacoes" rows="2"
                                      class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</button>
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                            {{ $modalCriar ? 'Cadastrar' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Lançar Guia
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalLancar)
        @php $obLancar = $obrigacoes->firstWhere('id', $obrigacaoLancarId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Lançar Guia</h2>
                        @if ($obLancar)
                            <p class="text-xs text-slate-500">{{ $obLancar->descricao }}</p>
                        @endif
                    </div>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="lancarGuia" class="divide-y divide-slate-50">
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
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Valor Principal (R$) <span class="text-red-500">*</span></label>
                            <input wire:model="valorPrincipal" type="number" step="0.01" min="0.01" placeholder="0,00"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            @error('valorPrincipal') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Multa (R$)</label>
                                <input wire:model="valorMulta" type="number" step="0.01" min="0" placeholder="0,00"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Juros (R$)</label>
                                <input wire:model="valorJuros" type="number" step="0.01" min="0" placeholder="0,00"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Código de Barras</label>
                            <input wire:model="codigoBarras" type="text" placeholder="Linha digitável da guia"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-mono text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Observações</label>
                            <textarea wire:model="lancarObs" rows="2"
                                      class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</button>
                        <button type="submit"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">Lançar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Registrar Pagamento
    ═══════════════════════════════════════════════════════════ --}}
    @if ($modalPagar && $lancamentoParaPagar)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Registrar Pagamento</h2>
                        <p class="text-xs text-slate-500">
                            {{ $lancamentoParaPagar->obrigacaoFiscal->descricao }} — {{ $lancamentoParaPagar->competenciaFormatada() }}
                        </p>
                    </div>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="pagar" class="divide-y divide-slate-50">
                    <div class="space-y-4 px-6 py-5">

                        {{-- Resumo da guia --}}
                        <div class="rounded-lg bg-slate-50 p-3 text-xs">
                            <div class="flex justify-between text-slate-500">
                                <span>Principal</span>
                                <span class="font-medium text-slate-700">R$ {{ number_format((float)$lancamentoParaPagar->valor_principal, 2, ',', '.') }}</span>
                            </div>
                            @if ($lancamentoParaPagar->temMultaOuJuros())
                                <div class="mt-1 flex justify-between text-slate-500">
                                    <span>Multa + Juros</span>
                                    <span class="font-medium text-red-600">R$ {{ number_format((float)$lancamentoParaPagar->valor_multa + (float)$lancamentoParaPagar->valor_juros, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            <div class="mt-1 flex justify-between border-t border-slate-200 pt-1 font-semibold text-slate-700">
                                <span>Total a pagar</span>
                                <span>R$ {{ number_format($lancamentoParaPagar->valorTotal(), 2, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Campos adicionais de multa/juros se necessário --}}
                        @if (!$lancamentoParaPagar->temMultaOuJuros())
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Multa (R$)</label>
                                    <input wire:model="pagarValorMulta" type="number" step="0.01" min="0" placeholder="0,00"
                                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">Juros (R$)</label>
                                    <input wire:model="pagarValorJuros" type="number" step="0.01" min="0" placeholder="0,00"
                                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Forma de Pagamento <span class="text-red-500">*</span></label>
                            <select wire:model="formaPagamento"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                                <option value="pix">Pix / TED</option>
                                <option value="boleto">Boleto</option>
                                <option value="debito">Débito Bancário</option>
                                <option value="dinheiro">Dinheiro</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Data do Pagamento <span class="text-red-500">*</span></label>
                            <input wire:model="dataPagamento" type="date"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">
                                Nº de Autenticação
                                <span class="ml-1 text-amber-600 font-normal">(guarde — exigido em auditoria)</span>
                            </label>
                            <input wire:model="numeroAutenticacao" type="text" maxlength="50" placeholder="Código de autenticação bancária"
                                   class="w-full rounded-lg border border-amber-200 bg-amber-50/50 px-3 py-2 text-sm font-mono text-slate-700 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                            @error('numeroAutenticacao') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-xs text-slate-500">
                            Uma transação de saída será criada automaticamente no módulo financeiro.
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 px-6 py-4">
                        <button type="button" wire:click="fecharModais"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Cancelar</button>
                        <button type="submit"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Confirmar Pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
