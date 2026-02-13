<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisionEcografiasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Administrador', 'Medico', 'Administrativo', 'Transcriptor'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        $map = [
            'ecografias.index' => ['Administrador', 'Transcriptor', 'Administrativo'],
            'ecografias.admision' => ['Administrador', 'Transcriptor'],
            'ecografias.anular' => ['Administrador', 'Transcriptor'],
            'ecografias.leer' => ['Administrador', 'Medico'],
            'ecografias.reporte' => ['Administrador', 'Medico', 'Administrativo'],
        ];

        foreach ($map as $name => $roles) {
            $perm = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $perm->syncRoles($roles);
        }
    }
}
