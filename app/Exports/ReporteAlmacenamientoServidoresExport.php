<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigSede;
use App\Services\Seguridad\TokenSedeService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
 * Exportador institucional de Excel para el Monitor de Almacenamiento, Discos y Servidores PACS.
 *
 * Flujo del proceso:
 * 1. Mide el almacenamiento del Servidor Central (total, libre, usado, porcentaje de disco).
 * 2. Consulta la telemetría en vivo de las sedes (respaldos de bases de datos, sync de imágenes y dosimetría).
 * 3. Mapea la salud integral de la infraestructura tecnológica de la red hospitalaria.
 * 4. Aplica estilos institucionales de monitoreo de sistemas.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-04
 */
class ReporteAlmacenamientoServidoresExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected ?int $sedeId;

    /**
     * Constructor del reporte de almacenamiento y servidores.
     *
     * @param  int|null  $sedeId  Filtro opcional de sede.
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
     * Consolida los datos de almacenamiento y telemetría de servidores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function collection(): Collection
    {
        $filas = [];

        // 1. Fila del Servidor Central LIDiag Cloud
        $pathApp = base_path();
        $totalBytes = @disk_total_space($pathApp);
        $freeBytes = @disk_free_space($pathApp);

        $totalGb = $totalBytes ? round($totalBytes / (1024 ** 3), 1) : 0;
        $freeGb = $freeBytes ? round($freeBytes / (1024 ** 3), 1) : 0;
        $usedGb = ($totalBytes && $freeBytes) ? round(($totalBytes - $freeBytes) / (1024 ** 3), 1) : 0;
        $freePct = $totalBytes ? round(($freeBytes / $totalBytes) * 100, 1) : 0;

        $estadoCentral = "ÓPTIMO";
        if ($freePct <= 5.0 || $freeGb < 20.0) {
            $estadoCentral = "CRÍTICO (<5% o <20GB)";
        } elseif ($freePct <= 15.0 || $freeGb < 50.0) {
            $estadoCentral = "ADVERTENCIA (<15% o <50GB)";
        }

        $filas[] = (object) [
            "tipo" => "Servidor Central",
            "nombre" => "LIDiag Cloud / Central Web",
            "ip" => "127.0.0.1 (Localhost / vHost)",
            "almacenamiento" => "Capacidad: {$totalGb} GB | Libre: {$freeGb} GB ({$freePct}%) | Usado: {$usedGb} GB",
            "estado_disco" => $estadoCentral,
            "backup_estado" => "N/A (Host)",
            "backup_tiempo" => "Al día",
            "sync_estado" => "Receptor Activo",
            "dosimetria_estado" => "Receptor API",
            "observacion" => "Unidad principal del servidor de aplicaciones",
        ];

        // 2. Filas de Sedes Periféricas y Agentes Edge
        try {
            /** @var TokenSedeService $tokenService */
            $tokenService = app(TokenSedeService::class);
            $res = $tokenService->listarSedesConTokens();
            $sedes = $res["data"]["sedes"] ?? [];

            foreach ($sedes as $s) {
                if ($this->sedeId && (int) $s["id"] !== $this->sedeId) {
                    continue;
                }

                $bkEstado = $s["agente_backup_db"]["estado"] ?? "SIN_DATOS";
                $bkTiempo = $s["agente_backup_db"]["tiempo_humano"] ?? "Nunca";
                $bkFecha = $s["agente_backup_db"]["fecha_legible"] ?? "Sin registros";
                $syncEstado = $s["agente_sync_imagenes"]["estado"] ?? "SIN_DATOS";
                $dosiEstado = $s["agente_dosimetria"]["estado"] ?? "SIN_DATOS";

                $filas[] = (object) [
                    "tipo" => "Sede Hospitalaria",
                    "nombre" => $s["nom_sede"],
                    "ip" => $s["ip_dcm"] ?: "Sin IP DCM",
                    "almacenamiento" => "Telemetría Edge Activa",
                    "estado_disco" => $s["tiene_token"] ? "Token Activo" : "Sin Token",
                    "backup_estado" => $bkEstado,
                    "backup_tiempo" => "{$bkTiempo} ({$bkFecha})",
                    "sync_estado" => $syncEstado,
                    "dosimetria_estado" => $dosiEstado,
                    "observacion" => $s["tiene_token"] ? "Sede sincronizada" : "Pendiente generación de token",
                ];
            }
        } catch (\Throwable $e) {
            // Fallback con ConfigSede si el servicio no responde
            $sedes = ConfigSede::where("estado", true)->get();
            foreach ($sedes as $s) {
                if ($this->sedeId && (int) $s->id !== $this->sedeId) {
                    continue;
                }
                $filas[] = (object) [
                    "tipo" => "Sede Hospitalaria",
                    "nombre" => $s->nom_sede,
                    "ip" => $s->ip_dcm ?: "Sin IP DCM",
                    "almacenamiento" => "Agente Periférico",
                    "estado_disco" => "Activo",
                    "backup_estado" => "Verificando",
                    "backup_tiempo" => "N/D",
                    "sync_estado" => "N/D",
                    "dosimetria_estado" => "N/D",
                    "observacion" => "Telemetría estándar",
                ];
            }
        }

        return collect($filas);
    }

    /**
     * Mapea cada fila a las columnas de Excel.
     *
     * @param  mixed  $row
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function map($row): array
    {
        return [
            $row->tipo,
            $row->nombre,
            $row->ip,
            $row->almacenamiento,
            $row->estado_disco,
            $row->backup_estado,
            $row->backup_tiempo,
            $row->sync_estado,
            $row->dosimetria_estado,
            $row->observacion,
        ];
    }

    /**
     * Encabezados de columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function headings(): array
    {
        return [
            "Tipo Nodo",
            "Servidor / Sede",
            "Dirección IP / Host",
            "Almacenamiento / Capacidad",
            "Estado Disco / Token",
            "Agente Backup DB",
            "Último Respaldo Recibido",
            "Agente Sync PACS",
            "Agente Dosimetría",
            "Diagnóstico / Observación",
        ];
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
            "A" => 20, // Tipo
            "B" => 30, // Servidor / Sede
            "C" => 24, // IP
            "D" => 40, // Almacenamiento
            "E" => 24, // Estado Disco
            "F" => 20, // Backup DB
            "G" => 30, // Último Respaldo
            "H" => 20, // Sync PACS
            "I" => 20, // Dosimetría
            "J" => 35, // Observación
        ];
    }

    /**
     * Estilos del reporte.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // Banner institucional
        $sheet->mergeCells("A1:J1");
        $sheet->setCellValue("A1", "SALUD TOLIMA / LIDIAG — MONITOR DE ALMACENAMIENTO, DISCOS Y SERVIDORES PACS");
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF263238"); // Blue Grey
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // Subtítulo
        $sheet->mergeCells("A2:J2");
        $sheet->setCellValue("A2", "Corte en tiempo real: ".Carbon::now()->format("d/m/Y H:i:s")." | Umbrales: Advertencia <15% (50GB), Crítico <5% (20GB)");
        $sheet->getStyle("A2")->getFont()->setItalic(true)->setSize(10)->setColor(new Color("FF333333"));
        $sheet->getStyle("A2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Encabezados tabla (Fila 4)
        $sheet->getStyle("A4:J4")->getFont()->setBold(true)->setColor(new Color("FFFFFFFF"));
        $sheet->getStyle("A4:J4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB("FF455A64");
        $sheet->getStyle("A4:J4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Bordes
        $highestRow = $sheet->getHighestRow();
        if ($highestRow >= 5) {
            $sheet->getStyle("A4:J{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB("FFCCCCCC");
            $sheet->getStyle("A5:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C5:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E5:I{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return [];
    }
}
