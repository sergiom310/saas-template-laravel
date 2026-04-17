<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar tu cuenta en {{ $nombreEmpresa }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header { text-align: center; padding: 20px 0; border-bottom: 1px solid #eee; }
        .logo { font-size: 24px; font-weight: bold; color: #1976d2; }
        .content { padding: 30px 0; }
        .button {
            display: inline-block;
            background: #1976d2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #666; text-align: center; }
        .warning { background: #fff3cd; border: 1px solid #ffecb5; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $nombreEmpresa }}</div>
        <p>{{ $descripcionEmpresa }}</p>
    </div>

    <div class="content">
        <h2>¡Hola {{ $user->name }}!</h2>
        <p>¡Gracias por registrarte en {{ $nombreEmpresa }}! Estamos emocionados de tenerte como parte de nuestra comunidad.</p>

        <p>Para completar tu registro y comenzar a disfrutar de todos nuestros productos únicos, necesitas verificar tu dirección de correo electrónico.</p>

        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">Verificar mi cuenta</a>
        </div>

        <div class="warning">
            <p><strong>⏰ Importante:</strong> Este enlace de verificación expirará en {{ $activationExpireText }} por seguridad.</p>
        </div>

        <p>Si no puedes hacer clic en el botón anterior, copia y pega el siguiente enlace en tu navegador:</p>

        <p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 3px;">{{ $verificationUrl }}</p>

        <p>Si no te registraste en {{ $nombreEmpresa }}, puedes ignorar este correo de forma segura.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ $nombreEmpresa }}</p>
        <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
    </div>
</body>
</html>
