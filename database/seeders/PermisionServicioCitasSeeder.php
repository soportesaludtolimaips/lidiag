<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionServicioCitasSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $aux = Role::firstOrCreate(['name' => 'Administrativo']);
        $trans = Role::firstOrCreate(['name' => 'Transcriptor']);
        $estad = Role::firstOrCreate(['name' => 'Estadistico']);

        $perms = [
            ['name' => 'config.servicio.citas.index', 'roles' => [$admin, $aux, $trans, $estad]],
            ['name' => 'config.servicio.citas.create', 'roles' => [$admin]],
            ['name' => 'config.servicio.citas.edit', 'roles' => [$admin]],
            ['name' => 'config.servicio.citas.destroy', 'roles' => [$admin]],
        ];

        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p['name']]);
            $perm->syncRoles($p['roles']);
        }
    }
}
