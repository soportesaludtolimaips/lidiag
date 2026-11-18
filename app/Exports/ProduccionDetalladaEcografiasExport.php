<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionDetalladaEcografiasDetalleSheet;
use App\Exports\Sheets\ProduccionDetalladaEcografiasEstadisticasSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Producción Detallada de Ecografías.
 *
 * Flujo del proceso:
 * 1. Recibe los filtros de fechas, sede, usuario asignador y médico ecografista.
 * 2. Consulta los registros de ecografías admitidas y realizadas incluyendo ubicación y cama hospitalaria.
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
            $ecografias = DB::table("ecografias_productos as ep")
                ->join("ecografias as e", "ep.ecografia_id", "=", "e.id")
                ->join("pacientes as p", "e.paciente_id", "=", "p.id")
                ->join("users as u_reg", "e.usua_regis_id", "=", "u_reg.id")
                ->leftJoin("users as u_med", "e.medico_id", "=", "u_med.id")
                ->leftJoin("config_admin_salud as cas", "e.admin_salud_id", "=", "cas.id")
                ->join("config_productos as cp", "ep.producto_id", "=", "cp.id")
                ->whereNull("e.deleted_at")
                ->whereNull("ep.deleted_at")
                ->when($this->fechaInicio, fn ($q) => $q->whereBetween(DB::raw("DATE(ep.fechor_lectura)"), [$this->fechaInicio, $this->fechaFin]))
                ->when($this->quienAsignoId, fn ($q) => $q->where("e.usua_regis_id", $this->quienAsignoId))
                ->when($this->medicoId, fn ($q) => $q->where("e.medico_id", $this->medicoId))
                ->select(
                    "ep.id",
                    "e.fec_cita_sahi",
                    "e.fec_regis_cita_sahi",
                    "ep.fechor_lectura",
                    "e.atencion_id",
                    "u_reg.name as quien_registro",
                    "u_med.name as medico",
                    "p.num_docu as documento_paciente",
                    "p.nombres as nombre_paciente",
                    "cp.cod_cups",
                    "cp.nom_produc",
                    "cas.nom_admin_salud as eps",
                    "e.contrato",
                    "e.plan",
                    "e.quien_ordena",
                    "e.ubicacion",
                    "e.cama",
                    "ep.lectura",
                    "ep.opinion"
                )
                ->orderByDesc("ep.fechor_lectura")
                ->get();
        } catch (\Throwable $e) {
            Log::error("ProduccionDetalladaEcografiasExport.getData failed", [
                "error" => $e->getMessage(),
            ]);
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
