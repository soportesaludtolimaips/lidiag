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
        Permission::create(['name' => 'agenda.index'])->syncRoles(['Administrador', 'Administrativo']);
        Permission::create(['name' => 'agenda.create'])->syncRoles(['Administrador', 'Administrativo']);
        Permission::create(['name' => 'agenda.edit'])->syncRoles(['Administrador', 'Administrativo']);
        Permission::create(['name' => 'agenda.show'])->syncRoles(['Administrador', 'Administrativo']);
        Permission::create(['name' => 'agenda.destroy'])->syncRoles(['Administrador', 'Administrativo']);
    }
}
