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
     * Flujo del proceso:
     * 1. Obtiene el modelo de la sede hospitalaria desde la base de datos local.
     * 2. Resuelve de forma dinámica los parámetros de conexión al servidor PACS (priorizando ip_dcm, puerto_dcm, etc.).
     * 3. Configura una conexión temporal dedicada con timeout estricto para evitar bloqueos por sedes fuera de línea.
     * 4. Realiza la consulta de estudios y pacientes mediante LEFT JOIN sobre la tabla study y patient.
     * 5. Purga y limpia la conexión temporal antes de retornar la colección.
     *
     * @returns Collection Colección de estudios adquiridos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    protected function consultarEstudiosPac(): Collection
    {
        $sedeActual = ConfigSede::find($this->sedeId);

        if (! $sedeActual) {
            Log::warning("ReportesEstudioPAC: Sede ID {$this->sedeId} no encontrada en la base de datos.");

            return collect();
        }

        if ($this->sedeNombre === "") {
            $this->sedeNombre = (string) ($sedeActual->nom_sede ?? "Sede {$this->sedeId}");
        }

        $host = ! empty($sedeActual->ip_dcm) ? $sedeActual->ip_dcm : config("database.connections.mysql_sucursal.host");
        $port = ! empty($sedeActual->puerto_dcm) ? (int) $sedeActual->puerto_dcm : (int) (config("database.connections.mysql_sucursal.port") ?: 3306);
        $database = ! empty($sedeActual->bd_dcm) ? $sedeActual->bd_dcm : config("database.connections.mysql_sucursal.database");
        $username = ! empty($sedeActual->usuario_dcm) ? $sedeActual->usuario_dcm : config("database.connections.mysql_sucursal.username");
        $password = $sedeActual->password_dcm ?? config("database.connections.mysql_sucursal.password");

        if (empty($host) || empty($database)) {
            Log::warning("ReportesEstudioPAC: La sede {$this->sedeNombre} (ID {$this->sedeId}) no tiene host o base de datos PACS configurada.");

            return collect();
        }

        $connName = "pacs_reporte_".$this->sedeId."_".uniqid();

        config([
            "database.connections.{$connName}" => [
                "driver" => "mysql",
                "host" => $host,
                "port" => $port,
                "database" => $database,
                "username" => $username,
                "password" => $password,
                "charset" => "latin1",
                "collation" => "latin1_swedish_ci",
                "options" => [
                    \PDO::ATTR_TIMEOUT => 15,
                ],
            ],
        ]);

        $fechaIni = trim($this->fechaInicio);
        $fechaFin = trim($this->fechaFin);
        $desde = str_contains($fechaIni, " ") ? $fechaIni : $fechaIni." 00:00:00";
        $hasta = str_contains($fechaFin, " ") ? $fechaFin : $fechaFin." 23:59:59";

        try {
            $estudios = DB::connection($connName)
                ->table("study")
                ->select(
                    "patient.pat_id",
                    "patient.pat_name",
                    "study.study_datetime",
                    "study.study_desc",
                    "study.mods_in_study"
                )
                ->leftJoin("patient", "patient.pk", "=", "study.patient_fk")
                ->whereBetween("study.study_datetime", [$desde, $hasta])
                ->orderBy("study.study_datetime", "desc")
                ->get();

            // Normalizar codificación latin1 a UTF-8 para visualización en Excel
            return $estudios->map(function ($row) {
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

                return $row;
            });
        } catch (\Throwable $e) {
            Log::error(
                "ReportesEstudioPAC: Error consultando BD PACS remota",
                [
                    "sede_id" => $this->sedeId,
                    "host" => $host,
                    "database" => $database,
                    "error" => $e->getMessage(),
                ]
            );

            // Relanzar excepción para que el controlador informe al usuario en lugar de entregar un Excel vacío engañoso
            throw new \RuntimeException("No fue posible conectar con el PACS de la sede (Host: {$host}): ".$e->getMessage(), 0, $e);
        } finally {
            DB::purge($connName);
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
