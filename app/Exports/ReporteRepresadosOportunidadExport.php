<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteRepresadosDetalleSheet;
use App\Exports\Sheets\ReporteRepresadosEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el Semáforo de Represamiento Asistencial y Urgencias fuera de SLA.
 *
 * Flujo del proceso:
 * 1. Consulta estudios pendientes de informe médico fuera de SLA (>24h) o urgencias una sola vez.
 * 2. Cruza con sedes, prioridades, pacientes y productos.
 * 3. Categoriza según severidad clínica: Preventivo (24-48h), Crítico (48-72h), Severo (>72h) y Urgencias (>30m).
 * 4. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de represamiento, severidad semáforo y gráficos nativos).
 *    - Hoja 2: Detalle de Represamiento (Listado completo con tiempos de espera y clasificación).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteRepresadosOportunidadExport implements WithMultipleSheets
{
    protected ?int $sedeId;

    protected string $sedeNombre = "Todas las Sedes";

    protected Collection $estudios;

    /**
     * Constructor del exportador de estudios represados y urgencias.
     *
     * @param  int|null  $sedeId  Identificador de la sede opcional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(?int $sedeId = null)
    {
        $this->sedeId = $sedeId;

        if ($this->sedeId) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        $this->estudios = $this->consultarEstudiosRepresados();
    }

    /**
     * Consulta estudios represados o urgencias pendientes sin lectura.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarEstudiosRepresados(): Collection
    {
        return DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->leftJoin("users as medico", "estudios.medico_id", "=", "medico.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where(function ($q) {
                $q->whereRaw("TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 24")
                    ->orWhere("estudios.prioridad_id", 1);
            })
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->select([
                "estudios.id as estudio_id",
                "config_sedes.nom_sede",
                "config_prioridades.nom_priori as nom_prioridad",
                "estudios.prioridad_id",
                "estudios.mods_in_study",
                "estudios_productos.nom_produc as nombre_procedimiento",
                "pacientes.num_docu as documento",
                "pacientes.nombres as paciente_nombre",
                "medico.name as medico_nombre",
                DB::raw("COALESCE(estudios.fec_estudio, estudios.created_at) as fecha_estudio"),
                "estudios.created_at as fecha_ingreso",
                DB::raw("TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) as horas_espera"),
                DB::raw("TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) as minutos_espera"),
                DB::raw("CASE 
                    WHEN estudios.prioridad_id = 1 AND TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 30 THEN '🚨 URGENCIA FUERA DE SLA (>30m)'
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 72 THEN '🔴 Severo (>72h)'
                    WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 48 THEN '🟠 Crítico (48-72h)'
                    ELSE '🟡 Preventivo (24-48h)'
                END as semaforo"),
            ])
            ->orderBy("horas_espera", "desc")
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
            new ReporteRepresadosEstadisticasSheet(
                $this->estudios,
                $this->sedeNombre
            ),
            new ReporteRepresadosDetalleSheet(
                $this->estudios
            ),
        ];
    }
}
