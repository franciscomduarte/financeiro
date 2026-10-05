{{-- Ações de contato: $tipo, $pacienteId, $referencia, $aceita, $telefone, $procedimento (opcional) --}}
@php $chave = "{$tipo}-{$pacienteId}-{$referencia}"; @endphp
<div class="flex shrink-0 flex-wrap gap-2">
    @if ($aceita && $telefone)
        <button type="button" class="btn-primary min-h-10 px-3 text-sm"
                wire:click="enviar('{{ $tipo }}', '{{ $pacienteId }}', '{{ $referencia }}', @js($procedimento ?? null))"
                wire:loading.attr="disabled" wire:target="enviar">
            Enviar WhatsApp
        </button>
    @else
        <span class="badge self-center bg-stone-100 text-stone-600" title="{{ $telefone ? 'O paciente não autorizou mensagens por WhatsApp' : 'Sem telefone' }}">
            {{ $telefone ? 'Sem autorização' : 'Sem telefone' }}
        </span>
    @endif
    <button type="button" class="btn-ghost min-h-10 px-3 text-sm" wire:click="marcarFeito('{{ $tipo }}', '{{ $pacienteId }}', '{{ $referencia }}')">
        Já falei
    </button>
</div>
