<?php

namespace App\Traits;

trait JWTResponseTrait
{
    public function respondWithToken($token)
    {
        // Validar si el tenant está activo (capa adicional de seguridad)
        $currentTenant = \Spatie\Multitenancy\Models\Tenant::current();
        if ($currentTenant && !$currentTenant->is_active) {
            return response()->json([
                'error' => 'Cuenta suspendida. Contacte al administrador del sistema.',
                'tenant_inactive' => true
            ], 401);
        }

        $user = auth('api')->user();
        $permisos = $user->getAllPermissions()->pluck('name')->toArray();
        $user->permissions = $permisos;
        $role = \DB::table('model_has_roles')
            ->select('role_id', 'name')
            ->where('model_id', '=', $user->id)
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->first();

        // Obtener módulos activos del tenant actual
        $modulosActivos = [];
        try {
            $tenant = null;
            
            // Intentar obtener el tenant del helper
            if (function_exists('tenant') && tenant()) {
                $tenant = tenant();
            } else {
                // Si no hay tenant activo, intentar obtenerlo desde el request
                $request = request();
                
                // Obtener el host desde el Origin header o desde el request
                $origin = $request->header('Origin') ?? '';
                $host = parse_url($origin, PHP_URL_HOST);
                
                // Si no hay Origin, usar el host del request
                if (!$host) {
                    $host = $request->getHost();
                }
                                
                // Dividir el dominio por puntos
                $parts = explode('.', $host);
                
                // Si el primer subdominio es 'api', quitarlo
                if (isset($parts[0]) && $parts[0] === 'api') {
                    array_shift($parts);
                }
                
                // El identificador del tenant es el primer subdominio
                $identifier = $parts[0] ?? null;
                
                if ($identifier) {
                    // Buscar el tenant en la base de datos landlord
                    $tenant = \Spatie\Multitenancy\Models\Tenant::where('domain', $identifier)->first();
                }
            }
            
            // Si encontramos el tenant, cargar sus módulos activos
            if ($tenant) {
                $tenantModel = \App\Models\Tenant\CustomTenantModel::with('modulosActivos')->find($tenant->id);
                if ($tenantModel && $tenantModel->modulosActivos) {
                    $modulosActivos = $tenantModel->modulosActivos->pluck('slug')->toArray();
                } else {
                    \Log::warning('JWTResponseTrait: No se encontraron módulos activos', ['tenant_id' => $tenant->id]);
                }
            } else {
                \Log::warning('JWTResponseTrait: No se pudo determinar el tenant');
            }
        } catch (\Exception $e) {
            \Log::error('Error obteniendo módulos del tenant', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // El dominio de la cookie debe ser configurable por entorno para funcionar en desarrollo y producción
        $cookieDomain = config('session.domain', '127.0.0.1'); // Cambia en producción
        $cookieSecure = config('session.secure', false); // true en producción con HTTPS
        return response()->json([
            'user' => $user,
            'permission' => $permisos,
            'roleuser' => ['id' => $role->role_id, 'role' => $role->name],
            'modulos' => $modulosActivos, // Agregar módulos activos del tenant
            'token_type' => 'bearer',
        ])->cookie(
            'access_token',
            $token,
            auth('api')->factory()->getTTL(), // Usa el TTL configurado en JWT (525600 min = 1 año)
            '/',
            $cookieDomain, // dominio configurable
            $cookieSecure, // secure configurable
            true,  // httpOnly
            false, // raw
            'lax'  // samesite
        );
    }
}
