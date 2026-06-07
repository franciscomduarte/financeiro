<div class="space-y-6">

    {{-- Flash: Sucesso --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ $flashSucesso }}
        </div>
    @endif

    {{-- Flash: Erro --}}
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 flex items-center gap-2 rounded-lg bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ $flashErro }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Cobranças</h1>
            <p class="text-sm text-stone-500 mt-0.5">Mensalidades PIX via Asaas · {{ Carbon\Carbon::now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if ($pacientesAtivos === 0)
                <div class="flex items-center gap-2 rounded-xl bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-700">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Nenhum paciente com mensalidade PIX cadastrada.
                    <a href="{{ route('pacientes.index') }}" class="font-semibold underline hover:text-amber-900">Editar pacientes →</a>
                </div>
            @else
                <button wire:click="dispararTodas" wire:loading.attr="disabled"
                        wire:confirm="Disparar cobranças para {{ $pacientesAtivos }} paciente(s) com mensalidade PIX?"
                        class="flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-60 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span wire:loading.remove wire:target="dispararTodas">Disparar para {{ $pacientesAtivos }} paciente(s)</span>
                    <span wire:loading wire:target="dispararTodas">Agendando...</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Stats do mês atual --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
        <div class="rounded-xl border border-stone-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-stone-500">Total (mês)</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $totalMes }}</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-600">Pagas</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $pagosMes }}</p>
        </div>
        <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-amber-600">Pendentes</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $pendentesMes }}</p>
        </div>
        <div class="rounded-xl border border-red-100 bg-red-50/50 p-4 shadow-sm">
            <p class="text-xs font-medium text-red-600">Vencidas</p>
            <p class="mt-1 text-2xl font-bold text-red-700">{{ $vencidosMes }}</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-white p-4 shadow-sm col-span-2 sm:col-span-1">
            <p class="text-xs font-medium text-stone-500">Aptos p/ cobrança</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $pacientesAtivos }}</p>
            <p class="text-xs text-stone-400 mt-0.5">pacientes PIX ativos</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="rounded-2xl border border-stone-100 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.400ms="busca"
                       type="text"
                       placeholder="Buscar por nome do paciente..."
                       class="w-full rounded-lg border border-stone-200 py-2 pl-9 pr-3 text-sm text-stone-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
            </div>
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100 sm:w-40">
                <option value="">Todos os status</option>
                <option value="PENDING">Pendente</option>
                <option value="RECEIVED">Pago</option>
                <option value="OVERDUE">Vencido</option>
                <option value="REFUNDED">Estornado</option>
            </select>
            <input wire:model.live="filtroMes" type="month"
                   class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100 sm:w-44"
                   value="{{ $mesAtual }}">
            @if ($busca || $filtroStatus || ($filtroMes && $filtroMes !== $mesAtual))
                <button wire:click="$set('busca', ''); $set('filtroStatus', ''); $set('filtroMes', '')"
                        class="text-sm text-rose-600 hover:text-rose-700 font-medium whitespace-nowrap">
                    Limpar
                </button>
            @endif
        </div>
    </div>

    {{-- Tabela --}}
    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
        @if ($cobrancas->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="h-10 w-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
                <p class="text-stone-500 font-medium">Nenhuma cobrança encontrada</p>
                <p class="text-stone-400 text-sm mt-1">Use "Disparar Todas" para gerar as cobranças do mês</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 bg-stone-50/60">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Paciente</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 sm:table-cell">Mês Ref.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Valor</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 md:table-cell">Vencimento</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Status</th>
                            <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 lg:table-cell">Envios</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach ($cobrancas as $cobranca)
                            @php
                                $statusMap = [
                                    'PENDING'  => ['Pendente',  'text-amber-700 bg-amber-50 border-amber-200'],
                                    'RECEIVED' => ['Pago',      'text-emerald-700 bg-emerald-50 border-emerald-200'],
                                    'OVERDUE'  => ['Vencido',   'text-red-700 bg-red-50 border-red-200'],
                                    'REFUNDED' => ['Estornado', 'text-slate-600 bg-slate-100 border-slate-200'],
                                    'DELETED'  => ['Deletado',  'text-stone-500 bg-stone-100 border-stone-200'],
                                ];
                                [$statusLabel, $statusCls] = $statusMap[$cobranca->status] ?? ['Desconhecido', 'text-stone-500 bg-stone-100 border-stone-200'];
                            @endphp
                            <tr class="group hover:bg-stone-50/50 transition-colors">
                                {{-- Paciente --}}
                                <td class="px-4 py-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-stone-800 truncate">{{ $cobranca->paciente?->nome ?? '—' }}</p>
                                        @if ($cobranca->link_fatura)
                                            <a href="{{ $cobranca->link_fatura }}" target="_blank"
                                               class="text-xs text-rose-500 hover:text-rose-700 truncate hidden sm:block">Ver fatura ↗</a>
                                        @endif
                                    </div>
                                </td>
                                {{-- Mês referência --}}
                                <td class="hidden px-4 py-3 text-stone-500 sm:table-cell">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $cobranca->mes_referencia)->translatedFormat('M/Y') }}
                                </td>
                                {{-- Valor --}}
                                <td class="px-4 py-3 text-right font-semibold text-stone-800">
                                    R$ {{ number_format((float) $cobranca->valor, 2, ',', '.') }}
                                </td>
                                {{-- Vencimento --}}
                                <td class="hidden px-4 py-3 text-stone-600 md:table-cell">
                                    @if ($cobranca->pago_em)
                                        <span class="text-emerald-600 text-xs font-medium">Pago {{ $cobranca->pago_em->format('d/m/Y') }}</span>
                                    @else
                                        <span class="{{ $cobranca->vencimento->isPast() && $cobranca->status === 'PENDING' ? 'text-red-600 font-medium' : 'text-stone-600' }}">
                                            {{ $cobranca->vencimento->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusCls }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                {{-- Envios --}}
                                <td class="hidden px-4 py-3 lg:table-cell">
                                    <div class="flex items-center gap-1.5">
                                        @if ($cobranca->whatsapp_enviado_em)
                                            <span title="WhatsApp enviado {{ $cobranca->whatsapp_enviado_em->format('d/m H:i') }}"
                                                  class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-xs text-emerald-700">
                                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.946-1.418A9.954 9.954 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" fill-rule="evenodd" clip-rule="evenodd"/></svg>
                                                WA
                                            </span>
                                        @endif
                                        @if ($cobranca->email_enviado_em)
                                            <span title="E-mail enviado {{ $cobranca->email_enviado_em->format('d/m H:i') }}"
                                                  class="inline-flex items-center gap-1 rounded-full bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-xs text-blue-700">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                Email
                                            </span>
                                        @endif
                                        @if (!$cobranca->whatsapp_enviado_em && !$cobranca->email_enviado_em)
                                            <span class="text-xs text-stone-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                {{-- Ações --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        @if ($cobranca->status !== 'RECEIVED' && $cobranca->status !== 'DELETED')
                                            <button wire:click="abrirModalReenviar({{ $cobranca->id }})"
                                                    class="rounded-lg p-1.5 text-stone-400 hover:bg-emerald-50 hover:text-emerald-600 transition-colors"
                                                    title="Reenviar cobrança">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                            </button>
                                        @endif
                                    </div>
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

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DISPARAR MANUAL
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDisparar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Disparar Cobrança</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-stone-600">Gerar e enviar cobrança PIX para <span class="font-semibold text-stone-800">{{ $pacienteDispararNome }}</span>.</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="dispararWhatsapp"
                                   class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-stone-700">Enviar por WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="dispararEmail"
                                   class="h-4 w-4 rounded border-stone-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-stone-700">Enviar por E-mail</span>
                        </label>
                    </div>
                    <p class="text-xs text-stone-400">Se já existe cobrança para o mês atual, o disparo será ignorado automaticamente.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="confirmarDisparar" wire:loading.attr="disabled"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 transition-colors">
                        <span wire:loading.remove wire:target="confirmarDisparar">Disparar</span>
                        <span wire:loading wire:target="confirmarDisparar">Aguarde...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: REENVIAR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalReenviar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-sm rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Reenviar Cobrança</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-stone-600">Reenviar notificação da cobrança existente.</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="reenviarWhatsapp"
                                   class="h-4 w-4 rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm text-stone-700">Reenviar por WhatsApp</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="reenviarEmail"
                                   class="h-4 w-4 rounded border-stone-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-stone-700">Reenviar por E-mail</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="confirmarReenviar" wire:loading.attr="disabled"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 transition-colors">
                        <span wire:loading.remove wire:target="confirmarReenviar">Reenviar</span>
                        <span wire:loading wire:target="confirmarReenviar">Enviando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
