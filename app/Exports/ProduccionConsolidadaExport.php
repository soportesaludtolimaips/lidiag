<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionConsolidadaDetalleSheet;
use App\Exports\Sheets\ProduccionConsolidadaEstadisticasSheet;
use App\Exports\Sheets\ProduccionConsolidadaSinAsignarSheet;
use App\Models\Configuracion\ConfigSede;
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

            $connName = "pacs_temp_".$sede->id;
            config([
                "database.connections.{$connName}" => [
                    "driver" => "mysql",
                    "host" => $sede->ip_dcm,
                    "port" => $sede->puerto_dcm ?: 3306,
                    "database" => $sede->bd_dcm,
                    "username" => $sede->usuario_dcm,
                    "password" => $sede->password_dcm,
                    "charset" => "latin1",
                    "collation" => "latin1_swedish_ci",
                    "options" => [
                        PDO::ATTR_TIMEOUT => 3, // Timeout de 3 segundos para prevenir bloqueos
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
                    $query->where("study.mods_in_study", $this->modalidad);
                }

                $rows = $query->orderBy("study.study_datetime", "asc")->get();

                foreach ($rows as $row) {
                    $row->sede_id = $sede->id;
                    $row->nom_sede = $sede->nom_sede;
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

        // 3. Cruzar con estudios asignados y leídos en LIDiag
        if ($estudiosTomados->isNotEmpty()) {
            $iuids = $estudiosTomados->pluck("study_iuid")->filter()->unique()->values()->all();

            $lidiagEstudios = collect();
            // Consultar en bloques para evitar límites de tamaño SQL en IN (...)
            foreach (array_chunk($iuids, 1000) as $chunk) {
                $matchedChunk = DB::table("estudios")
                    ->whereIn("estudios.study_iuid", $chunk)
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
                        DB::raw("MAX(estudios_productos.fechor_lectura) as fechor_lectura"),
                        DB::raw("MAX(CASE WHEN estudios_productos.fechor_lectura IS NOT NULL OR estudios_productos.lectura IS NOT NULL THEN 1 ELSE 0 END) as con_lectura")
                    )
                    ->groupBy(
                        "estudios.id",
                        "estudios.study_iuid",
                        "estudios.destino_estudio",
                        "estudios.created_at",
                        "estudios.estado",
                        "medico.name",
                        "asignador.name"
                    )
                    ->get()
                    ->keyBy("study_iuid");

                $lidiagEstudios = $lidiagEstudios->merge($matchedChunk);
            }

            // Enlazar los datos en memoria
            foreach ($estudiosTomados as $item) {
                $match = $lidiagEstudios->get($item->study_iuid);
                if ($match) {
                    $item->estudio_id = $match->estudio_id;
                    $item->fecha_asignacion = $match->fecha_asignacion;
                    $item->medico_nombre = $match->medico_nombre;
                    $item->asignador_nombre = $match->asignador_nombre;
                    $item->fechor_lectura = $match->fechor_lectura;
                    $item->con_lectura = (int) $match->con_lectura;

                    $esTele = ($match->destino_estudio === "Tele Radiologia") || str_starts_with((string) $match->medico_nombre, "TL ");
                    $item->destino_estudio = $esTele ? "Tele-Radiología" : "Salud Tolima";
                } else {
                    $item->estudio_id = null;
                    $item->fecha_asignacion = null;
                    $item->medico_nombre = null;
                    $item->asignador_nombre = null;
                    $item->fechor_lectura = null;
                    $item->con_lectura = 0;
                    $item->destino_estudio = "Sin Asignar";
                }
            }

            // Filtrar por destino si fue seleccionado
            if ($this->destino === "salud_tolima" || $this->destino === "Salud Tolima") {
                $estudiosTomados = $estudiosTomados->filter(fn ($e) => $e->destino_estudio === "Salud Tolima")->values();
            } elseif ($this->destino === "tele_radiologia" || $this->destino === "Tele Radiologia") {
                $estudiosTomados = $estudiosTomados->filter(fn ($e) => $e->destino_estudio === "Tele-Radiología")->values();
            }
        }

        $this->estudiosConsolidados = $estudiosTomados;
        $this->estudiosSinAsignar = $estudiosTomados->filter(fn ($e) => empty($e->estudio_id))->values();
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
