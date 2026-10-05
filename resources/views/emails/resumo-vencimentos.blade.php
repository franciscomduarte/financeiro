<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
        .card { background: white; max-width: 560px; margin: 0 auto; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { color: #be123c; margin: 0; font-size: 20px; }
        .header p { color: #666; margin: 4px 0 0; font-size: 14px; }
        h3 { font-size: 13px; text-transform: uppercase; letter-spacing: .05em; margin: 24px 0 8px; }
        .vencidos { color: #b91c1c; }
        .avencer { color: #b45309; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 9px 0; border-bottom: 1px solid #eee; vertical-align: top; }
        .tipo { color: #888; font-size: 12px; }
        .prazo { color: #666; font-size: 12px; }
        .valor { text-align: right; white-space: nowrap; font-weight: bold; }
        .btn { display: inline-block; background: #be123c; color: #fff !important; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 24px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>📅 Resumo de vencimentos</h2>
        <p>{{ $nomeClinica ?: config('app.name') }} · {{ $hoje->translatedFormat('d \d\e F \d\e Y') }}</p>
    </div>

    @foreach ([['vencidos', 'Já venceram', $vencidos], ['avencer', 'A vencer', $aVencer]] as [$classe, $titulo, $itens])
        @if ($itens->isNotEmpty())
            <h3 class="{{ $classe }}">{{ $titulo }} ({{ $itens->count() }})</h3>
            <table>
                @foreach ($itens as $a)
                    <tr>
                        <td>
                            <div class="tipo">{{ $a->tipo->label() }}</div>
                            <div>{{ $a->titulo }}</div>
                            <div class="prazo">{{ $a->data->format('d/m/Y') }} · {{ $a->prazo($hoje) }}</div>
                        </td>
                        <td class="valor">{{ $a->valorFormatado() }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    <p style="text-align:center; margin-top:24px">
        <a class="btn" href="{{ route('inicio') }}">Abrir o sistema</a>
    </p>

    <div class="footer">Resumo automático enviado todos os dias às 07:30 quando há vencimentos.</div>
</div>
</body>
</html>
