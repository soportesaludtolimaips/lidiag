<?php

namespace Database\Seeders;

use App\Models\Dosimetria\UmbralNrd;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UmbralesNrdSeeder extends Seeder
{
    /**
     * Siembra los Niveles de Referencia para Diagnóstico (NRD) canónicos según
     * la Resolución 482 de 2018 del Ministerio de Salud de Colombia, OIEA e ICRP 135.
     *
     * Flujo del proceso:
     * 1. Verifica y crea la columna 'region_anatomica' de forma defensiva si no existiera en la tabla.
     * 2. Normaliza umbrales base legados sin región anatómica a la categoría 'GENERAL'.
     * 3. Define el catálogo estándar multidimensional por Modalidad, Tipo de Métrica y Región Anatómica.
     * 4. Ejecuta un updateOrCreate idempotente para permitir re-ejecuciones seguras sin duplicidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function run(): void
    {
        if (Schema::hasTable('medicion_dosis_umbrales_nrd') && ! Schema::hasColumn('medicion_dosis_umbrales_nrd', 'region_anatomica')) {
            Schema::table('medicion_dosis_umbrales_nrd', function (Blueprint $table) {
                $table->string('region_anatomica', 50)->nullable()->after('tipo_metrica');
                $table->index('region_anatomica', 'idx_umbrales_region');
            });
        }

        UmbralNrd::whereNull('region_anatomica')->update(['region_anatomica' => 'GENERAL']);

        $umbrales = [
            // =========================================================================
            // 1. TOMOGRAFÍA COMPUTARIZADA (CT) — CTDIvol (mGy)
            // =========================================================================
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'CRANEO',
                'valor_alerta' => 45.0000,
                'valor_limite_nrd' => 50.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC Cráneo Adulto (Cerebro simple o contrastado) — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'TORAX',
                'valor_alerta' => 12.0000,
                'valor_limite_nrd' => 15.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC Tórax Adulto estándar — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'ABDOMEN',
                'valor_alerta' => 20.0000,
                'valor_limite_nrd' => 25.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC Abdomen / UROTAC Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'PELVIS',
                'valor_alerta' => 18.0000,
                'valor_limite_nrd' => 22.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC Pelvis Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'COLUMNA',
                'valor_alerta' => 25.0000,
                'valor_limite_nrd' => 35.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC Columna Cervical / Lumbar Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'region_anatomica' => 'GENERAL',
                'valor_alerta' => 25.0000,
                'valor_limite_nrd' => 30.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'TAC General / Referencia Global Adulto — Res. 482 / OIEA',
            ],

            // =========================================================================
            // 2. TOMOGRAFÍA COMPUTARIZADA (CT) — DLP (mGy*cm)
            // =========================================================================
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'CRANEO',
                'valor_alerta' => 800.0000,
                'valor_limite_nrd' => 1000.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC Cráneo Adulto completo — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'TORAX',
                'valor_alerta' => 350.0000,
                'valor_limite_nrd' => 450.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC Tórax Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'ABDOMEN',
                'valor_alerta' => 650.0000,
                'valor_limite_nrd' => 800.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC Abdomen Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'PELVIS',
                'valor_alerta' => 500.0000,
                'valor_limite_nrd' => 650.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC Pelvis Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'COLUMNA',
                'valor_alerta' => 550.0000,
                'valor_limite_nrd' => 700.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC Columna Adulto — Res. 482 / ICRP 135',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'region_anatomica' => 'GENERAL',
                'valor_alerta' => 600.0000,
                'valor_limite_nrd' => 750.0000,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'DLP TAC General / Referencia Global — Res. 482 / OIEA',
            ],

            // =========================================================================
            // 3. RADIOGRAFÍA CONVENCIONAL Y DIGITAL (DX / CR) — DAP (Gy*cm2)
            // =========================================================================
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'TORAX',
                'valor_alerta' => 0.1200,
                'valor_limite_nrd' => 0.1500,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Tórax PA Adulto — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'ABDOMEN',
                'valor_alerta' => 2.0000,
                'valor_limite_nrd' => 2.5000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Abdomen Simple AP Adulto — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'COLUMNA',
                'valor_alerta' => 1.8000,
                'valor_limite_nrd' => 2.2000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Columna Lumbar AP/LAT — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'PELVIS',
                'valor_alerta' => 1.5000,
                'valor_limite_nrd' => 2.0000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Pelvis AP Adulto — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'CRANEO',
                'valor_alerta' => 0.8000,
                'valor_limite_nrd' => 1.0000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Cráneo AP/LAT Adulto — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'EXTREMIDADES',
                'valor_alerta' => 0.2000,
                'valor_limite_nrd' => 0.3000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X Extremidades Adulto — Res. 482 / IAEA TRS-457',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'region_anatomica' => 'GENERAL',
                'valor_alerta' => 1.0000,
                'valor_limite_nrd' => 1.2000,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Rayos X General / Referencia Global — Res. 482 / IAEA',
            ],

            // =========================================================================
            // 4. MAMOGRAFÍA DIGITAL (MG / DX) — DGM (mGy)
            // =========================================================================
            [
                'modalidad' => 'MG',
                'tipo_metrica' => 'DGM',
                'region_anatomica' => 'MAMA',
                'valor_alerta' => 2.4000,
                'valor_limite_nrd' => 3.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'Mamografía DGM por proyección (4.5 cm comprimido) — Res. 482 / OIEA',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DGM',
                'region_anatomica' => 'MAMA',
                'valor_alerta' => 2.4000,
                'valor_limite_nrd' => 3.0000,
                'unidad_medida' => 'mGy',
                'descripcion' => 'Mamografía Digital DX/CR — DGM Res. 482 / OIEA',
            ],
        ];

        foreach ($umbrales as $u) {
            UmbralNrd::updateOrCreate(
                [
                    'sede_id' => null,
                    'modalidad' => $u['modalidad'],
                    'tipo_metrica' => $u['tipo_metrica'],
                    'region_anatomica' => $u['region_anatomica'],
                ],
                [
                    'valor_alerta' => $u['valor_alerta'],
                    'valor_limite_nrd' => $u['valor_limite_nrd'],
                    'unidad_medida' => $u['unidad_medida'],
                    'descripcion' => $u['descripcion'],
                    'estado' => true,
                ]
            );
        }
    }
}
