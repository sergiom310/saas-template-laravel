<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    // Vacío porque usamos allowed_origins_patterns para mayor flexibilidad
    'allowed_origins' => [],

    // Permite todos los subdominios de bitwia.com (incluyendo el dominio raíz)
    // Ejemplos: agendas.bitwia.com, api.agendas.bitwia.com, barberia1.agendas.bitwia.com
    'allowed_origins_patterns' => [
        '/^https:\/\/([a-z0-9-]+\.)?bitwia\.com$/', // Producción (solo HTTPS)
        '/^http:\/\/([a-z0-9-]+\.)?template\.local(:[0-9]+)?$/', // Desarrollo local
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
