@props(['agendamento'])
{{-- Situação do pedido de confirmação da véspera (WhatsApp) --}}
@php $ag = $agendamento; @endphp
@if ($ag->status === \App\Enums\StatusAgendamento::Agendado && $ag->pediu_remarcar_em)
    <span class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-medium text-amber-700">🔄 Pediu para remarcar</span>
@elseif ($ag->status === \App\Enums\StatusAgendamento::Confirmado && $ag->confirmacao_origem === \App\Enums\OrigemConfirmacao::Paciente)
    <span class="inline-flex items-center gap-1 whitespace-nowrap text-xs text-emerald-700">💬 pelo WhatsApp</span>
@elseif ($ag->status === \App\Enums\StatusAgendamento::Agendado && $ag->confirmacao_pedida_em)
    <span class="inline-flex items-center gap-1 whitespace-nowrap text-xs text-stone-500">⏳ Sem resposta</span>
@endif
