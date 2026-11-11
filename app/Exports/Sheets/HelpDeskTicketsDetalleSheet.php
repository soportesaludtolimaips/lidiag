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
 * Hoja de Detalle de Tickets de Mesa de Ayuda (HelpDesk).
 *
 * Flujo del proceso:
 * 1. Mapea todos los incidentes y requerimientos con folio, solicitante, técnico y tiempos.
 * 2. Calcula las horas de resolución cuando aplique.
 * 3. Aplica estilos corporativos Salud Tolima Azul Clásico.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class HelpDeskTicketsDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $collection
    ) {}

    /**
     * Título de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Detalle Tickets";
    }

    /**
     * Retorna la colección de tickets.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->collection;
    }

    /**
     * Celda inicial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function startCell(): string
    {
        return "A3";
    }

    /**
     * Encabezados de columnas.
     *
     * @return array<int, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "ID",
            "Folio",
            "Título",
            "Estado",
            "Prioridad",
            "Orden prioridad",
            "Categoría",
            "Pendiente de",
            "Sede",
            "Solicitante",
            "Técnico",
            "Creado",
            "Resuelto",
            "Cerrado",
            "Horas resolución",
        ];
    }

    /**
     * Mapeo de columnas.
     *
     * @param  mixed  $row
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        $horas = null;
        if (! empty($row->segundos_resolucion)) {
            $horas = round(((float) $row->segundos_resolucion) / 3600, 2);
        }

        return [
            $row->id ?? "N/A",
            $row->folio ?? "N/A",
            $row->titulo ?? "N/A",
            $row->estado ?? "N/A",
            $row->prioridad ?? "N/A",
            $row->prioridad_orden ?? "N/A",
            $row->categoria ?? "N/A",
            $row->pendiente_de ?? "N/A",
            $row->sede ?? "N/A",
            $row->solicitante ?? "N/A",
            $row->tecnico ?? "N/A",
            $row->created_at ?? "N/A",
            $row->resolved_at ?? "N/A",
            $row->closed_at ?? "N/A",
            $horas ?? "N/A",
        ];
    }

    /**
     * Anchos de columna.
     *
     * @return array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 8,
            "B" => 20,
            "C" => 40,
            "D" => 14,
            "E" => 16,
            "F" => 14,
            "G" => 20,
            "H" => 14,
            "I" => 18,
            "J" => 22,
            "K" => 22,
            "L" => 18,
            "M" => 18,
            "N" => 18,
            "O" => 16,
        ];
    }

    /**
     * Estilos de la hoja de detalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setCellValue("A1", "Helpdesk - Tickets (Detallado)");
        $sheet->mergeCells("A1:O1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 14],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A3:O3")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:O{$highestRow}")->applyFromArray([
                "borders" => [
                    "allBorders" => [
                        "borderStyle" => Border::BORDER_THIN,
                        "color" => ["rgb" => "000000"],
                    ],
                ],
            ]);
        }

        return [
            1 => ["font" => ["bold" => true]],
            3 => ["font" => ["bold" => true]],
        ];
    }
}
