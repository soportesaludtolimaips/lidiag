<?php

namespace App\Console\Commands;

use App\Helpers\SahiConnectionHelper;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioProducto;
use App\Models\User;
use App\Services\Interfaces\SAHITranscibirService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class EstudiosSahiEnviarRangoCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * Uso:
     * - php artisan estudios:sahi-enviar-rango (usa rango por defecto 2026-10-28 a 2026-10-30)
     * - php artisan estudios:sahi-enviar-rango 2026-10-28 2026-10-30 --dry-run
     * - php artisan estudios:sahi-enviar-rango 2026-10-28 2026-10-30 --sede=1
     * - php artisan estudios:sahi-enviar-rango 2026-10-28 2026-10-30 --force
     */
    protected $signature = 'estudios:sahi-enviar-rango
        {from=2026-10-28 : Fecha inicial en formato YYYY-MM-DD}
        {to=2026-10-30 : Fecha final en formato YYYY-MM-DD}
        {--from= : Fecha inicial alternativa (YYYY-MM-DD)}
        {--to= : Fecha final alternativa (YYYY-MM-DD)}
        {--sede= : ID de la sede específica (opcional)}
        {--field=fechor_trascrito : Campo de fecha para el filtro (fechor_trascrito, fec_estudio, created_at, all)}
        {--force : Reenviar incluso estudios que ya tengan estado ENVIADO}
        {--dry-run : Muestra los registros encontrados sin ejecutar inserciones en SAHI}';

    /**
     * Descripción del comando de consola.
     */
    protected $description = 'Envía a SAHI (SQL Server) los estudios de radiología transcritos dentro de un rango de fechas.';

    /**
     * Ejecuta el despacho en lote de estudios hacia SAHI por rango de fechas.
     *
     * Flujo del proceso:
     * 1. Resuelve y valida las fechas inicial y final (por argumento u opción).
     * 2. Consulta los registros de estudios_productos con transcripción y atención en sedes con software SAHI.
     * 3. Si se especifica --dry-run, imprime una tabla con el detalle de los estudios y finaliza sin escribir.
     * 4. Valida preventivamente la conectividad con SQL Server antes de iniciar el procesamiento.
     * 5. Para cada registro pendiente (o todos si se usa --force), envía a SAHI actualizando el tracking.
     * 6. Imprime un reporte resumen con los resultados de la operación.
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
        $from = (string) ($this->option('from') ?: $this->argument('from'));
        $to = (string) ($this->option('to') ?: $this->argument('to'));

        try {
            $fromDate = Carbon::parse($from)->format('Y-m-d');
            $toDate = Carbon::parse($to)->format('Y-m-d');
        } catch (\Throwable $e) {
            $this->error("Formato de fecha inválido. Utilice YYYY-MM-DD. Error: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($fromDate > $toDate) {
            $this->error("La fecha inicial ({$fromDate}) no puede ser mayor a la fecha final ({$toDate}).");

            return self::FAILURE;
        }

        $sedeId = $this->option('sede') ? (int) $this->option('sede') : null;
        $field = (string) $this->option('field');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');
        $hasTracking = Schema::hasColumn('estudios_productos', 'estado_envio_sahi');

        $this->info("==========================================================");
        $this->info(" ENVÍO DE ESTUDIOS A SAHI POR RANGO DE FECHAS");
        $this->info(" Rango: {$fromDate} hasta {$toDate} | Campo: {$field}");
        if ($sedeId) {
            $this->info(" Filtrando por Sede ID: {$sedeId}");
        }
        if ($force) {
            $this->warn(" Modo FORCE: Se incluirán registros ya marcados como ENVIADO.");
        }
        if ($dryRun) {
            $this->warn(" Modo DRY-RUN: Simulación activa. No se realizarán escrituras en SAHI.");
        }
        $this->info("==========================================================");

        $query = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->where('config_sedes.interface_software', 'SAHI')
            ->whereNotNull('estudios_productos.fechor_trascrito')
            ->whereNotNull('estudios.atencion');

        if ($sedeId) {
            $query->where('estudios.sede_id', $sedeId);
        }

        // Filtro por campo de fecha
        switch ($field) {
            case 'fec_estudio':
                $query->whereBetween(DB::raw('DATE(estudios.fec_estudio)'), [$fromDate, $toDate]);
                break;
            case 'created_at':
                $query->whereBetween(DB::raw('DATE(estudios.created_at)'), [$fromDate, $toDate]);
                break;
            case 'all':
                $query->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereBetween(DB::raw('DATE(estudios_productos.fechor_trascrito)'), [$fromDate, $toDate])
                        ->orWhereBetween(DB::raw('DATE(estudios.fec_estudio)'), [$fromDate, $toDate])
                        ->orWhereBetween(DB::raw('DATE(estudios.created_at)'), [$fromDate, $toDate]);
                });
                break;
            case 'fechor_trascrito':
            default:
                $query->whereBetween(DB::raw('DATE(estudios_productos.fechor_trascrito)'), [$fromDate, $toDate]);
                break;
        }

        if (! $force && $hasTracking) {
            $query->where(function ($q) {
                $q->whereNull('estudios_productos.estado_envio_sahi')
                    ->orWhere('estudios_productos.estado_envio_sahi', '!=', 'ENVIADO');
            });
        }

        $registros = $query->select([
            'estudios_productos.id',
            'estudios_productos.estudio_id',
            'estudios_productos.cod_cups',
            'estudios_productos.lectura',
            'estudios_productos.fechor_lectura',
            'estudios_productos.fechor_trascrito',
            $hasTracking ? 'estudios_productos.estado_envio_sahi' : DB::raw("'N/A' as estado_envio_sahi"),
            'estudios.atencion',
            'estudios.medico_id',
            'estudios.sede_id',
            'estudios.fec_estudio',
            'config_sedes.nom_sede as sede_nombre',
        ])
            ->orderBy('estudios_productos.id', 'asc')
            ->get();

        if ($registros->isEmpty()) {
            $this->info("No se encontraron estudios pendientes de envío a SAHI en el rango especificado.");

            return self::SUCCESS;
        }

        $this->info("Se encontraron {$registros->count()} registros para procesar.");

        if ($dryRun) {
            $tableData = $registros->map(function ($r) {
                $medico = User::find($r->medico_id);

                return [
                    'Estudio ID' => $r->estudio_id,
                    'Producto ID' => $r->id,
                    'Atención SAHI' => $r->atencion,
                    'CUPS' => $r->cod_cups,
                    'Sede' => $r->sede_nombre ?? $r->sede_id,
                    'Médico' => $medico ? ($medico->name ?: (string) $r->medico_id) : (string) $r->medico_id,
                    'Fecha Trascrito' => $r->fechor_trascrito,
                    'Estado Actual' => $r->estado_envio_sahi ?? 'PENDIENTE',
                ];
            });

            $this->table(
                ['Estudio ID', 'Producto ID', 'Atención SAHI', 'CUPS', 'Sede', 'Médico', 'Fecha Trascrito', 'Estado Actual'],
                $tableData
            );

            return self::SUCCESS;
        }

        // Verificación preventiva de SQL Server
        try {
            DB::connection('sqlsrv')->getPdo();
        } catch (\Throwable $e) {
            $this->error("SQL Server inalcanzable: {$e->getMessage()}. Abortando proceso.");
            Log::error('EstudiosSahiEnviarRangoCommand: SQL Server inalcanzable.', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $total = $registros->count();
        $exitosos = 0;
        $fallidos = 0;
        $idx = 0;

        foreach ($registros as $item) {
            $idx++;
            $atencionId = (int) $item->atencion;
            $this->line("[{$idx}/{$total}] Procesando Producto #{$item->id} (Estudio #{$item->estudio_id}, Atención #{$atencionId}, CUPS {$item->cod_cups})...");

            $sede = ConfigSede::find($item->sede_id);
            SahiConnectionHelper::configureForSede($sede);

            if ($atencionId <= 0) {
                if ($hasTracking) {
                    EstudioProducto::where('id', $item->id)->update([
                        'estado_envio_sahi' => 'ERROR',
                        'fechor_envio_sahi' => Carbon::now(),
                        'error_envio_sahi' => 'Número de atención en el estudio padre no es válido.',
                    ]);
                }
                $this->error(" -> [ERROR] Atención inválida: {$item->atencion}");
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

        $this->info("==========================================================");
        $this->info(" RESUMEN FINAL DE ENVÍO");
        $this->info(" Total registros: {$total}");
        $this->info(" Enviados con éxito: {$exitosos}");
        if ($fallidos > 0) {
            $this->error(" Fallidos: {$fallidos}");
        }
        $this->info("==========================================================");

        return $fallidos === 0 ? self::SUCCESS : self::FAILURE;
    }
}
