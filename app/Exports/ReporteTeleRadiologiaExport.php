<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteTeleRadiologiaDetalleSheet;
use App\Exports\Sheets\ReporteTeleRadiologiaEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Tele-Radiología y Lecturas Remotas.
 *
 * Flujo del proceso:
 * 1. Consulta los estudios derivados a tele-radiología en el rango de fechas una sola vez.
 * 2. Cruza con la base de datos de pacientes, sedes y médicos lectores remotos.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de derivación, tasa de devolución por modalidad y gráficos nativos).
 *    - Hoja 2: Detalle Tele-Radiología (Listado completo con tiempos de ciclo y estado).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteTeleRadiologiaExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected mixed $sedeId;

    protected string $sedeNombre = "Todas las Sedes";

    protected Collection $estudios;

    /**
     * Constructor del reporte de tele-radiología y lecturas remotas.
     *
     * @param  mixed  $fechaInicio  Fecha de inicio del rango (YYYY-MM-DD).
     * @param  mixed  $fechaFin  Fecha de fin del rango (YYYY-MM-DD).
     * @param  mixed  $sedeId  Identificador de sede o null para todas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null)
    {
        $this->fechaInicio = (string) $fechaInicio;
        $this->fechaFin = (string) $fechaFin;
        $this->sedeId = $sedeId;

        if ($this->sedeId) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        $this->estudios = $this->consultarEstudiosTeleRadiologia();
    }

    /**
     * Consulta los estudios de tele-radiología derivados a lectura externa.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosTeleRadiologia(): Collection
    {
        return DB::table("estudios")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("estudios_productos", "estudios_productos.estudio_id", "=", "estudios.id")
            ->leftJoin("users as medico", "medico.id", "=", "estudios.medico_id")
            ->where(function ($q) {
                $q->where("estudios.destino_estudio", "Tele Radiologia")
                    ->orWhere("medico.name", "LIKE", "TL %");
            })
            ->whereBetween("estudios.created_at", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->select([
                "estudios.id",
                "config_sedes.nom_sede",
                "estudios.created_at as fecha_envio",
                "estudios.study_iuid",
                "estudios.accession_no",
                "estudios.mods_in_study as modalidad",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "medico.name as medico_lector",
                "estudios_productos.fechor_lectura",
                "estudios.destino_estudio",
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL THEN 'Leído / Devuelto'
                    ELSE 'Pendiente de Lectura'
                END as estado_lectura"),
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura) / 60, 1)
                    ELSE NULL 
                END as tat_horas"),
            ])
            ->orderBy("estudios.created_at", "desc")
            ->get();
    }

    /**
     * Define las hojas que conforman el libro Excel.
     *
     * @returns array<int, object>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        return [
            new ReporteTeleRadiologiaEstadisticasSheet(
                $this->estudios,
                $this->sedeNombre,
                $this->fechaInicio,
                $this->fechaFin
            ),
            new ReporteTeleRadiologiaDetalleSheet(
                $this->estudios
            ),
        ];
    }
}
