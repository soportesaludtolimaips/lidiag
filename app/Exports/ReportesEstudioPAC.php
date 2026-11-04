<?php

namespace App\Exports;

use App\Exports\Sheets\ReportesEstudioPACDetalleSheet;
use App\Exports\Sheets\ReportesEstudioPACEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Libro Excel Multi-Hoja de Estudios Adquiridos en el PACS (Dcm4chee / MySQL Sucursal).
 *
 * Flujo del proceso:
 * 1. Recibe los parámetros de consulta: rango de fechas ($fechaInicio, $fechaFin) y $sedeId.
 * 2. Conecta de forma dinámica a la base de datos MySQL de la sede seleccionada ('mysql_sucursal').
 * 3. Ejecuta la consulta unificada de estudios y pacientes (`study` JOIN `patient`) una sola vez.
 * 4. Compone un libro multi-hoja profesional con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs ejecutivos, tabla de modalidades, top 10 procedimientos y gráficos nativos).
 *    - Hoja 2: Detalle de Estudios (Trazabilidad registro por registro de los estudios adquiridos).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReportesEstudioPAC implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected mixed $sedeId;

    protected string $sedeNombre;

    protected string $conexionOriginal;

    protected Collection $estudios;

    /**
     * Constructor de la exportación de estudios PACS.
     *
     * @param  string  $fechaInicio  Fecha de inicio del rango (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha de fin del rango (YYYY-MM-DD).
     * @param  mixed  $sedeId  Identificador de la sede hospitalaria.
     * @param  string  $sedeNombre  Nombre descriptivo opcional de la sede.
     * @param  string  $conexionOriginal  Nombre de la conexión de BD original.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId, $sedeNombre = "", $conexionOriginal = "")
    {
        $this->fechaInicio = (string) $fechaInicio;
        $this->fechaFin = (string) $fechaFin;
        $this->sedeId = $sedeId;
        $this->sedeNombre = (string) $sedeNombre;
        $this->conexionOriginal = (string) $conexionOriginal;
        $this->estudios = $this->consultarEstudiosPac();
    }

    /**
     * Consulta los estudios desde la base de datos PACS de la sede configurada.
     *
     * @returns Collection Colección de estudios adquiridos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosPac(): Collection
    {
        $sedeActual = ConfigSede::find($this->sedeId);

        if (! $sedeActual) {
            return collect();
        }

        if ($this->sedeNombre === "") {
            $this->sedeNombre = (string) ($sedeActual->nom_sede ?? "");
        }

        config([
            "database.connections.mysql_sucursal" => [
                "driver" => "mysql",
                "host" => config("database.connections.mysql_sucursal.host") ?: $sedeActual->ip_dcm,
                "port" => config("database.connections.mysql_sucursal.port") ?: $sedeActual->puerto_dcm,
                "database" => config("database.connections.mysql_sucursal.database") ?: $sedeActual->bd_dcm,
                "username" => config("database.connections.mysql_sucursal.username") ?: $sedeActual->usuario_dcm,
                "password" => config("database.connections.mysql_sucursal.password") ?: $sedeActual->password_dcm,
                "charset" => "latin1",
                "collation" => "latin1_swedish_ci",
            ],
        ]);
        DB::purge("mysql_sucursal");

        try {
            return DB::connection("mysql_sucursal")
                ->table("study")
                ->select(
                    "patient.pat_id",
                    "patient.pat_name",
                    "study.study_datetime",
                    "study.study_desc",
                    "study.mods_in_study"
                )
                ->join("patient", "patient.pk", "=", "study.patient_fk")
                ->whereBetween("study.study_datetime", [
                    $this->fechaInicio." 00:00:00",
                    $this->fechaFin." 23:59:59",
                ])
                ->orderBy("study.study_datetime", "desc")
                ->get();
        } catch (\Throwable $e) {
            Log::error(
                "ReportesEstudioPAC: Error querying remote PAC DB",
                ["sede_id" => $this->sedeId, "error" => $e->getMessage()]
            );

            return collect();
        }
    }

    /**
     * Define las hojas que conforman el libro Excel.
     *
     * @returns array<int, object> Lista de hojas del libro.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        return [
            new ReportesEstudioPACEstadisticasSheet(
                $this->estudios,
                $this->sedeNombre,
                $this->fechaInicio,
                $this->fechaFin
            ),
            new ReportesEstudioPACDetalleSheet(
                $this->estudios,
                $this->sedeNombre
            ),
        ];
    }
}
