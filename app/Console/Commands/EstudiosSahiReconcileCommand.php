<?php

namespace App\Console\Commands;

use App\Helpers\SahiConnectionHelper;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioProducto;
use App\Services\Interfaces\SAHITranscibirService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EstudiosSahiReconcileCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * Uso:
     * - php artisan estudios:sahi-reconcile --minutes=5 --limit=50
     */
    protected $signature = 'estudios:sahi-reconcile {--minutes=5 : Minutos mínimos desde la transcripción o fallo} {--limit=50 : Máximo de registros a procesar por corrida}';

    /**
     * Descripción del comando de consola.
     */
    protected $description = 'Reconcilia y reintenta envíos pendientes o fallidos hacia SAHI (SQL Server) para estudios de radiología.';

    /**
     * Ejecuta la reconciliación y reenvío idempotente de estudios hacia SAHI.
     *
     * Flujo del proceso:
     * 1. Verifica la existencia de las columnas de tracking SAHI en estudios_productos.
     * 2. Consulta registros de sedes con interface_software = 'SAHI' en estado ERROR o PENDIENTE.
     * 3. Comprueba la conectividad preventiva con SQL Server antes de procesar la cola.
     * 4. Para cada registro, resuelve la atención, médico y CUPS e invoca SAHITranscibirService.
     * 5. Actualiza el estado a ENVIADO o ERROR registrando los consecutivos de SAHI.
     *
     * @param  SAHITranscibirService  $sahiService  Servicio de despacho hacia SAHI
     * @return int Código de salida del comando (0 = éxito, 1 = fallo)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function handle(SAHITranscibirService $sahiService): int
    {
        if (! Schema::hasColumn('estudios_productos', 'estado_envio_sahi')) {
            $this->info('La tabla estudios_productos no posee la columna estado_envio_sahi. Ejecute las migraciones primero.');

            return self::SUCCESS;
        }

        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, (int) $this->option('limit'));
        $cutoff = Carbon::now()->subMinutes($minutes);

        $pendientes = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->where('config_sedes.interface_software', 'SAHI')
            ->whereNotNull('estudios_productos.fechor_trascrito')
            ->whereNotNull('estudios.atencion')
            ->where(function ($q) use ($cutoff) {
                $q->whereIn('estudios_productos.estado_envio_sahi', ['ERROR', 'PENDIENTE'])
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->where('estudios_productos.estado_envio_sahi', 'ENVIANDO')
                            ->where('estudios_productos.fechor_envio_sahi', '<=', $cutoff);
                    });
            })
            ->select([
                'estudios_productos.id',
                'estudios_productos.estudio_id',
                'estudios_productos.cod_cups',
                'estudios_productos.lectura',
                'estudios_productos.fechor_lectura',
                'estudios_productos.estado_envio_sahi',
                'estudios.atencion',
                'estudios.medico_id',
                'estudios.sede_id',
            ])
            ->limit($limit)
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info("No hay estudios pendientes de reconciliación hacia SAHI con más de {$minutes} min.");

            return self::SUCCESS;
        }

        // Verificación preventiva de conectividad
        try {
            DB::connection('sqlsrv')->getPdo();
        } catch (\Throwable $e) {
            $this->warn('SQL Server (SAHI) inalcanzable: '.$e->getMessage().'. Abortando para evitar errores falsos.');
            Log::warning('EstudiosSahiReconcileCommand abortado: SQL Server inalcanzable.', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info("Procesando {$pendientes->count()} estudios para sincronización con SAHI...");
        $exitosos = 0;
        $fallidos = 0;

        foreach ($pendientes as $item) {
            $sede = ConfigSede::find($item->sede_id);
            SahiConnectionHelper::configureForSede($sede);

            $atencionId = (int) $item->atencion;
            if ($atencionId <= 0) {
                EstudioProducto::where('id', $item->id)->update([
                    'estado_envio_sahi' => 'ERROR',
                    'fechor_envio_sahi' => Carbon::now(),
                    'error_envio_sahi' => 'El valor de atención en el estudio padre no es válido.',
                ]);
                $fallidos++;

                continue;
            }

            try {
                $res = $sahiService->enviarEstudio(
                    $atencionId,
                    (int) $item->medico_id,
                    $item->lectura,
                    $item->cod_cups,
                    $item->fechor_lectura ?? Carbon::now()->format('Y-m-d H:i:s')
                );

                if ($res['success']) {
                    $dataSahi = $res['data'] ?? [];
                    EstudioProducto::where('id', $item->id)->update([
                        'estado_envio_sahi' => 'ENVIADO',
                        'fechor_envio_sahi' => Carbon::now(),
                        'sahi_esquema_149' => $dataSahi['conseEsquemaEncavezado'] ?? null,
                        'sahi_esquema_150' => $dataSahi['conseEsquemaEncavezado2'] ?? null,
                        'error_envio_sahi' => null,
                    ]);
                    $exitosos++;
                } else {
                    EstudioProducto::where('id', $item->id)->update([
                        'estado_envio_sahi' => 'ERROR',
                        'fechor_envio_sahi' => Carbon::now(),
                        'error_envio_sahi' => $res['message'] ?? 'Error desconocido en SAHI',
                    ]);
                    $fallidos++;
                }
            } catch (\Throwable $e) {
                EstudioProducto::where('id', $item->id)->update([
                    'estado_envio_sahi' => 'ERROR',
                    'fechor_envio_sahi' => Carbon::now(),
                    'error_envio_sahi' => $e->getMessage(),
                ]);
                $fallidos++;
            }
        }

        $this->info("Reconciliación finalizada: {$exitosos} sincronizados exitosamente, {$fallidos} fallidos.");

        return self::SUCCESS;
    }
}
