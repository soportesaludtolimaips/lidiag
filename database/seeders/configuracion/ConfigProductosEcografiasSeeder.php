<?php

namespace Database\Seeders\configuracion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Seeder;

class ConfigProductosEcografiasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            ['cod_cups' => '061001', 'nom_produc' => 'BIOPSIA DE GLÁNDULA TIROIDES VÍA ABIERTA', 'requi_lectura' => 1],
            ['cod_cups' => '061002', 'nom_produc' => 'BIOPSIA DE GLÁNDULA TIROIDES VÍA PERCUTÁNEA', 'requi_lectura' => 1],
            ['cod_cups' => '061302', 'nom_produc' => 'BIOPSIA DE GLÁNDULA PARATIROIDES VÍA PERCUTÁNEA', 'requi_lectura' => 1],
            ['cod_cups' => '345001', 'nom_produc' => 'TORACENTESIS DIAGNÓSTICA', 'requi_lectura' => 1],
            ['cod_cups' => '345002', 'nom_produc' => 'TORACENTESIS DE DRENAJE O DESCOMPRESIVA', 'requi_lectura' => 1],
            ['cod_cups' => '345401', 'nom_produc' => 'BIOPSIA DE PLEURA PERCUTÁNEA', 'requi_lectura' => 1],
            ['cod_cups' => '542801', 'nom_produc' => 'PARACENTESIS ABDOMINAL DIAGNÓSTICA VÍA PERCUTÁNEA', 'requi_lectura' => 1],
            ['cod_cups' => '542802', 'nom_produc' => 'PARACENTESIS ABDOMINAL TERAPÉUTICA VÍA PERCUTÁNEA', 'requi_lectura' => 1],
            ['cod_cups' => '542901', 'nom_produc' => 'BIOPSIA POR PUNCIÓN Y ASPIRACIÓN GUIADA POR ECOENDOSCOPIA', 'requi_lectura' => 1],
            ['cod_cups' => '851101', 'nom_produc' => 'BIOPSIA POR PUNCIÓN CON AGUJA FINA DE MAMA', 'requi_lectura' => 1],
            ['cod_cups' => '851102', 'nom_produc' => 'BIOPSIA DE MAMA CON AGUJA (TRUCUT)', 'requi_lectura' => 1],
            ['cod_cups' => '851103', 'nom_produc' => 'BIOPSIA DE MAMA POR ESTEREOTAXIA ', 'requi_lectura' => 1],
            ['cod_cups' => '861101', 'nom_produc' => 'DRENAJE DE COLECCIÓN SUPERFICIAL DE PIEL O TEJIDO CELULAR SUBCUTÁNEO POR INCISIÓN O ASPIRACIÓN', 'requi_lectura' => 1],
            ['cod_cups' => '881220', 'nom_produc' => 'BIOPSIA PERCUTANEA POR PUNCION O ASPIRACION', 'requi_lectura' => 1],
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
