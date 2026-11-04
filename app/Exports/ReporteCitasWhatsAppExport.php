<?php

namespace App\Exports;

use App\Exports\Sheets\ReporteCitasWhatsAppDetalleSheet;
use App\Exports\Sheets\ReporteCitasWhatsAppEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportador institucional de Excel para el reporte de Citas y Canal WhatsApp.
 *
 * Flujo del proceso:
 * 1. Consulta el histórico de citas y solicitudes recibidas a través de WhatsApp una sola vez.
 * 2. Cruza con sedes, pacientes, servicios y estados de gestión.
 * 3. Compone un libro multi-hoja con:
 *    - Hoja 1: Estadísticas y Gráficas (KPIs de canal, tasa de conversión por servicio y gráficos nativos).
 *    - Hoja 2: Detalle Citas WhatsApp (Listado completo con estados y teléfonos de contacto).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteCitasWhatsAppExport implements WithMultipleSheets
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?int $sedeId;

    protected string $etiquetaPeriodo;

    protected string $sedeNombre = "Todas las Sedes";

    protected Collection $citas;

    /**
     * Constructor del reporte de gestión de citas y WhatsApp.
     *
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
     * @param  int|null  $sedeId  Identificador de la sede o null para todas.
     * @param  string  $etiquetaPeriodo  Etiqueta del período.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(string $fechaInicio, string $fechaFin, ?int $sedeId = null, string $etiquetaPeriodo = "")
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->etiquetaPeriodo = $etiquetaPeriodo ?: "Del {$fechaInicio} al {$fechaFin}";

        if ($this->sedeId) {
            $sede = ConfigSede::find($this->sedeId);
            if ($sede) {
                $this->sedeNombre = (string) $sede->nom_sede;
            }
        }

        $this->citas = $this->consultarCitas();
    }

    /**
     * Consulta las citas y solicitudes en el período.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function consultarCitas(): Collection
    {
        if (Schema::hasTable("citas")) {
            return DB::table("citas")
                ->leftJoin("config_sedes", "citas.sede_id", "=", "config_sedes.id")
                ->leftJoin("pacientes", "citas.paciente_id", "=", "pacientes.id")
                ->leftJoin("config_servicios", "citas.servicio_id", "=", "config_servicios.id")
                ->leftJoin("config_productos", "citas.producto_id", "=", "config_productos.id")
                ->whereBetween("citas.created_at", [
                    $this->fechaInicio." 00:00:00",
                    $this->fechaFin." 23:59:59",
                ])
                ->when($this->sedeId, function ($query) {
                    return $query->where("citas.sede_id", $this->sedeId);
                })
                ->select([
                    "citas.id",
                    "config_sedes.nom_sede",
                    "citas.estado",
                    "citas.whatsapp_estado",
                    "citas.fecha_hora_inicio",
                    "citas.created_at as fecha_solicitud",
                    "pacientes.num_docu as documento",
                    "pacientes.nombres as nombre_completo",
                    "pacientes.movil as telefono",
                    "config_servicios.nom_servicio",
                    "config_productos.nom_produc",
                    "citas.canal_ingreso",
                ])
                ->orderBy("citas.created_at", "desc")
                ->get();
        }

        return collect();
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
            new ReporteCitasWhatsAppEstadisticasSheet(
                $this->citas,
                $this->fechaInicio,
                $this->fechaFin,
                $this->etiquetaPeriodo
            ),
            new ReporteCitasWhatsAppDetalleSheet(
                $this->citas
            ),
        ];
    }
}
