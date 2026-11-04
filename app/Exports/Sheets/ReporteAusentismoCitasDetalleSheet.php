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
 * Hoja de Detalle de Ausentismo y Cancelaciones de Citas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAusentismoCitasDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Constructor con inyección de citas.
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
        return "Detalle de Inasistencias";
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
            "Documento Paciente",
            "Nombre Paciente",
            "Teléfono Contacto",
            "Servicio Clínico",
            "Código CUPS",
            "Nombre Procedimiento",
            "Estado Cita",
            "Motivo Rechazo / Cancelación",
            "Observación / Detalle",
            "Estado WhatsApp",
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
            $row->num_docu ?? "N/A",
            $row->paciente ?? "N/A",
            $row->telefono ?? "N/A",
            $row->nom_servicio ?? "N/A",
            $row->cod_cups ?? "N/A",
            $row->nom_produc ?? "N/A",
            ucfirst(str_replace("_", " ", (string) ($row->estado ?? "N/A"))),
            $row->motivo_rechazo ?? "Inasistencia no especificada",
            $row->rechazo_observacion ?? "N/A",
            $row->whatsapp_estado ?? "N/A",
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
        $sheet->setCellValue("A1", "Salud Tolima - Reporte Detallado de Ausentismo y Cancelaciones de Citas");
        $sheet->mergeCells("A1:M1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 13, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle("A3:M3")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 9, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $highestRow = max(3, $sheet->getHighestRow());
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:M{$highestRow}")->applyFromArray([
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
            "A" => 10, "B" => 25, "C" => 20, "D" => 16, "E" => 30,
            "F" => 16, "G" => 22, "H" => 14, "I" => 32, "J" => 18,
            "K" => 30, "L" => 30, "M" => 16,
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
