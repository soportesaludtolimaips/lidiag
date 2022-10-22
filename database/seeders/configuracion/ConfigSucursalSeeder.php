<?php

namespace Database\Seeders\configuracion;

use App\Models\Configuracion\ConfigSucursale;
use Illuminate\Database\Seeder;

class ConfigSucursalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ConfigSucursale::create(['sucursal' => 'Espinal', 'ip_dcm' => '127.0.0.1', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'root', 'password_dcm' => '', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin']);
    }
}
