<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\CustomTenantModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class UpdateTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:update 
                            {--tenant= : ID o dominio específico del tenant a actualizar}
                            {--force : Forzar la ejecución sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta las migraciones pendientes en todos los tenants existentes o en uno específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtener todos los tenants o uno específico
        $tenants = $this->option('tenant') 
            ? CustomTenantModel::where('id', $this->option('tenant'))
                ->orWhere('domain', $this->option('tenant'))
                ->get()
            : CustomTenantModel::where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            $this->error('No se encontraron tenants para actualizar.');
            return 1;
        }

        $this->info("Se actualizarán {$tenants->count()} tenant(s)");

        if (!$this->option('force') && !$this->confirm('¿Deseas continuar?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $updated = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $this->line("\n---------------------------------");
            $this->info("Procesando tenant: {$tenant->name_company} ({$tenant->domain})");
            
            try {
                // Verificar que la base de datos existe
                $dbExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$tenant->database]);
                
                if (empty($dbExists)) {
                    $this->warn("  ⚠️  La base de datos '{$tenant->database}' no existe. Saltando...");
                    $failed++;
                    continue;
                }

                // Configurar la conexión al tenant
                config(['database.connections.tenant.database' => $tenant->database]);
                DB::purge('tenant');

                // Ejecutar migraciones incrementales
                $this->info("  Ejecutando migraciones de actualización...");
                
                $exitCode = Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations',
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $this->info("  ✓ Tenant actualizado exitosamente");
                    $updated++;
                } else {
                    $this->error("  ✗ Error al actualizar tenant");
                    $failed++;
                }

                // Mostrar output de las migraciones
                $output = Artisan::output();
                if (trim($output)) {
                    $this->line("  " . str_replace("\n", "\n  ", trim($output)));
                }

            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                $failed++;
            }
        }

        // Resumen final
        $this->line("\n=================================");
        $this->info("Resumen de actualización:");
        $this->line("  ✓ Actualizados: {$updated}");
        if ($failed > 0) {
            $this->line("  ✗ Fallidos: {$failed}");
        }
        $this->line("=================================\n");

        return $failed > 0 ? 1 : 0;
    }
}

