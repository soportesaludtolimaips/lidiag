<?php

namespace App\Console\Commands;

use App\Helpers\SahiConnectionHelper;
use App\Models\Estudio\Estudio;
use App\Services\Interfaces\SAHIService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudiosSahiBackfillDatosCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * Uso:
     * - php artisan estudios:sahi-backfill --atencion=7017456
     * - php artisan estudios:sahi-backfill --estudio-id=212636
     * - php artisan estudios:sahi-backfill --day=365
     * - php artisan estudios:sahi-backfill --days=30 --limit=100
     * - php artisan estudios:sahi-backfill --from=2026-01-01 --to=2026-11-10
     */
    protected $signature = "estudios:sahi-backfill
        {--atencion= : Número de atención puntual a sincronizar}
        {--estudio-id= : ID del estudio puntual a sincronizar}
        {--days= : Filtrar estudios de los últimos N días (ej: 30, 365)}
        {--day= : Alias de --days}
        {--from= : Fecha inicial (YYYY-MM-DD)}
        {--to= : Fecha final (YYYY-MM-DD)}
        {--limit=100 : Cantidad máxima de registros a procesar (0 para procesar todos)}
        {--all : Procesar todos los registros encontrados sin límite}
        {--force : Forzar actualización incluso si ya poseen EPS o Contrato}";

    /**
     * Descripción del comando de consola.
     */
    protected $description = "Sincroniza y completa retroactivamente EPS, Contrato, Plan, Servicio y Médico que ordenó desde SAHI (SQL Server) para estudios en LIDiag.";

    /**
     * Ejecuta el comando de backfill desde SAHI.
     *
     * Flujo del proceso:
     * 1. Resuelve los filtros por atención, ID de estudio, días recientes o rango de fechas.
     * 2. Consulta estudios asociados a sedes SAHI que requieran completar EPS/Contrato o con flag --force.
     * 3. Itera cada estudio y configura la conexión SQL Server según su sede.
     * 4. Invoca buscarEpsPlanContrato para extraer pagador, plan, contrato, servicio y médico.
     * 5. Actualiza los campos correspondientes en la tabla estudios.
     * 6. Para los productos asociados sin medico_ordena, consulta y asigna el médico responsable de SAHI.
     * 7. Emite informe en consola y registros de auditoría.
     *
     * @param  SAHIService  $sahiService  Servicio de interoperabilidad con SAHI
     * @return int Código de salida del comando
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function handle(SAHIService $sahiService): int
    {
        $atencion = $this->option("atencion");
        $estudioId = $this->option("estudio-id");
        $days = $this->option("days") ? (int) $this->option("days") : ($this->option("day") ? (int) $this->option("day") : null);
        $from = $this->option("from");
        $to = $this->option("to");
        $all = (bool) $this->option("all");
        $force = (bool) $this->option("force");
        $limit = $all ? 0 : (int) $this->option("limit");

        $query = Estudio::query()
            ->with(["sede", "productos"])
            ->whereNotNull("atencion")
            ->where("atencion", "!=", "")
            ->whereHas("sede", function ($q) {
                $q->where("interface_software", "SAHI");
            });

        if ($atencion) {
            $query->where("atencion", $atencion);
        } elseif ($estudioId) {
            $query->where("id", (int) $estudioId);
        } else {
            if (! $force) {
                $query->where(function ($q) {
                    $q->whereNull("eps")
                        ->orWhere("eps", "")
                        ->orWhereNull("contrato")
                        ->orWhere("contrato", "");
                });
            }

            if ($days && $days > 0) {
                $since = Carbon::now()->subDays($days)->startOfDay();
                $query->where(function ($q) use ($since) {
                    $q->where("fec_estudio", ">=", $since)
                        ->orWhere("created_at", ">=", $since);
                });
                $this->info("Filtrando estudios de los últimos {$days} días (desde {$since->format('Y-m-d H:i')})...");
            } elseif ($from || $to) {
                if ($from && $to) {
                    $query->whereBetween("fec_estudio", [$from." 00:00:00", $to." 23:59:59"]);
                    $this->info("Filtrando estudios por rango: {$from} hasta {$to}...");
                } elseif ($from) {
                    $query->where("fec_estudio", ">=", $from." 00:00:00");
                    $this->info("Filtrando estudios desde: {$from}...");
                } elseif ($to) {
                    $query->where("fec_estudio", "<=", $to." 23:59:59");
                    $this->info("Filtrando estudios hasta: {$to}...");
                }
            }

            if ($limit > 0) {
                $query->limit($limit);
            }
        }

        $estudios = $query->orderByDesc("id")->get();

        if ($estudios->isEmpty()) {
            $this->info("No se encontraron estudios pendientes para sincronizar datos de SAHI.");

            return self::SUCCESS;
        }

        $this->info("Se encontraron {$estudios->count()} estudios para sincronizar.");

        $actualizados = 0;
        $fallidos = 0;

        foreach ($estudios as $index => $estudio) {
            $num = $index + 1;
            $this->line("[{$num}/{$estudios->count()}] Procesando Estudio #{$estudio->id} (Atención #{$estudio->atencion})...");

            try {
                if (! $estudio->sede) {
                    $this->warn(" -> Sede no encontrada para estudio #{$estudio->id}. Omitido.");
                    $fallidos++;

                    continue;
                }

                SahiConnectionHelper::configureForSede($estudio->sede);

                $res = $sahiService->buscarEpsPlanContrato((int) $estudio->atencion);

                if (! ($res["success"] ?? false) || empty($res["data"])) {
                    $this->warn(" -> No se encontraron datos en SAHI para atención #{$estudio->atencion}.");
                    $fallidos++;

                    continue;
                }

                $datosSahi = (array) (is_array($res["data"]) ? ($res["data"][0] ?? []) : $res["data"]->first());

                $eps = (string) ($datosSahi["NomTercero"] ?? "");
                $contrato = (string) ($datosSahi["DesContrato"] ?? "");
                $plan = (string) ($datosSahi["DesPlan"] ?? "");
                $servicio = (string) ($datosSahi["NomUbicacion"] ?? "");
                $medicoGeneral = (string) ($datosSahi["quien_ordeno"] ?? "");

                $estudio->update([
                    "eps" => $eps,
                    "contrato" => $contrato,
                    "plan" => $plan,
                    "servicio" => $servicio,
                ]);

                // Sincronizar medico_ordena en productos si faltaba
                foreach ($estudio->productos as $producto) {
                    if (empty($producto->medico_ordena)) {
                        $medicoProducto = null;

                        if (! empty($producto->cod_cups)) {
                            $proceQuery = DB::connection("sqlsrv")->select("
                                SELECT TOP 1 QuinOrdenoOrden.NomResponsable
                                FROM hceProceOrden QuinOrdenoOrden
                                    INNER JOIN hceProceDetalle ON QuinOrdenoOrden.IdOrdenPro = hceProceDetalle.IdOrdenPro
                                    INNER JOIN proProducto PoducOrden ON hceProceDetalle.IdProducto = PoducOrden.IdProducto
                                WHERE QuinOrdenoOrden.IdAtencion = ? AND PoducOrden.CodProducto = ?
                                ORDER BY QuinOrdenoOrden.IdOrdenPro DESC
                            ", [(int) $estudio->atencion, $producto->cod_cups]);

                            if (! empty($proceQuery) && ! empty($proceQuery[0]->NomResponsable)) {
                                $medicoProducto = (string) $proceQuery[0]->NomResponsable;
                            }
                        }

                        $medicoFinal = $medicoProducto ?: $medicoGeneral;

                        if (! empty($medicoFinal)) {
                            $producto->update(["medico_ordena" => $medicoFinal]);
                        }
                    }
                }

                $this->info(" -> [OK] Actualizado exitosamente. EPS: {$eps} | Contrato: {$contrato}");
                $actualizados++;
            } catch (\Throwable $e) {
                $this->error(" -> Error procesando estudio #{$estudio->id}: {$e->getMessage()}");
                Log::error("Error en estudios:sahi-backfill para estudio #{$estudio->id}: ".$e->getMessage());
                $fallidos++;
            }
        }

        $this->newLine();
        $this->info("==========================================");
        $this->info("Resumen: Procesados {$estudios->count()}, Actualizados: {$actualizados}, Fallidos: {$fallidos}");
        $this->info("==========================================");

        return self::SUCCESS;
    }
}
