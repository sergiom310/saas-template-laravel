<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class MigrateTenants extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:migrate {--path=}';

    /**
     * The console command description.
     */
    protected $description = 'Run migrations for all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->option('path');

        Tenant::all()->each(function (Tenant $tenant) use ($path) {
            $tenant->makeCurrent();

            $this->info("Migrating tenant: {$tenant->id}");

            Artisan::call('migrate', [
                '--path' => $path,
                '--force' => true,
            ]);

            $this->info(Artisan::output());
        });

        $this->info('Migrations completed for all tenants.');
    }
}
