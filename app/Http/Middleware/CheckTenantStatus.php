<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\Log;

class CheckTenantStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener el tenant actual (usa Tenant base para evitar conflictos de tipo)
        $currentTenant = Tenant::current();
               
        // Si no hay tenant (rutas no multi-tenant), continuar normalmente
        if (!$currentTenant) {
            return $next($request);
        }
        
        // Verificar si el tenant está inactivo
        if (!$currentTenant->is_active) {
            Log::warning('Tenant inactivo bloqueado', [
                'tenant' => $currentTenant->domain,
                'tenant_id' => $currentTenant->id,
                'user' => $request->user() ? $request->user()->id : 'guest'
            ]);
            
            return response()->json([
                'message' => 'Tenant inactivo. Contacte al administrador del sistema.',
                'tenant_inactive' => true
            ], 401);
        }
        
        // Si el tenant está activo, continuar con la petición
        return $next($request);
    }
}
