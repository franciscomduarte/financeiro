<div class="space-y-4">
    {{-- Linha 1: Nome fantasia + Status --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label class="block text-xs font-medium text-stone-600 mb-1">Nome Fantasia <span class="text-red-500">*</span></label>
            <input wire:model="nomeFantasia" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('nomeFantasia') border-red-400 @enderror"
                   placeholder="Ex: Água Premium">
            @error('nomeFantasia') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Status</label>
            <select wire:model="status"
                    class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <option value="ativo">Ativo</option>
                <option value="suspenso">Suspenso</option>
                <option value="encerrado">Encerrado</option>
            </select>
        </div>
    </div>

    {{-- Linha 2: Razão social + CNPJ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Razão Social</label>
            <input wire:model="razaoSocial" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="Ex: Água Premium Ltda.">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">CNPJ</label>
            <input wire:model="cnpj" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="00.000.000/0000-00">
        </div>
    </div>

    {{-- Linha 3: Serviço + Categoria --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Serviço Prestado</label>
            <input wire:model="servicoPrestado" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="Ex: Fornecimento de água mineral">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Categoria</label>
            <input wire:model="categoria" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="Ex: Insumos, Limpeza, TI...">
        </div>
    </div>

    {{-- Separador contato --}}
    <div class="border-t border-stone-100 pt-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Contato Principal</p>
    </div>

    {{-- Contato principal --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Nome</label>
            <input wire:model="contatoNome" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="Nome do contato">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Telefone</label>
            <input wire:model="contatoTelefone" type="tel"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="(11) 99999-9999">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">E-mail</label>
            <input wire:model="contatoEmail" type="email"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100 @error('contatoEmail') border-red-400 @enderror"
                   placeholder="email@fornecedor.com">
            @error('contatoEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Separador emergência --}}
    <div class="border-t border-stone-100 pt-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Contato de Emergência</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Nome</label>
            <input wire:model="contatoEmergenciaNome" type="text"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="Nome do contato de emergência">
        </div>
        <div>
            <label class="block text-xs font-medium text-stone-600 mb-1">Telefone</label>
            <input wire:model="contatoEmergenciaTelefone" type="tel"
                   class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                   placeholder="(11) 99999-9999">
        </div>
    </div>

    {{-- Observações --}}
    <div>
        <label class="block text-xs font-medium text-stone-600 mb-1">Observações</label>
        <textarea wire:model="observacoes" rows="2"
                  class="w-full rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-800 placeholder-stone-400 focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100"
                  placeholder="Informações adicionais sobre este fornecedor..."></textarea>
    </div>
</div>
