<?php

namespace App\Exports;

use App\Exports\Sheets\HelpDeskTicketsDetalleSheet;
use App\Exports\Sheets\HelpDeskTicketsEstadisticasSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Tickets Detallados de HelpDesk.
 *
 * Flujo del proceso:
 * 1. Recibe la colección de tickets detallados filtrados.
 * 2. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas de Mesa de Ayuda y Detalle de Tickets.
 * 3. Proporciona gráficos nativos y KPIs de resolución de incidentes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class HelpDeskTicketsDetalladoExport implements WithMultipleSheets
{
    /** @var Collection */
    protected $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Retorna las hojas del libro de cálculo.
     *
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        return [
            new HelpDeskTicketsEstadisticasSheet($this->collection),
            new HelpDeskTicketsDetalleSheet($this->collection),
        ];
    }

    /**
     * Devuelve la colección de tickets detallados a exportar.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function collection(): Collection
    {
        return $this->collection;
    }
}
