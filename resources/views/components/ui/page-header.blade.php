@props(['titulo', 'subtitulo' => null])

{{-- Cabeçalho padrão das telas: título, descrição curta e ações à direita (empilha no celular) --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">{{ $titulo }}</h1>
        @if ($subtitulo)
            <p class="mt-1 text-sm text-stone-500">{{ $subtitulo }}</p>
        @endif
    </div>
    @isset($acoes)
        <div class="flex flex-wrap items-center gap-2 shrink-0">{{ $acoes }}</div>
    @endisset
</div>
