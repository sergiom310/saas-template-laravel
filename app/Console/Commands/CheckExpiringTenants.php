<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant\CustomTenantModel;
use App\Mail\TenantExpirationReminderMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CheckExpiringTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:check-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar tenants próximos a expirar y enviar correos de recordatorio (5 días antes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando tenants próximos a expirar...');
        
        // Obtener la fecha de hoy y dentro de 5 días
        $hoy = Carbon::now()->startOfDay();
        $cincosDias = Carbon::now()->addDays(5)->endOfDay();
        
        // Buscar tenants activos cuya fecha de expiración esté entre hoy y 5 días
        $tenantsProximosExpirar = CustomTenantModel::where('is_active', true)
            ->whereBetween('expires_at', [$hoy, $cincosDias])
            ->with('modulos')
            ->get();
        
        $this->info("Encontrados {$tenantsProximosExpirar->count()} tenants próximos a expirar.");
        
        foreach ($tenantsProximosExpirar as $tenant) {
            try {
                // Calcular días restantes correctamente (desde hoy hasta expires_at)
                // Usamos startOfDay() para comparar días completos y evitar problemas con horas
                // Sumamos 1 para incluir el día de hoy en el conteo (más amigable para el usuario)
                $hoyInicio = Carbon::now()->startOfDay();
                $expiraInicio = Carbon::parse($tenant->expires_at)->startOfDay();
                $diasRestantes = $hoyInicio->diffInDays($expiraInicio) + 1;
                
                // Construir URL de acceso al tenant
                $frontUrl = config('services.frontend_protocol') . '://' . $tenant->domain . '.' . config('services.frontend_domain');
                if (config('services.frontend_port')) {
                    $frontUrl .= ':' . config('services.frontend_port');
                }
                
                // Enviar correo de recordatorio
                Mail::to($tenant->owner_email)->send(
                    new TenantExpirationReminderMail($tenant, $diasRestantes, $frontUrl)
                );
                
                $this->info("Correo enviado a: {$tenant->owner_email} ({$tenant->name}) - Expira en {$diasRestantes} días");
                
            } catch (\Exception $e) {
                $this->error("Error al enviar correo a {$tenant->owner_email}: " . $e->getMessage());
            }
        }
        
        $this->info('Proceso completado.');
        return 0;
    }
}
