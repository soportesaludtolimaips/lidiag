<?php

namespace Database\Seeders\configuracion;

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
        ConfigSede::create(['nom_sede' => 'Espinal', 'ip_dcm' => '127.0.0.1', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'root', 'password_dcm' => '', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin']);
    }
}
