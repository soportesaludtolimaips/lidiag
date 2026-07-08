<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigServicioCita;
use App\Models\Configuracion\ConfigWhatsapp;
use Illuminate\Database\Seeder;

class ConfigWhatsappSeeder extends Seeder
{
    public function run(): void
    {
        // Mensaje genérico para cuando no hay saludo específico
        ConfigWhatsapp::firstOrCreate(
            ['sede_id' => null, 'servicio_cita_id' => null],
            [
                'mensaje_bienvenida' => "Hola, bienvenido a Lidiag Salud.\n\nPor favor proporcione su número de documento para iniciar el proceso.",
                'activo' => true,
            ]
        );

        $mensajesPorServicio = [
            'Tomografia' => "Hola, bienvenido a Lidiag Salud.\n\nUsted solicita un servicio de *Tomografía*. Por favor proporcione su número de documento para continuar.",
            'Resonancia' => "Hola, bienvenido a Lidiag Salud.\n\nUsted solicita un servicio de *Resonancia Magnética*. Por favor proporcione su número de documento para continuar.",
            'Rayos X' => "Hola, bienvenido a Lidiag Salud.\n\nUsted solicita un servicio de *Rayos X*. Por favor proporcione su número de documento para continuar.",
            'Ecografia' => "Hola, bienvenido a Lidiag Salud.\n\nUsted solicita un servicio de *Ecografía*. Por favor proporcione su número de documento para continuar.",
            'Mamografia' => "Hola, bienvenido a Lidiag Salud.\n\nUsted solicita un servicio de *Mamografía*. Por favor proporcione su número de documento para continuar.",
        ];

        $servicios = ConfigServicioCita::all();

        foreach ($servicios as $servicio) {
            $mensaje = $mensajesPorServicio[$servicio->nombre] ?? null;
            if (! $mensaje) {
                continue;
            }

            ConfigWhatsapp::firstOrCreate(
                ['sede_id' => $servicio->sede_id, 'servicio_cita_id' => $servicio->id],
                [
                    'mensaje_bienvenida' => $mensaje,
                    'activo' => true,
                ]
            );
        }
    }
}
