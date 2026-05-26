<div>
    {{-- Flash --}}
    @if ($flashSucesso)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="fixed top-4 right-4 z-50 max-w-sm pointer-events-none">
            <div class="bg-white border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm pointer-events-auto">
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
            <div class="bg-white border border-red-200 text-red-800 rounded-xl px-4 py-3 shadow-xl flex items-center gap-2.5 text-sm">
                <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                {{ $flashErro }}
            </div>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-xl font-bold text-slate-800">Minha Conta</h1>
        <p class="mt-0.5 text-sm text-slate-500">Gerencie seus dados pessoais e senha de acesso.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Dados pessoais --}}
        <div class="rounded-xl border border-slate-100 bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Dados Pessoais</h2>
                <p class="text-sm text-slate-500 mt-0.5">Atualize seu nome e endereço de e-mail.</p>
            </div>
            <form wire:submit="salvarPerfil" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nome *</label>
                    <input wire:model="nome" type="text"
                        class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    @error('nome') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">E-mail *</label>
                    <input wire:model="email" type="email"
                        class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
                        <span wire:loading.remove wire:target="salvarPerfil">Salvar dados</span>
                        <span wire:loading wire:target="salvarPerfil">Salvando…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Alterar senha --}}
        <div class="rounded-xl border border-slate-100 bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Alterar Senha</h2>
                <p class="text-sm text-slate-500 mt-0.5">Certifique-se de usar uma senha forte com pelo menos 8 caracteres.</p>
            </div>
            <form wire:submit="salvarSenha" class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Senha atual *</label>
                    <input wire:model="senhaAtual" type="password"
                        class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        placeholder="••••••••">
                    @error('senhaAtual') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nova senha *</label>
                    <input wire:model="novaSenha" type="password"
                        class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        placeholder="Mínimo 8 caracteres">
                    @error('novaSenha') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar nova senha *</label>
                    <input wire:model="confirmarSenha" type="password"
                        class="w-full rounded-lg border border-slate-300 text-sm px-3 py-2 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                        placeholder="••••••••">
                    @error('confirmarSenha') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="pt-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-rose-200 transition hover:bg-rose-700 active:scale-95">
                        <span wire:loading.remove wire:target="salvarSenha">Alterar senha</span>
                        <span wire:loading wire:target="salvarSenha">Alterando…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
