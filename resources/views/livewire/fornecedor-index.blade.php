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

    {{-- ── Cabeçalho da página ──────────────────────────────────────── --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-800">Fornecedores</h1>
            <p class="mt-0.5 text-sm text-stone-500">Gerencie parceiros e prestadores de serviço</p>
        </div>
        <button wire:click="abrirModalCriar"
                class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Novo Fornecedor
        </button>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────── --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $totalCount }}</p>
            <p class="mt-0.5 text-xs text-stone-500">fornecedores cadastrados</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Ativos</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $ativosCount }}</p>
            <p class="mt-0.5 text-xs text-stone-500">em atividade</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Categorias</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ $categoriasList->count() }}</p>
            <p class="mt-0.5 text-xs text-stone-500">tipos de serviço</p>
        </div>
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────────── --}}
    <div class="mb-4 rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input wire:model.live.debounce.300ms="busca" type="search" placeholder="Buscar por nome, serviço ou CNPJ..."
                       class="w-full rounded-lg border border-stone-200 py-2 pl-9 pr-4 text-sm text-stone-700 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
            </div>
            <select wire:model.live="filtroStatus"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os status</option>
                <option value="ativo">Ativo</option>
                <option value="suspenso">Suspenso</option>
                <option value="encerrado">Encerrado</option>
            </select>
            <select wire:model.live="filtroCategoria"
                    class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todas as categorias</option>
                @foreach ($categoriasList as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            @if ($filtroStatus !== '' || $filtroCategoria !== '' || $busca !== '')
                <button wire:click="$set('filtroStatus', ''); $set('filtroCategoria', ''); $set('busca', '')"
                        class="inline-flex items-center gap-1 rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-500 hover:bg-stone-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Limpar
                </button>
            @endif
        </div>
    </div>

    {{-- ── Tabela ───────────────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl border border-stone-100 bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-stone-100 bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <th class="px-4 py-3">Fornecedor</th>
                        <th class="hidden px-4 py-3 md:table-cell">Serviço / Categoria</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Contato</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="hidden px-4 py-3 lg:table-cell text-center">Contratos</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-50">
                    @forelse ($fornecedores as $fornecedor)
                        <tr class="group hover:bg-stone-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-stone-800">{{ $fornecedor->nome_fantasia }}</div>
                                @if ($fornecedor->razao_social)
                                    <div class="text-xs text-stone-400">{{ $fornecedor->razao_social }}</div>
                                @endif
                                @if ($fornecedor->cnpj)
                                    <div class="font-mono text-xs text-stone-400">{{ $fornecedor->cnpj }}</div>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                @if ($fornecedor->servico_prestado)
                                    <div class="text-stone-700">{{ \Illuminate\Support\Str::limit($fornecedor->servico_prestado, 50) }}</div>
                                @endif
                                @if ($fornecedor->categoria)
                                    <span class="mt-1 inline-block rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700">
                                        {{ $fornecedor->categoria }}
                                    </span>
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                @if ($fornecedor->contato_nome)
                                    <div class="text-stone-700">{{ $fornecedor->contato_nome }}</div>
                                @endif
                                @if ($fornecedor->contato_telefone)
                                    <div class="text-xs text-stone-400">{{ $fornecedor->contato_telefone }}</div>
                                @endif
                                @if ($fornecedor->contato_email)
                                    <div class="text-xs text-stone-400">{{ $fornecedor->contato_email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusCor = match($fornecedor->status->value) {
                                        'ativo'     => 'bg-emerald-50 text-emerald-700',
                                        'suspenso'  => 'bg-amber-50 text-amber-700',
                                        default     => 'bg-stone-100 text-stone-500',
                                    };
                                    $statusDot = match($fornecedor->status->value) {
                                        'ativo'    => 'bg-emerald-500',
                                        'suspenso' => 'bg-amber-500',
                                        default    => 'bg-stone-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium {{ $statusCor }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ ucfirst($fornecedor->status->value) }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell text-center">
                                <span class="inline-flex items-center justify-center rounded-full bg-stone-100 px-2 py-0.5 text-xs font-semibold text-stone-600 tabular-nums">
                                    {{ $fornecedor->contratos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button wire:click="abrirDetalhe('{{ $fornecedor->id }}')"
                                            class="rounded-md p-1.5 text-stone-400 hover:bg-stone-100 hover:text-stone-600" title="Detalhes">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button wire:click="abrirModalEditar('{{ $fornecedor->id }}')"
                                            class="rounded-md p-1.5 text-stone-400 hover:bg-rose-50 hover:text-rose-600" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-stone-400">
                                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <p class="text-sm font-medium">Nenhum fornecedor encontrado</p>
                                    @if ($filtroStatus !== '' || $filtroCategoria !== '' || $busca !== '')
                                        <p class="text-xs">Tente ajustar os filtros</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($fornecedores->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $fornecedores->links() }}
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════════════════════════
         MODAL: CRIAR FORNECEDOR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalCriar)
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">Novo Fornecedor</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    @include('livewire.partials.fornecedor-form')
                </div>
                <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                    <button wire:click="salvar" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        <span wire:loading.remove wire:target="salvar">Salvar</span>
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
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">Editar Fornecedor</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6">
                    @include('livewire.partials.fornecedor-form')
                </div>
                <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancelar</button>
                    <button wire:click="atualizar" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">
                        <span wire:loading.remove wire:target="atualizar">Salvar alterações</span>
                        <span wire:loading wire:target="atualizar">Salvando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════
         MODAL: DETALHE FORNECEDOR
    ════════════════════════════════════════════════════════════ --}}
    @if ($modalDetalhe && $fornecedorDetalhe)
        @php $f = $fornecedorDetalhe; @endphp
        <div class="fixed inset-0 z-40 flex items-center justify-center p-4" x-data x-init="document.body.style.overflow = 'hidden'" x-destroy="document.body.style.overflow = ''">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" wire:click="fecharModais"></div>
            <div class="relative z-10 w-full max-w-lg rounded-2xl bg-surface shadow-xl animate-[modal-in_0.2s_cubic-bezier(0.16,1,0.3,1)]">
                <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                    <h2 class="text-base font-semibold text-stone-800">{{ $f->nome_fantasia }}</h2>
                    <button wire:click="fecharModais" class="rounded-lg p-1.5 text-stone-400 hover:bg-stone-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="max-h-[70vh] overflow-y-auto p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        @if ($f->razao_social)
                            <div class="col-span-2">
                                <p class="text-xs text-stone-400">Razão Social</p>
                                <p class="font-medium text-stone-700">{{ $f->razao_social }}</p>
                            </div>
                        @endif
                        @if ($f->cnpj)
                            <div>
                                <p class="text-xs text-stone-400">CNPJ</p>
                                <p class="font-mono font-medium text-stone-700">{{ $f->cnpj }}</p>
                            </div>
                        @endif
                        @if ($f->categoria)
                            <div>
                                <p class="text-xs text-stone-400">Categoria</p>
                                <p class="font-medium text-stone-700">{{ $f->categoria }}</p>
                            </div>
                        @endif
                        @if ($f->servico_prestado)
                            <div class="col-span-2">
                                <p class="text-xs text-stone-400">Serviço Prestado</p>
                                <p class="font-medium text-stone-700">{{ $f->servico_prestado }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs text-stone-400">Status</p>
                            <p class="font-medium text-stone-700">{{ ucfirst($f->status->value) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-stone-400">Contratos</p>
                            <p class="font-medium text-stone-700">{{ $f->contratos_count }}</p>
                        </div>
                    </div>

                    @if ($f->contato_nome || $f->contato_telefone || $f->contato_email)
                        <div class="rounded-lg border border-stone-100 bg-stone-50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-400">Contato Principal</p>
                            <div class="space-y-1 text-sm">
                                @if ($f->contato_nome)     <p class="font-medium text-stone-700">{{ $f->contato_nome }}</p> @endif
                                @if ($f->contato_telefone) <p class="text-stone-500">{{ $f->contato_telefone }}</p> @endif
                                @if ($f->contato_email)    <p class="text-stone-500">{{ $f->contato_email }}</p> @endif
                            </div>
                        </div>
                    @endif

                    @if ($f->contato_emergencia_nome || $f->contato_emergencia_telefone)
                        <div class="rounded-lg border border-amber-100 bg-amber-50 p-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-500">Contato de Emergência</p>
                            <div class="space-y-1 text-sm">
                                @if ($f->contato_emergencia_nome)     <p class="font-medium text-stone-700">{{ $f->contato_emergencia_nome }}</p> @endif
                                @if ($f->contato_emergencia_telefone) <p class="text-stone-500">{{ $f->contato_emergencia_telefone }}</p> @endif
                            </div>
                        </div>
                    @endif

                    @if ($f->observacoes)
                        <div>
                            <p class="mb-1 text-xs text-stone-400">Observações</p>
                            <p class="text-sm text-stone-600">{{ $f->observacoes }}</p>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end gap-3 border-t border-stone-100 px-6 py-4">
                    <button wire:click="fecharModais" class="rounded-lg border border-stone-200 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Fechar</button>
                    <button wire:click="abrirModalEditar('{{ $f->id }}')" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">Editar</button>
                </div>
            </div>
        </div>
    @endif
</div>
