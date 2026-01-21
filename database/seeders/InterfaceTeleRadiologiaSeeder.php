<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InterfaceTeleRadiologiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'tele.ragiologia.index'])->assignRole(['Administrador', 'Transcriptor']); //Ver los estudios de la plataforma de teleradiologia
        Permission::create(['name' => 'tele.ragiologia.enviar'])->assignRole(['Administrador', 'Transcriptor']); //Enviar estudios a la plataforma de teleradiologia
        Permission::create(['name' => 'tele.ragiologia.transcribir'])->assignRole(['Administrador', 'Transcriptor']); //Transcribir estudios de la plataforma de teleradiologia
    }
}
