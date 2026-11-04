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
 * Exportador institucional de Excel para el Semáforo de Represamiento Asistencial y Urgencias fuera de SLA.
 *
 * Flujo del proceso:
 * 1. Consulta estudios pendientes de informe médico con más de 24 horas transcurridas o urgencias pendientes.
 * 2. Cruza con sedes, prioridades, pacientes y productos.
 * 3. Categoriza según severidad clínica: Preventivo (24-48h), Crítico (48-72h), Severo (>72h) y Urgencias (>30m).
 * 4. Aplica estilos institucionales de advertencia clínica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteRepresadosOportunidadExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected ?int $sedeId;

    /**
     * Constructor del exportador de estudios represados y urgencias.
     *
     * @param  int|null  $sedeId  Identificador de la sede opcional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(?int $sedeId = null)
    {
        $this->sedeId = $sedeId;
    }

    /**
     * Consulta estudios represados o urgencias pendientes sin lectura.
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
            ->leftJoin("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->leftJoin("users as medico", "estudios.medico_id", "=", "medico.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where(function ($q) {
                // Estudios con más de 24h sin lectura O urgencias (prioridad_id = 1)
                $q->whereRaw("TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 24")
                    ->orWhere("estudios.prioridad_id", 1);
            })
            ->when($this->sedeId, function ($query) {
                return $query->where("estudios.sede_id", $this->sedeId);
            })
            ->select([
                "estudios.id as estudio_id",
                "config_sedes.nom_sede",
                "config_prioridades.nom_prioridad",
                "estudios.prioridad_id",
                "estudios.mods_in_study",
                "estudios_productos.nombre_procedimiento",
                "pacientes.documento",
                DB::raw("CONCAT(pacientes.primer_nombre, ' ', COALESCE(pacientes.segundo_nombre, ''), ' ', pacientes.primer_apellido, ' ', COALESCE(pacientes.segundo_apellido, '')) as paciente_nombre"),
                "medico.name as medico_nombre",
                DB::raw("COALESCE(estudios.fec_estudio, estudios.created_at) as fecha_estudio"),
                "estudios.created_at as fecha_ingreso",
                DB::raw("TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) as horas_espera"),
                DB::raw("TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) as minutos_espera"),
            ])
            ->orderByRaw("CASE WHEN estudios.prioridad_id = 1 THEN 0 ELSE 1 END")
            ->orderByDesc("horas_espera")
            ->get();
    }

    /**
     * Mapea cada registro a las columnas del reporte.
     *
     * @param  mixed  $row
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        $horas = (int) ($row->horas_espera ?? 0);
        $minutos = (int) ($row->minutos_espera ?? 0);
        $esUrgente = ((int) $row->prioridad_id) === 1;

        if ($esUrgente) {
            $clasificacion = $minutos > 30 ? "URGENCIA FUERA DE SLA (>30m)" : "Urgencia Pendiente (<30m)";
        } elseif ($horas > 72) {
            $clasificacion = "SEVERO (>72 horas)";
        } elseif ($horas >= 48) {
            $clasificacion = "CRÍTICO (48 a 72h)";
        } else {
            $clasificacion = "PREVENTIVO (24 a 48h)";
        }

        $tiempoEspera = $esUrgente ? "{$minutos} min" : "{$horas} horas";

        return [
            $row->estudio_id,
            $row->nom_sede,
            $clasificacion,
            $tiempoEspera,
            $row->nom_prioridad ?: ($esUrgente ? "Urgencias" : "Normal"),
            $row->mods_in_study ?: "N/D",
            $row->nombre_procedimiento ?: "Sin descripción",
            $row->documento,
            trim(preg_replace("/\s+/", " ", (string) $row->paciente_nombre)),
            $row->fecha_estudio ? Carbon::parse($row->fecha_estudio)->format("d/m/Y H:i") : "N/D",
            $row->fecha_ingreso ? Carbon::parse($row->fecha_ingreso)->format("d/m/Y H:i") : "N/D",
            $row->medico_nombre ?: "Sin radiólogo asignado",
        ];
    }

    /**
     * Encabezados del reporte.
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
            "Clasificación SLA",
            "Tiempo de Espera",
            "Prioridad",
            "Modalidad",
            "Procedimiento",
            "Documento Paciente",
            "Nombre Completo Paciente",
            "Fecha Estudio",
            "Fecha Ingreso Sistema",
            "Radiólogo Asignado",
        ];
    }

    /**
     * Celda de inicio de la tabla.
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
     * Anchos de columna.
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
            "C" => 32, // Clasificación
            "D" => 20, // Tiempo Espera
            "E" => 16, // Prioridad
            "F" => 14, // Modalidad
            "G" => 38, // Procedimiento
            "H" => 18, // Documento
            "I" => 35, // Paciente
            "J" => 20, // Fecha Estudio
            "K" => 20, // Fecha Ingreso
            "L" => 28, // Radiólogo
        ];
    }

    /**
     * Estilos del semáforo asistencial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Banner institucional
        $sheet->mergeCells("A1:L1");
        $sheet->setCellValue("A1", "SALUD TOLIMA / LIDIAG — SEMÁFORO ASISTENCIAL DE REPRESAMIENTOS Y URGENCIAS");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FFD32F2F"); // Rojo institucional
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo
        $sheet->mergeCells("A2:L2");
        $sheet->setCellValue("A2", "Corte en tiempo real: ".Carbon::now()->format("d/m/Y H:i:s")." | Prioridad: Atender urgencias >30m y estudios en rango >72h");
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->setColor(new Color("FF333333"));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados tabla (Fila 4)
        $sheet->getStyle("A4:L4")->getFont()->setBold(true)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A4:L4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF37474F"); // Gris oscuro elegante
        $sheet->getStyle("A4:L4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Bordes
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCCCCC");
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F5:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H5:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J5:K{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
