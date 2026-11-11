<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionDetalladaDetalleSheet;
use App\Exports\Sheets\ProduccionDetalladaEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use App\Services\Liquidacion\CruceFinancieroService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Libro Excel Multi-Hoja de Producción Detallada de Estudios Radiológicos.
 *
 * Flujo del proceso:
 * 1. Recibe los filtros de consulta: rango de fechas ($fechaInicio, $fechaFin), $sedeId, $quienAsignoId y $medicoId.
 * 2. Consulta y consolida la producción de estudios clínicos y productos una sola vez.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs ejecutivos, tabla de modalidades diagnósticas, ranking de médicos y gráficos nativos).
 *    - Hoja 2: Detalle de Producción (Listado completo de estudios con sus 19 columnas analíticas).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ProduccionDetalladaExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected mixed $sedeId;

    protected mixed $quienAsignoId;

    protected mixed $medicoId;

    protected string $sedeNombre = "Todas las Sedes";

    protected string $medicoNombre = "Todos los Médicos Radiólogos";

    protected Collection $estudios;

    /**
     * Recibe los filtros del reporte de producción de estudios.
     *
     * @param  mixed  $fechaInicio  Fecha de inicio del rango.
     * @param  mixed  $fechaFin  Fecha de fin del rango.
     * @param  mixed  $sedeId  Identificador de la sede hospitalaria o null.
     * @param  mixed  $quienAsignoId  Identificador del usuario asignador o null.
     * @param  mixed  $medicoId  Identificador del médico radiólogo o null.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null, $quienAsignoId = null, $medicoId = null)
    {
        $this->fechaInicio = (string) $fechaInicio;
        $this->fechaFin = (string) $fechaFin;
        $this->sedeId = $sedeId;
        $this->quienAsignoId = $quienAsignoId;
        $this->medicoId = $medicoId;

        $this->resolverMetadatos();
        $this->estudios = $this->consultarEstudiosProduccion();
    }

    /**
     * Resuelve los nombres descriptivos de sede y médico para encabezados ejecutivos.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function resolverMetadatos(): void
    {
        if (! empty($this->sedeId)) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        if (! empty($this->medicoId)) {
            $medico = User::find($this->medicoId);
            if ($medico) {
                $this->medicoNombre = (string) $medico->name;
            }
        }
    }

    /**
     * Consulta y devuelve los estudios del reporte de producción valorados mediante el motor de cruce financiero.
     *
     * @returns Collection Registros de estudios productivos con métricas operativas y financieras.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    protected function consultarEstudiosProduccion(): Collection
    {
        return app(CruceFinancieroService::class)->consultarProduccionValorada(
            $this->fechaInicio,
            $this->fechaFin,
            ! empty($this->sedeId) ? (int) $this->sedeId : null,
            ! empty($this->quienAsignoId) ? (int) $this->quienAsignoId : null,
            ! empty($this->medicoId) ? (int) $this->medicoId : null
        );
    }

    /**
     * Define las hojas que conforman el libro Excel de producción.
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
            new ProduccionDetalladaEstadisticasSheet(
                $this->estudios,
                $this->sedeNombre,
                $this->medicoNombre,
                $this->fechaInicio,
                $this->fechaFin,
                $this->medicoId,
                $this->sedeId
            ),
            new ProduccionDetalladaDetalleSheet(
                $this->estudios
            ),
        ];
    }
}
