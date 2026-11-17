<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigTipoRecaudoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => 'ATENCION',
                'nombre' => 'Por Atención (SAHI)',
                'aplica_recaudo' => true,
                'envia_sahi' => true,
                'descripcion' => 'Procedimiento convencional facturado y gestionado por atención SAHI.',
                'estado' => true,
            ],
            [
                'codigo' => 'PARTICULAR',
                'nombre' => 'Particular',
                'aplica_recaudo' => true,
                'envia_sahi' => false,
                'descripcion' => 'Procedimiento particular con recaudo directo. Envío exclusivo al portal paciente.',
                'estado' => true,
            ],
            [
                'codigo' => 'CORTESIA',
                'nombre' => 'Cortesía',
                'aplica_recaudo' => false,
                'envia_sahi' => false,
                'descripcion' => 'Procedimiento de cortesía sin recaudo. Envío exclusivo al portal paciente.',
                'estado' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('config_tipos_recaudo')->updateOrInsert(
                ['codigo' => $tipo['codigo']],
                array_merge($tipo, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
