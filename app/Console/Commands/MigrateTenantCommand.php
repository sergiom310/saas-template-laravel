<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class MigrateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate 
                            {database : El nombre de la base de datos del tenant (ej: cliente1)}
                            {--fresh : Eliminar todas las tablas y ejecutar todas las migraciones desde cero}
                            {--seed : Ejecutar los seeders después de migrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta migraciones en un tenant específico para desarrollo local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $database = $this->argument('database');
        
        // Verificar que la base de datos existe
        $dbExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);
        
        if (empty($dbExists)) {
            $this->error("❌ La base de datos '{$database}' no existe.");
            return 1;
        }

        $this->info("🔧 Ejecutando migraciones en tenant: {$database}");

        // Configurar la conexión al tenant
        config(['database.connections.tenant.database' => $database]);
        DB::purge('tenant');

        try {
            if ($this->option('fresh')) {
                // Migrate fresh
                $this->warn("⚠️  Ejecutando migrate:fresh (se eliminarán todas las tablas)...");
                
                $exitCode = Artisan::call('migrate:fresh', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations',
                    '--force' => true,
                ]);
            } else {
                // Migrate normal
                $exitCode = Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations',
                    '--force' => true,
                ]);
            }

            // Mostrar output
            $output = Artisan::output();
            if (trim($output)) {
                $this->line($output);
            }

            if ($exitCode === 0) {
                $this->info("✅ Migraciones ejecutadas exitosamente");
                
                // Ejecutar seeders si se solicitó
                if ($this->option('seed')) {
                    $this->info("🌱 Ejecutando seeders...");
                    Artisan::call('db:seed', [
                        '--database' => 'tenant',
                        '--class' => 'TenantSeeder',
                        '--force' => true,
                    ]);
                    $this->info(Artisan::output());
                }
                
                return 0;
            } else {
                $this->error("❌ Error al ejecutar migraciones");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
