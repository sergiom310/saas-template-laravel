<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::table('permissions')->insert([
            'name' => 'system.index',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'system.update',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'system.create',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'system.destroy',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'admin.index',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'admin.update',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'admin.create',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'admin.destroy',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'usuario.index',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'usuario.update',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'usuario.create',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('permissions')->insert([
            'name' => 'usuario.destroy',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // insertar roles *
        DB::table('roles')->insert([
            'name' => 'System',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'Administrador',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('roles')->insert([
            'name' => 'Usuario',
            'guard_name' => 'api',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // * ahora le asignamos todos los permisos al role system *
        $role = Role::find(1);
        $role->givePermissionTo(Permission::all());

        // * permisos de módulo admin *
        $role = Role::find(2);
        $role->syncPermissions([5, 6, 7, 8, 9, 10, 11, 12]);

        // * permisos de módulo usuario *
        $role = Role::find(3);
        $role->syncPermissions([9, 10, 11, 12]);

        $this->call([
            MetodosPagoSeeder::class,
        ]);
    }
}
