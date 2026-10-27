<?php

namespace App\Exports\Dosimetria;

use App\Exports\Dosimetria\Sheets\DosimetriaDetalleSheet;
use App\Exports\Dosimetria\Sheets\DosimetriaEstadisticasSheet;
use App\Services\Dosimetria\DosimetriaService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Libro Excel Multi-Hoja de Dosimetría Radiológica y Cumplimiento NRD (Resolución 482 de 2018).
 *
 * Flujo del proceso:
 * 1. Recibe los filtros de consulta (sede, modalidad, métrica, fechas) en el constructor.
 * 2. Delega la consolidación y auditoría analítica a DosimetriaService::obtenerDatosExportacion().
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y NRD (Resumen Ejecutivo, KPIs y cumplimiento normativo mensual).
 *    - Hoja 2: Registros Detallados (Trazabilidad clínica individual con PHI anonimizado por SHA-256).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-26
 */
class DosimetriaExport implements WithMultipleSheets
{
    protected array $datosExportacion;

    /**
     * Inicializa la exportación ejecutando la consolidación de datos.
     *
     * @param  array  $filtros  Criterios de filtrado del dashboard
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function __construct(array $filtros)
    {
        $this->datosExportacion = app(DosimetriaService::class)->obtenerDatosExportacion($filtros);
    }

    /**
     * Define las hojas que conforman el libro Excel de dosimetría.
     *
     * @return array Lista de hojas del libro Excel
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function sheets(): array
    {
        return [
            new DosimetriaEstadisticasSheet($this->datosExportacion),
            new DosimetriaDetalleSheet($this->datosExportacion),
        ];
    }
}
