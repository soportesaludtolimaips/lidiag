<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigServicioCitas;
use Illuminate\Database\Seeder;

class ConfigServicioCitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfigServicioCitas::create([
            'nombre' => 'Tomografia',
        ]);

        ConfigServicioCitas::create([
            'nombre' => 'Resonancia',
        ]);

        ConfigServicioCitas::create([
            'nombre' => 'Rayos X',
        ]);

        ConfigServicioCitas::create([
            'nombre' => 'Ecografia',
        ]);

        ConfigServicioCitas::create([
            'nombre' => 'Mamografia',
        ]);
    }
}
