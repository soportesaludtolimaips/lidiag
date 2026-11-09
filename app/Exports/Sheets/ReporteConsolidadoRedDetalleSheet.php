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
 * Hoja de Detalle de Estudios Consolidados de la Red Hospitalaria.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteConsolidadoRedDetalleSheet implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return "Detalle Consolidado Red";
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
            "Modalidad",
            "Código CUPS",
            "Procedimiento",
            "Documento",
            "Paciente",
            "Atención",
            "Prioridad",
            "Enviado a",
            "Quien Agendó",
            "Fecha Asignación",
            "Médico Lector",
            "Transcriptor",
            "EPS",
            "Contrato",
            "Plan",
            "Diagnóstico",
            "Fecha Estudio",
            "Fecha Lectura",
            "TAT (Horas)",
            "Semáforo Oportunidad",
            "Lectura",
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
            $row->estudio_id ?? "N/A",
            $row->nom_sede ?? "N/A",
            $row->mods_in_study ?? "N/A",
            $row->cod_cups ?? "N/A",
            $row->nombre_procedimiento ?? "N/A",
            $row->documento ?? "N/A",
            $row->paciente_nombre ?? "N/A",
            $row->atencion ?? "N/A",
            $row->prioridad ?? "Rutina",
            $row->enviado_a ?? "Salud Tolima",
            $row->quien_agendo ?? "N/A",
            $row->fecha_asignacion ?? "N/A",
            $row->medico_nombre ?? "Sin Asignar",
            $row->transcriptor_nombre ?? "N/A",
            $row->eps ?? "N/A",
            $row->contrato ?? "N/A",
            $row->plan ?? "N/A",
            $row->diagnostico ?? "Sin diagnóstico",
            $row->fecha_estudio ?? "N/A",
            $row->fecha_lectura ?? "Pendiente",
            $row->tat_horas !== null ? (float) $row->tat_horas : "En curso",
            $row->semaforo_oportunidad ?? "Normal (<=24h)",
            $row->lectura ?? "Pendiente",
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
        $sheet->setCellValue("A1", "Salud Tolima - Consolidado Detallado de Estudios de la Red Hospitalaria");
        $sheet->mergeCells("A1:W1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 13, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getStyle("A3:W3")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"], "size" => 9, "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER, "wrapText" => true],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(24);

        $highestRow = max(3, $sheet->getHighestRow());
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:W{$highestRow}")->applyFromArray([
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
            "A" => 12,
            "B" => 26,
            "C" => 12,
            "D" => 14,
            "E" => 35,
            "F" => 16,
            "G" => 32,
            "H" => 14,
            "I" => 12,
            "J" => 18,
            "K" => 25,
            "L" => 19,
            "M" => 25,
            "N" => 22,
            "O" => 20,
            "P" => 22,
            "Q" => 20,
            "R" => 35,
            "S" => 18,
            "T" => 18,
            "U" => 14,
            "V" => 22,
            "W" => 45,
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
