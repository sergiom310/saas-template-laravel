<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Bienvenido a {{ $nombreEmpresa }}!</title>
    <style>
        body {font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;}
        .header {text-align: center; padding: 20px 0; border-bottom: 1px solid #eee;}
        .logo {font-size: 24px; font-weight: bold; color: #1976d2;}
        .content {padding: 30px 0;}
        .button {display: inline-block; background: #4caf50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold;}
        .footer {margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #666; text-align: center;}
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{ $nombreEmpresa }}</div>
        <p>{{ $descripcionEmpresa }}</p>
    </div>
    <div class="content">
        <h2>¡Bienvenido {{ $name }}! 🎉</h2>
        <p>Tu cuenta ha sido verificada exitosamente. ¡Ya puedes comenzar a explorar la aplicación!</p>
        <div style="text-align: center;">
            <a href="{{ $frontUrl }}" class="button">Explorar App</a>
        </div>
        <p>¡Gracias por elegirnos!</p>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} {{ $nombreEmpresa }}</p>
        <p>¿Necesitas ayuda? Contáctanos en cualquier momento.</p>
    </div>
</body>
</html>
