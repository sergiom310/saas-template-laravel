<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $origin = $request->header('Origin');

        // Lista de orígenes permitidos para desarrollo
        $allowedOrigins = [
            'http://localhost:9000',
            'http://cliente1.template.local:9000',
            'http://template.local:9000',
        ];

        // Verificar si el origen está en la lista o si es un dominio de producción
        $allowOrigin = 'http://localhost:9000'; // Default

        if (in_array($origin, $allowedOrigins)) {
            $allowOrigin = $origin;
        } elseif ($origin) {
            // Verificar si es el dominio principal de producción o un subdominio
            if (preg_match('/^https?:\/\/([\.\w-]+\.)?template\.grupoados\.com$/', $origin)) {
                $allowOrigin = $origin;
            }
        }

        // Manejar preflight OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        try {
            $response = $next($request);

            // Agregar headers CORS a la respuesta
            return $response
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true');

        } catch (\Exception $e) {
            // Si hay una excepción, log el error y crear respuesta con CORS headers
            \Log::error('Exception in request:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);

            return $response
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With')
                ->header('Access-Control-Allow-Credentials', 'true');
        }
    }
}
