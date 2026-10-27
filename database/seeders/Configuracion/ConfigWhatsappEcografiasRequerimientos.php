<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicio;
use App\Models\Configuracion\ConfigWhatsappRequerimeinto;
use Illuminate\Database\Seeder;

class ConfigWhatsappEcografiasRequerimientos extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicioId = ConfigServicio::where('sede_id', 1)->where('nombre', 'like', '%Ecografia%')->value('id')
            ?? (ConfigServicio::where('sede_id', 1)->value('id') ?? 5);

        $requerimientos = [
            [
                'sede_id' => 1,
                'servicio_id' => $servicioId,
                'producto_id' => 250,
                'recomendacion' => "*REQUIRIMIENTOS BIOPSIAS O BACAF DE MAMA / BACAF DE TIROIDES:*\n\n"
                    ."BUENOS DIAS ESTIMADO USUARIO: POR FAVOR ESTIMADO USUARIO: VERIFIQUE HABER ENVIADO EL PAQUETE COMPLETO PARA EL PROCEDIMIENTO BIOPSIA O BACAF SE REQUIERE QUE "
                    ."SEAN ENVIADAS TRES 3 ÓRDENES Y TRES 3 AUTORIZACIONES DIRIGIDAS A NUESTRO HOSPITAL SAN RAFAEL DEL ESPINAL:\n\n"
                    ."1. BIOPSIA TRUCUT DE MAMA (COD.851102) O BACAF DE MAMA (COD.851101) BACAF DE TIROIDES (COD.061002)\n"
                    ."2. ESTUDIO DE COLORACIÓN BASICA EN BIOPSIA.TRUCUT (COD.898101) ESTUDIO DE COLORACION BASICA EN CITOLOGIA POR ASPIRACION DE CUALQUIER TEJIDO U ORGANO (COD.898003)\n"
                    ."3. GUIA ECOGRAFÍA (COD.881701) TODAS LAS TRES AUTORIZADAS PARA HOSPITAL SAN RAFAEL DEL ESPINAL ADICIONALMENTE DEBE ENVIARNOS FOTO DEL REPORTE ESCRITO DE MAMOGRAFÍA\n"
                    ."O ECOGRAFÍA YA TOMADA PREVIAMENTE DEL ÁREA A TOMARSE LA BIOPSIA O BACAF. POR FAVOR INFORMARNOS CUALES MEDICAMENTOS TOMANDO ACTUALMENTE Y ADICIONALMENTE CONFIRMARNOS SI ESTÁ TOMANDO ALGÚN MEDICAMENTO ANTICOAGULANTE COMO ASPIRINA, ASA, WARFARINA O ENVIARNOS FÓRMULAS MÉDICAS O LISTA DE MEDICAMENTOS QUE ACTUALMENTE TOMA.",
            ],
            [
                'sede_id' => 1,
                'servicio_id' => $servicioId,
                'producto_id' => 218,
                'recomendacion' => "*DOPPLER OBSTETRICO:*\n\n"
                    ."ESTIMADO USUARIO: NECESARIAMENTE PARA PROGRAMARLE EL DOPPLER OBSTETRICO, REQUERIMOS ADICIONALMENTE QUE LE ORDENEN Y AUTORICEN CON NOSOTROS HOSPITAL SAN RAFAEL DEL ESPINAL UNA ECOGRAFIA OBSTÉTRICA TRANSABDOMINAL COD.881431 SON EXAMENES COMPLEMENTARIOS QUE SE TOMAN Y PROGRAMAN EL MISMO DÍA DE LA CITA CON NUESTRO ESPECIALISTA GINECOOBSTETRA PERINATOLOGO, REQUERIMIENTO DE NUESTRO ESPECIALISTA\n\n"
                    ."PUEDE SOLICITAR QUE SE LO ORDENEN EN CITA CON MÉDICO ESPECIALISTA O CON SU MEDICO DE CONTROL PRENATAL O CON CITA MEDICO GENERAL O EN EL HOSPITAL DONDE LE ATIENDEN QUE LE ORDEN LA ECOGRAFIA OBSTETRICA TRANSABDOMINAL COD.881431 EN SU IPS SE LA ORDENEN Y LA EPS DEL USUARIO SE LA AUTORICEN CON NOSOTROS HOSPITAL SAN RAFAEL DEL ESPINAL PARA PROGRAMAR Y TOMAR AL MISMO TIEMPO LA CITA JUNTO CON EL DOOPLER.",
            ],
        ];

        foreach ($requerimientos as $req) {
            if (! ConfigProducto::where('id', $req['producto_id'])->exists()) {
                continue;
            }

            ConfigWhatsappRequerimeinto::firstOrCreate(
                [
                    'sede_id' => $req['sede_id'],
                    'servicio_id' => $req['servicio_id'],
                    'producto_id' => $req['producto_id'],
                ],
                ['recomendacion' => $req['recomendacion']]
            );
        }
    }
}
