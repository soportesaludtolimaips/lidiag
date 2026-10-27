<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Seeder;

class ConfigProductosEcografias2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            ['cod_cups' => '881360', 'nom_produc' => 'ECOGRAFIA PELVICA CON ANALISIS DOPPLER ACR', 'requi_lectura' => true],
            ['cod_cups' => '881436', 'nom_produc' => 'ECOGRAFIA OBSTETRICA DE TRANSLUCENCIA NUCAL', 'requi_lectura' => true],
            ['cod_cups' => '881436', 'nom_produc' => 'ECOGRAFIA OBSTETRICA DE TRANSLUCENCIA NUCAL (CONTROL PRENATAL)', 'requi_lectura' => true],
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
