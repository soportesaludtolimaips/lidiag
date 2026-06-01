<?php

namespace Database\Seeders;

use App\Models\Citas\ConfigCitaRechazoMotivo;
use Illuminate\Database\Seeder;

class ConfigCitaRechazoMotivoSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Documentación incompleta',
            'Orden médica vencida',
            'Autorización no válida',
            'Servicio no cubierto',
            'Datos del paciente incorrectos',
            'Cita duplicada',
            'Producto no disponible en la fecha solicitada',
        ];

        foreach ($motivos as $motivo) {
            ConfigCitaRechazoMotivo::firstOrCreate(
                ['motivo' => $motivo],
                ['activo' => true]
            );
        }
    }
}
