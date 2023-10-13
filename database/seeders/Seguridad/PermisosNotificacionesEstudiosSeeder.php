<?php

namespace Database\Seeders\Seguridad;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisosNotificacionesEstudiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'estudios.notificar.index'])->syncRoles('Administrador', 'Transcriptor');
    }
}
