<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Restablecer contraseña - {{ $nombreEmpresa }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { background: #f44336; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <h2>¡Hola {{ $user->name }}!</h2>
    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en {{ $nombreEmpresa }}.</p>
    <p>Haz clic en el siguiente botón para continuar:</p>
    <p style="text-align:center;">
        <a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a>
    </p>
    <p>Si no puedes hacer clic, copia y pega el siguiente enlace:</p>
    <p>{{ $resetUrl }}</p>
    <hr>
    <p><small>Este correo fue enviado por {{ $nombreEmpresa }} - {{ $descripcionEmpresa }}</small></p>
</body>
</html>
