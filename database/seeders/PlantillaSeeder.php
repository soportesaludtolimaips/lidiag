<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PlantillaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeder idempotente: no duplica permisos y no falla si ya existen.
        $permisos = [
            'config.plantillas.index',
            'config.plantillas.create',
            'config.plantillas.edit',
            'config.plantillas.delete',
        ];

        $roles = Role::query()
            ->whereIn('name', ['Administrador', 'Administrativo'])
            ->get();

        foreach ($permisos as $permiso) {
            $permission = Permission::firstOrCreate(
                ['name' => $permiso, 'guard_name' => 'web'],
                ['name' => $permiso, 'guard_name' => 'web']
            );

            // Si los roles existen, los asigna; si no, no revienta el seeder.
            if ($roles->isNotEmpty()) {
                $permission->syncRoles($roles);
            }
        }
    }
}
