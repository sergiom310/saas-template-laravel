<?php

namespace App\Http\Middleware;

use Closure;

class JwtAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // Permitir requests OPTIONS para preflight CORS
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }
        
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
