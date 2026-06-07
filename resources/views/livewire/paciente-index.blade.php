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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-900">Pacientes</h1>
            <p class="text-sm text-stone-500 mt-0.5">Gerencie os pacientes da clínica</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Paciente
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-stone-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-stone-500">Total</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $totalCount }}</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-stone-500">Ativos</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $ativosCount }}</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-stone-500">Inativos</p>
            <p class="mt-1 text-2xl font-bold text-stone-400">{{ $totalCount - $ativosCount }}</p>
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
                       placeholder="Buscar por nome, CPF, telefone..."
                       class="w-full rounded-lg border border-stone-200 py-2 pl-9 pr-3 text-sm text-stone-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
            </div>
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100 sm:w-44">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
            </select>
            @if ($busca || $filtroStatus)
                <button wire:click="$set('busca', ''); $set('filtroStatus', '')"
                        class="text-sm text-rose-600 hover:text-rose-700 font-medium whitespace-nowrap">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Tabela --}}
    <div class="rounded-2xl border border-stone-100 bg-white shadow-sm overflow-hidden">
        @if ($pacientes->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <svg class="h-10 w-10 text-stone-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-stone-500 font-medium">Nenhum paciente encontrado</p>
                <p class="text-stone-400 text-sm mt-1">Cadastre o primeiro paciente clicando em "Novo Paciente"</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50/60">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Paciente</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 sm:table-cell">CPF</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 md:table-cell">Telefone</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500 lg:table-cell">Mensalidade</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-stone-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-stone-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @foreach ($pacientes as $paciente)
                        <tr class="group hover:bg-stone-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    {{-- Avatar --}}
                                    @if ($paciente->foto_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paciente->foto_path) }}"
                                             class="h-9 w-9 rounded-full object-cover shrink-0"
                                             loading="lazy" alt="">
                                    @else
                                        <div class="h-9 w-9 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                                            <span class="text-sm font-bold text-rose-600">{{ strtoupper(substr($paciente->nome, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-medium text-stone-800 truncate">{{ $paciente->nome }}</p>
                                        @if ($paciente->email)
                                            <p class="text-xs text-stone-400 truncate hidden sm:block">{{ $paciente->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 text-stone-600 sm:table-cell">
                                {{ $paciente->cpf ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-stone-600 md:table-cell">
                                {{ $paciente->telefone ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                @if ($paciente->valor_mensalidade > 0)
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-medium text-stone-700">R$ {{ number_format((float) $paciente->valor_mensalidade, 2, ',', '.') }}</span>
                                        @php
                                            $badgeMap = ['pix' => ['PIX','text-emerald-700 bg-emerald-50'], 'cartao' => ['Cartão','text-blue-700 bg-blue-50'], 'dinheiro' => ['Dinheiro','text-amber-700 bg-amber-50'], 'boleto' => ['Boleto','text-slate-700 bg-slate-100']];
                                            [$label, $cls] = $badgeMap[$paciente->forma_pagamento] ?? ['—','text-stone-400 bg-stone-50'];
                                        @endphp
                                        <span class="rounded-full px-1.5 py-0.5 text-xs font-medium {{ $cls }}">{{ $label }}</span>
                                    </div>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($paciente->status->value === 'ativo')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span> Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="abrirDetalhe('{{ $paciente->id }}')"
                                            class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" title="Ver detalhes">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="abrirModalEditar('{{ $paciente->id }}')"
                                            class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($pacientes->hasPages())
                <div class="border-t border-stone-100 px-4 py-3">
                    {{ $pacientes->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: CRIAR PACIENTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Novo Paciente</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6 space-y-4">
                    @include('livewire.partials.paciente-form')
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="salvar" wire:loading.attr="disabled"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 transition-colors">
                        <span wire:loading.remove wire:target="salvar">Cadastrar Paciente</span>
                        <span wire:loading wire:target="salvar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: EDITAR PACIENTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalEditar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Editar Paciente</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6 space-y-4">
                    @include('livewire.partials.paciente-form')
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button wire:click="atualizar" wire:loading.attr="disabled"
                            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60 transition-colors">
                        <span wire:loading.remove wire:target="atualizar">Salvar Alterações</span>
                        <span wire:loading wire:target="atualizar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHE PACIENTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $this->pacienteDetalhe)
        @php $p = $this->pacienteDetalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-xl rounded-2xl bg-white shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">{{ $p->nome }}</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[75vh] overflow-y-auto p-6 space-y-5">
                    {{-- Avatar + dados básicos --}}
                    <div class="flex items-start gap-4">
                        @if ($p->foto_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->foto_path) }}"
                                 class="h-16 w-16 rounded-2xl object-cover shrink-0" alt="">
                        @else
                            <div class="h-16 w-16 rounded-2xl bg-rose-100 flex items-center justify-center shrink-0">
                                <span class="text-xl font-bold text-rose-600">{{ strtoupper(substr($p->nome, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-semibold text-stone-900 text-base">{{ $p->nome }}</p>
                            @if ($p->status->value === 'ativo')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 mt-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ativo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-500 mt-1">
                                    <span class="h-1.5 w-1.5 rounded-full bg-stone-400"></span> Inativo
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Dados de contato --}}
                    <div class="grid grid-cols-2 gap-4 bg-stone-50 rounded-xl p-4">
                        @if ($p->cpf)
                            <div>
                                <p class="text-xs text-stone-400">CPF</p>
                                <p class="text-sm font-medium text-stone-700">{{ $p->cpf }}</p>
                            </div>
                        @endif
                        @if ($p->data_nascimento)
                            <div>
                                <p class="text-xs text-stone-400">Data de Nascimento</p>
                                <p class="text-sm font-medium text-stone-700">{{ $p->data_nascimento->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if ($p->telefone)
                            <div>
                                <p class="text-xs text-stone-400">Telefone</p>
                                <p class="text-sm font-medium text-stone-700">{{ $p->telefone }}</p>
                            </div>
                        @endif
                        @if ($p->email)
                            <div>
                                <p class="text-xs text-stone-400">E-mail</p>
                                <p class="text-sm font-medium text-stone-700 truncate">{{ $p->email }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Cobrança --}}
                    @if ($p->valor_mensalidade > 0 || $p->forma_pagamento)
                        <div>
                            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">Cobrança Mensal</p>
                            <div class="grid grid-cols-2 gap-4 bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                                <div>
                                    <p class="text-xs text-stone-400">Mensalidade</p>
                                    <p class="text-sm font-semibold text-emerald-700">R$ {{ number_format((float) $p->valor_mensalidade, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-stone-400">Forma de Pagamento</p>
                                    @php
                                        $labelMap = ['pix' => 'PIX', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro', 'boleto' => 'Boleto'];
                                    @endphp
                                    <p class="text-sm font-medium text-stone-700">{{ $labelMap[$p->forma_pagamento] ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Anamnese --}}
                    @if ($p->anamnese)
                        <div>
                            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">Anamnese / Notas Clínicas</p>
                            <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                                <p class="text-sm text-stone-700 whitespace-pre-line">{{ $p->anamnese }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Observações --}}
                    @if ($p->observacoes)
                        <div>
                            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">Observações</p>
                            <p class="text-sm text-stone-600 whitespace-pre-line">{{ $p->observacoes }}</p>
                        </div>
                    @endif

                    {{-- Histórico de transações --}}
                    <div>
                        <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">Histórico de Transações</p>
                        @if ($p->transacoes->isEmpty())
                            <p class="text-sm text-stone-400">Nenhuma transação vinculada.</p>
                        @else
                            <div class="space-y-2">
                                @foreach ($p->transacoes as $t)
                                    <div class="flex items-center justify-between rounded-xl bg-stone-50 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="text-sm text-stone-700 font-medium truncate">{{ $t->descricao }}</p>
                                            <p class="text-xs text-stone-400">{{ $t->data_competencia->format('d/m/Y') }}</p>
                                        </div>
                                        <div class="text-right shrink-0 ml-3">
                                            <p class="text-sm font-semibold {{ $t->tipo->value === 'entrada' ? 'text-emerald-600' : 'text-rose-600' }}">
                                                {{ $t->tipo->value === 'entrada' ? '+' : '-' }} R$ {{ number_format((float) $t->valor_liquido, 2, ',', '.') }}
                                            </p>
                                            <span class="text-xs {{ $t->status->value === 'pago' ? 'text-emerald-600' : ($t->status->value === 'cancelado' ? 'text-stone-400' : 'text-amber-600') }}">
                                                {{ ucfirst($t->status->value) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4 bg-stone-50/50">
                    <button wire:click="abrirModalEditar('{{ $p->id }}')"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        Editar
                    </button>
                    <button wire:click="fecharModais"
                            class="rounded-lg px-4 py-2 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 text-sm font-medium transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
