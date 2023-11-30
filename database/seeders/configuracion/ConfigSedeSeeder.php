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
        ConfigSede::create(['nom_sede' => 'Espinal - Hospital San Rafael', 'ip_dcm' => '10.35.47.3', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3306', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag@852', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://190.254.23.227:34769/oviyam2/viewer.html', 'tap_oviyam' => 'Imagenes', 'interface_software' => 'SAHI']);
        ConfigSede::create(['nom_sede' => 'La dorada - Hospital San Félix', 'ip_dcm' => '10.35.47.161', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3306', 'usuario_dcm' => 'lidiag', 'password_dcm' => '&LaApp$593', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html', 'tap_oviyam' => 'Servidor']);
        ConfigSede::create(['nom_sede' => 'Chaparral - Hospital San Jua Bautista', 'ip_dcm' => '10.35.47.34', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3307', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag753...', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://186.170.28.80:8088/oviyam2/viewer.html', 'tap_oviyam' => 'Pacs']);
        ConfigSede::create(['nom_sede' => 'Ibague - Hospital Federico lleras', 'ip_dcm' => '172.16.7.6', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3306', 'usuario_dcm' => 'lidiag', 'password_dcm' => 'Lidiag', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html']);
        ConfigSede::create(['nom_sede' => 'Libano - Hospital Regional Alfonso Jaramillo Salazar', 'ip_dcm' => '10.35.47.130', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3306', 'usuario_dcm' => 'appconexlidiag', 'password_dcm' => '@Salud*.*2018*', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html']);
        ConfigSede::create(['nom_sede' => 'Honda - Hospital San Juan de Dios de Honda', 'ip_dcm' => '10.35.47.70', 'bd_dcm' => 'pacsdb', 'puerto_dcm' => '3306', 'usuario_dcm' => 'applidiag', 'password_dcm' => '@salud.2018*', 'usuario_oviyam' => 'admin', 'password_oviyam' => 'admin', 'url_oviyam' => 'http://152.200.148.86:8888/oviyam2/viewer.html']);
    }
}
