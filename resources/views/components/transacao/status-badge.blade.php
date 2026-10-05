@props(['status'])
@php
    $classes = match ($status) {
        \App\Enums\StatusTransacao::Pago      => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        \App\Enums\StatusTransacao::Pendente  => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
        \App\Enums\StatusTransacao::Cancelado => ['bg-stone-100 text-stone-600', 'bg-stone-400'],
    };
@endphp
<span {{ $attributes->merge(['class' => "badge whitespace-nowrap {$classes[0]}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $classes[1] }}" aria-hidden="true"></span>{{ $status->label() }}
</span>
