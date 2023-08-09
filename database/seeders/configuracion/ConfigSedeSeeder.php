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
        ConfigSede::create(['nom_sede' => 'Espinal - Hospital San Rafael', 'ip_dcm' => '127.0.0.1', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'lidiag', 'password_dcm' => '', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://190.254.23.227:34769/oviyam2/viewer.html']);
        ConfigSede::create(['nom_sede' => 'La dorada - Hospital San Félix', 'ip_dcm' => '172.16.7.6', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html', 'tap_oviyam' => 'Servidor']);
        ConfigSede::create(['nom_sede' => 'Chaparral - Hospital san Jua Bautista', 'ip_dcm' => '172.16.7.6', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html']);
        ConfigSede::create(['nom_sede' => 'Ibague - Hospital Federico lleras', 'ip_dcm' => '172.16.7.6', 'bd_dcm' => 'pacsdb', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html']);
    }
}
