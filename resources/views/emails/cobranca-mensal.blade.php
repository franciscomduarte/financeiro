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
        .valor { font-size: 36px; font-weight: bold; color: #15803d; text-align: center; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .info-row:last-of-type { border-bottom: none; }
        .btn { display: block; background: #be123c; color: white !important; text-align: center; padding: 14px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 24px; font-size: 15px; }
        .pix-copy { background: #f0fdf4; border: 1px dashed #16a34a; border-radius: 8px; padding: 14px; margin-top: 16px; word-break: break-all; font-size: 11px; color: #555; font-family: monospace; }
        .pix-copy-label { font-size: 13px; color: #444; margin-top: 20px; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 28px; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>💚 Cobrança Mensal</h2>
        <p>{{ now()->translatedFormat('F \d\e Y') }}</p>
    </div>

    <p style="font-size:15px">Olá, <strong>{{ $paciente->nome }}</strong>!</p>
    <p style="font-size:14px; color:#666">Segue sua cobrança de mensalidade:</p>

    <div class="valor">R$ {{ number_format((float) $cobranca['valor'], 2, ',', '.') }}</div>

    <hr class="divider">

    <div class="info-row">
        <span style="color:#666">Vencimento</span>
        <strong>{{ $cobranca['vencimento'] }}</strong>
    </div>
    <div class="info-row">
        <span style="color:#666">Forma de pagamento</span>
        <strong>Pix</strong>
    </div>

    @if (!empty($cobranca['link_fatura']))
        <a href="{{ $cobranca['link_fatura'] }}" class="btn">Ver fatura completa com QR Code</a>
    @endif

    @if (!empty($cobranca['qr_code_texto']))
        <p class="pix-copy-label">📋 Pix Copia e Cola:</p>
        <div class="pix-copy">{{ $cobranca['qr_code_texto'] }}</div>
    @endif

    <div class="footer">
        Em caso de dúvidas, entre em contato conosco.<br>
        Se já realizou o pagamento, desconsidere este e-mail.
    </div>
</div>
</body>
</html>
