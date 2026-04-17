<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant\CustomTenantModel;
use App\Models\Modulo;
use Carbon\Carbon;

class TenantController extends Controller
{
    
    public function index()
    {
      $this->middleware('permission:system.index');
      $tenants = CustomTenantModel::with('modulos')->get();
      return response()->json($tenants);
    }

    /**
     * Listar todos los módulos disponibles
     */
    public function listarModulos()
    {
        $modulos = Modulo::active()->get();
        return response()->json($modulos);
    }

    public function store(Request $request)
    {
        $this->middleware('permission:system.create');
        $request->validate([
            'name' => 'required|string',
            'domain' => 'required|string|unique:tenants,domain',
            'database' => 'required|string|unique:tenants,database',
            'name_company' => 'required|string',
            'owner_email' => 'required|email|unique:tenants,owner_email',
            'expires_at' => 'nullable|date',
            'estado_pago' => 'boolean',
            'modulos' => 'nullable|array',
            'modulos.*.modulo_id' => 'required|exists:modulos,id',
            'modulos.*.metodo_pago' => 'required|in:mensual,anual',
        ]);
        
        // Convertir fecha a timestamp si viene en formato YYYY-MM-DD
        $expiresAt = $request->expires_at;
        if ($expiresAt && strlen($expiresAt) === 10) {
            $expiresAt = $expiresAt . ' 00:00:00';
        }
        
        $tenant = CustomTenantModel::create([
            'name' => $request->name,
            'name_company' => $request->name_company,
            'domain' => $request->domain,
            'database' => $request->database,
            'owner_email' => $request->owner_email,
            'expires_at' => $expiresAt,
            'is_active' => 1,
            'estado_pago' => $request->input('estado_pago', false)
        ]);

        // Asociar módulos al tenant
        if ($request->has('modulos')) {
            foreach ($request->modulos as $moduloData) {
                $metodoPago = $moduloData['metodo_pago'];
                
                // fecha de vencimiento inicial son 10 dias de prueba del sistema
                $fechaInicio = now();
                $fechaVencimiento = now()->addDays(10);
                
                $tenant->modulos()->attach($moduloData['modulo_id'], [
                    'metodo_pago' => $metodoPago,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'is_active' => true,
                ]);
            }
        }

        return response()->json($tenant->load('modulos'), 201);
    }

    public function createDatabase($id)
    {
        $this->middleware('permission:system.create');
        $tenant = CustomTenantModel::findOrFail($id);

        $dbName = $tenant->database;
        $dbExists = \DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

        if ($dbExists) {
            return response()->json(['message' => 'La base de datos ya existe para este tenant'], 409);
        }

        \DB::statement("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $tenant->migrated_at = now();
        $tenant->save();

        return response()->json(['message' => 'Base de datos creada para el tenant']);
    }

    public function migrate($id)
    {
        $this->middleware('permission:system.create');
        $tenant = CustomTenantModel::findOrFail($id);

        // Configura la conexión 'tenant' con la base de datos del tenant
        config(['database.connections.tenant.database' => $tenant->database]);
        \DB::purge('tenant');

        // Ejecuta las migraciones en la base de datos del tenant
        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => '/database/migrations',
            '--force' => true,
        ]);

        return response()->json(['message' => 'Migración ejecutada para el tenant']);
    }

    public function show($id)
    {
        $this->middleware('permission:system.index');
        $tenant = CustomTenantModel::findOrFail($id);
        return response()->json($tenant);
    }

    public function update(Request $request, $id)
    {
        $this->middleware('permission:system.update');
        $tenant = CustomTenantModel::findOrFail($id);
        
        // Validar que el domain sea único (excluyendo el tenant actual)
        if ($request->has('domain')) {
            $request->validate([
                'domain' => 'required|string|unique:tenants,domain,' . $id,
            ]);
        }
        
        $data = $request->all();
        
        // NO permitir cambiar el nombre de la base de datos - renombrar BD es muy riesgoso
        // El campo domain SÍ puede modificarse (solo identifica el tenant desde la URL)
        unset($data['database']);
        unset($data['modulos']); // Los módulos se manejan por separado
        
        // Convertir fecha a timestamp si viene en formato YYYY-MM-DD
        if (isset($data['expires_at']) && strlen($data['expires_at']) === 10) {
            $data['expires_at'] = $data['expires_at'] . ' 00:00:00';
        }
        
        $tenant->update($data);

        // Actualizar módulos si se enviaron correctamente (debe ser un array con elementos)
        if ($request->has('modulos') && is_array($request->modulos) && count($request->modulos) > 0) {
            $request->validate([
                'modulos' => 'nullable|array',
                'modulos.*.modulo_id' => 'required|exists:modulos,id',
                'modulos.*.metodo_pago' => 'required|in:mensual,anual',
            ]);

            // Eliminar relaciones anteriores
            $tenant->modulos()->detach();
            
            // Crear nuevas relaciones
            foreach ($request->modulos as $moduloData) {
                $metodoPago = $moduloData['metodo_pago'];
                $fechaInicio = now();
                $fechaVencimiento = $metodoPago === 'mensual' 
                    ? now()->addMonth() 
                    : now()->addYear();
                
                $tenant->modulos()->attach($moduloData['modulo_id'], [
                    'metodo_pago' => $metodoPago,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'is_active' => true,
                ]);
            }
        }
        
        return response()->json($tenant->load('modulos'));
    }

    /**
     * Procesar pago de módulos del tenant
     * Registra el pago, actualiza fechas de vencimiento y estado de pago
     */
    public function pagoModuloTenant(Request $request, $id)
    {
        $request->validate([
            'modulos' => 'required|array|min:1',
            'modulos.*.modulo_id' => 'required|exists:modulos,id',
            'modulos.*.metodo_pago' => 'required|in:mensual,anual',
            'referencia_pago' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
        ], [
            'modulos.required' => 'Debe seleccionar al menos un módulo para pagar',
            'modulos.*.modulo_id.required' => 'El ID del módulo es requerido',
            'modulos.*.modulo_id.exists' => 'El módulo seleccionado no existe',
            'modulos.*.metodo_pago.required' => 'El método de pago es requerido',
            'modulos.*.metodo_pago.in' => 'El método de pago debe ser mensual o anual',
        ]);

        $tenant = CustomTenantModel::findOrFail($id);
        $fechaPago = Carbon::now();
        $maxFechaVencimiento = null;

        try {
            \DB::beginTransaction();

            foreach ($request->modulos as $moduloData) {
                $moduloId = $moduloData['modulo_id'];
                $metodoPago = $moduloData['metodo_pago'];
                
                // Obtener el módulo para obtener el precio
                $modulo = Modulo::findOrFail($moduloId);
                $monto = $metodoPago === 'mensual' ? $modulo->precio_mensual : $modulo->precio_anual;
                
                // Calcular fechas del período
                $fechaInicioPeriodo = Carbon::now();
                $fechaFinPeriodo = $metodoPago === 'mensual' 
                    ? Carbon::now()->addMonth() 
                    : Carbon::now()->addYear();
                
                // Actualizar tenant_modulo con las nuevas fechas y activar
                $tenant->modulos()->updateExistingPivot($moduloId, [
                    'metodo_pago' => $metodoPago,
                    'fecha_inicio' => $fechaInicioPeriodo,
                    'fecha_vencimiento' => $fechaFinPeriodo,
                    'is_active' => true,
                    'updated_at' => Carbon::now(),
                ]);
                
                // Registrar el pago en tenants_pagos
                \DB::connection('landlord')->table('tenants_pagos')->insert([
                    'tenant_id' => $tenant->id,
                    'modulo_id' => $moduloId,
                    'fecha_pago' => $fechaPago,
                    'monto' => $monto,
                    'metodo_pago' => $metodoPago,
                    'fecha_inicio_periodo' => $fechaInicioPeriodo,
                    'fecha_fin_periodo' => $fechaFinPeriodo,
                    'referencia_pago' => $request->referencia_pago,
                    'notas' => $request->notas,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                
                // Guardar la fecha de vencimiento más lejana para actualizar expires_at del tenant
                if (!$maxFechaVencimiento || $fechaFinPeriodo->gt($maxFechaVencimiento)) {
                    $maxFechaVencimiento = $fechaFinPeriodo;
                }
            }
            
            // Actualizar el tenant: estado_pago = true y expires_at = fecha más lejana
            $tenant->update([
                'estado_pago' => true,
                'expires_at' => $maxFechaVencimiento,
            ]);
            
            \DB::commit();
            
            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'tenant' => $tenant->load('modulos'),
                'fecha_pago' => $fechaPago->format('Y-m-d H:i:s'),
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error al procesar pago de tenant: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al procesar el pago',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los pagos registrados con información del tenant y módulo
     * Permite filtrar por tenant_id
     */
    public function listarPagos(Request $request)
    {
        try {
            $query = \DB::connection('landlord')
                ->table('tenants_pagos')
                ->join('tenants', 'tenants_pagos.tenant_id', '=', 'tenants.id')
                ->join('modulos', 'tenants_pagos.modulo_id', '=', 'modulos.id')
                ->select(
                    'tenants_pagos.*',
                    'tenants.name as tenant_name',
                    'tenants.name_company',
                    'tenants.owner_email',
                    'tenants.domain',
                    'modulos.nombre_modulo',
                    'modulos.slug as modulo_slug'
                );
            
            // Filtrar por tenant_id si se proporciona
            if ($request->has('tenant_id') && $request->tenant_id) {
                $query->where('tenants_pagos.tenant_id', $request->tenant_id);
            }
            
            // Ordenar por fecha de pago descendente (más recientes primero)
            $pagos = $query->orderBy('tenants_pagos.fecha_pago', 'desc')->get();
            
            return response()->json($pagos);
            
        } catch (\Exception $e) {
            \Log::error('Error al listar pagos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al consultar pagos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar los pagos del tenant actual (usuario logueado)
     * Para uso de clientes que consultan su propio historial
     */
    public function listarMisPagos()
    {
        try {
            // Obtener el tenant actual desde el contexto de Spatie Multitenancy
            $tenant = \Spatie\Multitenancy\Models\Tenant::current();
            
            if (!$tenant) {
                return response()->json([
                    'error' => 'No se pudo determinar el tenant actual',
                    'message' => 'Debe acceder desde el dominio de su tenant'
                ], 400);
            }
            
            // Obtener el tenant_id desde la conexión landlord
            $tenantData = \DB::connection('landlord')
                ->table('tenants')
                ->where('domain', $tenant->domain)
                ->first();
            
            if (!$tenantData) {
                return response()->json([
                    'error' => 'Tenant no encontrado',
                    'message' => 'El tenant no existe en la base de datos'
                ], 404);
            }
            
            // Consultar los pagos del tenant actual
            $pagos = \DB::connection('landlord')
                ->table('tenants_pagos')
                ->join('modulos', 'tenants_pagos.modulo_id', '=', 'modulos.id')
                ->where('tenants_pagos.tenant_id', $tenantData->id)
                ->select(
                    'tenants_pagos.*',
                    'modulos.nombre_modulo',
                    'modulos.slug as modulo_slug',
                    'modulos.descripcion as modulo_descripcion'
                )
                ->orderBy('tenants_pagos.fecha_pago', 'desc')
                ->get();
            
            return response()->json($pagos);
            
        } catch (\Exception $e) {
            \Log::error('Error al listar mis pagos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al consultar sus pagos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->middleware('permission:system.destroy');
        $tenant = CustomTenantModel::findOrFail($id);
        $tenant->delete();
        return response()->json(['message' => 'Tenant eliminado']);
    }
}