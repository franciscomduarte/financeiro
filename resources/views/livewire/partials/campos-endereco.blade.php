{{-- Endereço com busca pelo CEP (trait Concerns\CamposEndereco). Parâmetro: $prefixo (ids únicos). --}}
@php $prefixo ??= 'end'; @endphp
<div class="grid grid-cols-6 gap-3 sm:col-span-2">
    <div class="col-span-3 sm:col-span-2">
        <label for="{{ $prefixo }}-cep" class="label">CEP</label>
        <input id="{{ $prefixo }}-cep" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="9"
               wire:model.live.debounce.500ms="endCep" class="input tabular-nums" placeholder="Ex.: 71900-100">
        <p wire:loading wire:target="endCep" class="hint">Buscando o endereço…</p>
        @if ($endAviso) <p class="field-error">{{ $endAviso }}</p> @endif
        @error('endCep') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-6 sm:col-span-4">
        <label for="{{ $prefixo }}-logradouro" class="label">Rua / avenida</label>
        <input id="{{ $prefixo }}-logradouro" type="text" autocomplete="address-line1" maxlength="150" wire:model="endLogradouro" class="input" placeholder="Ex.: Rua 12">
        @error('endLogradouro') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div class="col-span-2">
        <label for="{{ $prefixo }}-numero" class="label">Número</label>
        <input id="{{ $prefixo }}-numero" type="text" maxlength="20" wire:model="endNumero" class="input" placeholder="Ex.: 300">
    </div>
    <div class="col-span-4">
        <label for="{{ $prefixo }}-complemento" class="label">Complemento</label>
        <input id="{{ $prefixo }}-complemento" type="text" autocomplete="address-line2" maxlength="80" wire:model="endComplemento" class="input" placeholder="Ex.: apto 101">
    </div>
    <div class="col-span-6 sm:col-span-2">
        <label for="{{ $prefixo }}-bairro" class="label">Bairro</label>
        <input id="{{ $prefixo }}-bairro" type="text" maxlength="80" wire:model="endBairro" class="input" placeholder="Ex.: Águas Claras">
    </div>
    <div class="col-span-4 sm:col-span-3">
        <label for="{{ $prefixo }}-cidade" class="label">Cidade</label>
        <input id="{{ $prefixo }}-cidade" type="text" maxlength="80" wire:model="endCidade" class="input" placeholder="Preenchida pelo CEP">
    </div>
    <div class="col-span-2 sm:col-span-1">
        <label for="{{ $prefixo }}-uf" class="label">UF</label>
        <input id="{{ $prefixo }}-uf" type="text" maxlength="2" wire:model="endUf" class="input uppercase" placeholder="DF">
        @error('endUf') <p class="field-error">{{ $message }}</p> @enderror
    </div>
</div>
