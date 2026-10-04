@props(['status'])
@php
    $classes = match ($status) {
        \App\Enums\StatusTransacao::Pago      => ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500'],
        \App\Enums\StatusTransacao::Pendente  => ['bg-amber-50 text-amber-700 border-amber-100', 'bg-amber-400'],
        \App\Enums\StatusTransacao::Cancelado => ['bg-stone-100 text-stone-500 border-stone-200', 'bg-stone-400'],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 border px-2 py-0.5 rounded-full text-xs whitespace-nowrap {$classes[0]}"]) }}>
    <span class="w-1 h-1 rounded-full {{ $classes[1] }}"></span>{{ $status->label() }}
</span>
