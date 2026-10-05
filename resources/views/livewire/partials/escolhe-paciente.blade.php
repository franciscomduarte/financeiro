{{-- Busca de paciente (trait EscolhePaciente). Uso: @include('livewire.partials.escolhe-paciente', ['idCampo' => 'x']) --}}
<div class="relative">
    <label for="{{ $idCampo }}" class="label">Paciente</label>
    <input id="{{ $idCampo }}" type="search" wire:model.live.debounce.300ms="buscaPaciente" autocomplete="off"
           class="input" placeholder="Digite o nome. Ex.: Maria Silva">
    @if ($this->pacientesEncontrados->isNotEmpty())
        <ul class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-stone-200 bg-surface py-1 shadow-lg" role="listbox">
            @foreach ($this->pacientesEncontrados as $pe)
                <li>
                    <button type="button" wire:click="escolherPaciente('{{ $pe->id }}')"
                            class="flex min-h-11 w-full items-center justify-between gap-2 px-4 text-left text-sm hover:bg-stone-50">
                        <span class="truncate text-stone-800">{{ $pe->nome }}</span>
                        <span class="shrink-0 text-xs text-stone-400">{{ $pe->telefone }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @elseif (mb_strlen(trim($buscaPaciente)) >= 2 && $pacienteId === '')
        <p class="hint">Nenhum paciente ativo com esse nome.</p>
    @endif
    @error('pacienteId') <p class="field-error">{{ $message }}</p> @enderror
</div>
