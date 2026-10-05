<div class="space-y-5">
    {{-- Nome fantasia + status --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label for="forn-nome-fantasia" class="label">Nome fantasia <span class="text-red-600">*</span></label>
            <input id="forn-nome-fantasia" wire:model="nomeFantasia" type="text"
                   class="input @error('nomeFantasia') border-red-300 @enderror"
                   placeholder="Ex.: Água Premium">
            @error('nomeFantasia') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="forn-status" class="label">Status</label>
            <select id="forn-status" wire:model="status" class="input">
                <option value="ativo">Ativo</option>
                <option value="suspenso">Suspenso</option>
                <option value="encerrado">Encerrado</option>
            </select>
            @error('status') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Razão social + CNPJ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="forn-razao-social" class="label">Razão social</label>
            <input id="forn-razao-social" wire:model="razaoSocial" type="text"
                   class="input @error('razaoSocial') border-red-300 @enderror"
                   placeholder="Ex.: Água Premium Ltda.">
            @error('razaoSocial') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="forn-cnpj" class="label">CNPJ</label>
            <input id="forn-cnpj" wire:model="cnpj" type="text" inputmode="numeric"
                   class="input tabular-nums @error('cnpj') border-red-300 @enderror"
                   placeholder="Ex.: 12.345.678/0001-90">
            @error('cnpj') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Serviço + categoria --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="forn-servico" class="label">Serviço prestado <span class="text-red-600">*</span></label>
            <input id="forn-servico" wire:model="servicoPrestado" type="text"
                   class="input @error('servicoPrestado') border-red-300 @enderror"
                   placeholder="Ex.: Fornecimento de água mineral">
            @error('servicoPrestado') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="forn-categoria" class="label">Categoria</label>
            <input id="forn-categoria" wire:model="categoria" type="text"
                   class="input @error('categoria') border-red-300 @enderror"
                   placeholder="Ex.: Insumos, Limpeza, TI">
            @error('categoria') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Contato principal --}}
    <div class="border-t border-stone-100 pt-4">
        <p class="text-sm font-semibold text-stone-800">Contato principal</p>
        <p class="hint mt-0.5">Quem você procura no dia a dia.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label for="forn-contato-nome" class="label">Nome</label>
            <input id="forn-contato-nome" wire:model="contatoNome" type="text"
                   class="input @error('contatoNome') border-red-300 @enderror"
                   placeholder="Ex.: João Souza">
            @error('contatoNome') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="forn-contato-telefone" class="label">Telefone</label>
            <input id="forn-contato-telefone" wire:model="contatoTelefone" type="tel" inputmode="tel"
                   class="input @error('contatoTelefone') border-red-300 @enderror"
                   placeholder="Ex.: (11) 99999-9999">
            @error('contatoTelefone') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="forn-contato-email" class="label">E-mail</label>
            <input id="forn-contato-email" wire:model="contatoEmail" type="email" inputmode="email"
                   class="input @error('contatoEmail') border-red-300 @enderror"
                   placeholder="Ex.: contato@fornecedor.com.br">
            @error('contatoEmail') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Contato de emergência --}}
    <div class="border-t border-stone-100 pt-4">
        <p class="text-sm font-semibold text-stone-800">Contato de emergência</p>
        <p class="hint mt-0.5">Opcional. Para urgências com o serviço.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="forn-emerg-nome" class="label">Nome</label>
            <input id="forn-emerg-nome" wire:model="contatoEmergenciaNome" type="text"
                   class="input"
                   placeholder="Ex.: Plantão técnico">
        </div>
        <div>
            <label for="forn-emerg-telefone" class="label">Telefone</label>
            <input id="forn-emerg-telefone" wire:model="contatoEmergenciaTelefone" type="tel" inputmode="tel"
                   class="input"
                   placeholder="Ex.: (11) 99999-9999">
        </div>
    </div>

    {{-- Observações --}}
    <div>
        <label for="forn-observacoes" class="label">Observações</label>
        <textarea id="forn-observacoes" wire:model="observacoes" rows="2"
                  class="input"
                  placeholder="Ex.: Entrega às terças, pedido mínimo de 10 galões"></textarea>
    </div>
</div>
