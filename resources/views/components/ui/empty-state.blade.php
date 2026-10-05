@props([
    'titulo',
    'texto' => null,
    // Caminho SVG (heroicons outline 24px) do ícone
    'icone' => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
])

{{-- Estado vazio: explica o que aparece aqui e oferece a próxima ação (slot) --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center text-center px-6 py-12 sm:py-16']) }}>
    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center">
        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icone }}" />
        </svg>
    </div>
    <h3 class="mt-4 text-base font-semibold text-stone-900">{{ $titulo }}</h3>
    @if ($texto)
        <p class="mt-1 max-w-sm text-sm text-stone-500">{{ $texto }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
