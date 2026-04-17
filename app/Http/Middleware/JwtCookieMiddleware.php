<?php

namespace App\Http\Middleware;

use Closure;

class JwtCookieMiddleware
{
    public function handle($request, Closure $next)
    {
        // Permitir requests OPTIONS para preflight CORS
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }
        
        $token = $request->bearerToken();
        
        if (!$token) {
            $token = $request->cookie('access_token');
            
            if ($token) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
