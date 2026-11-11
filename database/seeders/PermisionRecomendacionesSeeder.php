<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder para registrar y sincronizar los permisos del módulo de recomendaciones de productos.
 *
 * Flujo del proceso:
 * 1. Limpia la caché de permisos de Spatie.
 * 2. Registra de forma idempotente los permisos de recomendaciones.
 * 3. Asigna los permisos a los roles Administrador y Administrativo.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-22
 */
class PermisionRecomendacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'config.recomendaciones.index',
            'config.recomendaciones.create',
            'config.recomendaciones.edit',
            'config.recomendaciones.destroy',
        ];

        $roles = Role::query()
            ->whereIn('name', ['Administrador', 'Administrativo'])
            ->get();

        foreach ($permisos as $permiso) {
            $permission = Permission::firstOrCreate(
                ['name' => $permiso, 'guard_name' => 'web'],
                ['name' => $permiso, 'guard_name' => 'web']
            );

            if ($roles->isNotEmpty()) {
                $permission->syncRoles($roles);
            }
        }
    }
}
