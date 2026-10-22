<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisoAuditoriaSeeder extends Seeder
{
    public function run(): void
    {
        $perm = Permission::firstOrCreate(
            ['name' => 'seguridad.auditoria.index', 'guard_name' => 'web'],
            ['name' => 'seguridad.auditoria.index', 'guard_name' => 'web']
        );
        $perm->syncRoles(['Administrador']);
    }
}
