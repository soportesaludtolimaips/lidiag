<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportador institucional de Excel para el reporte de Citas y Canal WhatsApp.
 *
 * Flujo del proceso:
 * 1. Consulta el histórico de citas y solicitudes recibidas a través de WhatsApp y agendamiento.
 * 2. Cruza con sedes, pacientes, servicios y estados de gestión.
 * 3. Mapea indicadores de conversión, confirmaciones y cancelaciones.
 * 4. Aplica estilos institucionales de Salud Tolima / LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteCitasWhatsAppExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?int $sedeId;

    protected string $etiquetaPeriodo;

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
    }

    /**
     * Consulta las citas y solicitudes en el período.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
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
                    DB::raw("COALESCE(pacientes.documento, pacientes.num_docu) as documento_paciente"),
                    DB::raw("COALESCE(pacientes.nombres, CONCAT(pacientes.primer_nombre, ' ', COALESCE(pacientes.primer_apellido, ''))) as paciente_nombre"),
                    DB::raw("COALESCE(pacientes.movil, pacientes.telefono, 'N/D') as telefono"),
                    "config_servicios.nom_servicio",
                    "config_productos.nom_produc",
                    "citas.fecha_hora_inicio",
                    "citas.created_at as fecha_solicitud",
                    "citas.observaciones",
                ])
                ->orderByDesc("citas.created_at")
                ->get();
        }

        // Fallback a agenda_citas si aplica
        return DB::table("agenda_citas")
            ->leftJoin("config_sedes", "agenda_citas.sede_id", "=", "config_sedes.id")
            ->leftJoin("config_servicios", "agenda_citas.servicio_id", "=", "config_servicios.id")
            ->whereBetween("agenda_citas.created_at", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("agenda_citas.sede_id", $this->sedeId);
            })
            ->select([
                "agenda_citas.id",
                "config_sedes.nom_sede",
                "agenda_citas.estado",
                DB::raw("'N/D' as whatsapp_estado"),
                DB::raw("'N/D' as documento_paciente"),
                DB::raw("agenda_citas.title as paciente_nombre"),
                DB::raw("'N/D' as telefono"),
                "config_servicios.nom_servicio",
                DB::raw("agenda_citas.description as nom_produc"),
                "agenda_citas.fecha_hora_ini as fecha_hora_inicio",
                "agenda_citas.created_at as fecha_solicitud",
                DB::raw("'' as observaciones"),
            ])
            ->orderByDesc("agenda_citas.created_at")
            ->get();
    }

    /**
     * Mapea cada fila a las columnas del Excel.
     *
     * @param  mixed  $row
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        $estado = ucfirst(str_replace("_", " ", (string) ($row->estado ?? "Pendiente")));
        $canal = ! empty($row->whatsapp_estado) ? "WhatsApp ({$row->whatsapp_estado})" : "Presencial / Web";

        return [
            $row->id,
            $row->nom_sede ?: "Sede General",
            $estado,
            $canal,
            $row->documento_paciente ?: "N/D",
            trim(preg_replace("/\s+/", " ", (string) ($row->paciente_nombre ?: "Sin nombre"))),
            $row->telefono ?: "N/D",
            $row->nom_servicio ?: "Diagnóstico",
            $row->nom_produc ?: "Consulta / Procedimiento",
            $row->fecha_solicitud ? Carbon::parse($row->fecha_solicitud)->format("d/m/Y H:i") : "N/D",
            $row->fecha_hora_inicio ? Carbon::parse($row->fecha_hora_inicio)->format("d/m/Y H:i") : "Por asignar",
            $row->observaciones ?: "Sin observaciones",
        ];
    }

    /**
     * Encabezados de columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "ID Cita / Solicitud",
            "Sede Hospitalaria",
            "Estado de Gestión",
            "Canal de Entrada",
            "Documento Paciente",
            "Nombre Completo Paciente",
            "Teléfono de Contacto",
            "Servicio / Área",
            "Procedimiento / Cita",
            "Fecha Solicitud",
            "Fecha y Hora Programada",
            "Observaciones de Atención",
        ];
    }

    /**
     * Celda de inicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function startCell(): string
    {
        return "A4";
    }

    /**
     * Anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 14, // ID
            "B" => 26, // Sede
            "C" => 20, // Estado
            "D" => 24, // Canal
            "E" => 18, // Documento
            "F" => 35, // Paciente
            "G" => 18, // Teléfono
            "H" => 22, // Servicio
            "I" => 35, // Procedimiento
            "J" => 20, // Fecha Solicitud
            "K" => 22, // Fecha Programada
            "L" => 35, // Observaciones
        ];
    }

    /**
     * Estilos del reporte.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Banner institucional
        $sheet->mergeCells("A1:L1");
        $sheet->setCellValue("A1", "SALUD TOLIMA / LIDIAG — GESTIÓN DE CITAS Y CANAL WHATSAPP");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF25D366"); // Verde WhatsApp oficial
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo
        $sheet->mergeCells("A2:L2");
        $sheet->setCellValue("A2", "Período: {$this->etiquetaPeriodo} | Generado: ".Carbon::now()->format("d/m/Y H:i:s"));
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->setColor(new Color("FF333333"));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados (Fila 4)
        $sheet->getStyle("A4:L4")->getFont()->setBold(true)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A4:L4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF128C7E");
        $sheet->getStyle("A4:L4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Bordes
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCCCCC");
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G5:G{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J5:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
