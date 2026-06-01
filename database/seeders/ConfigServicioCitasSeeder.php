<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigServicioCita;
use Illuminate\Database\Seeder;

class ConfigServicioCitasSeeder extends Seeder
{
    public function run(): void
    {
        $sedes = [
            1 => 'Espinal - Hospital San Rafael',
            2 => 'La dorada - Hospital San Félix',
            3 => 'Chaparral - Hospital San Jua Bautista',
            4 => 'Ibague - Hospital Federico lleras',
            5 => 'Libano - Hospital Regional Alfonso Jaramillo Salazar',
            6 => 'Honda - Hospital San Juan de Dios de Honda',
        ];

        $servicios = [
            ['nombre' => 'Tomografia', 'sede_id' => 1],
            ['nombre' => 'Resonancia', 'sede_id' => 1],
            ['nombre' => 'Rayos X', 'sede_id' => 1],
            ['nombre' => 'Ecografia', 'sede_id' => 1],
            ['nombre' => 'Mamografia', 'sede_id' => 1],
            ['nombre' => 'Tomografia', 'sede_id' => 2],
            ['nombre' => 'Rayos X', 'sede_id' => 2],
            ['nombre' => 'Ecografia', 'sede_id' => 2],
            ['nombre' => 'Rayos X', 'sede_id' => 3],
            ['nombre' => 'Ecografia', 'sede_id' => 3],
            ['nombre' => 'Tomografia', 'sede_id' => 4],
            ['nombre' => 'Resonancia', 'sede_id' => 4],
            ['nombre' => 'Rayos X', 'sede_id' => 4],
            ['nombre' => 'Ecografia', 'sede_id' => 4],
            ['nombre' => 'Rayos X', 'sede_id' => 5],
            ['nombre' => 'Ecografia', 'sede_id' => 5],
            ['nombre' => 'Rayos X', 'sede_id' => 6],
            ['nombre' => 'Ecografia', 'sede_id' => 6],
        ];

        foreach ($servicios as $data) {
            ConfigServicioCita::firstOrCreate(
                ['nombre' => $data['nombre'], 'sede_id' => $data['sede_id']],
                ['estado' => true]
            );
        }
    }
}
