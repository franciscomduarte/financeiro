<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f4; margin: 0; padding: 20px; color: #292524; }
        .card { background: #fff; max-width: 560px; margin: 0 auto; border-radius: 14px; padding: 32px 28px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .marca { text-align: center; font-weight: bold; color: #be123c; font-size: 15px; letter-spacing: .02em; margin-bottom: 20px; }
        h2 { font-size: 20px; margin: 0 0 12px; color: #1c1917; }
        p { font-size: 15px; line-height: 1.6; margin: 0 0 14px; }
        .btn { display: inline-block; background: #be123c; color: #fff !important; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-size: 15px; font-weight: bold; }
        .centro { text-align: center; margin: 24px 0; }
        .dados { width: 100%; border-collapse: collapse; font-size: 14px; }
        .dados td { padding: 8px 0; border-bottom: 1px solid #eee; }
        .dados td:first-child { color: #78716c; width: 40%; }
        .pequeno { font-size: 12px; color: #a8a29e; line-height: 1.6; }
        .footer { text-align: center; font-size: 12px; color: #a8a29e; margin-top: 24px; }
    </style>
</head>
<body>
<div class="card">
    <div class="marca">{{ config('app.name') }}</div>
    @yield('conteudo')
</div>
<div class="footer">{{ config('app.name') }} · gestão para clínicas</div>
</body>
</html>
