<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PlantillaTipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            'config.plantillas.tipos.index',
            'config.plantillas.tipos.create',
            'config.plantillas.tipos.edit',
            'config.plantillas.tipos.destroy',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
        }

        $rolAdmin = Role::where('name', 'Administrador')->first();
        if ($rolAdmin) {
            $rolAdmin->givePermissionTo($permisos);
        }
    }
}
