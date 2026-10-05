<div>
    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 z-50 sm:max-w-sm pointer-events-none">
            <div class="bg-surface border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm pointer-events-auto">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
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
             class="fixed top-4 inset-x-4 sm:inset-x-auto sm:right-4 z-50 sm:max-w-sm">
            <div class="bg-surface border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    <x-ui.page-header titulo="Usuários" subtitulo="Convide sua equipe e defina o que cada pessoa pode acessar.">
        <x-slot:acoes>
            <button type="button" wire:click="abrirModalNovo" class="btn-primary w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Novo usuário
            </button>
        </x-slot:acoes>
    </x-ui.page-header>

    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-3 gap-3 sm:gap-4">
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Usuários</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalUsuarios }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Administradores</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalAdmins }}</p>
        </div>
        <div class="card p-4 sm:p-5">
            <p class="text-sm text-stone-500">Inativos</p>
            <p class="mt-1 text-2xl font-semibold text-stone-900 tabular-nums">{{ $totalInativos }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="mb-4 card p-4">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input wire:model.live.debounce.300ms="busca" type="search" aria-label="Buscar usuário"
                    placeholder="Buscar por nome ou e-mail" class="input pl-10">
            </div>
            <select wire:model.live="filtroRole" aria-label="Filtrar por perfil" class="input sm:w-48">
                <option value="">Todos os perfis</option>
                @foreach ($roleOpcoes as $r)
                    <option value="{{ $r->value }}">{{ $r->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroAtivo" aria-label="Filtrar por situação" class="input sm:w-44">
                <option value="">Ativos e inativos</option>
                <option value="1">Ativos</option>
                <option value="0">Inativos</option>
            </select>
        </div>
    </div>

    {{-- Lista --}}
    <div class="card overflow-hidden">
        @if ($usuarios->isEmpty())
            @if ($busca !== '' || $filtroRole !== '' || $filtroAtivo !== '')
                <x-ui.empty-state
                    titulo="Nada encontrado com esses filtros"
                    texto="Confira a busca ou escolha outro perfil e situação."
                    icone="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            @else
                <x-ui.empty-state
                    titulo="Nenhum usuário ainda"
                    texto="Adicione as pessoas da equipe para que cada uma entre com o próprio acesso."
                    icone="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z">
                    <button type="button" wire:click="abrirModalNovo" class="btn-primary">Adicionar usuário</button>
                </x-ui.empty-state>
            @endif
        @else
            {{-- Desktop: tabela --}}
            <table class="hidden md:table min-w-full">
                <thead class="bg-stone-50">
                    <tr class="text-left text-xs font-medium text-stone-500">
                        <th class="px-5 py-3 font-medium">Nome</th>
                        <th class="px-5 py-3 font-medium">E-mail</th>
                        <th class="px-5 py-3 font-medium">Perfil</th>
                        <th class="px-5 py-3 font-medium">Situação</th>
                        <th class="px-5 py-3 font-medium">Desde</th>
                        <th class="px-5 py-3"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($usuarios as $usuario)
                        <tr wire:key="usuario-{{ $usuario->id }}" class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 text-xs font-semibold">
                                        {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="text-sm font-medium text-stone-900">{{ $usuario->name }}</span>
                                        @if ($usuario->id === auth()->id())
                                            <span class="ml-1 text-xs text-stone-500">(você)</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-sm text-stone-600">{{ $usuario->email }}</td>
                            <td class="px-5 py-3">
                                <span class="badge {{ $usuario->pivot->papel === \App\Enums\RoleUsuario::Admin->value ? 'bg-rose-50 text-rose-700' : 'bg-stone-100 text-stone-600' }}">
                                    {{ \App\Enums\RoleUsuario::from($usuario->pivot->papel)->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <button type="button" wire:click="toggleAtivo('{{ $usuario->id }}')"
                                    @disabled($usuario->id === auth()->id())
                                    title="{{ $usuario->active ? 'Clique para desativar' : 'Clique para reativar' }}"
                                    class="badge transition {{ $usuario->active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }} disabled:opacity-60 disabled:cursor-not-allowed">
                                    {{ $usuario->active ? 'Ativo' : 'Inativo' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-sm text-stone-500 tabular-nums">{{ $usuario->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-2">
                                <div class="flex items-center gap-1 justify-end">
                                    <button type="button" wire:click="enviarResetSenha('{{ $usuario->id }}')"
                                        class="btn-ghost px-3 text-xs"
                                        title="Envia um e-mail para a pessoa criar uma nova senha">
                                        Redefinir senha
                                    </button>
                                    <button type="button" wire:click="abrirModalEditar('{{ $usuario->id }}')" class="btn-ghost px-3 text-xs">
                                        Editar
                                    </button>
                                    @if ($usuario->id !== auth()->id())
                                        <button type="button" wire:click="confirmarDeletar('{{ $usuario->id }}')" class="btn-ghost px-3 text-xs text-red-600 hover:bg-red-50 hover:text-red-700">
                                            Excluir
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Celular: cartões --}}
            <ul class="md:hidden divide-y divide-stone-100">
                @foreach ($usuarios as $usuario)
                    <li wire:key="usuario-m-{{ $usuario->id }}" class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-700 flex items-center justify-center shrink-0 text-sm font-semibold">
                                {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-stone-900 truncate">
                                    {{ $usuario->name }}
                                    @if ($usuario->id === auth()->id())
                                        <span class="text-xs font-normal text-stone-500">(você)</span>
                                    @endif
                                </p>
                                <p class="text-sm text-stone-500 truncate">{{ $usuario->email }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="badge {{ $usuario->pivot->papel === \App\Enums\RoleUsuario::Admin->value ? 'bg-rose-50 text-rose-700' : 'bg-stone-100 text-stone-600' }}">
                                        {{ \App\Enums\RoleUsuario::from($usuario->pivot->papel)->label() }}
                                    </span>
                                    <button type="button" wire:click="toggleAtivo('{{ $usuario->id }}')"
                                        @disabled($usuario->id === auth()->id())
                                        class="badge {{ $usuario->active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }} disabled:opacity-60">
                                        {{ $usuario->active ? 'Ativo' : 'Inativo' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <button type="button" wire:click="abrirModalEditar('{{ $usuario->id }}')" class="btn-secondary px-2 text-xs">Editar</button>
                            <button type="button" wire:click="enviarResetSenha('{{ $usuario->id }}')" class="btn-secondary px-2 text-xs">Redefinir senha</button>
                            @if ($usuario->id !== auth()->id())
                                <button type="button" wire:click="confirmarDeletar('{{ $usuario->id }}')" class="btn-secondary px-2 text-xs text-red-600">Excluir</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($usuarios->hasPages())
            <div class="border-t border-stone-100 px-4 py-3">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>

    {{-- Modal criar/editar --}}
    <div x-show="$wire.modalUsuario" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalUsuario">
        <div @click.stop class="w-full sm:max-w-md max-h-[92vh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl" role="dialog" aria-modal="true" aria-labelledby="modal-usuario-titulo">
            <div class="px-5 sm:px-6 py-4 border-b border-stone-100 flex items-center justify-between">
                <h2 id="modal-usuario-titulo" class="text-lg font-semibold text-stone-900">
                    {{ $usuarioEditandoId ? 'Editar usuário' : 'Novo usuário' }}
                </h2>
                <button type="button" wire:click="fecharModais" class="btn-ghost -mr-2 px-2.5" aria-label="Fechar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form wire:submit="salvarUsuario" class="px-5 sm:px-6 py-5 space-y-4">
                <div>
                    <label for="usuario-nome" class="label">Nome</label>
                    <input id="usuario-nome" wire:model="nome" type="text" autocomplete="off" class="input" placeholder="Ex.: Maria Silva">
                    @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="usuario-email" class="label">E-mail</label>
                    <input id="usuario-email" wire:model="email" type="email" inputmode="email" autocomplete="off" class="input" placeholder="Ex.: maria@clinica.com.br">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @if (! $usuarioEditandoId)
                    <div>
                        <label for="usuario-senha" class="label">Senha inicial</label>
                        <input id="usuario-senha" wire:model="senha" type="password" autocomplete="new-password" class="input" placeholder="Mínimo de 8 caracteres">
                        @error('senha') <p class="field-error">{{ $message }}</p> @enderror
                        <p class="hint">Se o e-mail já tiver conta em outra clínica, a pessoa só ganha acesso a esta e continua com a senha que já usa.</p>
                    </div>
                @endif
                <div>
                    <label for="usuario-role" class="label">Perfil</label>
                    <select id="usuario-role" wire:model="role" class="input">
                        @foreach ($roleOpcoes as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                @if ($usuarioEditandoId)
                    <label for="ativo-check" class="flex min-h-[44px] items-center gap-3 cursor-pointer select-none">
                        <input wire:model="ativo" id="ativo-check" type="checkbox" class="w-5 h-5 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
                        <span class="text-sm text-stone-700">Usuário ativo</span>
                    </label>
                @endif
                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="salvarUsuario">
                        <span wire:loading.remove wire:target="salvarUsuario">{{ $usuarioEditandoId ? 'Salvar alterações' : 'Criar usuário' }}</span>
                        <span wire:loading wire:target="salvarUsuario">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal confirmar exclusão --}}
    <div x-show="$wire.modalDelete" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40" x-trap="$wire.modalDelete">
        <div @click.stop class="w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl bg-surface shadow-xl p-5 sm:p-6" role="alertdialog" aria-modal="true" aria-labelledby="modal-excluir-titulo">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h2 id="modal-excluir-titulo" class="text-lg font-semibold text-stone-900">Excluir este usuário?</h2>
                    <p class="mt-1 text-sm text-stone-500">A pessoa perde o acesso a esta clínica. Essa ação não pode ser desfeita.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" wire:click="fecharModais" class="btn-secondary">Cancelar</button>
                <button type="button" wire:click="deletarUsuario" class="btn-danger" wire:loading.attr="disabled" wire:target="deletarUsuario">
                    <span wire:loading.remove wire:target="deletarUsuario">Excluir usuário</span>
                    <span wire:loading wire:target="deletarUsuario">Excluindo…</span>
                </button>
            </div>
        </div>
    </div>
</div>
