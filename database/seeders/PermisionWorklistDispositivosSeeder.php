<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionWorklistDispositivosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function run(): void
    {
        $permisos = [
            'config.dispositivos.index',
            'config.dispositivos.create',
            'config.dispositivos.edit',
            'config.dispositivos.destroy',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
        }

        // Asignar los permisos al rol de Administrador
        $adminRole = Role::where('name', 'Administrador')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permisos);
        }
    }
}
