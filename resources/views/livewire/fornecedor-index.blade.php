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
    <x-ui.page-header titulo="Fornecedores" subtitulo="Cadastre os parceiros e prestadores de serviço da clínica.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalCriar" class="btn-primary">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Novo fornecedor
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- ── Indicadores ──────────────────────────────────────────────── --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-stone-500">Fornecedores</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalCount }}</p>
            <p class="mt-1 text-xs text-stone-500">cadastrados no total</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Ativos</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-700 tabular-nums">{{ $ativosCount }}</p>
            <p class="mt-1 text-xs text-stone-500">prestando serviço hoje</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-stone-500">Categorias</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $categoriasList->count() }}</p>
            <p class="mt-1 text-xs text-stone-500">tipos de serviço</p>
        </div>
    </div>

    @php $temFiltro = $filtroStatus !== '' || $filtroCategoria !== '' || $busca !== ''; @endphp

    {{-- ── Filtros ──────────────────────────────────────────────────── --}}
    <div class="card mb-4 p-4">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input wire:model.live.debounce.300ms="busca" type="search" aria-label="Buscar fornecedor"
                       placeholder="Buscar por nome, serviço ou CNPJ"
                       class="input pl-10">
            </div>
            <select wire:model.live="filtroStatus" aria-label="Filtrar por status" class="input sm:w-44">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="suspenso">Suspenso</option>
                <option value="encerrado">Encerrado</option>
            </select>
            <select wire:model.live="filtroCategoria" aria-label="Filtrar por categoria" class="input sm:w-52">
                <option value="">Todas as categorias</option>
                @foreach ($categoriasList as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            @if ($temFiltro)
                <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroCategoria', ''); $set('busca', '')"
                        class="btn-ghost shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    @php
        $statusCores = [
            'ativo'     => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
            'suspenso'  => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
            'encerrado' => ['bg-stone-100 text-stone-600', 'bg-stone-400'],
        ];
    @endphp

    {{-- ── Lista ────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        @if ($fornecedores->isEmpty())
            @if ($temFiltro)
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Tente buscar por outro nome ou limpe os filtros para ver todos os fornecedores."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z">
                    <button type="button" wire:click="$set('filtroStatus', ''); $set('filtroCategoria', ''); $set('busca', '')" class="btn-secondary">Limpar filtros</button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state
                    titulo="Nenhum fornecedor ainda"
                    texto="Cadastre seus fornecedores para ligar contratos e ter os contatos sempre à mão."
                    icone="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z">
                    <button type="button" wire:click="abrirModalCriar" class="btn-primary">Cadastrar fornecedor</button>
                </x-ui.empty-state>
            @endif
        @else
            {{-- Celular: cartões --}}
            <ul class="divide-y divide-stone-100 md:hidden">
                @foreach ($fornecedores as $fornecedor)
                    @php [$statusCor, $statusDot] = $statusCores[$fornecedor->status->value] ?? $statusCores['encerrado']; @endphp
                    <li wire:key="forn-m-{{ $fornecedor->id }}" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" wire:click="abrirDetalhe('{{ $fornecedor->id }}')" class="min-w-0 flex-1 text-left">
                                <p class="truncate font-medium text-stone-900">{{ $fornecedor->nome_fantasia }}</p>
                                @if ($fornecedor->servico_prestado)
                                    <p class="mt-0.5 truncate text-sm text-stone-500">{{ $fornecedor->servico_prestado }}</p>
                                @endif
                            </button>
                            <span class="badge shrink-0 {{ $statusCor }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                {{ ucfirst($fornecedor->status->value) }}
                            </span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500">
                            @if ($fornecedor->categoria)
                                <span class="badge bg-rose-50 text-rose-700">{{ $fornecedor->categoria }}</span>
                            @endif
                            @if ($fornecedor->contato_telefone)
                                <span>{{ $fornecedor->contato_telefone }}</span>
                            @endif
                            <span class="tabular-nums">{{ $fornecedor->contratos_count }} {{ $fornecedor->contratos_count === 1 ? 'contrato' : 'contratos' }}</span>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="button" wire:click="abrirDetalhe('{{ $fornecedor->id }}')" class="btn-secondary flex-1">Ver detalhes</button>
                            <button type="button" wire:click="abrirModalEditar('{{ $fornecedor->id }}')" class="btn-secondary flex-1">Editar</button>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Desktop: tabela --}}
            <table class="hidden w-full text-sm md:table">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50 text-left text-xs font-medium text-stone-500">
                        <th class="px-4 py-3 font-medium">Fornecedor</th>
                        <th class="px-4 py-3 font-medium">Serviço e categoria</th>
                        <th class="hidden px-4 py-3 font-medium lg:table-cell">Contato</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="hidden px-4 py-3 text-right font-medium lg:table-cell">Contratos</th>
                        <th class="px-4 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($fornecedores as $fornecedor)
                        @php [$statusCor, $statusDot] = $statusCores[$fornecedor->status->value] ?? $statusCores['encerrado']; @endphp
                        <tr wire:key="forn-d-{{ $fornecedor->id }}" class="group transition-colors hover:bg-stone-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-stone-900">{{ $fornecedor->nome_fantasia }}</div>
                                @if ($fornecedor->razao_social)
                                    <div class="text-xs text-stone-500">{{ $fornecedor->razao_social }}</div>
                                @endif
                                @if ($fornecedor->cnpj)
                                    <div class="text-xs text-stone-400 tabular-nums">{{ $fornecedor->cnpj }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($fornecedor->servico_prestado)
                                    <div class="text-stone-700">{{ \Illuminate\Support\Str::limit($fornecedor->servico_prestado, 50) }}</div>
                                @endif
                                @if ($fornecedor->categoria)
                                    <span class="badge mt-1 bg-rose-50 text-rose-700">{{ $fornecedor->categoria }}</span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                @if ($fornecedor->contato_nome)
                                    <div class="text-stone-700">{{ $fornecedor->contato_nome }}</div>
                                @endif
                                @if ($fornecedor->contato_telefone)
                                    <div class="text-xs text-stone-500">{{ $fornecedor->contato_telefone }}</div>
                                @endif
                                @if ($fornecedor->contato_email)
                                    <div class="text-xs text-stone-500">{{ $fornecedor->contato_email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $statusCor }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ ucfirst($fornecedor->status->value) }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-right tabular-nums text-stone-700 lg:table-cell">
                                {{ $fornecedor->contratos_count }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="abrirDetalhe('{{ $fornecedor->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700" title="Ver detalhes" aria-label="Ver detalhes de {{ $fornecedor->nome_fantasia }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </button>
                                    <button type="button" wire:click="abrirModalEditar('{{ $fornecedor->id }}')"
                                            class="rounded-lg p-2 text-stone-400 transition hover:bg-rose-50 hover:text-rose-700" title="Editar" aria-label="Editar {{ $fornecedor->nome_fantasia }}">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($fornecedores->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $fornecedores->links() }}
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: NOVO FORNECEDOR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-criar"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-2xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-criar" class="text-lg font-semibold text-stone-900">Novo fornecedor</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-5 sm:p-6">
                    @include('livewire.partials.fornecedor-form')
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="salvar" wire:loading.attr="disabled" wire:target="salvar" class="btn-primary">
                        <span wire:loading.remove wire:target="salvar">Salvar fornecedor</span>
                        <span wire:loading wire:target="salvar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: EDITAR FORNECEDOR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalEditar)
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-editar"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-2xl sm:rounded-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-editar" class="text-lg font-semibold text-stone-900">Editar fornecedor</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-5 sm:p-6">
                    @include('livewire.partials.fornecedor-form')
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="button" wire:click="atualizar" wire:loading.attr="disabled" wire:target="atualizar" class="btn-primary">
                        <span wire:loading.remove wire:target="atualizar">Salvar alterações</span>
                        <span wire:loading wire:target="atualizar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHES DO FORNECEDOR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $fornecedorDetalhe)
        @php $f = $fornecedorDetalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center sm:items-center sm:p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="titulo-modal-detalhe"
                 class="relative z-10 w-full rounded-t-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)] sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 sm:px-6">
                    <h2 id="titulo-modal-detalhe" class="min-w-0 truncate text-lg font-semibold text-stone-900">{{ $f->nome_fantasia }}</h2>
                    <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5 sm:p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        @if ($f->razao_social)
                            <div class="col-span-2">
                                <dt class="text-xs text-stone-500">Razão social</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ $f->razao_social }}</dd>
                            </div>
                        @endif
                        @if ($f->cnpj)
                            <div>
                                <dt class="text-xs text-stone-500">CNPJ</dt>
                                <dd class="mt-0.5 font-medium text-stone-800 tabular-nums">{{ $f->cnpj }}</dd>
                            </div>
                        @endif
                        @if ($f->categoria)
                            <div>
                                <dt class="text-xs text-stone-500">Categoria</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ $f->categoria }}</dd>
                            </div>
                        @endif
                        @if ($f->servico_prestado)
                            <div class="col-span-2">
                                <dt class="text-xs text-stone-500">Serviço prestado</dt>
                                <dd class="mt-0.5 font-medium text-stone-800">{{ $f->servico_prestado }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-stone-500">Status</dt>
                            <dd class="mt-0.5 font-medium text-stone-800">{{ ucfirst($f->status->value) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-stone-500">Contratos</dt>
                            <dd class="mt-0.5 font-medium text-stone-800 tabular-nums">{{ $f->contratos_count }}</dd>
                        </div>
                    </dl>

                    @if ($f->contato_nome || $f->contato_telefone || $f->contato_email)
                        <div class="rounded-xl border border-stone-200/70 bg-stone-50 p-4">
                            <p class="mb-2 text-xs font-medium text-stone-500">Contato principal</p>
                            <div class="space-y-1 text-sm">
                                @if ($f->contato_nome)     <p class="font-medium text-stone-800">{{ $f->contato_nome }}</p> @endif
                                @if ($f->contato_telefone) <p class="text-stone-600">{{ $f->contato_telefone }}</p> @endif
                                @if ($f->contato_email)    <p class="text-stone-600">{{ $f->contato_email }}</p> @endif
                            </div>
                        </div>
                    @endif

                    @if ($f->contato_emergencia_nome || $f->contato_emergencia_telefone)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="mb-2 text-xs font-medium text-amber-700">Contato de emergência</p>
                            <div class="space-y-1 text-sm">
                                @if ($f->contato_emergencia_nome)     <p class="font-medium text-stone-800">{{ $f->contato_emergencia_nome }}</p> @endif
                                @if ($f->contato_emergencia_telefone) <p class="text-stone-600">{{ $f->contato_emergencia_telefone }}</p> @endif
                            </div>
                        </div>
                    @endif

                    @if ($f->observacoes)
                        <div>
                            <p class="mb-1 text-xs text-stone-500">Observações</p>
                            <p class="text-sm text-stone-700">{{ $f->observacoes }}</p>
                        </div>
                    @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-stone-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Fechar</button>
                    <button type="button" wire:click="abrirModalEditar('{{ $f->id }}')" class="btn-primary">Editar fornecedor</button>
                </div>
            </div>
        </div>
    @endif
</div>
