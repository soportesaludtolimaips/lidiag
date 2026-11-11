<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Detalle Consolidado: Trazabilidad total de estudios tomados en PACS vs asignados en LIDiag.
 *
 * Flujo del proceso:
 * 1. Presenta cada estudio tomado con su estado de asignación, tiempos transcurridos y lectura médica.
 * 2. Aplica estilos institucionales (#1A2274 / #283593) con semaforización en la columna de trazabilidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-09
 */
class ProduccionConsolidadaDetalleSheet implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $estudios;

    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?string $sedeNombre;

    protected int $totalFilas = 0;

    /**
     * Constructor de la hoja de detalle consolidado.
     *
     * @param  Collection  $estudios  Colección unificada de estudios tomados y asignados.
     * @param  string  $fechaInicio  Fecha de inicio del rango.
     * @param  string  $fechaFin  Fecha de fin del rango.
     * @param  string|null  $sedeNombre  Nombre de la sede o red hospitalaria.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function __construct(Collection $estudios, string $fechaInicio, string $fechaFin, ?string $sedeNombre = null)
    {
        $this->estudios = $estudios;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeNombre = $sedeNombre ?: "Todas las Sedes de la Red";
        $this->totalFilas = $estudios->count();
    }

    /**
     * Retorna la colección de estudios.
     *
     * @returns Collection
     */
    public function collection(): Collection
    {
        return $this->estudios;
    }

    /**
     * Celda de inicio de la tabla.
     *
     * @returns string
     */
    public function startCell(): string
    {
        return "A4";
    }

    /**
     * Título de la pestaña.
     *
     * @returns string
     */
    public function title(): string
    {
        return "Detalle Consolidado";
    }

    /**
     * Encabezados de columnas.
     *
     * @returns array<int, string>
     */
    public function headings(): array
    {
        return [
            "#",
            "Sede Hospitalaria",
            "Doc. Paciente",
            "Nombre Paciente",
            "Modalidad",
            "Descripción del Estudio (PACS)",
            "Fecha/Hora Toma PACS",
            "Destino / Flujo",
            "Estado Trazabilidad",
            "Fecha/Hora Asignación LIDiag",
            "Tiempo Toma a Asig (Horas)",
            "Usuario Asignador",
            "Médico Radiólogo Asignado",
            "Estado Lectura",
            "Fecha/Hora Lectura",
            "Tiempo Asig a Lectura (Horas)",
            "Accession Number",
            "Study Instance UID",
        ];
    }

    /**
     * Mapea cada fila de estudio consolidado.
     *
     * @param  mixed  $row
     *
     * @returns array<int, mixed>
     */
    public function map($row): array
    {
        static $index = 0;
        $index++;

        $fechaToma = ! empty($row->study_datetime) ? Carbon::parse($row->study_datetime) : null;
        $fechaAsig = ! empty($row->fecha_asignacion) ? Carbon::parse($row->fecha_asignacion) : null;
        $fechaLect = ! empty($row->fechor_lectura) ? Carbon::parse($row->fechor_lectura) : null;

        $horasTomaAsig = ($fechaToma && $fechaAsig) ? round(abs($fechaAsig->getTimestamp() - $fechaToma->getTimestamp()) / 3600, 1) : null;
        $horasAsigLect = ($fechaAsig && $fechaLect) ? round(abs($fechaLect->getTimestamp() - $fechaAsig->getTimestamp()) / 3600, 1) : null;

        $esAsignado = ! empty($row->estudio_id);

        if (! $esAsignado) {
            $destinoEstudio = "";
            $estadoTrazabilidad = "TOMADO SIN ASIGNAR";
            $fechaAsigTxt = "";
            $horasTomaAsigTxt = "";
            $asignadorTxt = "";
            $medicoTxt = "";
            $estadoLecturaTxt = "";
            $fechaLectTxt = "";
            $horasAsigLectTxt = "";
        } else {
            $destinoEstudio = $row->destino_estudio ?? "Salud Tolima";
            $fechaAsigTxt = $fechaAsig ? $fechaAsig->format("Y-m-d H:i:s") : "";
            $horasTomaAsigTxt = $horasTomaAsig !== null ? $horasTomaAsig : "";
            $asignadorTxt = $row->asignador_nombre ?? "";
            $medicoTxt = $row->medico_nombre ?? "";

            if ($fechaLect || ! empty($row->con_lectura)) {
                $estadoTrazabilidad = "ASIGNADO Y LEÍDO";
                $estadoLecturaTxt = "LEÍDO";
                $fechaLectTxt = $fechaLect ? $fechaLect->format("Y-m-d H:i:s") : "";
                $horasAsigLectTxt = $horasAsigLect !== null ? $horasAsigLect : "";
            } else {
                $estadoTrazabilidad = "ASIGNADO PENDIENTE";
                $estadoLecturaTxt = "PENDIENTE DE LECTURA";
                $fechaLectTxt = "";
                $horasAsigLectTxt = "";
            }
        }

        $pacienteNombre = trim(str_replace("^", " ", (string) ($row->pat_name ?? "")));

        return [
            $index,
            $row->nom_sede ?? "",
            $row->pat_id ?? "",
            $pacienteNombre ?: "",
            $row->mods_in_study ?? "",
            $row->study_desc ?? "",
            $fechaToma ? $fechaToma->format("Y-m-d H:i:s") : "",
            $destinoEstudio,
            $estadoTrazabilidad,
            $fechaAsigTxt,
            $horasTomaAsigTxt,
            $asignadorTxt,
            $medicoTxt,
            $estadoLecturaTxt,
            $fechaLectTxt,
            $horasAsigLectTxt,
            $row->accession_no ?? "",
            $row->study_iuid ?? "",
        ];
    }

    /**
     * Estilos base de la hoja.
     *
     * @returns array<mixed, mixed>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            4 => [
                "font" => [
                    "bold" => true,
                    "color" => ["argb" => "FFFFFFFF"],
                    "size" => 10,
                ],
                "fill" => [
                    "fillType" => Fill::FILL_SOLID,
                    "startColor" => ["argb" => "FF283593"], // Azul índigo institucional
                ],
                "alignment" => [
                    "horizontal" => Alignment::HORIZONTAL_CENTER,
                    "vertical" => Alignment::VERTICAL_CENTER,
                    "wrapText" => true,
                ],
            ],
        ];
    }

    /**
     * Eventos para cabecera corporativa y semáforo.
     *
     * @returns array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(5, $this->totalFilas + 4);

                // Fila 1: Título institucional
                $sheet->mergeCells("A1:R1");
                $sheet->setCellValue("A1", "TRAZABILIDAD DE PRODUCCIÓN CONSOLIDADA: ESTUDIOS TOMADOS (PACS) VS ASIGNADOS (LIDIAG)");
                $sheet->getStyle("A1")->applyFromArray([
                    "font" => ["bold" => true, "size" => 13, "color" => ["argb" => "FFFFFFFF"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["argb" => "FF1A2274"]],
                    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Fila 2: Subtítulo con metadata
                $sheet->mergeCells("A2:R2");
                $sheet->setCellValue(
                    "A2",
                    "Rango de Fechas de Toma: {$this->fechaInicio} a {$this->fechaFin}  |  Sede: {$this->sedeNombre}  |  Total Estudios: {$this->totalFilas}  |  Generado: ".now()->format("Y-m-d H:i:s")
                );
                $sheet->getStyle("A2")->applyFromArray([
                    "font" => ["bold" => true, "size" => 10, "color" => ["argb" => "FFFFFFFF"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["argb" => "FF283593"]],
                    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(28);

                if ($this->totalFilas > 0) {
                    $sheet->getStyle("A4:R{$lastRow}")->applyFromArray([
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                                "color" => ["argb" => "FFC5CAE9"],
                            ],
                        ],
                    ]);

                    // Centrar columnas clave
                    $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C5:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E5:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G5:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("N5:P{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("Q5:Q{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Color de cebra y semáforo condicional en Destino (Col H) y Estado Trazabilidad (Col I)
                    for ($r = 5; $r <= $lastRow; $r++) {
                        $cellH = (string) $sheet->getCell("H{$r}")->getValue();
                        $cellI = (string) $sheet->getCell("I{$r}")->getValue();

                        if ($cellI === "TOMADO SIN ASIGNAR") {
                            // Fondo ámbar suave con texto rojo
                            $sheet->getStyle("A{$r}:R{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FFFFF8E1");
                            $sheet->getStyle("I{$r}")->getFont()->setBold(true)->getColor()->setARGB("FFC62828");
                            $sheet->getStyle("H{$r}")->getFont()->setBold(true)->getColor()->setARGB("FF757575");
                        } elseif ($cellI === "ASIGNADO Y LEÍDO") {
                            $bg = ($r % 2 === 0) ? "FFF0F4FF" : "FFFFFFFF";
                            $sheet->getStyle("A{$r}:R{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                            $sheet->getStyle("I{$r}")->getFont()->setBold(true)->getColor()->setARGB("FF2E7D32");
                        } else { // ASIGNADO PENDIENTE
                            $bg = ($r % 2 === 0) ? "FFF0F4FF" : "FFFFFFFF";
                            $sheet->getStyle("A{$r}:R{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                            $sheet->getStyle("I{$r}")->getFont()->setBold(true)->getColor()->setARGB("FF1565C0");
                        }

                        // Resaltar Destino
                        if ($cellH === "Tele-Radiología") {
                            $sheet->getStyle("H{$r}")->getFont()->setBold(true)->getColor()->setARGB("FF6A1B9A"); // Púrpura
                        } elseif ($cellH === "Salud Tolima") {
                            $sheet->getStyle("H{$r}")->getFont()->setBold(true)->getColor()->setARGB("FF1565C0"); // Azul
                        }

                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                }
            },
        ];
    }
}
