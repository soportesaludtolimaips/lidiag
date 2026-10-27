<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteAusentismoCitasExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected $fechaInicio;

    protected $fechaFin;

    protected $sedeId;

    protected $estado;

    /**
     * Constructor del reporte de ausentismo y cancelaciones de citas.
     *
     * @param  string  $fechaInicio  Fecha de inicio (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha de fin (YYYY-MM-DD).
     * @param  int|null  $sedeId  Identificador de la sede o null.
     * @param  string|null  $estado  Filtro específico de estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null, $estado = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->estado = $estado;
    }

    /**
     * Consulta las citas canceladas, rechazadas o con inasistencia (no-show).
     *
     * @return Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function collection()
    {
        return DB::table("citas")
            ->leftJoin("config_sedes", "citas.sede_id", "=", "config_sedes.id")
            ->leftJoin("pacientes", "citas.paciente_id", "=", "pacientes.id")
            ->leftJoin("config_servicios", "citas.servicio_id", "=", "config_servicios.id")
            ->leftJoin("config_productos", "citas.producto_id", "=", "config_productos.id")
            ->leftJoin("config_cita_rechazo_motivos", "citas.rechazo_motivo_id", "=", "config_cita_rechazo_motivos.id")
            ->whereBetween("citas.fecha_hora_inicio", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("citas.sede_id", $this->sedeId);
            })
            ->when($this->estado && $this->estado !== "todos", function ($query) {
                return $query->where("citas.estado", $this->estado);
            }, function ($query) {
                return $query->whereIn("citas.estado", ["no_asistio", "cancelada", "rechazada"]);
            })
            ->select([
                "citas.id",
                "config_sedes.nom_sede",
                "citas.fecha_hora_inicio",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "pacientes.movil as telefono",
                "config_servicios.nom_servicio",
                "config_productos.cod_cups",
                "config_productos.nom_produc",
                "citas.estado",
                "config_cita_rechazo_motivos.motivo as motivo_rechazo",
                "citas.rechazo_observacion",
                "citas.whatsapp_estado",
            ])
            ->orderBy("citas.fecha_hora_inicio", "desc")
            ->get();
    }

    /**
     * Encabezados de columnas del reporte de ausentismo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function headings(): array
    {
        return [
            "ID Cita",
            "Sede Hospitalaria",
            "Fecha/Hora Programada",
            "Doc. Paciente",
            "Nombre Paciente",
            "Teléfono Contacto",
            "Servicio / Especialidad",
            "Código CUPS",
            "Procedimiento",
            "Estado de la Cita",
            "Motivo de Inasistencia / Rechazo",
            "Observaciones del Motivo",
            "Estado Notificación WhatsApp",
        ];
    }

    /**
     * Mapeo de la fila de datos.
     *
     * @param  mixed  $row  Fila del registro de cita.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function map($row): array
    {
        $estadoLabel = match ($row->estado) {
            "no_asistio" => "No Asistió (Ausente)",
            "cancelada" => "Cancelada",
            "rechazada" => "Rechazada",
            default => ucfirst((string) $row->estado),
        };

        return [
            $row->id ?? "N/A",
            $row->nom_sede ?? "Sin sede",
            $row->fecha_hora_inicio ?? "N/A",
            $row->num_docu ?? "N/A",
            $row->paciente ?? "N/A",
            $row->telefono ?? "N/A",
            $row->nom_servicio ?? "N/A",
            $row->cod_cups ?? "N/A",
            $row->nom_produc ?? "N/A",
            $estadoLabel,
            $row->motivo_rechazo ?? "No especificado",
            $row->rechazo_observacion ?? "",
            $row->whatsapp_estado ?? "No enviado",
        ];
    }

    /**
     * Celda de inicio de datos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function startCell(): string
    {
        return "A3";
    }

    /**
     * Estilos de encabezado y bordes institucionales.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Ausentismo y Cancelaciones de Citas (No-Show)");
        $sheet->mergeCells("A1:M1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 14,
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A3:M3")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:M".$highestRow)->applyFromArray([
                "borders" => [
                    "allBorders" => [
                        "borderStyle" => Border::BORDER_THIN,
                        "color" => ["rgb" => "CCCCCC"],
                    ],
                ],
            ]);
        }

        return [
            1 => ["font" => ["bold" => true]],
            3 => ["font" => ["bold" => true]],
        ];
    }

    /**
     * Anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function columnWidths(): array
    {
        return [
            "A" => 12,
            "B" => 22,
            "C" => 22,
            "D" => 18,
            "E" => 30,
            "F" => 18,
            "G" => 25,
            "H" => 14,
            "I" => 35,
            "J" => 22,
            "K" => 30,
            "L" => 35,
            "M" => 22,
        ];
    }
}
