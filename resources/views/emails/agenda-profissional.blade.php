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
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 10px 0; border-bottom: 1px solid #eee; vertical-align: top; }
        .hora { width: 64px; font-weight: bold; white-space: nowrap; }
        .hora span { display: block; color: #999; font-weight: normal; font-size: 12px; }
        .paciente { font-weight: bold; }
        .proc { color: #555; }
        .tag { display: inline-block; font-size: 11px; padding: 2px 8px; border-radius: 99px; margin-top: 4px; margin-right: 4px; }
        .ok { background: #ecfdf5; color: #047857; }
        .alerta { background: #fffbeb; color: #b45309; }
        .neutro { background: #f5f5f4; color: #57534e; }
        .nova { background: #fdf2f8; color: #be185d; }
        .obs { color: #666; font-size: 12px; margin-top: 4px; }
        .btn { display: inline-block; background: #be123c; color: #fff !important; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 24px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>🗓️ Sua agenda de amanhã</h2>
        <p>{{ $profissional }} · {{ $dia->locale('pt_BR')->translatedFormat('l, d \d\e F') }}</p>
        <p>{{ count($itens) }} {{ count($itens) === 1 ? 'atendimento' : 'atendimentos' }} · {{ $nomeClinica ?: config('app.name') }}</p>
    </div>

    <table>
        @foreach ($itens as $i)
            <tr>
                <td class="hora">{{ $i['inicio'] }}@if ($i['fim'])<span>até {{ $i['fim'] }}</span>@endif</td>
                <td>
                    <div class="paciente">{{ $i['paciente'] }}</div>
                    <div class="proc">{{ $i['procedimento'] }}</div>
                    @php
                        $classe = match ($i['confirmacao']) {
                            'Confirmado pelo paciente', 'Confirmado' => 'ok',
                            'Pediu para remarcar' => 'alerta',
                            default => 'neutro',
                        };
                    @endphp
                    <span class="tag {{ $classe }}">{{ $i['confirmacao'] }}</span>
                    @if ($i['primeira_vez'])
                        <span class="tag nova">Primeira vez na clínica</span>
                    @endif
                    @if ($i['observacoes'])
                        <div class="obs">📝 {{ $i['observacoes'] }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <p style="text-align:center; margin-top:24px">
        <a class="btn" href="{{ route('agenda.index', ['visao' => 'lista', 'filtroData' => $dia->toDateString()]) }}">Abrir a agenda</a>
    </p>

    <div class="footer">Enviado todos os dias às 19h com os atendimentos do dia seguinte. A agenda pode mudar até lá: confira no sistema.</div>
</div>
</body>
</html>
