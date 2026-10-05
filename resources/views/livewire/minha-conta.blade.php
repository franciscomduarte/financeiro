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

    <x-ui.page-header titulo="Minha conta" subtitulo="Atualize seus dados de acesso e sua senha." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">

        {{-- Dados pessoais --}}
        <section class="card">
            <div class="px-5 py-4 sm:px-6 border-b border-stone-100">
                <h2 class="text-base font-semibold text-stone-900">Dados pessoais</h2>
                <p class="mt-0.5 text-sm text-stone-500">Seu nome aparece para a equipe. O e-mail é usado para entrar.</p>
            </div>
            <form wire:submit="salvarPerfil" class="px-5 py-5 sm:px-6 space-y-4">
                <div>
                    <label for="mc-nome" class="label">Nome</label>
                    <input id="mc-nome" wire:model="nome" type="text" autocomplete="name" class="input" placeholder="Ex.: Maria Silva">
                    @error('nome') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mc-email" class="label">E-mail</label>
                    <input id="mc-email" wire:model="email" type="email" autocomplete="email" inputmode="email" class="input" placeholder="Ex.: maria@clinica.com.br">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="pt-1 flex justify-end">
                    <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="salvarPerfil">
                        <span wire:loading.remove wire:target="salvarPerfil">Salvar alterações</span>
                        <span wire:loading wire:target="salvarPerfil">Salvando…</span>
                    </button>
                </div>
            </form>
        </section>

        {{-- Alterar senha --}}
        <section class="card">
            <div class="px-5 py-4 sm:px-6 border-b border-stone-100">
                <h2 class="text-base font-semibold text-stone-900">Senha</h2>
                <p class="mt-0.5 text-sm text-stone-500">Use pelo menos 8 caracteres. Misture letras e números.</p>
            </div>
            <form wire:submit="salvarSenha" class="px-5 py-5 sm:px-6 space-y-4">
                <div>
                    <label for="mc-senha-atual" class="label">Senha atual</label>
                    <input id="mc-senha-atual" wire:model="senhaAtual" type="password" autocomplete="current-password" class="input" placeholder="••••••••">
                    @error('senhaAtual') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mc-nova-senha" class="label">Nova senha</label>
                    <input id="mc-nova-senha" wire:model="novaSenha" type="password" autocomplete="new-password" class="input" placeholder="Mínimo de 8 caracteres">
                    @error('novaSenha') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="mc-confirmar-senha" class="label">Confirme a nova senha</label>
                    <input id="mc-confirmar-senha" wire:model="confirmarSenha" type="password" autocomplete="new-password" class="input" placeholder="Repita a nova senha">
                    @error('confirmarSenha') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="pt-1 flex justify-end">
                    <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="salvarSenha">
                        <span wire:loading.remove wire:target="salvarSenha">Alterar senha</span>
                        <span wire:loading wire:target="salvarSenha">Alterando…</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
