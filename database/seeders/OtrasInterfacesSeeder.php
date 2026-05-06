<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OtrasInterfacesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'otrasPlataformas.index'])->assignRole(['Administrador', 'Transcriptor']); // Ver otras plataformas
        Permission::create(['name' => 'otrasPlataformas.enviar'])->assignRole(['Administrador', 'Transcriptor']); // Enviar estudios a otras plataformas
        Permission::create(['name' => 'otrasPlataformas.trasbcribir'])->assignRole(['Administrador', 'Transcriptor']); // Transcribir estudios de otras plataformas
    }
}
