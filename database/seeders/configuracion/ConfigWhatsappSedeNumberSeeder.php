<?php

namespace Database\Seeders\Configuracion;

use App\Models\Configuracion\ConfigSedeWhatsappNumber;
use Illuminate\Database\Seeder;

class ConfigWhatsappSedeNumberSeeder extends Seeder
{
    public function run(): void
    {
        $numbers = [
            [
                'sede_id' => 1,
                'phone_number_id' => '1090680644127207',
                'numero' => '15556370875',
                'descripcion' => 'Numero principal WhatsApp',
                'activo' => true,
            ],
        ];

        foreach ($numbers as $data) {
            ConfigSedeWhatsappNumber::firstOrCreate(
                ['phone_number_id' => $data['phone_number_id']],
                $data
            );
        }
    }
}
