<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro exitoso</title>
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
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $name_company }}</div>
    </div>

    <div class="content">
        <h2>¡Hola {{ $name }}!</h2>
        <p>¡Gracias por registrarte! Estamos emocionados de tenerte como parte de nuestra empresa.</p>
        <p>Tu cuenta ha sido creada exitosamente. Ya puedes ingresar a configurar tu aplicación.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $frontUrl }}" class="button">Acceder al Sistema</a>
        </div>
        
        <p><strong>Tu URL de acceso:</strong> <a href="{{ $frontUrl }}">{{ $frontUrl }}</a></p>
        
        <p>Si no te registraste con nosotros, puedes ignorar este correo de forma segura.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Grupo Empresarial ADOS - Desarrollo Web</p>
        <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
    </div>
</body>
</html>
