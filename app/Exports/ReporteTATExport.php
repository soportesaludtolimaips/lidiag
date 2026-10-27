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

/**
 * Exportador institucional de Excel para el reporte de Calidad y Oportunidad (Turnaround Time - TAT).
 *
 * Flujo del proceso:
 * 1. Consulta los estudios realizados en el rango de fechas.
 * 2. Cruza con detalle de productos, tiempos de dictado y tiempos de transcripción.
 * 3. Calcula los indicadores de horas de ciclo (ingreso -> lectura -> transcripción final).
 * 4. Aplica estilos institucionales de Salud Tolima / LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-27
 */
class ReporteTATExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?int $sedeId;

    protected ?string $modalidad;

    /**
     * Constructor del reporte de tiempos de respuesta TAT y oportunidad asistencial.
     *
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
     * @param  int|null  $sedeId  Identificador de la sede hospitalaria o null para todas.
     * @param  string|null  $modalidad  Modalidad técnica o null para todas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function __construct(string $fechaInicio, string $fechaFin, ?int $sedeId = null, ?string $modalidad = null)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->modalidad = ($modalidad === "todas" || empty($modalidad)) ? null : $modalidad;
    }

    /**
     * Consulta los registros para el cálculo de oportunidad y tiempos TAT.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function collection(): Collection
    {
        return DB::table("estudios")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->leftJoin("estudios_productos", "estudios_productos.estudio_id", "=", "estudios.id")
            ->leftJoin("users as medico", "medico.id", "=", "estudios.medico_id")
            ->leftJoin("users as transcriptor", "transcriptor.id", "=", "estudios_productos.transcriptor_id")
            ->whereBetween("estudios.created_at", [
                $this->fechaInicio." 00:00:00",
                $this->fechaFin." 23:59:59",
            ])
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->when($this->modalidad, function ($query) {
                return $query->where("estudios.mods_in_study", "LIKE", "%".$this->modalidad."%");
            })
            ->select([
                "estudios.id",
                "config_sedes.nom_sede",
                DB::raw("COALESCE(config_prioridades.nom_prioridad, 'Normal') as prioridad"),
                "estudios.mods_in_study as modalidad",
                "estudios.accession_no",
                "pacientes.num_docu",
                "pacientes.nombres as paciente",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "estudios.created_at as fecha_asignacion",
                "medico.name as medico_lector",
                "estudios_productos.fechor_lectura",
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura) / 60, 2)
                    ELSE NULL 
                END as horas_ingreso_a_lectura"),
                "transcriptor.name as usuario_transcriptor",
                "estudios_productos.fechor_trascrito",
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_lectura IS NOT NULL AND estudios_productos.fechor_trascrito IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios_productos.fechor_lectura, estudios_productos.fechor_trascrito) / 60, 2)
                    ELSE NULL 
                END as horas_lectura_a_transcripcion"),
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_trascrito IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_trascrito) / 60, 2)
                    WHEN estudios_productos.fechor_lectura IS NOT NULL 
                    THEN ROUND(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura) / 60, 2)
                    ELSE NULL 
                END as tat_total_horas"),
                DB::raw("CASE 
                    WHEN estudios_productos.fechor_trascrito IS NULL AND estudios_productos.fechor_lectura IS NULL THEN 'Pendiente Lectura'
                    WHEN estudios_productos.fechor_trascrito IS NULL THEN 'Pendiente Transcripción'
                    WHEN (TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_trascrito) / 60) <= 24 THEN 'Conforme (<= 24h)'
                    WHEN (TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_trascrito) / 60) <= 48 THEN 'Alerta (24h - 48h)'
                    ELSE 'Extemporáneo (> 48h)'
                END as semaforo_oportunidad"),
            ])
            ->orderBy("estudios.created_at", "desc")
            ->get();
    }

    /**
     * Define los encabezados de columnas de la tabla del reporte.
     *
     * @return array<string>
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
            "Prioridad",
            "Modalidad",
            "Accession Number",
            "Doc. Paciente",
            "Nombre Paciente",
            "Código CUPS",
            "Procedimiento",
            "Fecha/Hora Asignación",
            "Médico Lector",
            "Fecha/Hora Dictado",
            "Horas Ingreso a Dictado",
            "Usuario Transcriptor",
            "Fecha/Hora Transcrito",
            "Horas Dictado a Transcripción",
            "TAT Total (Horas)",
            "Estado / Oportunidad",
        ];
    }

    /**
     * Mapea cada fila de la consulta a las columnas del Excel.
     *
     * @param  mixed  $row
     * @return array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->nom_sede,
            $row->prioridad,
            $row->modalidad ?? "N/A",
            $row->accession_no ?? "S/N",
            $row->num_docu,
            $row->paciente,
            $row->cod_cups ?? "N/A",
            $row->nom_produc ?? "Estudio radiológico",
            $row->fecha_asignacion,
            $row->medico_lector ?? "Sin asignar",
            $row->fechor_lectura ?? "Pendiente",
            $row->horas_ingreso_a_lectura ?? "N/A",
            $row->usuario_transcriptor ?? "Pendiente",
            $row->fechor_trascrito ?? "Pendiente",
            $row->horas_lectura_a_transcripcion ?? "N/A",
            $row->tat_total_horas ?? "N/A",
            $row->semaforo_oportunidad,
        ];
    }

    /**
     * Ancho de columnas optimizado.
     *
     * @return array<string, int>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function columnWidths(): array
    {
        return [
            "A" => 12,
            "B" => 25,
            "C" => 14,
            "D" => 12,
            "E" => 20,
            "F" => 16,
            "G" => 32,
            "H" => 14,
            "I" => 36,
            "J" => 20,
            "K" => 28,
            "L" => 20,
            "M" => 22,
            "N" => 24,
            "O" => 20,
            "P" => 24,
            "Q" => 18,
            "R" => 24,
        ];
    }

    /**
     * Celda donde inicia la tabla de datos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function startCell(): string
    {
        return "A6";
    }

    /**
     * Aplica la identidad visual institucional de Salud Tolima.
     *
     * @return array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function styles(Worksheet $sheet): array
    {
        $sheet->setShowGridLines(true);

        // Bloque de membrete institucional
        $sheet->mergeCells("A2:R2");
        $sheet->setCellValue("A2", "SALUD TOLIMA S.A.S. — GESTIÓN DE CALIDAD Y OPORTUNIDAD (TAT)");
        $sheet->getStyle("A2")->getFont()->setBold(true)->setSize(14)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A2:R2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF1E3A8A");

        $sheet->mergeCells("A3:R3");
        $modalidadTexto = $this->modalidad ? " | Modalidad: ".$this->modalidad : "";
        $sheet->setCellValue("A3", "Reporte de Tiempos de Entrega (Turnaround Time) | Periodo: ".$this->fechaInicio." al ".$this->fechaFin.$modalidadTexto);
        $sheet->getStyle("A3")->getFont()->setItalic(true)->setSize(11)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A3:R3")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF2563EB");

        $sheet->mergeCells("A4:R4");
        $sheet->setCellValue("A4", "Generado el: ".date("Y-m-d H:i:s")." | Sistema LIDiag — Módulo de Calidad");
        $sheet->getStyle("A4")->getFont()->setSize(9)->getColor()->setARGB("FF475569");
        $sheet->getStyle("A4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Encabezados de la tabla (Fila 6)
        $sheet->getStyle("A6:R6")->getFont()->setBold(true)->getColor()->setARGB("FFFFFFFF");
        $sheet->getStyle("A6:R6")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0F172A");
        $sheet->getStyle("A6:R6")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A6:R6")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(6)->setRowHeight(28);

        // Bordes finos y alineaciones
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 7) {
            $sheet->getStyle("A6:R{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFE2E8F0");
            $sheet->getStyle("A7:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C7:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F7:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H7:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J7:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L7:M{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O7:Q{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("R7:R{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
