<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionBackupMonitorSeeder extends Seeder
{
    /**
     * Registra y sincroniza los permisos para el monitoreo de backups en LIDiag.
     *
     * Flujo del proceso:
     * 1. Crea de forma idempotente los permisos de monitoreo y ejecución manual de backups.
     * 2. Asigna los permisos al rol Administrador.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function run(): void
    {
        $permisos = [
            'seguridad.backups.index',
            'seguridad.backups.ejecutar',
        ];

        foreach ($permisos as $nombrePermiso) {
            Permission::firstOrCreate([
                'name' => $nombrePermiso,
                'guard_name' => 'web',
            ]);
        }

        $rolAdmin = Role::where('name', 'Administrador')->first();
        if ($rolAdmin) {
            $rolAdmin->givePermissionTo($permisos);
        }
    }
}
