<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteConsolidadoRedDetalleSheet;
use App\Exports\Sheets\ReporteConsolidadoRedEstadisticasSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte Consolidado de Toda la Red Hospitalaria.
 *
 * Flujo del proceso:
 * 1. Consulta los estudios leídos en todas las sedes activas en el rango de fechas una sola vez.
 * 2. Cruza información de pacientes, sedes, modalidades y médicos radiólogos.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de red, ranking de producción por sede, modalidades y gráficos nativos).
 *    - Hoja 2: Detalle Consolidado Red (Listado completo con tiempos clínicos de respuesta TAT).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteConsolidadoRedExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected string $etiquetaPeriodo;

    protected mixed $sedeId;

    protected Collection $estudios;

    /**
     * Constructor del reporte consolidado de la red hospitalaria.
     *
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
     * @param  string  $etiquetaPeriodo  Etiqueta legible del período.
     * @param  mixed  $sedeId  ID opcional de sede para filtrar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(string $fechaInicio, string $fechaFin, string $etiquetaPeriodo = "", mixed $sedeId = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->etiquetaPeriodo = $etiquetaPeriodo ?: "Del {$fechaInicio} al {$fechaFin}";
        $this->sedeId = $sedeId;
        $this->estudios = $this->consultarEstudiosRed();
    }

    /**
     * Consulta los registros leídos en toda la red para el consolidado.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosRed(): Collection
    {
        return DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("users as medico", "estudios.medico_id", "=", "medico.id")
            ->leftJoin("config_tipo_documentos", "pacientes.tipo_documento_id", "=", "config_tipo_documentos.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->when($this->sedeId, fn ($q) => $q->where("estudios.sede_id", $this->sedeId))
            ->whereBetween("estudios_productos.fechor_lectura", [
                "{$this->fechaInicio} 00:00:00",
                "{$this->fechaFin} 23:59:59",
            ])
            ->select([
                "estudios.id as estudio_id",
                "config_sedes.nom_sede",
                "estudios.mods_in_study",
                "estudios_productos.nom_produc as nombre_procedimiento",
                "config_tipo_documentos.sigla as tipo_documento",
                "pacientes.num_docu as documento",
                "pacientes.nombres as paciente_nombre",
                "medico.name as medico_nombre",
                DB::raw("COALESCE(estudios.fec_estudio, estudios.created_at) as fecha_estudio"),
                "estudios_productos.fechor_lectura as fecha_lectura",
                DB::raw("ROUND(TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura) / 60, 1) as tat_horas"),
                DB::raw("CASE 
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura) <= 24 THEN 'Normal (<=24h)'
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura) <= 48 THEN 'Preventivo (24-48h)'
                    ELSE 'Crítico (>48h)'
                END as semaforo_oportunidad"),
            ])
            ->orderBy("estudios_productos.fechor_lectura", "desc")
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
            new ReporteConsolidadoRedEstadisticasSheet(
                $this->estudios,
                $this->fechaInicio,
                $this->fechaFin,
                $this->etiquetaPeriodo
            ),
            new ReporteConsolidadoRedDetalleSheet(
                $this->estudios
            ),
        ];
    }
}
