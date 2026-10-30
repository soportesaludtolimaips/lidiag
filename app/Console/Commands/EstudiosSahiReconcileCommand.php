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
     * - php artisan estudios:sahi-reconcile --id=123 (Enviar un producto específico de estudio)
     * - php artisan estudios:sahi-reconcile --estudio-id=211528 (Enviar los productos de un estudio específico)
     * - php artisan estudios:sahi-reconcile --minutes=5 --limit=50 (Procesar cola pendiente)
     */
    protected $signature = 'estudios:sahi-reconcile {--id= : ID específico de estudios_productos a enviar a SAHI} {--estudio-id= : ID del estudio padre (tabla estudios) a enviar a SAHI} {--minutes=0 : Minutos mínimos desde la transcripción o fallo (0 procesa todo lo pendiente)} {--limit=50 : Máximo de registros a procesar por corrida}';

    /**
     * Descripción del comando de consola.
     */
    protected $description = 'Reconcilia y reintenta envíos pendientes o fallidos hacia SAHI (SQL Server) para estudios de radiología.';

    /**
     * Ejecuta la reconciliación y reenvío idempotente de estudios hacia SAHI.
     *
     * Flujo del proceso:
     * 1. Verifica si se especificó un ID puntual (--id) o si se procesa la cola de pendientes.
     * 2. Consulta registros de sedes con interface_software = 'SAHI' en estado NULL, ERROR o PENDIENTE.
     * 3. Comprueba la conectividad preventiva con SQL Server antes de procesar la cola.
     * 4. Para cada registro, resuelve la atención, médico y CUPS e invoca SAHITranscibirService.
     * 5. Actualiza el estado a ENVIADO o ERROR registrando los consecutivos de SAHI si la columna existe.
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
        $hasTracking = Schema::hasColumn('estudios_productos', 'estado_envio_sahi');
        $id = $this->option('id') ? (int) $this->option('id') : null;
        $estudioId = $this->option('estudio-id') ? (int) $this->option('estudio-id') : null;

        if (! $hasTracking && ! $id && ! $estudioId) {
            $this->warn('La tabla estudios_productos no posee la columna estado_envio_sahi. Ejecute `php artisan migrate` para habilitar la cola automática, o use `--id={ID}` o `--estudio-id={ID}` para enviar uno específico.');

            return self::SUCCESS;
        }

        $minutes = (int) $this->option('minutes');
        $limit = max(1, (int) $this->option('limit'));
        $cutoff = $minutes > 0 ? Carbon::now()->subMinutes($minutes) : Carbon::now();

        $query = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->where('config_sedes.interface_software', 'SAHI')
            ->whereNotNull('estudios_productos.fechor_trascrito')
            ->whereNotNull('estudios.atencion');

        if ($id) {
            $query->where('estudios_productos.id', $id);
        } elseif ($estudioId) {
            $query->where('estudios.id', $estudioId);
        } else {
            $query->where(function ($q) use ($cutoff, $minutes) {
                $q->whereNull('estudios_productos.estado_envio_sahi')
                    ->orWhereIn('estudios_productos.estado_envio_sahi', ['ERROR', 'PENDIENTE']);
                if ($minutes > 0) {
                    $q->orWhere(function ($q2) use ($cutoff) {
                        $q2->where('estudios_productos.estado_envio_sahi', 'ENVIANDO')
                            ->where('estudios_productos.fechor_envio_sahi', '<=', $cutoff);
                    });
                }
            });
        }

        $pendientes = $query->select([
            'estudios_productos.id',
            'estudios_productos.estudio_id',
            'estudios_productos.cod_cups',
            'estudios_productos.lectura',
            'estudios_productos.fechor_lectura',
            'estudios.atencion',
            'estudios.medico_id',
            'estudios.sede_id',
        ])
            ->limit($limit)
            ->get();

        if ($pendientes->isEmpty()) {
            if ($id) {
                $this->warn("No se encontró el producto de estudio #{$id} con transcripción y atención SAHI válida.");
            } elseif ($estudioId) {
                $this->warn("No se encontraron productos para el estudio #{$estudioId} con transcripción y atención SAHI válida.");
            } else {
                $this->info('No hay estudios pendientes de sincronización hacia SAHI.');
            }

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

            $this->line("Procesando producto #{$item->id} (Estudio #{$item->estudio_id}, Atención #{$atencionId}, CUPS {$item->cod_cups})...");

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
                    if ($hasTracking) {
                        EstudioProducto::where('id', $item->id)->update([
                            'estado_envio_sahi' => 'ENVIADO',
                            'fechor_envio_sahi' => Carbon::now(),
                            'sahi_esquema_149' => $dataSahi['conseEsquemaEncavezado'] ?? null,
                            'sahi_esquema_150' => $dataSahi['conseEsquemaEncavezado2'] ?? null,
                            'error_envio_sahi' => null,
                        ]);
                    }
                    if (! empty($dataSahi['already_exists'])) {
                        $this->warn(" -> [YA EXISTÍA EN SAHI] Vinculados esquemas existentes sin duplicar. Esquema 149: {$dataSahi['conseEsquemaEncavezado']}, Esquema 150: {$dataSahi['conseEsquemaEncavezado2']}");
                    } else {
                        $this->info(" -> [OK] Enviado a SAHI exitosamente. Esquema 149: {$dataSahi['conseEsquemaEncavezado']}, Esquema 150: {$dataSahi['conseEsquemaEncavezado2']}");
                    }
                    $exitosos++;
                } else {
                    $msgError = $res['message'] ?? 'Error desconocido en SAHI';
                    if ($hasTracking) {
                        EstudioProducto::where('id', $item->id)->update([
                            'estado_envio_sahi' => 'ERROR',
                            'fechor_envio_sahi' => Carbon::now(),
                            'error_envio_sahi' => $msgError,
                        ]);
                    }
                    $this->error(" -> [ERROR] Falló envío a SAHI: {$msgError}");
                    $fallidos++;
                }
            } catch (\Throwable $e) {
                if ($hasTracking) {
                    EstudioProducto::where('id', $item->id)->update([
                        'estado_envio_sahi' => 'ERROR',
                        'fechor_envio_sahi' => Carbon::now(),
                        'error_envio_sahi' => $e->getMessage(),
                    ]);
                }
                $this->error(" -> [EXCEPCIÓN] {$e->getMessage()}");
                $fallidos++;
            }
        }

        $this->info("Reconciliación finalizada: {$exitosos} sincronizados exitosamente, {$fallidos} fallidos.");

        return self::SUCCESS;
    }
}
