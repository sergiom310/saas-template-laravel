<?php

namespace App\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SubdomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        // 1️⃣ Intentar primero obtener el host del ORIGIN (más confiable en CORS)
        $origin = $request->header('Origin') ?? '';
        $host = parse_url($origin, PHP_URL_HOST); // extrae sólo el dominio del Origin

        // 2️⃣ Si no hay Origin (por ejemplo, desde Postman), usar el host del request
        if (!$host) {
            $host = $request->getHost();
        }        

        // \Log::info('[TenantFinder] Host recibido', [
        //     'host' => $host,
        //     'full_url' => $request->fullUrl(),
        //     'origin' => $request->header('origin'),
        // ]);

        // 3️⃣ Dividir el dominio por puntos
        $parts = explode('.', $host);

        // Si el primer subdominio es 'api', lo quitamos
        if ($parts[0] === 'api') {
            array_shift($parts);
        }

        // 4️⃣ El identificador del tenant es ahora el primer subdominio real
        $identifier = $parts[0] ?? null;

        // \Log::info('[TenantFinder] Identificador extraído', [
        //     'identifier' => $identifier
        // ]);

        if (!$identifier) {
            \Log::warning('[TenantFinder] No se encontró identificador de tenant en el host', [
                'host' => $host
            ]);
            return null;
        }
        
        // 5️⃣ Buscar el tenant en base de datos
        $tenant = Tenant::where('domain', $identifier)->first();

        // \Log::info('[TenantFinder] Tenant encontrado', [
        //     'tenant' => $tenant
        // ]);
        return $tenant;
    }
}
