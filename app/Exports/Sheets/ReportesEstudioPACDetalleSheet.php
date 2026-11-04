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
 * Hoja de Detalle de Estudios Radiológicos Tomados en el Sistema PACS (Dcm4chee / MySQL Sucursal).
 *
 * Flujo del proceso:
 * 1. Recibe la colección de estudios adquiridos en el PACS para la sede y rango de fechas.
 * 2. Mapea cada registro a 6 columnas: Sede, Fecha de Estudio, Documento, Paciente, Procedimiento y Modalidad.
 * 3. Aplica estilos corporativos institucionales (encabezado azul oscuro #1A2274, bordes, alineación y anchos).
 * 4. Asigna el título de pestaña "Detalle de Estudios" para navegación ágil en el libro multi-hoja.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReportesEstudioPACDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Constructor de la hoja de detalle de estudios PACS.
     *
     * @param  Collection  $estudios  Colección de registros de la base de datos PACS.
     * @param  string  $sedeNombre  Nombre de la sede hospitalaria consultada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $estudios,
        protected string $sedeNombre
    ) {}

    /**
     * Retorna el nombre de la pestaña en el libro Excel.
     *
     * @returns string Nombre descriptivo de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function title(): string
    {
        return "Detalle de Estudios";
    }

    /**
     * Retorna la colección de estudios para poblar la hoja.
     *
     * @returns Collection Colección de estudios del PACS.
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
     * Define los encabezados de las columnas de la tabla de detalle.
     *
     * @returns array<string> Nombres de las columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "Sede",
            "Fecha de Estudio",
            "Documento del Paciente",
            "Paciente",
            "Descripción del Estudio",
            "Modalidad",
        ];
    }

    /**
     * Mapea cada objeto de estudio a un array de celdas para el archivo Excel.
     *
     * @param  mixed  $estudio  Registro con campos de la tabla study y patient.
     *
     * @returns array<string> Valores mapeados para la fila.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($estudio): array
    {
        return [
            $this->sedeNombre !== "" ? $this->sedeNombre : "N/A",
            $estudio->study_datetime ?? "N/A",
            $estudio->pat_id ?? "N/A",
            $estudio->pat_name ?? "N/A",
            $estudio->study_desc ?? "N/A",
            $estudio->mods_in_study ?? "N/A",
        ];
    }

    /**
     * Aplica estilos visuales a la hoja de cálculo (encabezado corporativo, bordes y colores).
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa.
     *
     * @returns array<int, array<string, mixed>> Estilos adicionales.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Encabezado corporativo institucional
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Estudios Tomados en PAC");
        $sheet->mergeCells("A1:F1");

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
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Encabezado de la tabla de datos
        $sheet->getStyle("A3:F3")->applyFromArray([
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
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Bordes para los registros de datos
        $highestRow = max(3, $sheet->getHighestRow());
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:F{$highestRow}")->applyFromArray([
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

    /**
     * Define el ancho de cada columna del archivo Excel.
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
            "A" => 30, // Sede
            "B" => 20, // Fecha de Estudio
            "C" => 16, // Documento del Paciente
            "D" => 48, // Paciente
            "E" => 38, // Descripción del Estudio
            "F" => 14, // Modalidad
        ];
    }

    /**
     * Retorna la celda de inicio para los encabezados de la tabla.
     *
     * @returns string Celda de inicio ('A3').
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
