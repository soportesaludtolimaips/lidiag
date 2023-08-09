<?php

namespace Database\Seeders\Seguridad;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* User::create(['num_docu' => 'Administrator', 'name' => 'Administrator', 'email' => 'administrador@administrador.com', 'password' => bcrypt('123456')])->assignRole('Admintrador', 'Medico', 'Administrativo', 'Transcriptor', 'Estadistico');
        User::create(['num_docu' => 'Medico 1', 'name' => 'Medico 1', 'email' => 'medico1@medico1.com', 'password' => bcrypt('123456')])->assignRole('Medico'); */



        User::create(['num_docu' => '97612752', 'reg_med' => '', 'name' => 'administrador administrador', 'email' => 'administrador@administrador.com', 'password' => bcrypt('123456')])->assignRole('Administrador');
        User::create(['num_docu' => '1113784184', 'reg_med' => '', 'name' => 'Cristian Andrey Muñoz Hortua', 'email' => 'chumy901602@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1105687606', 'reg_med' => '', 'name' => 'LUISA FERNANDA CARTAGENA OVIEDO', 'email' => 'luisacartagena0395@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '000000', 'reg_med' => '', 'name' => 'ESTEFANIA HERMOSA', 'email' => 'hermosavida123@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1006147882', 'reg_med' => '', 'name' => 'KEVIN LOPEZ', 'email' => 'lopezmonteskevin96@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1024494433', 'reg_med' => '', 'name' => 'WILLIAM SALAS AYALA', 'email' => 'wilsal01@live.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '000000', 'reg_med' => '', 'name' => 'NELLY DIAZ  MONTOYA', 'email' => 'nelly_1408@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '000000', 'reg_med' => '', 'name' => 'MARLEN TORRES RODRIGUEZ ', 'email' => 'rxsaludtolima2019@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1082806596', 'reg_med' => '', 'name' => 'YEFER ADNRES AVILEZ GOMEZ', 'email' => 'andres19982016@outlook.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '18129435', 'reg_med' => '', 'name' => 'CARLOS VEGAS MAVISOY', 'email' => 'camive4@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '79291593', 'reg_med' => '788492', 'name' => 'PEDRO SERGIO COLMENARES MILLAN', 'email' => 'sercolme@yahoo.com.ar', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '14398973', 'reg_med' => '7360107', 'name' => 'Camilo Enrique Mejía Gutiérrez', 'email' => 'dicatalina1111@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '18522172', 'reg_med' => '18522172', 'name' => 'TL YESID  CARDOZO VELEZ', 'email' => 'YESCAR01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '80871222', 'reg_med' => '80871222', 'name' => 'TL FELIX ANDRES MARTINEZ BALLESTEROS', 'email' => 'FELMAR01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '79950036', 'reg_med' => '1551', 'name' => 'TL HERNAN DARIO ROA ARIAS', 'email' => 'HERROA01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1049617544', 'reg_med' => '1049617544', 'name' => 'MONICA JULIETH FONSECA PARRA', 'email' => 'monicafonseca988@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '79425534', 'reg_med' => '651194', 'name' => 'TL JUAN CARLOS COLLAZOS ', 'email' => 'JUACOL01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '79554448', 'reg_med' => '00437', 'name' => 'ALVARO ENRIQUE FOSSI DELGADO', 'email' => 'alvarofossi@yahoo.es', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '16934054', 'reg_med' => '', 'name' => 'Efrain Betancourt Lopez', 'email' => 'betancourt.lopez.efrain@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1020742661', 'reg_med' => '1020742661', 'name' => 'TL MARIA MONICA OLARTE QUIÑONEZ', 'email' => 'MAROLA03@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1020742661', 'reg_med' => '1020742661', 'name' => 'MARIA MONICA OLARTE QUIÑONES', 'email' => 'moniol19@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1016046168', 'reg_med' => '1016046168', 'name' => 'TL SANDRA PATRICIA MALAGON BASTO', 'email' => 'SANMAL01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1032462976', 'reg_med' => '1032462976', 'name' => 'TL ANDRES FELIPE VELOSA MORENO', 'email' => 'ANDVEL02@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '43978861', 'reg_med' => '', 'name' => 'ALEJANDRA ECHEVERRY MORENO', 'email' => 'ALEECH01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '92543710', 'reg_med' => '92543710', 'name' => 'TL GUSTAVO GERMAN GUEVARA HERRERA', 'email' => 'GUSGUE01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1083879244', 'reg_med' => '1083879244', 'name' => 'CRISTY JOHNA CATRO PALENCIA', 'email' => 'cristycastro0125@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '79599090', 'reg_med' => '52109797', 'name' => 'TL JORGE ERNESTO AARON GOMEZ', 'email' => 'JORAAR01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '69790950', 'reg_med' => '', 'name' => 'RAFAEL EDUARDO MILLAM', 'email' => 'scanteleradiologia@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '52496819', 'reg_med' => '52496819', 'name' => 'TL MARCELA ANDREA HERNANDEZ VALDERRAMA', 'email' => 'MARHER06@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '0000000', 'reg_med' => '', 'name' => 'SALUD TOLIMA SAS SALUD TOLIMA SAS', 'email' => 'saludtolima@saludtolima.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '52007303', 'reg_med' => '335595', 'name' => 'TL SANTIAGO ANDRES CARDENAS GUERRERO', 'email' => 'SANCAR05@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '102387203', 'reg_med' => '', 'name' => 'FABIO DOMINGO TOVAR ALDANA', 'email' => 'tovaraldana.fabio80@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '72004207', 'reg_med' => '72004207', 'name' => 'Miguel José Acosta López', 'email' => 'migueljose.ime@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1085260394', 'reg_med' => '', 'name' => 'TL DEISHI MIREYA VALLEJO', 'email' => 'DEIVAL01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '43876298', 'reg_med' => '43876298', 'name' => 'TL GENNY MELINA AMAYA PANIAGUA', 'email' => 'GENAMA01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '79554448', 'reg_med' => '80082041', 'name' => 'CARLOS ANDRES CORTES VILLARREAL', 'email' => 'carlosandrescortesv@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '93284421', 'reg_med' => '', 'name' => 'JOSE FABIO MARIN CASTAÑO', 'email' => 'josefabiomarin1403@gmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '77184037', 'reg_med' => '2520', 'name' => 'TL JAIRO FERNANDO COLLAZO LIMA', 'email' => 'JAICOL01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '93401481', 'reg_med' => '73257', 'name' => 'FRANCISCO JAVIER ANDRADE LINARES', 'email' => 'dr_andradel@yahoo.es', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '94457973', 'reg_med' => '760349', 'name' => 'Gustavo Adolfo Quintero Galvis ', 'email' => 'tavoplus13@msn.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '1020725183', 'reg_med' => '1020725183', 'name' => 'TL MANUEL DAVID TORRES GUZMAN', 'email' => 'MANTOR01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '000000', 'reg_med' => '', 'name' => 'GERMÁN MORALES M', 'email' => 'germanmoralesm@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '74083005', 'reg_med' => '', 'name' => 'JOSÉ RODRIGO AGUIRRE VARGAS', 'email' => 'rodrigoxp72@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '52080801', 'reg_med' => '52080801', 'name' => 'TL NORMA LUCIA CORTES MORALES', 'email' => 'NORCOR01@tl.com', 'password' => bcrypt('123456')])->assignRole('Medico');
        User::create(['num_docu' => '000000', 'reg_med' => '', 'name' => 'ESTADISTICA ESTADISTICA', 'email' => 'estadistica@estadistica.com', 'password' => bcrypt('123456')])->assignRole('Medico');






        User::create(['num_docu' => '1054546111', 'reg_med' => '', 'name' => 'Erika Viviana Popayán Linares', 'email' => 'transcripcionsaludtolimadorada@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1054554781', 'reg_med' => '', 'name' => 'Luisa Fernanda Pérez conde', 'email' => 'Perezconde_1991@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1054558125', 'reg_med' => '', 'name' => 'Jesus alberto gaviria cespedes', 'email' => 'chuchogaviria15@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '52464199', 'reg_med' => '', 'name' => 'Nombre yeimy zubieta', 'email' => 'yeizubi@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1073322793', 'reg_med' => '', 'name' => 'Juan Felipe riveros galeano', 'email' => 'juanfe_64@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1053811400', 'reg_med' => '', 'name' => 'Manuel Alejandro montilla Rodríguez', 'email' => 'Alejo.mon2@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '24717623', 'reg_med' => '', 'name' => 'Xiomara delgado cardona', 'email' => 'xidelca1284@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3204795421', 'reg_med' => '', 'name' => ' Emy Esmeralda Diaz Olmos', 'email' => 'emyolmos2702@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3057492365', 'reg_med' => '', 'name' => ' Luz Mélida Gonzalez Hernandez', 'email' => 'melidanov1984@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3227208260', 'reg_med' => '', 'name' => 'Jorge Andrés Rojas Ardila ', 'email' => 'Jorgerojas09@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3206039926', 'reg_med' => '', 'name' => ' Johan Camilo León Díaz', 'email' => 'Senasis96@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3104543006', 'reg_med' => '', 'name' => 'María Murillo ', 'email' => 'daicymurillo@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => ' 321 2504148', 'reg_med' => '', 'name' => 'Martha Biviana Guzmán Mora', 'email' => 'Radiologiahospitalhonda@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '3174952297', 'reg_med' => '', 'name' => 'Sandy Valentina Sanabria Castellanos', 'email' => 'sandyvskast@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '', 'reg_med' => '', 'name' => 'Andrés Leonardo Lozada Chacón ', 'email' => 'andreslozada0430@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '', 'reg_med' => '', 'name' => 'Wilson Javier Molina lozano', 'email' => 'rayosxalternouno@gmail.com ', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '79217301', 'reg_med' => '', 'name' => 'JULIO CESAR VELEZ', 'email' => 'cevelez12@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '38070416', 'reg_med' => '', 'name' => 'VIKY  JAZMIN  SIERRA HERNANDEZ', 'email' => 'vikysierra228@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1110482403', 'reg_med' => '', 'name' => 'OLGA ZULEMA BONILLA', 'email' => 'dannasofiq1003@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1105688656', 'reg_med' => '', 'name' => 'SOL ANGIE CALDERON', 'email' => 'sol_anguie24@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');

        User::create(['num_docu' => '1108207024', 'reg_med' => '', 'name' => 'DIANA MARCELA DIAZ VILLAMIL', 'email' => 'diana.diaz221993@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1110538719', 'reg_med' => '', 'name' => 'LIZETH MELIZA MUÑOZ LOZANO', 'email' => 'lizeth.ml@hotmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '100745876305', 'reg_med' => '', 'name' => 'BIBIAN YURLEY RAMOS BAHAMON', 'email' => 'bivian.ramos@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1104705591', 'reg_med' => '', 'name' => 'DIEGO ALEJANDRO OTALORA CELIS', 'email' => 'diotalora90@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1104701424', 'reg_med' => '', 'name' => 'JESUS NAVARRO AGUDELO', 'email' => 'chuchonava1225@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1012394305', 'reg_med' => '', 'name' => 'JONATHAN GUEVARA GIRALDO', 'email' => 'jonathangiraldo635@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1104701310', 'reg_med' => '', 'name' => 'ALEJANDRO MORENO AGUDELO', 'email' => 'alejandromorenoagudelo603@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1104711067', 'reg_med' => '', 'name' => 'DANIELA FERNANDA RODRIGUEZ MUÑOZ', 'email' => 'drodrigurz0197@gmail.com', 'password' => bcrypt('123456')])->assignRole('Administrativo');
        User::create(['num_docu' => '1111200743', 'reg_med' => '', 'name' => 'LUISA FERNANDA MARTIN GARZON', 'email' => 'lfmartin3@misena.edu.co', 'password' => bcrypt('123456')])->assignRole('Administrativo');
    }
}
