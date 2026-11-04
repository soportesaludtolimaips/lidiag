<?php

namespace App\Exports;

use Carbon\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exportador institucional de Excel para el reporte Consolidado de Toda la Red Hospitalaria.
 *
 * Flujo del proceso:
 * 1. Consulta los estudios leídos en todas las sedes activas en el rango de fechas.
 * 2. Cruza información de pacientes, sedes, modalidades y médicos radiólogos.
 * 3. Calcula tiempos clínicos de respuesta (TAT) y clasificación semáforo.
 * 4. Aplica estilos institucionales de Salud Tolima / LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteConsolidadoRedExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected string $fechaInicio;

    protected string $fechaFin;

    protected string $etiquetaPeriodo;

    /**
     * Constructor del reporte consolidado de la red hospitalaria.
     *
     * @param  string  $fechaInicio  Fecha inicial (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final (YYYY-MM-DD).
     * @param  string  $etiquetaPeriodo  Etiqueta legible del período.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(string $fechaInicio, string $fechaFin, string $etiquetaPeriodo = "")
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->etiquetaPeriodo = $etiquetaPeriodo ?: "Del {$fechaInicio} al {$fechaFin}";
    }

    /**
     * Consulta los registros leídos en toda la red para el consolidado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        return DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("users as medico", "estudios.medico_id", "=", "medico.id")
            ->leftJoin("config_tipo_documentos", "pacientes.tipo_documento_id", "=", "config_tipo_documentos.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->whereBetween("estudios_productos.fechor_lectura", [
                "{$this->fechaInicio} 00:00:00",
                "{$this->fechaFin} 23:59:59",
            ])
            ->select([
                "estudios.id as estudio_id",
                "config_sedes.nom_sede",
                "estudios.mods_in_study",
                "estudios_productos.nombre_procedimiento",
                "config_tipo_documentos.sigla as tipo_documento",
                "pacientes.documento",
                DB::raw("CONCAT(pacientes.primer_nombre, ' ', COALESCE(pacientes.segundo_nombre, ''), ' ', pacientes.primer_apellido, ' ', COALESCE(pacientes.segundo_apellido, '')) as paciente_nombre"),
                "medico.name as medico_nombre",
                DB::raw("COALESCE(estudios.fec_estudio, estudios.created_at) as fecha_estudio"),
                "estudios_productos.fechor_lectura as fecha_lectura",
                DB::raw("TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura) as minutos_tat"),
            ])
            ->orderBy("config_sedes.nom_sede")
            ->orderByDesc("estudios_productos.fechor_lectura")
            ->get();
    }

    /**
     * Mapea cada registro a las columnas del archivo Excel.
     *
     * @param  mixed  $row
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        $minutos = (int) ($row->minutos_tat ?? 0);
        $horasTat = $minutos > 0 ? round($minutos / 60, 1) : 0;

        $estadoTat = "N/D";
        if ($minutos > 0) {
            if ($minutos <= 720) {
                $estadoTat = "Óptimo (<12h)";
            } elseif ($minutos <= 1440) {
                $estadoTat = "Aceptable (<24h)";
            } else {
                $estadoTat = "Retrasado (>24h)";
            }
        }

        return [
            $row->estudio_id,
            $row->nom_sede,
            $row->mods_in_study ?: "N/D",
            $row->nombre_procedimiento ?: "Sin descripción",
            $row->tipo_documento ?: "CC",
            $row->documento,
            trim(preg_replace("/\s+/", " ", (string) $row->paciente_nombre)),
            $row->medico_nombre ?: "Sin asignar",
            $row->fecha_estudio ? Carbon::parse($row->fecha_estudio)->format("d/m/Y H:i") : "N/D",
            $row->fecha_lectura ? Carbon::parse($row->fecha_lectura)->format("d/m/Y H:i") : "N/D",
            $horasTat,
            $estadoTat,
        ];
    }

    /**
     * Encabezados de las columnas.
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
            "Procedimiento Realizado",
            "Tipo Doc",
            "Documento Paciente",
            "Nombre Completo Paciente",
            "Médico Informante",
            "Fecha Toma Estudio",
            "Fecha Lectura / Informe",
            "TAT Clínico (Horas)",
            "Cumplimiento Oportunidad",
        ];
    }

    /**
     * Celda de inicio para dejar espacio al banner institucional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function startCell(): string
    {
        return "A4";
    }

    /**
     * Anchos de columna optimizados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function columnWidths(): array
    {
        return [
            "A" => 12, // ID
            "B" => 28, // Sede
            "C" => 14, // Modalidad
            "D" => 40, // Procedimiento
            "E" => 10, // Tipo Doc
            "F" => 18, // Documento
            "G" => 35, // Paciente
            "H" => 28, // Médico
            "I" => 20, // Fecha Toma
            "J" => 20, // Fecha Lectura
            "K" => 18, // TAT Horas
            "L" => 24, // Estado TAT
        ];
    }

    /**
     * Aplica estilos institucionales al reporte de red.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Banner institucional
        $sheet->mergeCells("A1:L1");
        $sheet->setCellValue("A1", "SALUD TOLIMA / LIDIAG — CONSOLIDADO DE PRODUCCIÓN DE LA RED HOSPITALARIA");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF004B87");
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo con período y fecha de corte
        $sheet->mergeCells("A2:L2");
        $sheet->setCellValue("A2", "Período: {$this->etiquetaPeriodo} | Generado: ".Carbon::now()->format("d/m/Y H:i:s"));
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->setColor(new Color("FF333333"));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados de tabla (Fila 4)
        $sheet->getStyle("A4:L4")->getFont()->setBold(true)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A4:L4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF0288D1");
        $sheet->getStyle("A4:L4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Bordes de la tabla
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCCCCC");
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E5:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I5:L{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
