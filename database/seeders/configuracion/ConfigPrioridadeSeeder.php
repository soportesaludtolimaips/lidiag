<?php

namespace Database\Seeders\configuracion;

use App\Models\Configuracion\ConfigPrioridad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfigPrioridadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ConfigPrioridad::create(['nom_priori' => 'Alta', 'nivel' => 1, 'tiempo' => 4, 'tipo_tiempo' => 'Horas']);
        ConfigPrioridad::create(['nom_priori' => 'Media', 'nivel' => 2, 'tiempo' => 24, 'tipo_tiempo' => 'Horas']);
        ConfigPrioridad::create(['nom_priori' => 'Baja', 'nivel' => 3, 'tiempo' => 3, 'tipo_tiempo' => 'Dias', 'observacion' => 'Habiles']);
        ConfigPrioridad::create(['nom_priori' => 'Sin Prioridad', 'nivel' => 4, 'tiempo' => 3, 'tipo_tiempo' => 'Dias', 'observacion' => 'Habiles']);
    }
}
