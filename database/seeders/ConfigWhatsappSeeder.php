<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigServicio;
use App\Models\Configuracion\ConfigWhatsapp;
use Illuminate\Database\Seeder;

class ConfigWhatsappSeeder extends Seeder
{
    public function run(): void
    {
        // Mensaje genérico para cuando no hay saludo específico
        ConfigWhatsapp::firstOrCreate(
            ['sede_id' => null, 'servicio_id' => null],
            [
                'mensaje_bienvenida' => "Hola, bienvenido a Lidiag Salud.\n\nPor favor proporcione su número de documento para iniciar el proceso.",
                'activo' => true,
            ]
        );

        $mensajesPorServicio = [
            'Ecografia' => "ESTIMADO USUARIO ESTE WHATSAPP ES UNICAMENTE PARA PROGRAMAR CITA DE ECOGRAFIAS, DOPPLER Y BIOPSIAS O BACAF DE MAMA O TIROIDES.\n\n
                            APROXIMADAMENTE EN 8 DÍAS HABILES DAREMOS RESPUESTA A SU SOLICITUD, LOS FINES DE SEMANA NI FESTIVOS LABORAMOS.
                            (DIA HABIL; DE LUNES A VIERNES EN HORARIO DE 7 AM A 3 PM JORNADA CONTINUA).\n\n
                            FAVOR ENVIAR UNA SOLA VEZ FOTOS CLARAS, DE LA ORDEN MEDICA, AUTORIZACIÓN VIGENTE, CÉDULA POR AMBOS LADOS, DIRECCIÓN DONDE VIVE BARRIO MUNICIPIO,
                            NOS INFORMA 2 TELÉFONOS DE CONTACTO. LUEGO ESPERA CON PACIENCIA NUESTRO CONTACTO.  LEA TODOS NUESTROS MENSAJES Y ATIENDA NUESTROS REQUERIMIENTOS
                            Y RECOMENDACIONES PARA AGENDARLE SU CITA.\n\n
                            SI SE ENCUENTRA EMBARAZADA Y SU CITA ES PARA SOLICITAR CITAS DE ECOGRAFIA DE DETALLE ANATOMICO, TRANSLUCENCIA NUCAL O DOPPLER OBSTETRICO, QUE NOS ENVIE FOTOS DEL
                            INFORME ESCRITO DE ECOGRAFIAS PREVIAMENTE TOMADAS, TODAS LAS QUE TENGA Y POR EL MEDIO QUE SE LAS HAYA TOMADO:  EN SU EPPS, PARTICULAR O POR URGENCIAS.\n\n
                            SI REQUIERE CITA ECOCARDIOGRAMAS ADULTO, CONSULTA CARDIOLOGIA, ECOCARDIOGRAMA DE STRESS FARMACOLOGICO, ECOCARDIOGRAMA TRANSESOFAGICO, MONITOREO DE PRESION ARTERIAL
                            CITAS MEDICAS, ESPECIALISTAS Y OTROS SERVICIOS☎️📱📞LLAMADAS AL NUMERO *333 602 5999* Y MENSAJES VIA WHATSAPP AL NUMERO *315 981 9581*.\n\n
                            SISTEMICA Y HOLTER 24 HORAS, FAVOR ENVIAR MENSAJE VIA WHATSAPP AL NUMERO *322 609 8432*.\n\n
                            RX TAC RESONANCIA MAMOGRAFÍA FAVOR ENVIAR MENSAJES VIA WHATSAPP AL NUMERO **.\n\n
                            DIAGNOSTICOS Y EXAMENES OFTALMOLOGICOS: ECOGRAFIAS OCULARES PAQUIMETRIAS BIOMETRIAS OCULARES TOMOGRAFIAS OCULARES (SEGMENTO ANTERIOR Y POSTERIOR) CAMPOS VISUALES RECUENTO ENDOTELIAL. FAVOR ENVIAR MENSAJE VIA WHATSAPP AL NUMERO 3160280167
                            ",
        ];

        ConfigWhatsapp::firstOrCreate(
            ['sede_id' => 1, 'servicio_id' => 9],
            [
                'mensaje_bienvenida' => $mensajesPorServicio['Ecografia'],
                'activo' => true,
            ]
        );

        /* $servicios = ConfigServicio::all();

        foreach ($servicios as $servicio) {
            $mensaje = $mensajesPorServicio[$servicio->nombre] ?? null;
            if (! $mensaje) {
                continue;
            }

            ConfigWhatsapp::firstOrCreate(
                ['sede_id' => $servicio->sede_id, 'servicio_id' => $servicio->id],
                [
                    'mensaje_bienvenida' => $mensaje,
                    'activo' => true,
                ]
            );
        } */
    }
}
