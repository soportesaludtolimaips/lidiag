<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigTipoDocumento;
use Illuminate\Database\Seeder;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfigTipoDocumento::create([
            'codigo' => 'CC',
            'tipo_documento' => 'Cédula de Ciudadanía',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'CE',
            'tipo_documento' => 'Cédula de Extranjería',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'PS',
            'tipo_documento' => 'Pasaporte',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'TI',
            'tipo_documento' => 'Tarjeta de Identidad',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'RC',
            'tipo_documento' => 'Registro Civil',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'AS',
            'tipo_documento' => 'Adulto Sin Identificacion',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'MS',
            'tipo_documento' => 'Menor Sin Identificacion',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'NU',
            'tipo_documento' => 'Numero Unico',
        ]);
        ConfigTipoDocumento::create([
            'codigo' => 'RU',
            'tipo_documento' => 'Rut',
        ]);
    }
}
