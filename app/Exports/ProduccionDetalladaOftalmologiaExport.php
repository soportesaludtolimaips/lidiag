<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionDetalladaOftalmologiaDetalleSheet;
use App\Exports\Sheets\ProduccionDetalladaOftalmologiaEstadisticasSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Producción Detallada de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Recibe los filtros de fechas, sede, usuario asignador y médico oftalmólogo.
 * 2. Consulta los registros de exámenes y procedimientos oftalmológicos con manejo defensivo.
 * 3. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas y Detalle de Oftalmología.
 * 4. Aplica gráficos nativos y estilos corporativos Salud Tolima Verde Esmeralda.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-06
 */
class ProduccionDetalladaOftalmologiaExport implements WithMultipleSheets
{
    protected mixed $fechaInicio;

    protected mixed $fechaFin;

    protected mixed $sedeId;

    protected mixed $quienAsignoId;

    protected mixed $medicoId;

    /**
     * Cache de registros de exámenes oftalmológicos.
     */
    protected ?Collection $cachedData = null;

    /**
     * Recibe los filtros del reporte de producción de oftalmología.
     *
     * @param  mixed  $fechaInicio
     * @param  mixed  $fechaFin
     * @param  mixed  $sedeId
     * @param  mixed  $quienAsignoId
     * @param  mixed  $medicoId
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null, $quienAsignoId = null, $medicoId = null)
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
     * @date 2026-11-06
     */
    public function sheets(): array
    {
        $data = $this->getData();

        return [
            new ProduccionDetalladaOftalmologiaEstadisticasSheet(
                $data,
                $this->fechaInicio ? (string) $this->fechaInicio : null,
                $this->fechaFin ? (string) $this->fechaFin : null
            ),
            new ProduccionDetalladaOftalmologiaDetalleSheet($data),
        ];
    }

    /**
     * Consulta y devuelve los exámenes de oftalmología del reporte según los filtros aplicados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function getData(): Collection
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        try {
            $quien_asigno_id = $this->quienAsignoId;
            $medico_id = $this->medicoId;

            // Busca en primera instancia en vista dedicada si existe
            if (DB::getSchemaBuilder()->hasTable("view_reporte_oftalmologia")) {
                $examenes = DB::table("view_reporte_oftalmologia")
                    ->when($this->fechaInicio, fn ($q) => $q->whereBetween(DB::raw("DATE(fechor_lectura)"), [$this->fechaInicio, $this->fechaFin]))
                    ->when($this->sedeId, fn ($q) => $q->where("sede_id", $this->sedeId))
                    ->when($this->quienAsignoId, fn ($q) => $q->where("usua_regis_id", $this->quienAsignoId))
                    ->when($this->medicoId, fn ($q) => $q->where("medico_id", $this->medicoId))
                    ->get();
            } else {
                // Consulta en estudios_productos acotado a médicos del módulo 3 (Oftalmología) o filtro específico
                $query = DB::table("estudios_productos")
                    ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
                    ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
                    ->join("users as registro", "estudios.quien_registro_id", "=", "registro.id")
                    ->join("users as medico", "medico.id", "=", "estudios.medico_id")
                    ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
                    ->whereBetween("estudios_productos.fechor_lectura", [
                        $this->fechaInicio." 00:00:00",
                        $this->fechaFin." 23:59:59",
                    ])
                    ->when($this->sedeId, fn ($q) => $q->where("estudios.sede_id", $this->sedeId))
                    ->when($this->quienAsignoId, fn ($q) => $q->where("estudios.quien_registro_id", $quien_asigno_id))
                    ->when($this->medicoId, fn ($q) => $q->where("estudios.medico_id", $medico_id))
                    ->where("estudios_productos.activo", "=", 1)
                    ->select(
                        "estudios.id",
                        "estudios.fec_estudio",
                        "estudios_productos.created_at",
                        "estudios_productos.fechor_lectura",
                        "estudios.atencion as atencion_id",
                        "registro.name as quien_registro",
                        "medico.name as medico",
                        "pacientes.num_docu as documento_paciente",
                        "pacientes.nombres as nombre_paciente",
                        "estudios_productos.cod_cups",
                        "estudios_productos.nom_produc",
                        "estudios.eps",
                        "estudios.contrato",
                        "estudios.plan",
                        "estudios.destino_estudio as quien_ordena",
                        "estudios_productos.lectura",
                        "estudios_productos.opinion"
                    );

                // Si no se pasó médico específico, filtrar por médicos asignados al módulo 3
                if (! $this->medicoId) {
                    $medicosOftalmoIds = DB::table("users_modulos")
                        ->where("modulo_id", 3)
                        ->pluck("user_id");

                    if ($medicosOftalmoIds->isNotEmpty()) {
                        $query->whereIn("estudios.medico_id", $medicosOftalmoIds);
                    }
                }

                $examenes = $query->get();
            }
        } catch (\Throwable) {
            $examenes = collect();
        }

        $this->cachedData = $examenes;

        return $this->cachedData;
    }

    /**
     * Compatibilidad con colecciones directas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function collection(): Collection
    {
        return $this->getData();
    }
}
