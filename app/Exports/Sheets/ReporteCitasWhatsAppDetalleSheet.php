<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Detalle de Citas y Solicitudes del Canal WhatsApp.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteCitasWhatsAppDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Constructor con inyección de registros.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $citas
    ) {}

    /**
     * Título de la pestaña en el libro Excel.
     *
     * @returns string
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Detalle Citas WhatsApp";
    }

    /**
     * Retorna la colección.
     *
     * @returns Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->citas;
    }

    /**
     * Encabezados de columnas.
     *
     * @returns array<string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "ID Cita",
            "Sede Hospitalaria",
            "Fecha/Hora Programada",
            "Fecha Solicitud",
            "Documento",
            "Paciente",
            "Teléfono Móvil",
            "Servicio Solicitado",
            "Procedimiento",
            "Estado Cita",
            "Estado WhatsApp",
            "Canal de Ingreso",
        ];
    }

    /**
     * Mapea cada cita a valores de celda.
     *
     * @param  mixed  $row
     *
     * @returns array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        return [
            $row->id ?? "N/A",
            $row->nom_sede ?? "N/A",
            $row->fecha_hora_inicio ?? "N/A",
            $row->fecha_solicitud ?? "N/A",
            $row->documento ?? "N/A",
            $row->nombre_completo ?? "N/A",
            $row->telefono ?? "N/A",
            $row->nom_servicio ?? "N/A",
            $row->nom_produc ?? "N/A",
            ucfirst(str_replace("_", " ", (string) ($row->estado ?? "N/A"))),
            $row->whatsapp_estado ?? "Pendiente",
            $row->canal_ingreso ?? "WhatsApp",
        ];
    }

    /**
     * Aplica estilos institucionales.
     *
     *
     * @returns array<int, array<string, mixed>>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setCellValue("A1", "Salud Tolima - Trazabilidad Detallada de Citas y Solicitudes WhatsApp");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 13, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "196F3D"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle("A3:L3")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 9, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "196F3D"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $highestRow = max(3, $sheet->getHighestRow());
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:L{$highestRow}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "D9D9D9"]]],
                "font" => ["name" => "Segoe UI", "size" => 9],
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
     * @returns array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 10, "B" => 25, "C" => 18, "D" => 18, "E" => 16,
            "F" => 30, "G" => 16, "H" => 22, "I" => 32, "J" => 16,
            "K" => 18, "L" => 16,
        ];
    }

    /**
     * Celda de inicio.
     *
     * @returns string
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function startCell(): string
    {
        return "A3";
    }
}
