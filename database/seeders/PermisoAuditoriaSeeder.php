<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisoAuditoriaSeeder extends Seeder
{
    public function run(): void
    {
        Permission::create(['name' => 'seguridad.auditoria.index'])
            ->assignRole('Administrador');
    }
}
