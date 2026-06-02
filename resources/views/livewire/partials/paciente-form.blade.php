<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    {{-- Nome --}}
    <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-slate-600 mb-1">Nome <span class="text-red-500">*</span></label>
        <input wire:model="nome" type="text" placeholder="Nome completo do paciente"
               class="w-full rounded-lg border @error('nome') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
        @error('nome') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- CPF --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">CPF</label>
        <input wire:model="cpf" type="text" placeholder="000.000.000-00" maxlength="14"
               class="w-full rounded-lg border @error('cpf') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
        @error('cpf') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Data de Nascimento --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Data de Nascimento</label>
        <input wire:model="dataNascimento" type="date"
               class="w-full rounded-lg border @error('dataNascimento') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
        @error('dataNascimento') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Telefone --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Telefone</label>
        <input wire:model="telefone" type="tel" placeholder="(11) 99999-9999"
               class="w-full rounded-lg border @error('telefone') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
        @error('telefone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- E-mail --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">E-mail</label>
        <input wire:model="email" type="email" placeholder="paciente@email.com"
               class="w-full rounded-lg border @error('email') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Status --}}
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Status <span class="text-red-500">*</span></label>
        <select wire:model="status"
                class="w-full rounded-lg border @error('status') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
        @error('status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Foto de perfil --}}
    <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-slate-600 mb-1">Foto de Perfil</label>
        <input wire:model="foto" type="file" accept=".jpg,.jpeg,.png,.webp"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
        <p class="mt-1 text-xs text-slate-400">JPG, PNG ou WebP · máx. 2 MB</p>
        <div wire:loading wire:target="foto" class="mt-1 text-xs text-slate-500">Carregando foto...</div>
        @error('foto') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Anamnese --}}
    <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-slate-600 mb-1">Anamnese / Notas Clínicas</label>
        <textarea wire:model="anamnese" rows="4"
                  placeholder="Alergias, histórico de procedimentos, contraindicações..."
                  class="w-full rounded-lg border @error('anamnese') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100 resize-none"></textarea>
        @error('anamnese') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Observações --}}
    <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-slate-600 mb-1">Observações</label>
        <textarea wire:model="observacoes" rows="3"
                  placeholder="Notas gerais sobre o paciente..."
                  class="w-full rounded-lg border @error('observacoes') border-red-400 @else border-slate-200 @enderror px-3 py-2 text-sm text-slate-700 focus:border-rose-300 focus:outline-none focus:ring-2 focus:ring-rose-100 resize-none"></textarea>
        @error('observacoes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

</div>
