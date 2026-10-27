<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeederReporteLectura extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permiso = Permission::firstOrCreate([
            'name' => 'estudios.reporteLectura.index',
            'guard_name' => 'web',
        ]);
        $permiso->syncRoles(['Administrador', 'Administrativo', 'Estadistico']);
    }
}
