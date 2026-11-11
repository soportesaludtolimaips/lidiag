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
 * Hoja de Detalle de Producción de Procedimientos y Exámenes de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Mapea cada registro oftalmológico con datos del paciente, cita/estudio, CUPS, médico y diagnósticos.
 * 2. Limpia el contenido HTML de lecturas y opiniones médicas.
 * 3. Aplica estilos institucionales Salud Tolima Verde Esmeralda (#0D5C3A).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-06
 */
class ProduccionDetalladaOftalmologiaDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected Collection $examenes
    ) {}

    /**
     * Título de la pestaña.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function title(): string
    {
        return "Detalle Oftalmología";
    }

    /**
     * Retorna la colección de exámenes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function collection(): Collection
    {
        return $this->examenes;
    }

    /**
     * Celda inicial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
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
     * @date 2026-11-06
     */
    public function headings(): array
    {
        return [
            "ID",
            "Fecha Cita / Examen",
            "Fecha Registro",
            "Fecha Lectura / Diagnóstico",
            "Atención",
            "Quien Registró",
            "Médico Oftalmólogo",
            "Documento Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Nombre Examen / Procedimiento",
            "EPS",
            "Contrato",
            "Plan",
            "Quien Ordena",
            "Resultado / Informe",
            "Diagnóstico / Conclusión",
        ];
    }

    /**
     * Mapeo de columnas.
     *
     * @param  mixed  $examen
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function map($examen): array
    {
        return [
            $examen->id ?? "N/A",
            $examen->fec_cita_sahi ?? ($examen->fec_estudio ?? "N/A"),
            $examen->fec_regis_cita_sahi ?? ($examen->created_at ?? "N/A"),
            $examen->fechor_lectura ?? "N/A",
            $examen->atencion_id ?? ($examen->atencion ?? "N/A"),
            $examen->quien_registro ?? "N/A",
            $examen->medico ?? "N/A",
            $examen->documento_paciente ?? ($examen->num_docu ?? "N/A"),
            $examen->nombre_paciente ?? ($examen->nombres ?? "N/A"),
            $examen->cod_cups ?? "N/A",
            $examen->nom_produc ?? "N/A",
            $examen->eps ?? "N/A",
            $examen->contrato ?? "N/A",
            $examen->plan ?? "N/A",
            $examen->quien_ordena ?? "N/A",
            strip_tags((string) ($examen->lectura ?? "N/A")),
            strip_tags((string) ($examen->opinion ?? "N/A")),
        ];
    }

    /**
     * Anchos de columna.
     *
     * @return array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function columnWidths(): array
    {
        return [
            "A" => 10,
            "B" => 18,
            "C" => 18,
            "D" => 20,
            "E" => 14,
            "F" => 22,
            "G" => 26,
            "H" => 20,
            "I" => 32,
            "J" => 14,
            "K" => 36,
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
     * @date 2026-11-06
     */
    public function styles(Worksheet $sheet): array
    {
        // Encabezado personalizado
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Producción Oftalmología");
        $sheet->mergeCells("A1:Q1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 14,
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "0D5C3A"],
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
                "startColor" => ["rgb" => "0D5C3A"],
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
