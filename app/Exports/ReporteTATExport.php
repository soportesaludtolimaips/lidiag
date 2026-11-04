<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteTATDetalleSheet;
use App\Exports\Sheets\ReporteTATEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Calidad y Oportunidad (Turnaround Time - TAT).
 *
 * Flujo del proceso:
 * 1. Consulta los estudios realizados en el rango de fechas una sola vez.
 * 2. Cruza con detalle de productos, tiempos de dictado y tiempos de transcripción.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de oportunidad, cumplimiento SLA por modalidad y gráficos nativos).
 *    - Hoja 2: Detalle de Oportunidad TAT (Listado completo con tiempos de ciclo y clasificación de SLA).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteTATExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?int $sedeId;

    protected ?string $modalidad;

    protected string $sedeNombre = "Todas las Sedes";

    protected Collection $estudios;

    /**
     * Constructor del reporte de tiempos de respuesta TAT y oportunidad asistencial.
     *
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
     * @param  int|null  $sedeId  Identificador de la sede hospitalaria o null para todas.
     * @param  string|null  $modalidad  Modalidad técnica o null para todas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(string $fechaInicio, string $fechaFin, ?int $sedeId = null, ?string $modalidad = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->modalidad = ($modalidad === "todas" || empty($modalidad)) ? null : $modalidad;

        if ($this->sedeId) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        $this->estudios = $this->consultarEstudiosTAT();
    }

    /**
     * Consulta los registros para el cálculo de oportunidad y tiempos TAT.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosTAT(): Collection
    {
        return DB::table("estudios")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->leftJoin("estudios_productos", "estudios_productos.estudio_id", "=", "estudios.id")
            ->leftJoin("users as medico", "medico.id", "=", "estudios.medico_id")
            ->leftJoin("users as transcriptor", "transcriptor.id", "=", "estudios_productos.transcriptor_id")
            ->whereBetween("estudios.created_at", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->when($this->modalidad, function ($query) {
                return $query->where("estudios.mods_in_study", "LIKE", "%".$this->modalidad."%");
            })
            ->select([
                "estudios.id",
                "config_sedes.nom_sede",
                "estudios.created_at as fecha_estudio",
                "estudios.mods_in_study as modalidad",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "config_prioridades.nom_priori as prioridad",
                "medico.name as medico_lector",
                "estudios_productos.fechor_lectura",
                "transcriptor.name as transcriptor",
                "estudios_productos.fechor_trascrito",
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura) / 60, 1)
                    ELSE NULL 
                END as tat_horas_lectura"),
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL AND estudios_productos.fechor_trascrito IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios_productos.fechor_lectura, estudios_productos.fechor_trascrito) / 60, 1)
                    ELSE NULL 
                END as tat_horas_transcripcion"),
                DB::raw("CASE 
                    WHEN COALESCE(estudios_productos.fechor_trascrito, estudios_productos.fechor_lectura) IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, COALESCE(estudios_productos.fechor_trascrito, estudios_productos.fechor_lectura)) / 60, 1)
                    ELSE NULL 
                END as tat_horas_total"),
                DB::raw("CASE 
                    WHEN COALESCE(estudios_productos.fechor_trascrito, estudios_productos.fechor_lectura) IS NULL THEN 'Pendiente'
                    WHEN TIMESTAMPDIFF(HOUR, estudios.created_at, COALESCE(estudios_productos.fechor_trascrito, estudios_productos.fechor_lectura)) <= 24 THEN 'Cumple SLA (<=24h)'
                    WHEN TIMESTAMPDIFF(HOUR, estudios.created_at, COALESCE(estudios_productos.fechor_trascrito, estudios_productos.fechor_lectura)) <= 48 THEN 'Alerta (24-48h)'
                    ELSE 'Fuera de SLA (>48h)'
                END as clasificacion_sla"),
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
            new ReporteTATEstadisticasSheet(
                $this->estudios,
                $this->sedeNombre,
                $this->fechaInicio,
                $this->fechaFin
            ),
            new ReporteTATDetalleSheet(
                $this->estudios
            ),
        ];
    }
}
