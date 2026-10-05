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
    <x-ui.page-header titulo="Pacientes" subtitulo="Cadastre e acompanhe seus pacientes.">
        <x-slot:acoes>
            <button wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo paciente
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- Indicadores --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Total</p>
            <p class="mt-1 text-xl font-semibold text-stone-900 tabular-nums sm:text-2xl">{{ $totalCount }}</p>
        </div>
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Ativos</p>
            <p class="mt-1 text-xl font-semibold text-emerald-700 tabular-nums sm:text-2xl">{{ $ativosCount }}</p>
        </div>
        <div class="card p-3 sm:p-5">
            <p class="text-xs text-stone-500 sm:text-sm">Inativos</p>
            <p class="mt-1 text-xl font-semibold text-stone-900 tabular-nums sm:text-2xl">{{ $totalCount - $ativosCount }}</p>
        </div>
    </div>

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
                       placeholder="Buscar por nome, CPF ou telefone"
                       class="input !pl-10">
            </div>
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:!w-44">
                <option value="">Todos os status</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
            @if ($busca || $filtroStatus)
                <button wire:click="$set('busca', ''); $set('filtroStatus', '')" class="btn-ghost whitespace-nowrap">
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    {{-- Lista --}}
    <div class="card overflow-hidden">
        @if ($pacientes->isEmpty())
            @if ($busca || $filtroStatus)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Confira se o nome, CPF ou telefone está certo, ou limpe os filtros para ver todos os pacientes."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button wire:click="$set('busca', ''); $set('filtroStatus', '')" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhum paciente ainda"
                    texto="Cadastre o primeiro paciente para agendar e cobrar por aqui."
                    icone="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z">
                    <button wire:click="abrirModalCriar" class="btn-primary">Cadastrar paciente</button>
                </x-ui.empty-state>
            @endif
        @else
            @php
                $badgeMap = [
                    'pix'      => ['PIX', 'bg-emerald-50 text-emerald-700'],
                    'cartao'   => ['Cartão', 'bg-blue-50 text-blue-700'],
                    'dinheiro' => ['Dinheiro', 'bg-amber-50 text-amber-700'],
                    'boleto'   => ['Boleto', 'bg-stone-100 text-stone-600'],
                ];
            @endphp

            {{-- Celular: lista de cartões --}}
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($pacientes as $paciente)
                    <li wire:key="pac-cel-{{ $paciente->id }}">
                        <button type="button" wire:click="abrirDetalhe('{{ $paciente->id }}')"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-stone-50 transition-colors">
                            @if ($paciente->foto_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paciente->foto_path) }}"
                                     class="h-10 w-10 shrink-0 rounded-full object-cover"
                                     loading="lazy" alt="">
                            @else
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50">
                                    <span class="text-sm font-semibold text-rose-700">{{ strtoupper(substr($paciente->nome, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $paciente->nome }}</p>
                                <p class="truncate text-xs text-stone-500">{{ $paciente->telefone ?? $paciente->email ?? 'Sem contato' }}</p>
                            </div>
                            @if ($paciente->status->value === 'ativo')
                                <span class="badge shrink-0 bg-emerald-50 text-emerald-700">Ativo</span>
                            @else
                                <span class="badge shrink-0 bg-stone-100 text-stone-600">Inativo</span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: tabela --}}
            <table class="hidden w-full text-sm md:table">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Paciente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">CPF</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Telefone</th>
                        <th class="hidden px-4 py-3 text-right text-xs font-medium text-stone-500 lg:table-cell">Mensalidade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-stone-500">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-stone-500"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($pacientes as $paciente)
                        <tr class="hover:bg-stone-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    {{-- Avatar --}}
                                    @if ($paciente->foto_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($paciente->foto_path) }}"
                                             class="h-9 w-9 rounded-full object-cover shrink-0"
                                             loading="lazy" alt="">
                                    @else
                                        <div class="h-9 w-9 rounded-full bg-rose-50 flex items-center justify-center shrink-0">
                                            <span class="text-sm font-semibold text-rose-700">{{ strtoupper(substr($paciente->nome, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <button type="button" wire:click="abrirDetalhe('{{ $paciente->id }}')"
                                                class="block max-w-full truncate text-left font-medium text-stone-900 hover:text-rose-700 transition-colors">
                                            {{ $paciente->nome }}
                                        </button>
                                        @if ($paciente->email)
                                            <p class="text-xs text-stone-500 truncate">{{ $paciente->email }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-stone-600 tabular-nums">
                                {{ $paciente->cpf ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-stone-600 tabular-nums">
                                {{ $paciente->telefone ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-right lg:table-cell">
                                @if ($paciente->valor_mensalidade > 0)
                                    @php [$label, $cls] = $badgeMap[$paciente->forma_pagamento] ?? ['—', 'bg-stone-100 text-stone-500']; @endphp
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="badge {{ $cls }}">{{ $label }}</span>
                                        <span class="font-medium text-stone-900 tabular-nums">R$ {{ number_format((float) $paciente->valor_mensalidade, 2, ',', '.') }}</span>
                                    </div>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($paciente->status->value === 'ativo')
                                    <span class="badge bg-emerald-50 text-emerald-700">Ativo</span>
                                @else
                                    <span class="badge bg-stone-100 text-stone-600">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-0.5">
                                    <button wire:click="abrirDetalhe('{{ $paciente->id }}')"
                                            class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" title="Ver detalhes" aria-label="Ver detalhes de {{ $paciente->nome }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button wire:click="abrirModalEditar('{{ $paciente->id }}')"
                                            class="rounded-lg p-2 text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors" title="Editar" aria-label="Editar {{ $paciente->nome }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
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
         MODAL: NOVO PACIENTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900">Novo paciente</h2>
                        <p class="mt-0.5 text-sm text-stone-500">Só o nome é obrigatório. O resto você completa depois.</p>
                    </div>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    @include('livewire.partials.paciente-form')
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="salvar" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="salvar">Salvar paciente</span>
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
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 class="text-lg font-semibold text-stone-900">Editar paciente</h2>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    @include('livewire.partials.paciente-form')
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button wire:click="atualizar" wire:loading.attr="disabled" class="btn-primary">
                        <span wire:loading.remove wire:target="atualizar">Salvar alterações</span>
                        <span wire:loading wire:target="atualizar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHE DO PACIENTE
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $this->pacienteDetalhe)
        @php $p = $this->pacienteDetalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40" wire:click="fecharModais"></div>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-t-2xl bg-surface shadow-xl animate-modal-in sm:rounded-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($p->foto_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->foto_path) }}"
                                 class="h-12 w-12 shrink-0 rounded-xl object-cover" alt="">
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-50">
                                <span class="text-lg font-semibold text-rose-700">{{ strtoupper(substr($p->nome, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-stone-900">{{ $p->nome }}</h2>
                            @if ($p->status->value === 'ativo')
                                <span class="badge mt-0.5 bg-emerald-50 text-emerald-700">Ativo</span>
                            @else
                                <span class="badge mt-0.5 bg-stone-100 text-stone-600">Inativo</span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="fecharModais" aria-label="Fechar"
                            class="-mr-2 -mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-6 overflow-y-auto px-5 py-5 sm:px-6">

                    {{-- Dados de contato --}}
                    @if ($p->cpf || $p->data_nascimento || $p->telefone || $p->email)
                        <dl class="grid grid-cols-2 gap-4 rounded-xl bg-stone-50 p-4">
                            @if ($p->cpf)
                                <div>
                                    <dt class="text-xs text-stone-500">CPF</dt>
                                    <dd class="text-sm font-medium text-stone-800 tabular-nums">{{ $p->cpf }}</dd>
                                </div>
                            @endif
                            @if ($p->data_nascimento)
                                <div>
                                    <dt class="text-xs text-stone-500">Data de nascimento</dt>
                                    <dd class="text-sm font-medium text-stone-800 tabular-nums">{{ $p->data_nascimento->format('d/m/Y') }}</dd>
                                </div>
                            @endif
                            @if ($p->telefone)
                                <div>
                                    <dt class="text-xs text-stone-500">Telefone</dt>
                                    <dd class="text-sm font-medium text-stone-800 tabular-nums">{{ $p->telefone }}</dd>
                                </div>
                            @endif
                            @if ($p->email)
                                <div class="min-w-0">
                                    <dt class="text-xs text-stone-500">E-mail</dt>
                                    <dd class="truncate text-sm font-medium text-stone-800">{{ $p->email }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    {{-- Cobrança --}}
                    @if ($p->valor_mensalidade > 0 || $p->forma_pagamento)
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-stone-900">Cobrança mensal</h3>
                            <dl class="grid grid-cols-2 gap-4 rounded-xl border border-stone-200 p-4">
                                <div>
                                    <dt class="text-xs text-stone-500">Mensalidade</dt>
                                    <dd class="text-sm font-semibold text-stone-900 tabular-nums">R$ {{ number_format((float) $p->valor_mensalidade, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-stone-500">Forma de pagamento</dt>
                                    @php
                                        $labelMap = ['pix' => 'PIX', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro', 'boleto' => 'Boleto'];
                                    @endphp
                                    <dd class="text-sm font-medium text-stone-800">{{ $labelMap[$p->forma_pagamento] ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    {{-- Anamnese --}}
                    @if ($p->anamnese)
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-stone-900">Anamnese e notas clínicas</h3>
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                <p class="text-sm text-stone-800 whitespace-pre-line">{{ $p->anamnese }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Observações --}}
                    @if ($p->observacoes)
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-stone-900">Observações</h3>
                            <p class="text-sm text-stone-600 whitespace-pre-line">{{ $p->observacoes }}</p>
                        </div>
                    @endif

                    {{-- Histórico de lançamentos --}}
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-stone-900">Histórico de lançamentos</h3>
                        @if ($p->transacoes->isEmpty())
                            <p class="rounded-xl border border-dashed border-stone-200 px-4 py-6 text-center text-sm text-stone-500">Nenhum lançamento ligado a este paciente ainda.</p>
                        @else
                            <ul class="divide-y divide-stone-100 overflow-hidden rounded-xl border border-stone-200">
                                @foreach ($p->transacoes as $t)
                                    <li class="flex items-center justify-between gap-3 px-3 py-2.5">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-stone-800">{{ $t->descricao }}</p>
                                            <p class="text-xs text-stone-500 tabular-nums">{{ $t->data_competencia->format('d/m/Y') }}</p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-sm font-semibold tabular-nums {{ $t->tipo->value === 'entrada' ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ $t->tipo->value === 'entrada' ? '+' : '−' }} R$ {{ number_format((float) $t->valor_liquido, 2, ',', '.') }}
                                            </p>
                                            <span class="text-xs {{ $t->status->value === 'pago' ? 'text-emerald-700' : ($t->status->value === 'cancelado' ? 'text-stone-500' : 'text-amber-700') }}">
                                                {{ $t->status->label() }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button wire:click="fecharModais" class="btn-secondary">
                        Fechar
                    </button>
                    <button wire:click="abrirModalEditar('{{ $p->id }}')" class="btn-primary">
                        Editar paciente
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
