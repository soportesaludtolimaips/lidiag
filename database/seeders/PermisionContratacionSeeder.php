<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermisionContratacionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'contratos.index',
            'contratos.create',
            'contratos.edit',
            'contratos.destroy',
            'contratos.show',
            'contratos.detalle.create',
            'contratos.detalle.edit',
            'contratos.detalle.destroy',
        ];

        foreach ($permisos as $name) {
            $perm = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $perm->syncRoles(['Administrador']);
        }
    }
}
