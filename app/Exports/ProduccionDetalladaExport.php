<?php

namespace App\Exports;

use App\Exports\Sheets\ProduccionDetalladaDetalleSheet;
use App\Exports\Sheets\ProduccionDetalladaEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
     * Consulta y devuelve los estudios del reporte de producción según los filtros aplicados.
     *
     * @returns Collection Registros de estudios productivos con sus diagnósticos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosProduccion(): Collection
    {
        $quien_asigno_id = $this->quienAsignoId;
        $medico_id = $this->medicoId;

        return DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->join("users as registro", "estudios.quien_registro_id", "=", "registro.id")
            ->join("users as medico", "medico.id", "=", "estudios.medico_id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("users as transcrip", "transcrip.id", "=", "estudios_productos.transcriptor_id")
            ->whereBetween("estudios_productos.fechor_lectura", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->when($this->quienAsignoId, function ($query) use ($quien_asigno_id) {
                return $query->where("estudios.quien_registro_id", $quien_asigno_id);
            })
            ->when($this->medicoId, function ($query) use ($medico_id) {
                return $query->where("estudios.medico_id", $medico_id);
            })
            ->where("estudios_productos.activo", "=", 1)
            ->select(
                "estudios.id",
                "config_sedes.nom_sede",
                "estudios.fec_estudio",
                "estudios_productos.created_at",
                "estudios_productos.fechor_lectura",
                "estudios.atencion",
                "config_prioridades.nom_priori",
                "registro.name as quien_registro",
                "medico.name as medico",
                "pacientes.num_docu",
                "pacientes.nombres",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "estudios.mods_in_study",
                "transcrip.name as transcriptor",
                "estudios.eps",
                "estudios.contrato",
                "estudios.plan",
                DB::raw("(SELECT GROUP_CONCAT('- ', nom_diagnos SEPARATOR '\n')
                            FROM estudios_diagnosticos
                            WHERE estudio_id = estudios.id) as diagnosticos"),
                "estudios_productos.lectura"
            )
            ->orderBy("estudios_productos.fechor_lectura", "desc")
            ->get();
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
