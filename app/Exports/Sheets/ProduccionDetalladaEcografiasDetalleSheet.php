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
 * Hoja de Detalle de Producción de Ecografías.
 *
 * Flujo del proceso:
 * 1. Mapea cada registro de ecografía con datos del paciente, cita, CUPS, médico y diagnósticos.
 * 2. Limpia el contenido HTML de lecturas y opiniones médicas.
 * 3. Aplica estilos institucionales Salud Tolima Azul Clásico.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ProduccionDetalladaEcografiasDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $ecografias
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
        return "Detalle Ecografías";
    }

    /**
     * Retorna la colección de ecografías.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return $this->ecografias;
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
     * Encabezados de la tabla.
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
            "Fecha Cita",
            "Fecha Registro",
            "Fecha Lectura",
            "Atención",
            "Quien Registró",
            "Médico",
            "Documento Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Nombre Producto",
            "EPS",
            "Contrato",
            "Plan",
            "Quien Ordena",
            "Lectura",
            "Opinión",
        ];
    }

    /**
     * Mapeo de columnas.
     *
     * @param  mixed  $ecografia
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($ecografia): array
    {
        return [
            $ecografia->id ?? "N/A",
            $ecografia->fec_cita_sahi ?? "N/A",
            $ecografia->fec_regis_cita_sahi ?? "N/A",
            $ecografia->fechor_lectura ?? "N/A",
            $ecografia->atencion_id ?? "N/A",
            $ecografia->quien_registro ?? "N/A",
            $ecografia->medico ?? "N/A",
            $ecografia->documento_paciente ?? "N/A",
            $ecografia->nombre_paciente ?? "N/A",
            $ecografia->cod_cups ?? "N/A",
            $ecografia->nom_produc ?? "N/A",
            $ecografia->eps ?? "N/A",
            $ecografia->contrato ?? "N/A",
            $ecografia->plan ?? "N/A",
            $ecografia->quien_ordena ?? "N/A",
            strip_tags((string) ($ecografia->lectura ?? "N/A")),
            strip_tags((string) ($ecografia->opinion ?? "N/A")),
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
            "A" => 10,
            "B" => 18,
            "C" => 18,
            "D" => 18,
            "E" => 12,
            "F" => 20,
            "G" => 25,
            "H" => 20,
            "I" => 30,
            "J" => 12,
            "K" => 30,
            "L" => 25,
            "M" => 20,
            "N" => 20,
            "O" => 25,
            "P" => 40,
            "Q" => 40,
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
        // Encabezado personalizado
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Producción Ecografías");
        $sheet->mergeCells("A1:Q1");
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

        // Estilo para el encabezado de los datos (Fila 3)
        $sheet->getStyle("A3:Q3")->applyFromArray([
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

        // Bordes
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:Q{$highestRow}")->applyFromArray([
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
