<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Seeder;

class ConfigSedeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sedes = [
            1 => ['nom_sede' => 'Espinal - Hospital San Rafael', 'cod_sede' => 'ESP'],
            2 => ['nom_sede' => 'La dorada - Hospital San Félix', 'cod_sede' => 'LAD'],
            3 => ['nom_sede' => 'Chaparral - Hospital San Jua Bautista', 'cod_sede' => 'CHA'],
            4 => ['nom_sede' => 'Ibague - Hospital Federico lleras', 'cod_sede' => 'IBA'],
            5 => ['nom_sede' => 'Libano - Hospital Regional Alfonso Jaramillo Salazar', 'cod_sede' => 'LIB'],
            6 => ['nom_sede' => 'Honda - Hospital San Juan de Dios de Honda', 'cod_sede' => 'HON'],
            7 => ['nom_sede' => 'Rovira - Hospital San Vicente', 'cod_sede' => 'ROV'],
        ];

        foreach ($sedes as $id => $data) {
            ConfigSede::where('id', $id)
                ->where(function ($q) {
                    $q->whereNull('cod_sede')->orWhere('cod_sede', '');
                })
                ->update(['cod_sede' => $data['cod_sede']]);
        }
    }
}
