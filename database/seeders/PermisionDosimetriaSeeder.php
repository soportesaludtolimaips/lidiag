<?php

namespace Database\Seeders;

use App\Models\Dosimetria\UmbralNrd;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermisionDosimetriaSeeder extends Seeder
{
    /**
     * Registra permisos de Dosimetría y carga los Niveles de Referencia para Diagnóstico (Resolución 482 de 2018).
     *
     * Flujo del proceso:
     * 1. Registra de forma idempotente los permisos Spatie del módulo de dosimetría radiológica.
     * 2. Asigna los permisos al rol de Administrador.
     * 3. Siembra los umbrales NRD nacionales iniciales para CT, MG y DX.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function run(): void
    {
        $permisos = [
            'dosimetria.dashboard',
            'dosimetria.registros',
            'dosimetria.configurar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $rolAdmin = Role::where('name', 'Administrador')->first();
        if ($rolAdmin) {
            $rolAdmin->givePermissionTo($permisos);
        }

        // Sembrar Niveles de Referencia para Diagnóstico (NRD) estándar nacionales
        $umbralesBase = [
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'CTDIvol',
                'valor_alerta' => 48.0,
                'valor_limite_nrd' => 60.0,
                'unidad_medida' => 'mGy',
                'descripcion' => 'Tomografía Computarizada - Abdomen adulto (Resolución 482 / IAEA)',
            ],
            [
                'modalidad' => 'CT',
                'tipo_metrica' => 'DLP',
                'valor_alerta' => 800.0,
                'valor_limite_nrd' => 1000.0,
                'unidad_medida' => 'mGy*cm',
                'descripcion' => 'Tomografía Computarizada - Producto Dosis-Longitud total examen',
            ],
            [
                'modalidad' => 'MG',
                'tipo_metrica' => 'DGM',
                'valor_alerta' => 2.4,
                'valor_limite_nrd' => 3.0,
                'unidad_medida' => 'mGy',
                'descripcion' => 'Mamografía - Dosis Glandular Media por proyección estándar',
            ],
            [
                'modalidad' => 'DX',
                'tipo_metrica' => 'DAP',
                'valor_alerta' => 3.2,
                'valor_limite_nrd' => 4.0,
                'unidad_medida' => 'Gy*cm2',
                'descripcion' => 'Radiografía Digital - Producto Dosis-Área Tórax PA adulto',
            ],
        ];

        foreach ($umbralesBase as $item) {
            UmbralNrd::firstOrCreate(
                [
                    'sede_id' => null,
                    'modalidad' => $item['modalidad'],
                    'tipo_metrica' => $item['tipo_metrica'],
                ],
                $item
            );
        }
    }
}
