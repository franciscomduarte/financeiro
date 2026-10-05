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
        .badge { display: inline-block; background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; border-radius: 6px; padding: 4px 12px; font-size: 13px; font-weight: bold; margin: 16px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .info-row:last-of-type { border-bottom: none; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 28px; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>📎 Documento Anexo</h2>
        @include('emails.partials.nome-clinica')
        <p>{{ now()->translatedFormat('d \d\e F \d\e Y') }}</p>
    </div>

    <p style="font-size:15px">Olá, <strong>{{ $paciente->nome }}</strong>!</p>
    <p style="font-size:14px; color:#666">
        Segue em anexo o documento solicitado:
    </p>

    <div style="text-align:center">
        <span class="badge">{{ ucfirst($anexo->tipo->value) }}</span>
    </div>

    <hr class="divider">

    <div class="info-row">
        <span style="color:#666">Arquivo</span>
        <strong>{{ $anexo->nome_arquivo }}</strong>
    </div>

    <div class="footer">
        Em caso de dúvidas, entre em contato conosco.<br>
        Este e-mail foi enviado automaticamente.<br>
        @include('emails.partials.rodape-clinica')
    </div>
</div>
</body>
</html>
