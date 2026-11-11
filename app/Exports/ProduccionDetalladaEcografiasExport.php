<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionDetalladaEcografiasDetalleSheet;
use App\Exports\Sheets\ProduccionDetalladaEcografiasEstadisticasSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Producción Detallada de Ecografías.
 *
 * Flujo del proceso:
 * 1. Recibe los filtros de fechas, sede, usuario asignador y médico ecografista.
 * 2. Consulta los registros de la vista view_reporte_ecografias con manejo defensivo.
 * 3. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas y Detalle de Ecografías.
 * 4. Aplica gráficos nativos y estilos corporativos Salud Tolima.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class ProduccionDetalladaEcografiasExport implements WithMultipleSheets
{
    protected mixed $fechaInicio;

    protected mixed $fechaFin;

    protected mixed $sedeId;

    protected mixed $quienAsignoId;

    protected mixed $medicoId;

    /**
     * Cache de registros de ecografías.
     */
    protected ?Collection $cachedData = null;

    /**
     * Recibe los filtros del reporte de producción de ecografías.
     *
     * @param  mixed  $fechaInicio
     * @param  mixed  $fechaFin
     * @param  mixed  $sedeId
     * @param  mixed  $quienAsignoId
     * @param  mixed  $medicoId
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->quienAsignoId = $quienAsignoId;
        $this->medicoId = $medicoId;
    }

    /**
     * Retorna las hojas del reporte.
     *
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        $data = $this->getData();

        return [
            new ProduccionDetalladaEcografiasEstadisticasSheet(
                $data,
                $this->fechaInicio ? (string) $this->fechaInicio : null,
                $this->fechaFin ? (string) $this->fechaFin : null
            ),
            new ProduccionDetalladaEcografiasDetalleSheet($data),
        ];
    }

    /**
     * Consulta y devuelve las ecografías del reporte según los filtros aplicados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function getData(): Collection
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        try {
            $ecografias = DB::table("view_reporte_ecografias")
                ->when($this->fechaInicio, fn ($q) => $q->whereBetween(DB::raw("DATE(fechor_lectura)"), [$this->fechaInicio, $this->fechaFin]))
                ->when($this->sedeId, fn ($q) => $q->where("sede_id", $this->sedeId))
                ->when($this->quienAsignoId, fn ($q) => $q->where("usua_regis_id", $this->quienAsignoId))
                ->when($this->medicoId, fn ($q) => $q->where("medico_id", $this->medicoId))
                ->select(
                    "id",
                    "fec_cita_sahi",
                    "fec_regis_cita_sahi",
                    "fechor_lectura",
                    "atencion_id",
                    "quien_registro",
                    "medico",
                    "documento_paciente",
                    "nombre_paciente",
                    "cod_cups",
                    "nom_produc",
                    "eps",
                    "contrato",
                    "plan",
                    "quien_ordena",
                    "lectura",
                    "opinion"
                )
                ->get();
        } catch (\Throwable) {
            $ecografias = collect();
        }

        $this->cachedData = $ecografias;

        return $this->cachedData;
    }

    /**
     * Compatibilidad con colecciones directas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function collection(): Collection
    {
        return $this->getData();
    }
}
