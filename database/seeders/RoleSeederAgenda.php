<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RoleSeederAgenda extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            'agenda.index',
            'agenda.create',
            'agenda.edit',
            'agenda.show',
            'agenda.destroy',
        ];

        foreach ($permisos as $nombre) {
            $permiso = Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
            $permiso->syncRoles(['Administrador', 'Administrativo']);
        }
    }
}
