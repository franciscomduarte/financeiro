<div>
    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="fixed top-4 right-4 z-50 max-w-sm pointer-events-none">
            <div class="bg-surface border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm pointer-events-auto">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                {{ $flashSucesso }}
            </div>
        </div>
    @endif
    @if ($flashErro)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed top-4 right-4 z-50 max-w-sm">
            <div class="bg-surface border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-stone-800">Usuários</h1>
            <p class="mt-0.5 text-sm text-stone-500">Gerencie os usuários e perfis de acesso do sistema.</p>
        </div>
        <button wire:click="abrirModalNovo"
            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Novo Usuário
        </button>
    </div>

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Total de usuários</p>
            <p class="mt-1 text-2xl font-bold text-stone-800">{{ $totalUsuarios }}</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Administradores</p>
            <p class="mt-1 text-2xl font-bold text-rose-600">{{ $totalAdmins }}</p>
        </div>
        <div class="rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-400">Inativos</p>
            <p class="mt-1 text-2xl font-bold text-stone-400">{{ $totalInativos }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 rounded-xl border border-stone-100 bg-surface p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row">
            <input wire:model.live.debounce.300ms="busca" type="text"
                placeholder="Buscar por nome ou e-mail…"
                class="flex-1 rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
            <select wire:model.live="filtroRole"
                class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os perfis</option>
                @foreach ($roleOpcoes as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroAtivo"
                class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-700 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="">Todos os status</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="rounded-xl border border-stone-100 bg-surface shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-stone-100">
            <thead>
                <tr class="bg-stone-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Nome</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">E-mail</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Perfil</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">Criado em</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-50">
                @forelse ($usuarios as $usuario)
                    <tr class="hover:bg-stone-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-white">{{ strtoupper(substr($usuario->name, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-stone-800">{{ $usuario->name }}</span>
                                    @if ($usuario->id === auth()->id())
                                        <span class="ml-1 text-xs text-rose-500 font-medium">(você)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-stone-600">{{ $usuario->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $usuario->pivot->papel === \App\Enums\RoleUsuario::Admin->value ? 'bg-rose-50 text-rose-700' : 'bg-stone-100 text-stone-600' }}">
                                {{ \App\Enums\RoleUsuario::from($usuario->pivot->papel)->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleAtivo('{{ $usuario->id }}')"
                                @disabled($usuario->id === auth()->id())
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium transition
                                    {{ $usuario->active
                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                        : 'bg-stone-100 text-stone-500 hover:bg-stone-200' }}
                                    disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ $usuario->active ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-sm text-stone-400">{{ $usuario->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3 justify-end">
                                <button wire:click="enviarResetSenha('{{ $usuario->id }}')"
                                    class="text-xs text-stone-500 hover:text-stone-700 font-medium transition"
                                    title="Enviar e-mail de redefinição de senha">
                                    Reset senha
                                </button>
                                <button wire:click="abrirModalEditar('{{ $usuario->id }}')"
                                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                                    Editar
                                </button>
                                @if ($usuario->id !== auth()->id())
                                    <button wire:click="confirmarDeletar('{{ $usuario->id }}')"
                                        class="text-xs text-red-500 hover:text-red-700 font-medium transition">
                                        Excluir
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-stone-400">
                            Nenhum usuário encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($usuarios->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Criar/Editar --}}
    <div x-show="$wire.modalUsuario" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalUsuario">
        <div @click.stop class="w-full max-w-md rounded-2xl bg-surface shadow-xl">
            <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-stone-800">
                    {{ $usuarioEditandoId ? 'Editar Usuário' : 'Novo Usuário' }}
                </h2>
                <button wire:click="fecharModais" class="text-stone-400 hover:text-stone-600 text-xl leading-none">&times;</button>
            </div>
            <form wire:submit="salvarUsuario" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1">Nome *</label>
                    <input wire:model="nome" type="text"
                        class="w-full rounded-lg border border-stone-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    @error('nome') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1">E-mail *</label>
                    <input wire:model="email" type="email"
                        class="w-full rounded-lg border border-stone-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @if (! $usuarioEditandoId)
                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Senha *</label>
                        <input wire:model="senha" type="password"
                            class="w-full rounded-lg border border-stone-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                            placeholder="Mínimo 8 caracteres">
                        @error('senha') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-stone-400 mt-1">Se o e-mail já tiver conta em outra clínica, a pessoa só ganha acesso a esta e continua com a senha atual.</p>
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1">Perfil *</label>
                    <select wire:model="role"
                        class="w-full rounded-lg border border-stone-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                        @foreach ($roleOpcoes as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                @if ($usuarioEditandoId)
                    <div class="flex items-center gap-2.5">
                        <input wire:model="ativo" id="ativo-check" type="checkbox"
                            class="w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                        <label for="ativo-check" class="text-sm text-stone-700 select-none cursor-pointer">Usuário ativo</label>
                    </div>
                @endif
                <div class="flex gap-3 pt-2">
                    <button type="button" wire:click="fecharModais"
                        class="flex-1 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                        <span wire:loading.remove wire:target="salvarUsuario">Salvar</span>
                        <span wire:loading wire:target="salvarUsuario">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Confirmar Exclusão --}}
    <div x-show="$wire.modalDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-trap="$wire.modalDelete">
        <div @click.stop class="w-full max-w-sm rounded-2xl bg-surface shadow-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-stone-800">Confirmar exclusão</h2>
                    <p class="text-sm text-stone-500">Esta ação é irreversível.</p>
                </div>
            </div>
            <p class="text-sm text-stone-600 mb-6">Deseja realmente excluir este usuário? Todos os dados associados serão perdidos.</p>
            <div class="flex gap-3">
                <button wire:click="fecharModais"
                    class="flex-1 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">
                    Cancelar
                </button>
                <button wire:click="deletarUsuario"
                    class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    <span wire:loading.remove wire:target="deletarUsuario">Excluir</span>
                    <span wire:loading wire:target="deletarUsuario">Excluindo…</span>
                </button>
            </div>
        </div>
    </div>
</div>
