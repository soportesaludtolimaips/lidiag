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
 * Hoja de alerta operativa: Estudios tomados en PACS sin asignar en LIDiag.
 *
 * Flujo del proceso:
 * 1. Filtra los estudios que llegaron al PACS pero no tienen asignación en LIDiag.
 * 2. Calcula las horas y días transcurridos desde la toma médica.
 * 3. Aplica formato condicional de advertencia asistencial en tonos ámbar y rojo.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-09
 */
class ProduccionConsolidadaSinAsignarSheet implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $estudiosSinAsignar;

    protected string $fechaInicio;

    protected string $fechaFin;

    protected ?string $sedeNombre;

    protected int $totalFilas = 0;

    /**
     * Constructor de la hoja de estudios sin asignar.
     *
     * @param  Collection  $estudiosSinAsignar  Colección de estudios sin asignar.
     * @param  string  $fechaInicio  Fecha de inicio del rango.
     * @param  string  $fechaFin  Fecha de fin del rango.
     * @param  string|null  $sedeNombre  Nombre de la sede o red hospitalaria.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function __construct(Collection $estudiosSinAsignar, string $fechaInicio, string $fechaFin, ?string $sedeNombre = null)
    {
        $this->estudiosSinAsignar = $estudiosSinAsignar;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeNombre = $sedeNombre ?: "Todas las Sedes de la Red";
        $this->totalFilas = $estudiosSinAsignar->count();
    }

    /**
     * Retorna la colección de estudios sin asignar.
     *
     * @returns Collection
     */
    public function collection(): Collection
    {
        return $this->estudiosSinAsignar;
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
        return "Estudios Sin Asignar";
    }

    /**
     * Encabezados de las columnas.
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
            "Fecha y Hora Toma (PACS)",
            "Horas en Espera",
            "Días en Espera",
            "Accession Number",
            "Study Instance UID",
        ];
    }

    /**
     * Mapea cada fila de estudio sin asignar.
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
        $horasEspera = $fechaToma ? round(max(0, now()->getTimestamp() - $fechaToma->getTimestamp()) / 3600, 1) : 0;
        $diasEspera = $fechaToma ? round(max(0, now()->getTimestamp() - $fechaToma->getTimestamp()) / 86400, 1) : 0;

        $pacienteNombre = trim(str_replace("^", " ", (string) ($row->pat_name ?? "")));

        return [
            $index,
            $row->nom_sede ?? "Sede Desconocida",
            $row->pat_id ?? "S/N",
            $pacienteNombre ?: "PACIENTE NO REGISTRADO",
            $row->mods_in_study ?? "ND",
            $row->study_desc ?? "SIN DESCRIPCIÓN",
            $fechaToma ? $fechaToma->format("Y-m-d H:i:s") : "S/F",
            $horasEspera,
            $diasEspera,
            $row->accession_no ?? "S/A",
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
                    "startColor" => ["argb" => "FFC62828"], // Rojo oscuro de alerta
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
     * Registra eventos para cabecera corporativa y formato.
     *
     * @returns array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(5, $this->totalFilas + 4);

                // Fila 1: Título de advertencia
                $sheet->mergeCells("A1:K1");
                $sheet->setCellValue("A1", "ALERTA OPERATIVA: ESTUDIOS TOMADOS EN PACS PENDIENTES DE ASIGNAR A LECTURA");
                $sheet->getStyle("A1")->applyFromArray([
                    "font" => ["bold" => true, "size" => 13, "color" => ["argb" => "FFFFFFFF"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["argb" => "FFB71C1C"]],
                    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Fila 2: Subtítulo con metadata
                $sheet->mergeCells("A2:K2");
                $totalMsg = $this->totalFilas." estudios rezagados sin asignación";
                $sheet->setCellValue(
                    "A2",
                    "Rango de Fechas: {$this->fechaInicio} a {$this->fechaFin}  |  Sede: {$this->sedeNombre}  |  Estado: {$totalMsg}  |  Generado: ".now()->format("Y-m-d H:i:s")
                );
                $sheet->getStyle("A2")->applyFromArray([
                    "font" => ["bold" => true, "size" => 10, "color" => ["argb" => "FFFFFFFF"]],
                    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["argb" => "FFD32F2F"]],
                    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(28);

                // Bordes y alineación de datos
                if ($this->totalFilas > 0) {
                    $sheet->getStyle("A4:K{$lastRow}")->applyFromArray([
                        "borders" => [
                            "allBorders" => [
                                "borderStyle" => Border::BORDER_THIN,
                                "color" => ["argb" => "FFFFCDD2"],
                            ],
                        ],
                    ]);

                    // Centrar columnas clave
                    $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C5:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E5:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G5:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J5:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Formato condicional de cebra suave en tonos ámbar
                    for ($r = 5; $r <= $lastRow; $r++) {
                        $bg = ($r % 2 === 0) ? "FFFFF8E1" : "FFFFFFFF";
                        $sheet->getStyle("A{$r}:K{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bg);
                        $sheet->getRowDimension($r)->setRowHeight(20);
                    }
                } else {
                    // Mensaje si no hay estudios sin asignar
                    $sheet->mergeCells("A5:K5");
                    $sheet->setCellValue("A5", "¡EXCELENTE! NO HAY ESTUDIOS REZAGADOS SIN ASIGNAR EN EL PERÍODO SELECCIONADO.");
                    $sheet->getStyle("A5")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["argb" => "FF2E7D32"], "size" => 11],
                        "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                        "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["argb" => "FFE8F5E9"]],
                    ]);
                    $sheet->getRowDimension(5)->setRowHeight(26);
                }
            },
        ];
    }
}
