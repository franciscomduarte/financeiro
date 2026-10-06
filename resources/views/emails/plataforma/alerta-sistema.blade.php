<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:20px; color:#333;">
<div style="background:#fff; max-width:560px; margin:0 auto; border-radius:12px; padding:28px;">
    <h2 style="margin:0 0 16px; color:{{ $problemas ? '#b91c1c' : '#047857' }}; font-size:18px;">
        {{ $problemas ? 'Problemas no sistema' : 'Sistema normalizado' }}
    </h2>
    @foreach ($problemas as $texto)
        <p style="margin:0 0 12px; padding:12px; background:#fef2f2; border-radius:8px; font-size:14px; white-space:pre-line;">{{ $texto }}</p>
    @endforeach
    @if ($normalizados)
        <p style="font-size:14px; color:#047857;">Voltou ao normal: {{ implode(', ', $normalizados) }}.</p>
    @endif
    <p style="font-size:12px; color:#888; margin-top:20px;">
        {{ now()->format('d/m/Y H:i') }} · {{ config('app.url') }}<br>
        Você recebe um aviso por problema a cada hora, no máximo, e outro quando ele for resolvido.
    </p>
</div>
</body>
</html>
