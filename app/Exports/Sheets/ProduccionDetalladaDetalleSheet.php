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
 * Hoja de Detalle de Producción de Estudios Radiológicos (RX, Tomografía, Mamografía, Resonancia).
 *
 * Flujo del proceso:
 * 1. Recibe la colección consolidada de estudios productivos con sus diagnósticos y transcripciones.
 * 2. Mapea cada registro a las 19 columnas analíticas y clínicas del sistema de información.
 * 3. Aplica estilos corporativos institucionales (banner azul oscuro #1A2274, alineación centrada y bordes).
 * 4. Asigna el título "Detalle de Producción" a la pestaña para navegación organizada en el libro multi-hoja.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ProduccionDetalladaDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * Constructor con inyección de la colección de estudios.
     *
     * @param  Collection  $estudios  Colección de estudios de producción.
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
        return "Detalle de Producción";
    }

    /**
     * Retorna la colección de estudios para poblar la hoja.
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
     * Define los encabezados de las 19 columnas del reporte.
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
            "ID",
            "Sede",
            "Fecha de Estudio",
            "Fecha de Asignacion",
            "Fecha de Lectura",
            "Atención",
            "Prioridad",
            "Quien Registró",
            "Médico",
            "Documento Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Nombre Producto",
            "Diagnostico",
            "Transcriptor",
            "EPS",
            "Contrato",
            "Plan",
            "Lectura",
        ];
    }

    /**
     * Convierte cada estudio a un array de celdas para el archivo Excel.
     *
     * @param  mixed  $estudio  Registro del estudio productivo.
     *
     * @returns array<string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($estudio): array
    {
        return [
            $estudio->id ?? "N/A",
            $estudio->nom_sede ?? "N/A",
            $estudio->fec_estudio ?? "N/A",
            $estudio->created_at ?? "N/A",
            $estudio->fechor_lectura ?? "N/A",
            $estudio->atencion ?? "N/A",
            $estudio->nom_priori ?? "N/A",
            $estudio->quien_registro ?? "N/A",
            $estudio->medico ?? "N/A",
            $estudio->num_docu ?? "N/A",
            $estudio->nombres ?? "N/A",
            $estudio->cod_cups ?? "N/A",
            $estudio->nom_produc ?? "N/A",
            $estudio->diagnosticos ?? "N/A",
            $estudio->transcriptor ?? "N/A",
            $estudio->eps ?? "N/A",
            $estudio->contrato ?? "N/A",
            $estudio->plan ?? "N/A",
            $estudio->lectura ?? "N/A",
        ];
    }

    /**
     * Aplica estilos institucionales al encabezado, título y cuadrícula de datos.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa.
     *
     * @returns array<int, array<string, mixed>>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Encabezado corporativo institucional
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Producción");
        $sheet->mergeCells("A1:S1");
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
        $sheet->getStyle("A3:S3")->applyFromArray([
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
            $sheet->getStyle("A4:S{$highestRow}")->applyFromArray([
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
     * Define el ancho de cada columna del reporte (A hasta S).
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
            "A" => 10, // ID
            "B" => 24, // Sede
            "C" => 18, // Fecha de Estudio
            "D" => 20, // Fecha de Asignación
            "E" => 20, // Fecha de Lectura
            "F" => 12, // Atención
            "G" => 14, // Prioridad
            "H" => 24, // Quien Registró
            "I" => 30, // Médico
            "J" => 18, // Documento Paciente
            "K" => 32, // Nombre Paciente
            "L" => 14, // Código CUPS
            "M" => 36, // Nombre Producto
            "N" => 32, // Diagnostico
            "O" => 24, // Transcriptor
            "P" => 26, // EPS
            "Q" => 24, // Contrato
            "R" => 24, // Plan
            "S" => 40, // Lectura
        ];
    }

    /**
     * Celda desde la que inician los encabezados de la tabla.
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
