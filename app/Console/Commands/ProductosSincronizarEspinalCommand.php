<?php

namespace App\Console\Commands;

use App\Services\Configuracion\ProductosEspinalSyncService;
use Illuminate\Console\Command;

class ProductosSincronizarEspinalCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * Uso:
     * - php artisan productos:sincronizar-espinal
     * - php artisan productos:sincronizar-espinal --dry-run
     * - php artisan productos:sincronizar-espinal --year=2026 --no-db
     * - php artisan productos:sincronizar-espinal --file="ruta/alternativa.xlsx"
     */
    protected $signature = 'productos:sincronizar-espinal
        {--file= : Ruta alternativa al archivo Excel de productos}
        {--year=2026 : Año de facturación a consultar en SAHI}
        {--dry-run : Ejecutar análisis y cruce sin alterar el archivo Excel ni la base de datos}
        {--no-excel : Omitir actualización física del archivo Excel}
        {--no-db : Omitir actualización en la tabla config_productos de MySQL}';

    /**
     * Descripción del comando de consola.
     */
    protected $description = 'Sincroniza y completa códigos CUPS y tarifas financieras de SAHI (SQL Server) en el archivo Excel y en la base de datos de LIDiag.';

    /**
     * Ejecuta el comando de consola.
     *
     * Flujo del proceso:
     * 1. Determina la ruta del archivo Excel (por defecto o parámetro --file).
     * 2. Evalúa banderas de ejecución (--dry-run, --no-excel, --no-db, --year).
     * 3. Invoca ProductosEspinalSyncService para procesar la resolución de CUPS y consulta financiera en SAHI.
     * 4. Despliega tablas de resultados con CUPS completados y tarifas asignadas.
     * 5. Emite resumen final de registros modificados en Excel y en la base de datos.
     *
     * @param  ProductosEspinalSyncService  $syncService  Servicio de procesamiento y cruce
     * @return int Código de salida
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function handle(ProductosEspinalSyncService $syncService): int
    {
        $defaultPath = base_path('Documentos/Productos por medicos apra el modulo de contabilidad/Productos espinal.xlsx');
        $filePath = $this->option('file') ?: $defaultPath;
        $year = (int) $this->option('year');
        $dryRun = (bool) $this->option('dry-run');
        $noExcel = (bool) $this->option('no-excel');
        $noDb = (bool) $this->option('no-db');

        $actualizarExcel = ! $dryRun && ! $noExcel;
        $actualizarDb = ! $dryRun && ! $noDb;

        $this->info('========================================================================');
        $this->info('  SINCRONIZACIÓN Y ENRIQUECIMIENTO DE PRODUCTOS ESPINAL (LIDIAG / SAHI) ');
        $this->info('========================================================================');
        $this->line(" Archivo: {$filePath}");
        $this->line(" Año base SAHI: {$year}");
        $this->line(' Modo: '.($dryRun ? '<comment>DRY-RUN (Simulación sin cambios)</comment>' : '<info>PRODUCCIÓN (Aplicar cambios)</info>'));
        $this->line(' Actualizar Excel: '.($actualizarExcel ? 'SÍ' : 'NO'));
        $this->line(' Actualizar BD LIDiag: '.($actualizarDb ? 'SÍ' : 'NO'));
        $this->newLine();

        if (! file_exists($filePath)) {
            $this->error("Error: No se encontró el archivo Excel en: {$filePath}");

            return self::FAILURE;
        }

        $this->comment('Iniciando lectura de Excel y cruce con SAHI (SQL Server)...');
        $resultado = $syncService->procesarArchivoExcel($filePath, $actualizarExcel, $actualizarDb, $year);

        if (! ($resultado['success'] ?? false)) {
            $this->error('Error durante el proceso: '.($resultado['message'] ?? 'Fallo desconocido'));

            return self::FAILURE;
        }

        $data = $resultado['data'] ?? [];
        $detalle = $data['detalle'] ?? [];

        // Mostrar primeros 20 y los que no tenían CUPS
        $filasTabla = [];
        foreach ($detalle as $item) {
            $esNuevoCups = empty($item['cups_original']) && ! empty($item['cups']);
            $filasTabla[] = [
                $item['row'],
                $esNuevoCups ? "<comment>{$item['cups']} (Auto)</comment>" : $item['cups'],
                mb_strimwidth($item['nom_produc'], 0, 48, '...'),
                $item['valor_sahi'] ? '$'.number_format($item['valor_sahi'], 0, ',', '.') : '<error>Sin Tarifa</error>',
                $item['origen_tarifa'],
            ];
        }

        $this->table(
            ['Fila', 'CUPS', 'Procedimiento', 'Valor SAHI', 'Fuente de Tarifa'],
            array_slice($filasTabla, 0, 25)
        );

        if (count($filasTabla) > 25) {
            $this->line('... y '.(count($filasTabla) - 25).' procedimientos más procesados.');
        }

        $this->newLine();
        $this->info('========================================================================');
        $this->info('                         RESUMEN DE OPERACIÓN                           ');
        $this->info('========================================================================');
        $this->line(" Total filas evaluadas en Excel: <info>{$data['total_filas']}</info>");
        $this->line(" Códigos CUPS completados automáticamente: <info>{$data['cups_completados']}</info>");
        $this->line(' Valores asignados en Excel (Columna C): '.($actualizarExcel ? "<info>{$data['valores_actualizados_excel']}</info>" : '<comment>Omitido (Dry-Run)</comment>'));
        $this->line(' Productos actualizados en BD (config_productos): '.($actualizarDb ? "<info>{$data['productos_bd_actualizados']}</info>" : '<comment>Omitido (Dry-Run)</comment>'));
        $this->info('========================================================================');

        return self::SUCCESS;
    }
}
