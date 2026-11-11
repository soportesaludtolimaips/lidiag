<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Seeder;

class ConfigProductosEcografias1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            ['cod_cups' => '882222', 'nom_produc' => 'DUPLEX SCANNING [DOPPLER ECOGRAFIA DE ARTERIAS RENALES A COLOR', 'requi_lectura' => true],
            ['cod_cups' => '882296', 'nom_produc' => 'DUPLEX SCANNING [DOPPLER ECOGRAFIA CON EVALUACION DE FLUJO SANGUINEO EN HIPERTENSION PORTAL A COLOR', 'requi_lectura' => true],
        ];

        foreach ($productos as $data) {
            ConfigProducto::firstOrCreate(
                ['cod_cups' => $data['cod_cups']],
                [
                    'nom_produc' => $data['nom_produc'],
                    'requi_lectura' => $data['requi_lectura'],
                ]
            );
        }
    }
}
