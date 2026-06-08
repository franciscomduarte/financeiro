<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
        .card { background: white; max-width: 480px; margin: 0 auto; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { text-align: center; margin-bottom: 24px; }
        .header h2 { color: #be123c; margin: 0; font-size: 20px; }
        .header p { color: #666; margin: 4px 0 0; font-size: 14px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .info-row:last-of-type { border-bottom: none; }
        .destaque { font-size: 28px; font-weight: bold; color: #be123c; text-align: center; margin: 16px 0; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 28px; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>⏰ Lembrete de Consulta</h2>
        <p>LC Estética</p>
    </div>

    <p style="font-size:15px">Olá, <strong>{{ $agendamento->paciente->nome }}</strong>!</p>
    <p style="font-size:14px; color:#666">
        Este é um lembrete de que você tem uma consulta marcada
        <strong>{{ $quando === 'amanhã' ? 'amanhã' : 'em aproximadamente 2 horas' }}</strong>.
    </p>

    <div class="destaque">{{ $agendamento->inicio_em->format('H:i') }}</div>

    <hr class="divider">

    <div class="info-row">
        <span style="color:#666">Procedimento</span>
        <strong>{{ $agendamento->procedimento->nome }}</strong>
    </div>
    <div class="info-row">
        <span style="color:#666">Profissional</span>
        <strong>{{ $agendamento->profissional->nome }}</strong>
    </div>
    <div class="info-row">
        <span style="color:#666">Data</span>
        <strong>{{ $agendamento->inicio_em->translatedFormat('l, d \d\e F \d\e Y') }}</strong>
    </div>
    <div class="info-row">
        <span style="color:#666">Horário</span>
        <strong>{{ $agendamento->inicio_em->format('H:i') }} — {{ $agendamento->fim_em->format('H:i') }}</strong>
    </div>

    <div class="footer">
        Se precisar cancelar ou reagendar, entre em contato com antecedência.<br>
        LC Estética — sua beleza em boas mãos.
    </div>
</div>
</body>
</html>
