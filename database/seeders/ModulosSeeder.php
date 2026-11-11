<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Seeder;

class ModulosSeeder extends Seeder
{
    /**
     * Siembra los módulos clínicos y asistenciales base de LIDiag.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function run(): void
    {
        $modulos = [
            'Rx',
            'Ecografias',
            'Tomografia',
            'Resonancia',
            'Mamografia',
            'Oftalmologia',
        ];

        foreach ($modulos as $nombre) {
            ConfigModulo::firstOrCreate([
                'modulo' => $nombre,
            ]);
        }

        // Asignación inicial de productos sin módulo a su modalidad asistencial correspondiente
        $moduloMap = [
            'Ecografias' => ['ECOGRAF', 'ULTRASON'],
            'Mamografia' => ['MAMOGRAF', 'XEROMAMOGRAFIA'],
            'Tomografia' => ['TOMOGRAF', 'TAC', 'UROTAG'],
            'Resonancia' => ['RESONANC', 'RMN', 'COLANGIORESONANCIA'],
            'Rx' => ['RADIOGRAF', 'RAYOS', 'FLUOROSCOPIA', ' RX '],
        ];

        foreach ($moduloMap as $moduloNombre => $keywords) {
            $moduloModel = ConfigModulo::where('modulo', $moduloNombre)->first();
            if (! $moduloModel) {
                continue;
            }

            foreach ($keywords as $kw) {
                ConfigProducto::whereNull('modulo_id')
                    ->where(function ($q) use ($kw) {
                        $q->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloModel->id]);
            }
        }
    }
}
