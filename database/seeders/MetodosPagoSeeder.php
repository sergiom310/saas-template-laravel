<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetodosPagoSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            ['detalle' => 'Efectivo', 'activo' => true],
            ['detalle' => 'Tarjeta Crédito', 'activo' => true],
            ['detalle' => 'Transferencia', 'activo' => true],
        ];

        foreach ($metodos as $metodo) {
            $existe = DB::table('metodo_pago')
                ->where('detalle', $metodo['detalle'])
                ->exists();

            if (! $existe) {
                DB::table('metodo_pago')->insert($metodo);
            }
        }
    }
}
