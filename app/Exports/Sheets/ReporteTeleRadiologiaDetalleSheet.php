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
 * Hoja de Detalle de Estudios Derivados a Tele-Radiología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteTeleRadiologiaDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
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
        protected Collection $estudios
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
        return "Detalle Tele-Radiología";
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
        return $this->estudios;
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
            "ID Estudio",
            "Sede Hospitalaria",
            "Fecha Envío / Registro",
            "Study Instance UID",
            "Accession Number",
            "Modalidad",
            "Documento Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Nombre Procedimiento",
            "Médico Lector Remoto",
            "Fecha/Hora Lectura",
            "Estado Lectura",
            "TAT (Horas)",
            "Observación Destino",
        ];
    }

    /**
     * Mapea cada registro a las columnas.
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
            $row->fecha_envio ?? "N/A",
            $row->study_iuid ?? "N/A",
            $row->accession_no ?? "N/A",
            $row->modalidad ?? "N/A",
            $row->num_docu ?? "N/A",
            $row->paciente ?? "N/A",
            $row->cod_cups ?? "N/A",
            $row->nom_produc ?? "N/A",
            $row->medico_lector ?? "Sin asignar",
            $row->fechor_lectura ?? "Pendiente",
            $row->estado_lectura ?? "Pendiente",
            $row->tat_horas !== null ? (float) $row->tat_horas : "En curso",
            $row->destino_estudio ?? "Tele Radiologia",
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
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Tele-Radiología y Lecturas Remotas");
        $sheet->mergeCells("A1:O1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 13, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle("A3:O3")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 9, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $highestRow = max(3, $sheet->getHighestRow());
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:O{$highestRow}")->applyFromArray([
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
            "A" => 12, "B" => 25, "C" => 18, "D" => 25, "E" => 16,
            "F" => 12, "G" => 16, "H" => 30, "I" => 14, "J" => 35,
            "K" => 25, "L" => 18, "M" => 18, "N" => 14, "O" => 20,
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
