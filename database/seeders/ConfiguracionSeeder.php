<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    public function run()
    {
        DB::table('configuracion')->insert([
            'imp_logo' => 'S',
            'imp_mensaje' => 'S',
            'imp_nit' => 'S',
            'imp_tel' => 'S',
            'imp_dir' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
