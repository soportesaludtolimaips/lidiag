<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteAusentismoCitasDetalleSheet;
use App\Exports\Sheets\ReporteAusentismoCitasEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Ausentismo y Cancelaciones de Citas.
 *
 * Flujo del proceso:
 * 1. Consulta las citas no atendidas, canceladas o rechazadas en el rango de fechas.
 * 2. Cruza con sedes, pacientes, servicios y motivos de rechazo.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de ausentismo, distribución por estado, top motivos y gráficos nativos).
 *    - Hoja 2: Detalle de Inasistencias (Listado completo con motivos y observaciones).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAusentismoCitasExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected mixed $sedeId;

    protected mixed $estado;

    protected string $sedeNombre = "Todas las Sedes";

    protected Collection $citas;

    /**
     * Constructor del reporte de ausentismo y cancelaciones de citas.
     *
     * @param  mixed  $fechaInicio  Fecha de inicio (YYYY-MM-DD).
     * @param  mixed  $fechaFin  Fecha de fin (YYYY-MM-DD).
     * @param  mixed  $sedeId  Identificador de la sede o null.
     * @param  mixed  $estado  Filtro específico de estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null, $estado = null)
    {
        $this->fechaInicio = (string) $fechaInicio;
        $this->fechaFin = (string) $fechaFin;
        $this->sedeId = $sedeId;
        $this->estado = $estado;

        if ($this->sedeId) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        $this->citas = $this->consultarAusentismo();
    }

    /**
     * Consulta las citas canceladas, rechazadas o con inasistencia (no-show).
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarAusentismo(): Collection
    {
        return DB::table("citas")
            ->leftJoin("config_sedes", "citas.sede_id", "=", "config_sedes.id")
            ->leftJoin("pacientes", "citas.paciente_id", "=", "pacientes.id")
            ->leftJoin("config_servicios", "citas.servicio_id", "=", "config_servicios.id")
            ->leftJoin("config_productos", "citas.producto_id", "=", "config_productos.id")
            ->leftJoin("config_cita_rechazo_motivos", "citas.rechazo_motivo_id", "=", "config_cita_rechazo_motivos.id")
            ->whereBetween("citas.fecha_hora_inicio", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("citas.sede_id", $this->sedeId);
            })
            ->when($this->estado && $this->estado !== "todos", function ($query) {
                return $query->where("citas.estado", $this->estado);
            }, function ($query) {
                return $query->whereIn("citas.estado", ["no_asistio", "cancelada", "rechazada"]);
            })
            ->select([
                "citas.id",
                "config_sedes.nom_sede",
                "citas.fecha_hora_inicio",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "pacientes.movil as telefono",
                "config_servicios.nom_servicio",
                "config_productos.cod_cups",
                "config_productos.nom_produc",
                "citas.estado",
                "config_cita_rechazo_motivos.motivo as motivo_rechazo",
                "citas.rechazo_observacion",
                "citas.whatsapp_estado",
            ])
            ->orderBy("citas.fecha_hora_inicio", "desc")
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
            new ReporteAusentismoCitasEstadisticasSheet(
                $this->citas,
                $this->sedeNombre,
                $this->fechaInicio,
                $this->fechaFin
            ),
            new ReporteAusentismoCitasDetalleSheet(
                $this->citas
            ),
        ];
    }
}
