<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Administrador',
            'username' => 'systemadmin',
            'email' => 'sergiogiovanny05@gmail.com',
            'email_verified_at' => Carbon::now(),
            'activation_code' => Str::random(30).time(),
            'password' => bcrypt('12345678'),
            'tipo_documento' => 'Cédula',
            'documento' => '13746931',
            'is_active' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

    }
}
