<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Modulo;

class ModulosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modulos = [
            [
                'nombre_modulo' => 'Agendas',
                'slug' => 'agendas',
                'descripcion' => 'Módulo de gestión de agendas y citas para profesionales. Configuración y reportes.',
                'precio_mensual' => 30000.00,
                'precio_anual' => 360000.00,
                'is_active' => true,
            ],
            [
                'nombre_modulo' => 'Parking',
                'slug' => 'parking',
                'descripcion' => 'Módulo de gestión de entradas, salidas, tarifas y reportes.',
                'precio_mensual' => 30000.00,
                'precio_anual' => 360000.00,
                'is_active' => true,
            ],
        ];

        foreach ($modulos as $modulo) {
            Modulo::updateOrCreate(
                ['slug' => $modulo['slug']],
                $modulo
            );
        }

        $this->command->info('Módulos creados exitosamente.');
    }
}
