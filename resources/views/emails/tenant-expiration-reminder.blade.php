<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Expiración</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #7a76c6;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .alert {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
        }
        .modulos-list {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .modulos-list ul {
            list-style-type: none;
            padding: 0;
        }
        .modulos-list li {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .modulos-list li:last-child {
            border-bottom: none;
        }
        .button {
            display: inline-block;
            background-color: #9391b4;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>¡Recordatorio de Renovación!</h1>
    </div>
    
    <div class="content">
        <p>Hola, <strong>{{ $tenant->name_company }}</strong></p>
        
        <div class="alert">
            <p style="margin: 0; font-weight: bold;">⚠️ Tu suscripción expirará en {{ $diasRestantes }} {{ $diasRestantes == 1 ? 'día' : 'días' }}</p>
        </div>
        
        <p>Te escribimos para recordarte que tu suscripción a nuestros servicios está próxima a vencer.</p>
        
        <p><strong>Detalles de tu cuenta:</strong></p>
        <ul>
            <li><strong>Empresa:</strong> {{ $tenant->name_company }}</li>
            <li><strong>Dominio:</strong> {{ $tenant->domain }}</li>
            <li><strong>Fecha de expiración:</strong> {{ \Carbon\Carbon::parse($tenant->expires_at)->format('d/m/Y') }}</li>
        </ul>
        
        @if($modulos && $modulos->count() > 0)
        <div class="modulos-list">
            <p><strong>Módulos activos:</strong></p>
            <ul>
                @foreach($modulos as $modulo)
                <li>
                    📦 <strong>{{ $modulo->nombre_modulo }}</strong>
                    <br>
                    <small style="color: #666;">
                        Plan: {{ ucfirst($modulo->pivot->metodo_pago) }} | 
                        Vence: {{ \Carbon\Carbon::parse($modulo->pivot->fecha_vencimiento)->format('d/m/Y') }}
                    </small>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <p>Para continuar disfrutando de nuestros servicios sin interrupciones, por favor renueva tu suscripción antes de la fecha de expiración.</p>
        
        <center>
            <a href="{{ $frontUrl }}" class="button">
                Renovar Suscripción
            </a>
        </center>
        
        <p style="margin-top: 30px;">Si tienes alguna pregunta o necesitas asistencia, no dudes en contactarnos.</p>
        
        <p>Gracias por confiar en nosotros.</p>
        
        <p><strong>El equipo de Soporte</strong></p>
    </div>
    
    <div class="footer">
        <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>
