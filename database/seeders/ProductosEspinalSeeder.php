<?php

namespace Database\Seeders;

use App\Services\Configuracion\ProductosEspinalSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductosEspinalSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para sincronizar CUPS y valores financieros de productos Espinal.
     *
     * Flujo del proceso:
     * 1. Resuelve la ruta canónica del archivo Excel Productos espinal.xlsx.
     * 2. Si el archivo no existe, emite advertencia en consola y detiene la ejecución.
     * 3. Invoca ProductosEspinalSyncService para procesar la lectura, resolución de CUPS, enriquecimiento financiero desde SAHI y actualización en config_productos.
     * 4. Registra en consola el resultado de productos y códigos actualizados.
     *
     * @param  ProductosEspinalSyncService  $syncService  Servicio de sincronización de productos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function run(ProductosEspinalSyncService $syncService): void
    {
        $filePath = base_path('Documentos/Productos por medicos apra el modulo de contabilidad/Productos espinal.xlsx');

        if (! file_exists($filePath)) {
            $this->command?->warn("Archivo no encontrado: {$filePath}. Seeder omitido.");
            Log::warning("ProductosEspinalSeeder: archivo no encontrado en {$filePath}");

            return;
        }

        $this->command?->info("Iniciando sincronización de productos Espinal desde {$filePath}...");

        $resultado = $syncService->procesarArchivoExcel(
            $filePath,
            actualizarExcel: true,
            actualizarDb: true,
            year: 2026
        );

        if ($resultado['success'] ?? false) {
            $data = $resultado['data'] ?? [];
            $this->command?->info('Sincronización completada exitosamente.');
            $this->command?->line(" -> CUPS completados en Excel: {$data['cups_completados']}");
            $this->command?->line(" -> Valores asignados en Excel: {$data['valores_actualizados_excel']}");
            $this->command?->line(" -> Productos actualizados en BD (config_productos): {$data['productos_bd_actualizados']}");
        } else {
            $this->command?->error('Error en seeder: '.($resultado['message'] ?? 'Fallo desconocido'));
        }
    }
}
