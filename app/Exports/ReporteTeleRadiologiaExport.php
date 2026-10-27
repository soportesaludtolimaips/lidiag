<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReporteTeleRadiologiaExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected $fechaInicio;

    protected $fechaFin;

    protected $sedeId;

    /**
     * Constructor del reporte de tele-radiología y lecturas remotas.
     *
     * @param  string  $fechaInicio  Fecha de inicio del rango (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha de fin del rango (YYYY-MM-DD).
     * @param  int|null  $sedeId  Identificador de sede o null para todas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
    }

    /**
     * Consulta los estudios de tele-radiología derivados a lectura externa.
     *
     * @return Collection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function collection()
    {
        return DB::table("estudios")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("estudios_productos", "estudios_productos.estudio_id", "=", "estudios.id")
            ->leftJoin("users as medico", "medico.id", "=", "estudios.medico_id")
            ->where(function ($q) {
                $q->where("estudios.destino_estudio", "Tele Radiologia")
                    ->orWhere("medico.name", "LIKE", "TL %");
            })
            ->whereBetween("estudios.created_at", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->select([
                "estudios.id",
                "config_sedes.nom_sede",
                "estudios.created_at as fecha_envio",
                "estudios.study_iuid",
                "estudios.accession_no",
                "estudios.mods_in_study as modalidad",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "medico.name as medico_lector",
                "estudios_productos.fechor_lectura",
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL THEN 'Leído / Devuelto'
                    ELSE 'Pendiente de Lectura'
                END as estado_lectura"),
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura) / 60, 1)
                    ELSE NULL 
                END as horas_respuesta"),
            ])
            ->orderBy("estudios.created_at", "desc")
            ->get();
    }

    /**
     * Define los encabezados de columnas del reporte de tele-radiología.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function headings(): array
    {
        return [
            "ID Estudio",
            "Sede Hospitalaria",
            "Fecha/Hora Envío",
            "Study Instance UID",
            "Accession Number",
            "Modalidad",
            "Doc. Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Procedimiento",
            "Médico Tele-radiólogo",
            "Fecha/Hora Lectura",
            "Estado Lectura",
            "Tiempo Respuesta (Horas)",
        ];
    }

    /**
     * Convierte el registro de tele-radiología en una fila formateada.
     *
     * @param  mixed  $row  Fila del resultado de la consulta.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
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
            $row->horas_respuesta !== null ? $row->horas_respuesta." h" : "Pendiente",
        ];
    }

    /**
     * Celda de inicio para los datos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function startCell(): string
    {
        return "A3";
    }

    /**
     * Aplica los estilos del título institucional y encabezados.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->setCellValue("A1", "Salud Tolima - Reporte de Tele-radiología y Lecturas Externas");
        $sheet->mergeCells("A1:N1");
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

        $sheet->getStyle("A3:N3")->applyFromArray([
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

        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 4) {
            $sheet->getStyle("A4:N".$highestRow)->applyFromArray([
                "borders" => [
                    "allBorders" => [
                        "borderStyle" => Border::BORDER_THIN,
                        "color" => ["rgb" => "CCCCCC"],
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
     * Define los anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function columnWidths(): array
    {
        return [
            "A" => 12,
            "B" => 22,
            "C" => 20,
            "D" => 35,
            "E" => 18,
            "F" => 12,
            "G" => 18,
            "H" => 30,
            "I" => 14,
            "J" => 35,
            "K" => 25,
            "L" => 20,
            "M" => 20,
            "N" => 22,
        ];
    }
}
