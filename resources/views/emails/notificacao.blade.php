<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
        .card { background: white; max-width: 480px; margin: 0 auto; border-radius: 12px; padding: 32px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { text-align: center; margin-bottom: 24px; color: #be123c; font-weight: bold; }
        .corpo { font-size: 15px; line-height: 1.6; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 28px; line-height: 1.6; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">@include('emails.partials.nome-clinica')</div>
    <div class="corpo">{{ $corpo }}</div>
    <div class="footer">@include('emails.partials.rodape-clinica')</div>
</div>
</body>
</html>
