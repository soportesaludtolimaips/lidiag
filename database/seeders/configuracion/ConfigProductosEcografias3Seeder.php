<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Seeder;

class ConfigProductosEcografias3Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            ['cod_cups' => '881432', 'nom_produc' => 'ECOGRAFIA OBSTETRICA TRANSVAGINAL', 'requi_lectura' => true],
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
