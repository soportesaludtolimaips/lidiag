<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionWhatsAppSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $aux = Role::firstOrCreate(['name' => 'Administrativo']);
        $trans = Role::firstOrCreate(['name' => 'Transcriptor']);

        $perms = [
            ['name' => 'config.whatsapp.saludo.index', 'roles' => [$admin, $aux, $trans]],
            ['name' => 'config.whatsapp.saludo.create', 'roles' => [$admin]],
            ['name' => 'config.whatsapp.saludo.edit', 'roles' => [$admin]],
            ['name' => 'config.whatsapp.saludo.destroy', 'roles' => [$admin]],
        ];

        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p['name']]);
            $perm->syncRoles($p['roles']);
        }
    }
}
