<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionConsolidadaDetalleSheet;
use App\Exports\Sheets\ProduccionConsolidadaEstadisticasSheet;
use App\Exports\Sheets\ProduccionConsolidadaSinAsignarSheet;
use App\Helpers\DicomModalityHelper;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PDO;

/**
 * Libro Excel Multi-Hoja de Producción Consolidada (Estudios Tomados PACS vs Asignados LIDiag).
 *
 * Flujo del proceso:
 * 1. Conecta de forma resiliente a las bases de datos PACS de las sedes (individual o todas las sedes de la red).
 * 2. Cruza los estudios tomados contra los registros de asignación y lectura médica en LIDiag.
 * 3. Identifica rezagados y genera 3 pestañas analíticas:
 *    - Hoja 1: Estadísticas y Gráficas ejecutivas.
 *    - Hoja 2: Detalle Consolidado registro a registro.
 *    - Hoja 3: Estudios Tomados Sin Asignar (Alerta asistencial).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-09
 */
class ProduccionConsolidadaExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected mixed $sedeId;

    protected ?string $modalidad;

    protected ?string $destino;

    protected string $sedeNombre = "";

    protected Collection $estudiosConsolidados;

    protected Collection $estudiosSinAsignar;

    protected bool $esFallbackLocal = false;

    /**
     * Constructor del reporte de producción consolidada.
     *
     * @param  string  $fechaInicio  Fecha inicial del rango (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final del rango (YYYY-MM-DD).
     * @param  mixed  $sedeId  ID de la sede o null para todas las sedes.
     * @param  string|null  $modalidad  Modalidad diagnóstica opcional (o null para todas).
     * @param  string|null  $destino  Destino del estudio opcional ('salud_tolima' o 'tele_radiologia').
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function __construct(string $fechaInicio, string $fechaFin, $sedeId = null, ?string $modalidad = null, ?string $destino = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->modalidad = ($modalidad && $modalidad !== "TODAS" && $modalidad !== "null") ? trim($modalidad) : null;
        $this->destino = ($destino && $destino !== "todos" && $destino !== "null") ? trim($destino) : null;

        $this->consultarYCruzarDatos();
    }

    /**
     * Conecta a las bases PACS, extrae estudios tomados y los cruza contra LIDiag.
     */
    protected function consultarYCruzarDatos(): void
    {
        // 1. Resolver sedes a consultar
        if (! empty($this->sedeId)) {
            $sedes = ConfigSede::where("id", $this->sedeId)->get();
            $this->sedeNombre = $sedes->first()->nom_sede ?? "Sede Individual";
        } else {
            $sedes = ConfigSede::whereNotNull("ip_dcm")
                ->where("ip_dcm", "!=", "")
                ->get();
            $this->sedeNombre = "Todas las Sedes de la Red";
        }

        $estudiosTomados = collect();

        // 2. Iterar cada sede con conexión resiliente
        foreach ($sedes as $sede) {
            if (empty($sede->ip_dcm) || empty($sede->bd_dcm)) {
                continue;
            }

            // Pre-chequeo veloz de socket TCP (1.5 segundos máx) antes de invocar PDO.
            // Si la VPN está desconectada o la IP es inalcanzable, esto evita que el request HTTP
            // se quede congelado durante 90+ segundos acumulando timeouts PDO secuenciales.
            $puerto = (int) ($sede->puerto_dcm ?: 3306);
            $socketCheck = @fsockopen($sede->ip_dcm, $puerto, $errno, $errstr, 1.5);
            if (! $socketCheck) {
                Log::warning(
                    "ProduccionConsolidadaExport: Servidor PACS no responde en red para la sede {$sede->nom_sede} ({$sede->ip_dcm}:{$puerto}): {$errstr} ({$errno})",
                    ["sede_id" => $sede->id]
                );

                continue;
            }
            fclose($socketCheck);

            $connName = "pacs_temp_".$sede->id;
            config([
                "database.connections.{$connName}" => [
                    "driver" => "mysql",
                    "host" => $sede->ip_dcm,
                    "port" => $puerto,
                    "database" => $sede->bd_dcm,
                    "username" => $sede->usuario_dcm,
                    "password" => $sede->password_dcm,
                    "charset" => "latin1",
                    "collation" => "latin1_swedish_ci",
                    "options" => [
                        PDO::ATTR_TIMEOUT => 8, // Timeout razonable una vez confirmado el socket
                    ],
                ],
            ]);

            try {
                $query = DB::connection($connName)
                    ->table("study")
                    ->join("patient", "patient.pk", "=", "study.patient_fk")
                    ->select(
                        "study.pk as study_pk",
                        "study.study_iuid",
                        "study.accession_no",
                        "study.study_datetime",
                        "study.study_desc",
                        "study.mods_in_study",
                        "patient.pat_id",
                        "patient.pat_name"
                    )
                    ->whereBetween("study.study_datetime", [
                        $this->fechaInicio." 00:00:00",
                        $this->fechaFin." 23:59:59",
                    ]);

                if ($this->modalidad) {
                    DicomModalityHelper::aplicarFiltroModalidad($query, "study.mods_in_study", $this->modalidad);
                }

                $rows = $query->orderBy("study.study_datetime", "asc")->get();

                foreach ($rows as $row) {
                    $row->sede_id = $sede->id;
                    $row->nom_sede = $sede->nom_sede;
                    if (isset($row->pat_name) && is_string($row->pat_name)) {
                        $row->pat_name = mb_check_encoding($row->pat_name, 'UTF-8')
                            ? $row->pat_name
                            : mb_convert_encoding($row->pat_name, 'UTF-8', 'ISO-8859-1');
                    }
                    if (isset($row->study_desc) && is_string($row->study_desc)) {
                        $row->study_desc = mb_check_encoding($row->study_desc, 'UTF-8')
                            ? $row->study_desc
                            : mb_convert_encoding($row->study_desc, 'UTF-8', 'ISO-8859-1');
                    }
                    $estudiosTomados->push($row);
                }
            } catch (\Throwable $e) {
                // Registrar advertencia y continuar con las demás sedes disponibles
                Log::warning(
                    "ProduccionConsolidadaExport: Servidor PACS inaccesible para la sede {$sede->nom_sede} ({$sede->ip_dcm}): ".$e->getMessage(),
                    ["sede_id" => $sede->id]
                );
            } finally {
                DB::purge($connName);
            }
        }

        // Si no se pudo obtener ningún estudio remoto del PACS (por ejemplo en entorno local de pruebas sin VPN),
        // realizamos fallback a los estudios registrados localmente en la base de datos de LIDiag.
        if ($estudiosTomados->isEmpty()) {
            $this->esFallbackLocal = true;

            $localQuery = DB::table("estudios")
                ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
                ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
                ->whereBetween("estudios.created_at", [
                    $this->fechaInicio." 00:00:00",
                    $this->fechaFin." 23:59:59",
                ])
                ->when($this->sedeId, fn ($q) => $q->where("estudios.sede_id", $this->sedeId))
                ->select([
                    "estudios.id as study_pk",
                    "estudios.study_iuid",
                    "estudios.accession_no",
                    DB::raw("COALESCE(estudios.fec_estudio, estudios.created_at) as study_datetime"),
                    "estudios.study_desc",
                    "estudios.mods_in_study",
                    "pacientes.num_docu as pat_id",
                    "pacientes.nombres as pat_name",
                    "config_sedes.id as sede_id",
                    "config_sedes.nom_sede",
                ]);

            if ($this->modalidad) {
                DicomModalityHelper::aplicarFiltroModalidad($localQuery, "estudios.mods_in_study", $this->modalidad);
            }

            $estudiosTomados = $localQuery->orderBy("study_datetime", "asc")->get();
        }

        // 3. Cruzar con todos los estudios y productos relacionados en LIDiag (sin agrupamiento)
        $filasConsolidadas = collect();

        if ($estudiosTomados->isNotEmpty()) {
            $lidiagMapa = [];

            if ($this->esFallbackLocal) {
                $keys = $estudiosTomados->pluck("study_pk")->filter()->unique()->values()->all();

                foreach (array_chunk($keys, 1000) as $chunk) {
                    $matchedChunk = $this->consultarRegistrosLidiagPorColumna("estudios.id", $chunk);
                    foreach ($matchedChunk as $row) {
                        $lidiagMapa[$row->estudio_id][] = $row;
                    }
                }
            } else {
                $keys = $estudiosTomados->pluck("study_iuid")->filter()->unique()->values()->all();

                foreach (array_chunk($keys, 1000) as $chunk) {
                    $matchedChunk = $this->consultarRegistrosLidiagPorColumna("estudios.study_iuid", $chunk);
                    foreach ($matchedChunk as $row) {
                        $lidiagMapa[$row->study_iuid][] = $row;
                    }
                }
            }

            // Enlazar cada registro del PACS con cada registro relacionado en LIDiag
            foreach ($estudiosTomados as $item) {
                $lookupKey = $this->esFallbackLocal ? $item->study_pk : $item->study_iuid;
                $matches = ($lookupKey !== null && $lookupKey !== "") ? ($lidiagMapa[$lookupKey] ?? []) : [];

                if (! empty($matches)) {
                    foreach ($matches as $match) {
                        $fila = clone $item;
                        $fila->estudio_id = $match->estudio_id;
                        $fila->estudio_producto_id = $match->estudio_producto_id;
                        $fila->cod_cups = $match->cod_cups;
                        $fila->nom_produc = $match->nom_produc;
                        $fila->fecha_asignacion = $match->fecha_asignacion;
                        $fila->medico_nombre = $match->medico_nombre;
                        $fila->asignador_nombre = $match->asignador_nombre;
                        $fila->fechor_lectura = $match->fechor_lectura;
                        $fila->con_lectura = (int) $match->con_lectura;
                        $fila->producto_activo = $match->producto_activo;

                        if ($match->estudio_producto_id === null) {
                            $fila->estado_producto = "SIN PRODUCTOS";
                        } else {
                            $fila->estado_producto = ((int) $match->producto_activo === 1) ? "ACTIVO" : "ANULADO";
                        }

                        $esTele = ($match->destino_estudio === "Tele Radiologia") || str_starts_with((string) $match->medico_nombre, "TL ");
                        $fila->destino_estudio = $esTele ? "Tele-Radiología" : "Salud Tolima";

                        $filasConsolidadas->push($fila);
                    }
                } else {
                    $fila = clone $item;
                    $fila->estudio_id = null;
                    $fila->estudio_producto_id = null;
                    $fila->cod_cups = null;
                    $fila->nom_produc = null;
                    $fila->fecha_asignacion = null;
                    $fila->medico_nombre = null;
                    $fila->asignador_nombre = null;
                    $fila->fechor_lectura = null;
                    $fila->con_lectura = 0;
                    $fila->producto_activo = null;
                    $fila->estado_producto = "SIN ASIGNAR";
                    $fila->destino_estudio = "Sin Asignar";

                    $filasConsolidadas->push($fila);
                }
            }

            // Filtrar por destino si fue seleccionado
            if ($this->destino === "salud_tolima" || $this->destino === "Salud Tolima") {
                $filasConsolidadas = $filasConsolidadas->filter(fn ($e) => $e->destino_estudio === "Salud Tolima")->values();
            } elseif ($this->destino === "tele_radiologia" || $this->destino === "Tele Radiologia") {
                $filasConsolidadas = $filasConsolidadas->filter(fn ($e) => $e->destino_estudio === "Tele-Radiología")->values();
            }
        }

        $this->enriquecerUsuariosAnulacion($filasConsolidadas);

        $this->estudiosConsolidados = $filasConsolidadas;
        $this->estudiosSinAsignar = $filasConsolidadas->filter(fn ($e) => empty($e->estudio_id))->values();
    }

    /**
     * Enriquecer las filas anuladas con el usuario y fecha/hora de anulación desde audit_logs.
     *
     * @param  Collection  $filas  Colección consolidada ya cruzada.
     */
    protected function enriquecerUsuariosAnulacion(Collection $filas): void
    {
        $idsAnulados = $filas
            ->filter(fn ($f) => ($f->estado_producto ?? "") === "ANULADO" && ! empty($f->estudio_producto_id))
            ->pluck("estudio_producto_id")
            ->unique()
            ->values()
            ->all();

        $anulacionesMapa = [];

        if (! empty($idsAnulados)) {
            foreach (array_chunk($idsAnulados, 1000) as $chunk) {
                $registros = DB::table("audit_logs")
                    ->leftJoin("users", "users.id", "=", "audit_logs.user_id")
                    ->where("audit_logs.evento", "anular_estudio")
                    ->where("audit_logs.model_type", EstudioProducto::class)
                    ->whereIn("audit_logs.model_id", $chunk)
                    ->select(
                        "audit_logs.id",
                        "audit_logs.model_id",
                        "audit_logs.created_at as fecha_anulacion",
                        "users.name as usuario_anulador"
                    )
                    ->orderBy("audit_logs.id", "asc")
                    ->get();

                foreach ($registros as $reg) {
                    $anulacionesMapa[(int) $reg->model_id] = $reg;
                }
            }
        }

        foreach ($filas as $fila) {
            if (($fila->estado_producto ?? "") === "ANULADO" && ! empty($fila->estudio_producto_id)) {
                $anul = $anulacionesMapa[(int) $fila->estudio_producto_id] ?? null;
                $fila->usuario_anulador = ($anul && ! empty($anul->usuario_anulador))
                    ? trim((string) $anul->usuario_anulador)
                    : null;
                $fila->fecha_anulacion = $anul->fecha_anulacion ?? null;
            } else {
                $fila->usuario_anulador = null;
                $fila->fecha_anulacion = null;
            }
        }
    }

    /**
     * Consulta los registros relacionados en LIDiag (estudios + estudios_productos) sin agrupar.
     *
     * @param  string  $columna  Columna de cruce ('estudios.study_iuid' o 'estudios.id').
     * @param  array<int, mixed>  $valores  Lista de valores del bloque actual.
     *
     * @returns Collection
     */
    protected function consultarRegistrosLidiagPorColumna(string $columna, array $valores): Collection
    {
        return DB::table("estudios")
            ->whereIn($columna, $valores)
            ->where("estudios.estado", 1)
            ->leftJoin("users as medico", "medico.id", "=", "estudios.medico_id")
            ->leftJoin("users as asignador", "asignador.id", "=", "estudios.quien_registro_id")
            ->leftJoin("estudios_productos", "estudios_productos.estudio_id", "=", "estudios.id")
            ->select(
                "estudios.id as estudio_id",
                "estudios.study_iuid",
                "estudios.destino_estudio",
                "estudios.created_at as fecha_asignacion",
                "estudios.estado",
                "medico.name as medico_nombre",
                "asignador.name as asignador_nombre",
                "estudios_productos.id as estudio_producto_id",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "estudios_productos.fechor_lectura",
                "estudios_productos.activo as producto_activo",
                DB::raw("CASE WHEN estudios_productos.fechor_lectura IS NOT NULL OR (estudios_productos.lectura IS NOT NULL AND TRIM(estudios_productos.lectura) != '') THEN 1 ELSE 0 END as con_lectura")
            )
            ->orderBy("estudios.id", "asc")
            ->orderBy("estudios_productos.id", "asc")
            ->get();
    }

    /**
     * Define las hojas que conforman el libro Excel.
     *
     * @returns array<int, object>
     */
    public function sheets(): array
    {
        return [
            new ProduccionConsolidadaEstadisticasSheet(
                $this->estudiosConsolidados,
                $this->fechaInicio,
                $this->fechaFin,
                $this->sedeNombre
            ),
            new ProduccionConsolidadaDetalleSheet(
                $this->estudiosConsolidados,
                $this->fechaInicio,
                $this->fechaFin,
                $this->sedeNombre
            ),
            new ProduccionConsolidadaSinAsignarSheet(
                $this->estudiosSinAsignar,
                $this->fechaInicio,
                $this->fechaFin,
                $this->sedeNombre
            ),
        ];
    }
}
