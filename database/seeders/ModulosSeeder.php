<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigModulo;
use Illuminate\Database\Seeder;

class ModulosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfigModulo::create([
            'modulo' => 'Rx',
        ]);

        ConfigModulo::create([
            'modulo' => 'Ecografias',
        ]);

        ConfigModulo::create([
            'modulo' => 'Oftalmologia',
        ]);
    }
}
