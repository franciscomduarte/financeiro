<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

    {{-- Nome --}}
    <div class="sm:col-span-2">
        <label for="paciente-nome" class="label">Nome <span class="text-rose-600">*</span></label>
        <input id="paciente-nome" wire:model="nome" type="text" placeholder="Ex.: Maria Silva" autocomplete="name"
               class="input @error('nome') !border-red-300 @enderror">
        @error('nome') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- CPF --}}
    <div>
        <label for="paciente-cpf" class="label">CPF</label>
        <input id="paciente-cpf" wire:model="cpf" type="text" inputmode="numeric" placeholder="Ex.: 123.456.789-00" maxlength="14"
               class="input tabular-nums @error('cpf') !border-red-300 @enderror">
        @error('cpf') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Data de nascimento --}}
    <div>
        <label for="paciente-nascimento" class="label">Data de nascimento</label>
        <input id="paciente-nascimento" wire:model="dataNascimento" type="date"
               class="input @error('dataNascimento') !border-red-300 @enderror">
        @error('dataNascimento') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Telefone --}}
    <div>
        <label for="paciente-telefone" class="label">Telefone</label>
        <input id="paciente-telefone" wire:model="telefone" type="tel" placeholder="Ex.: (11) 98765-4321" autocomplete="tel"
               class="input tabular-nums @error('telefone') !border-red-300 @enderror">
        @error('telefone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- E-mail --}}
    <div>
        <label for="paciente-email" class="label">E-mail</label>
        <input id="paciente-email" wire:model="email" type="email" placeholder="Ex.: maria@email.com" autocomplete="email"
               class="input @error('email') !border-red-300 @enderror">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="paciente-sexo" class="label">Sexo</label>
        <select id="paciente-sexo" wire:model="sexo" class="input">
            <option value="">Não informado</option>
            @foreach (array_unique([...\App\Models\Paciente::SEXOS, ...array_filter([$sexo])]) as $opcao)
                <option value="{{ $opcao }}">{{ $opcao }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="paciente-estado-civil" class="label">Estado civil</label>
        <select id="paciente-estado-civil" wire:model="estadoCivil" class="input">
            <option value="">Não informado</option>
            @foreach (array_unique([...\App\Models\Paciente::ESTADOS_CIVIS, ...array_filter([$estadoCivil])]) as $opcao)
                <option value="{{ $opcao }}">{{ $opcao }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="paciente-profissao" class="label">Profissão</label>
        <input id="paciente-profissao" wire:model="profissao" type="text" maxlength="100" placeholder="Ex.: Professora" class="input">
    </div>

    <div>
        <label for="paciente-origem" class="label">Como conheceu a clínica</label>
        <input id="paciente-origem" wire:model="origem" type="text" maxlength="50" list="origens-paciente" placeholder="Ex.: Instagram" class="input">
        <datalist id="origens-paciente">
            @foreach (\App\Models\Paciente::ORIGENS as $opcao)
                <option value="{{ $opcao }}"></option>
            @endforeach
        </datalist>
    </div>

    {{-- Endereço (vai na nota fiscal) --}}
    @include('livewire.partials.campos-endereco', ['prefixo' => 'paciente-end'])

    @if ($endereco !== '')
        <div class="sm:col-span-2">
            <label for="paciente-endereco" class="label">Endereço anotado antes (texto livre)</label>
            <input id="paciente-endereco" wire:model="endereco" type="text" maxlength="255" class="input">
            <p class="hint">Passe para os campos acima e apague daqui quando puder.</p>
        </div>
    @endif

    {{-- Status --}}
    <div>
        <label for="paciente-status" class="label">Status <span class="text-rose-600">*</span></label>
        <select id="paciente-status" wire:model="status"
                class="input @error('status') !border-red-300 @enderror">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
        @error('status') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Separador: cobrança --}}
    @if ($veFinanceiro)
    <div class="sm:col-span-2 border-t border-stone-100 pt-4">
        <h3 class="text-sm font-semibold text-stone-900">Cobrança mensal</h3>
        <p class="mt-0.5 text-xs text-stone-500">Preencha se o paciente paga mensalidade. Deixe o valor em branco se não pagar.</p>
    </div>

    {{-- Valor da mensalidade --}}
    <div>
        <label for="paciente-mensalidade" class="label">Valor da mensalidade (R$)</label>
        <input id="paciente-mensalidade" wire:model="valorMensalidade" type="number" inputmode="decimal" min="0" step="0.01" placeholder="Ex.: 350,00"
               class="input tabular-nums @error('valorMensalidade') !border-red-300 @enderror">
        @error('valorMensalidade') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Forma de pagamento --}}
    <div>
        <label for="paciente-forma" class="label">Forma de pagamento <span class="text-rose-600">*</span></label>
        <select id="paciente-forma" wire:model="formaPagamento"
                class="input @error('formaPagamento') !border-red-300 @enderror">
            <option value="pix">PIX</option>
            <option value="cartao">Cartão</option>
            <option value="dinheiro">Dinheiro</option>
            <option value="boleto">Boleto</option>
        </select>
        @error('formaPagamento') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    @endif

    {{-- Foto --}}
    <div class="sm:col-span-2 border-t border-stone-100 pt-4">
        <label for="paciente-foto" class="label">Foto</label>
        <input id="paciente-foto" wire:model="foto" type="file" accept=".jpg,.jpeg,.png,.webp"
               class="input file:mr-3 file:rounded-lg file:border-0 file:bg-rose-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-rose-700 hover:file:bg-rose-100">
        <p class="hint">JPG, PNG ou WebP, até 2 MB.</p>
        <div wire:loading wire:target="foto" class="hint">Enviando foto...</div>
        @error('foto') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    {{-- Anamnese (só perfis com acesso a dados clínicos) --}}
    @if ($veDadosClinicos)
    <div class="sm:col-span-2">
        <label for="paciente-anamnese" class="label">Anamnese e notas clínicas</label>
        <textarea id="paciente-anamnese" wire:model="anamnese" rows="4"
                  placeholder="Ex.: Alergia a lidocaína. Fez peeling em março."
                  class="input resize-none @error('anamnese') !border-red-300 @enderror"></textarea>
        @error('anamnese') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    @endif

    {{-- Observações --}}
    <div class="sm:col-span-2">
        <label for="paciente-observacoes" class="label">Observações</label>
        <textarea id="paciente-observacoes" wire:model="observacoes" rows="3"
                  placeholder="Ex.: Prefere contato pelo WhatsApp"
                  class="input resize-none @error('observacoes') !border-red-300 @enderror"></textarea>
        @error('observacoes') <p class="field-error">{{ $message }}</p> @enderror
    </div>


    {{-- Privacidade (LGPD) --}}
    <div class="sm:col-span-2 border-t border-stone-100 pt-4 space-y-2">
        <h3 class="text-sm font-semibold text-stone-900">Privacidade (LGPD)</h3>
        <label class="flex min-h-[44px] items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model="consentimento" class="mt-1 w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
            <span class="text-sm text-stone-700">O paciente autorizou o uso dos dados para o atendimento
                <span class="block text-xs text-stone-500">Guardamos a data e quem registrou.</span></span>
        </label>
        <label class="flex min-h-[44px] items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model="aceitaWhatsappMarketing" class="mt-1 w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
            <span class="text-sm text-stone-700">Aceita receber novidades e promoções por WhatsApp</span>
        </label>
        <label class="flex min-h-[44px] items-start gap-3 cursor-pointer">
            <input type="checkbox" wire:model="aceitaEmailMarketing" class="mt-1 w-4 h-4 rounded border-stone-300 text-rose-600 focus:ring-rose-300">
            <span class="text-sm text-stone-700">Aceita receber novidades e promoções por e-mail</span>
        </label>
    </div>
</div>
