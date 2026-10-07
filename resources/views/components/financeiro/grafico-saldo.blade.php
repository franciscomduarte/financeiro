@props(['pontos', 'altura' => 160])
{{-- Linha do saldo (SVG puro): verde acima de zero, área vermelha quando fica negativo --}}
@php
    $valores = array_column($pontos, 'saldo');
    $n = count($valores);
    $min = min(0, ...($valores ?: [0]));
    $max = max(0, ...($valores ?: [0]));
    $amp = max(1, $max - $min);
    $w = 600;
    $h = (int) $altura;
    $x = fn ($i) => $n > 1 ? round($i / ($n - 1) * $w, 1) : 0;
    $y = fn ($v) => round(($max - $v) / $amp * ($h - 8) + 4, 1);
    $linha = collect($valores)->map(fn ($v, $i) => $x($i) . ',' . $y($v))->implode(' ');
    $zero = $y(0);
@endphp
<svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" role="img" {{ $attributes->merge(['class' => ($h <= 100 ? 'h-20' : 'h-40') . ' w-full']) }}>
    <line x1="0" x2="{{ $w }}" y1="{{ $zero }}" y2="{{ $zero }}" stroke="currentColor" class="text-stone-300" stroke-dasharray="4 4" vector-effect="non-scaling-stroke" />
    @if ($min < 0)
        <rect x="0" y="{{ $zero }}" width="{{ $w }}" height="{{ max(0, $h - $zero) }}" class="fill-red-500/5" />
    @endif
    @if ($n > 1)
        <polyline points="{{ $linha }}" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" class="{{ $min < 0 ? 'text-red-600' : 'text-emerald-600' }}" />
    @endif
</svg>
