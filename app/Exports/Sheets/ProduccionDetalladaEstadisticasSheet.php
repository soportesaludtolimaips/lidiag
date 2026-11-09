<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hoja de Estadísticas Consolidadas, Resumen Ejecutivo y Gráficos Nativos de Producción Radiológica.
 *
 * Flujo del proceso:
 * 1. Recibe la colección de estudios dictaminados y leídos por el equipo médico radiológico.
 * 2. Consolida indicadores clave de desempeño (KPIs): Total Estudios, Pacientes Únicos, Médicos Activos, Promedio Diario y Procedimientos.
 * 3. Normaliza y clasifica la carga por modalidad diagnóstica (Tomografía CT, Resonancia MR, Rayos X DX/CR, Mamografía MG, etc.).
 * 4. Analiza la productividad comparativa por médico radiólogo con su respectiva modalidad (o Top 10 procedimientos si el reporte está filtrado por un solo especialista).
 * 5. Agrupa la distribución de estudios asignados por usuario (admisiones/registro) y transcripciones realizadas.
 * 6. Genera tablas ejecutivas con estilo corporativo (#1A2274), alineaciones clínicas y formatos numéricos (#,##0 y 0.0%).
 * 7. Construye 4 gráficos ejecutivos nativos de PhpSpreadsheet: Torta de Modalidades, Barras de Médicos Radiólogos, Barras de Usuarios Asignadores y Barras de Transcripciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-09
 */
class ProduccionDetalladaEstadisticasSheet implements FromArray, WithCharts, WithColumnWidths, WithStyles, WithTitle
{
    protected int $totalEstudios = 0;

    protected int $totalPacientes = 0;

    protected int $totalProcedimientosDistintos = 0;

    protected float $promedioDiario = 0.0;

    protected bool $esFiltroUnSoloMedico = false;

    /**
     * @var array<int, array{codigo: string, nombre: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenModalidades = [];

    /**
     * @var array<int, array{ranking: int, nombre: string, detalle: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenSecundario = [];

    /**
     * @var array<int, array{ranking: int, nombre: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenAsignadores = [];

    /**
     * @var array<int, array{ranking: int, nombre: string, detalle: string, total: int, pacientes: int, porcentaje: float}>
     */
    protected array $resumenTranscriptores = [];

    /**
     * Constructor con inyección de datos y cálculo analítico de métricas.
     *
     * @param  Collection  $estudios  Colección de registros de estudios de producción.
     * @param  string  $sedeNombre  Nombre descriptivo de la sede hospitalaria o "Todas las Sedes".
     * @param  string  $medicoNombre  Nombre del médico radiólogo o "Todos los Médicos Radiólogos".
     * @param  string  $fechaInicio  Fecha inicial del filtro (YYYY-MM-DD).
     * @param  string  $fechaFin  Fecha final del filtro (YYYY-MM-DD).
     * @param  mixed  $medicoId  Identificador del médico si se aplicó filtro.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function __construct(
        protected Collection $estudios,
        protected string $sedeNombre,
        protected string $medicoNombre,
        protected string $fechaInicio,
        protected string $fechaFin,
        protected mixed $medicoId = null
    ) {
        $this->calcularEstadisticas();
    }

    /**
     * Infiere la modalidad diagnóstica estandarizada a partir de mods_in_study o nombre del producto.
     *
     * @param  string|null  $modsInStudy  Código de modalidad en el estudio DICOM.
     * @param  string|null  $nomProduc  Nombre del examen o procedimiento CUPS.
     *
     * @returns string Código normalizado de la modalidad (CT, MR, DX, CR, MG, US, etc.).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function normalizarModalidad(?string $modsInStudy, ?string $nomProduc): string
    {
        $modRaw = strtoupper(trim((string) $modsInStudy));
        if ($modRaw !== "" && $modRaw !== "N/A") {
            // Maneja casos con submodalidades como CT\SR o PR\MR
            $partes = explode("\\", $modRaw);
            $modClean = trim($partes[0]);
            if (in_array($modClean, ["CT", "MR", "DX", "CR", "MG", "US", "XA", "NM", "DR"])) {
                return $modClean;
            }
        }

        $prodUpper = mb_strtoupper((string) $nomProduc, "UTF-8");
        if (str_contains($prodUpper, "TOMOGRAFIA") || str_contains($prodUpper, "TAC")) {
            return "CT";
        }
        if (str_contains($prodUpper, "RESONANCIA") || str_contains($prodUpper, "RM")) {
            return "MR";
        }
        if (str_contains($prodUpper, "MAMOGRAFIA")) {
            return "MG";
        }
        if (str_contains($prodUpper, "ECOGRAFIA") || str_contains($prodUpper, "ULTRASONIDO")) {
            return "US";
        }
        if (str_contains($prodUpper, "RADIOGRAFIA") || str_contains($prodUpper, "RAYOS")) {
            return "DX";
        }

        return $modRaw !== "" ? $modRaw : "OTRA";
    }

    /**
     * Calcula indicadores de desempeño, agregación por modalidad y tabla secundaria.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function calcularEstadisticas(): void
    {
        $this->totalEstudios = $this->estudios->count();

        $this->totalPacientes = $this->estudios
            ->pluck("num_docu")
            ->filter(fn ($v) => ! empty($v) && $v !== "N/A")
            ->unique()
            ->count();

        try {
            $inicio = Carbon::parse($this->fechaInicio);
            $fin = Carbon::parse($this->fechaFin);
            $dias = max(1, $inicio->diffInDays($fin) + 1);
        } catch (\Throwable) {
            $dias = 1;
        }
        $this->promedioDiario = round($this->totalEstudios / $dias, 1);

        $this->totalProcedimientosDistintos = $this->estudios
            ->pluck("nom_produc")
            ->filter()
            ->unique()
            ->count();

        $modMeta = [
            "CT" => "Tomografía Computarizada (CT)",
            "MR" => "Resonancia Magnética (MR)",
            "DX" => "Radiografía Digital (DX)",
            "CR" => "Radiografía Computarizada (CR)",
            "MG" => "Mamografía Digital (MG)",
            "US" => "Ecografía / Ultrasonido (US)",
            "XA" => "Angiografía / Fluoroscopia (XA)",
            "DR" => "Radiología Digital Directa (DR)",
            "NM" => "Medicina Nuclear (NM)",
            "OTRA" => "Otras Técnicas de Imagen",
        ];

        // 1. Agrupación por Modalidad Diagnóstica
        $gruposMod = $this->estudios->groupBy(function ($item) {
            return $this->normalizarModalidad($item->mods_in_study ?? null, $item->nom_produc ?? null);
        });

        $resumenMod = [];
        foreach ($gruposMod as $cod => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("num_docu")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;
            $resumenMod[] = [
                "codigo" => (string) $cod,
                "nombre" => $modMeta[$cod] ?? "Modalidad {$cod}",
                "total" => $cant,
                "pacientes" => $pacs,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenMod, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $this->resumenModalidades = $resumenMod;

        // 2. Agrupación Secundaria: Médicos Radiólogos (o Top Procedimientos si es un solo médico)
        $medicosDistintos = $this->estudios
            ->pluck("medico")
            ->filter(fn ($v) => ! empty($v) && $v !== "N/A")
            ->unique();

        $this->esFiltroUnSoloMedico = (! empty($this->medicoId) || $medicosDistintos->count() <= 1);

        if (! $this->esFiltroUnSoloMedico) {
            // Ranking de Médicos Radiólogos
            $gruposMed = $this->estudios->groupBy(function ($item) {
                $med = trim((string) ($item->medico ?? ""));

                return $med !== "" ? mb_strtoupper($med, "UTF-8") : "SIN ASIGNAR";
            });

            $secundario = [];
            foreach ($gruposMed as $nombreMed => $items) {
                $cant = $items->count();
                $pacs = $items->pluck("num_docu")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
                $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;

                $modalidadesMed = $items->map(fn ($it) => $this->normalizarModalidad($it->mods_in_study ?? null, $it->nom_produc ?? null))
                    ->filter()
                    ->unique()
                    ->values();
                $modalidadTexto = $modalidadesMed->isNotEmpty() ? $modalidadesMed->implode(", ") : "N/A";

                $secundario[] = [
                    "ranking" => 0,
                    "nombre" => (string) $nombreMed,
                    "detalle" => (string) $modalidadTexto,
                    "total" => $cant,
                    "pacientes" => $pacs,
                    "porcentaje" => $pct,
                ];
            }
            usort($secundario, fn ($a, $b) => $b["total"] <=> $a["total"]);
            $secundario = array_slice($secundario, 0, 10);
        } else {
            // Top 10 Procedimientos Más Frecuentes
            $gruposProc = $this->estudios->groupBy(function ($item) {
                $desc = trim((string) ($item->nom_produc ?? ""));

                return $desc !== "" ? mb_strtoupper($desc, "UTF-8") : "SIN DESCRIPCIÓN";
            });

            $secundario = [];
            foreach ($gruposProc as $desc => $items) {
                $cant = $items->count();
                $pacs = $items->pluck("num_docu")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
                $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;
                $mod = $this->normalizarModalidad($items->first()->mods_in_study ?? null, $desc);

                $secundario[] = [
                    "ranking" => 0,
                    "nombre" => (string) $desc,
                    "detalle" => $mod,
                    "total" => $cant,
                    "pacientes" => $pacs,
                    "porcentaje" => $pct,
                ];
            }
            usort($secundario, fn ($a, $b) => $b["total"] <=> $a["total"]);
            $secundario = array_slice($secundario, 0, 10);
        }

        foreach ($secundario as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->resumenSecundario = $secundario;

        // 3. Agrupación por Usuario que Asignó / Registró Estudios
        $gruposAsig = $this->estudios->groupBy(function ($item) {
            $nom = trim((string) ($item->quien_registro ?? ""));

            return $nom !== "" ? mb_strtoupper($nom, "UTF-8") : "SIN ESPECIFICAR";
        });

        $resumenAsig = [];
        foreach ($gruposAsig as $nombreAsig => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("num_docu")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;

            $resumenAsig[] = [
                "ranking" => 0,
                "nombre" => (string) $nombreAsig,
                "total" => $cant,
                "pacientes" => $pacs,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenAsig, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $resumenAsig = array_slice($resumenAsig, 0, 10);
        foreach ($resumenAsig as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->resumenAsignadores = $resumenAsig;

        // 4. Agrupación por Usuario que Transcribió Estudios
        $gruposTrans = $this->estudios->groupBy(function ($item) {
            $nom = trim((string) ($item->transcriptor ?? ""));

            return $nom !== "" ? mb_strtoupper($nom, "UTF-8") : "SIN TRANSCRIPTOR / DIRECTO";
        });

        $resumenTrans = [];
        foreach ($gruposTrans as $nombreTrans => $items) {
            $cant = $items->count();
            $pacs = $items->pluck("num_docu")->filter(fn ($v) => ! empty($v) && $v !== "N/A")->unique()->count();
            $pct = $this->totalEstudios > 0 ? round($cant / $this->totalEstudios, 4) : 0.0;

            $modalidadesTrans = $items->map(fn ($it) => $this->normalizarModalidad($it->mods_in_study ?? null, $it->nom_produc ?? null))
                ->filter()
                ->unique()
                ->values();
            $modalidadTexto = $modalidadesTrans->isNotEmpty() ? $modalidadesTrans->implode(", ") : "N/A";

            $resumenTrans[] = [
                "ranking" => 0,
                "nombre" => (string) $nombreTrans,
                "detalle" => $modalidadTexto,
                "total" => $cant,
                "pacientes" => $pacs,
                "porcentaje" => $pct,
            ];
        }
        usort($resumenTrans, fn ($a, $b) => $b["total"] <=> $a["total"]);
        $resumenTrans = array_slice($resumenTrans, 0, 10);
        foreach ($resumenTrans as $i => &$item) {
            $item["ranking"] = $i + 1;
        }
        unset($item);
        $this->resumenTranscriptores = $resumenTrans;
    }

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
        return "Estadísticas y Gráficas";
    }

    /**
     * Retorna array base vacío (el renderizado se gestiona mediante styles).
     *
     * @returns array<mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function array(): array
    {
        return [];
    }

    /**
     * Define anchos de columna para lectura analítica y tablas en paralelo.
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
            "A" => 12, // Código Modalidad / # Ranking
            "B" => 34, // Modalidad Diagnóstica / Usuario Asignador
            "C" => 14, // Estudios
            "D" => 14, // Pacientes
            "E" => 16, // % Cuota
            "F" => 4,  // Separador
            "G" => 6,  // Rank #
            "H" => 44, // Médico Radiólogo o Procedimiento / Usuario Transcriptor
            "I" => 18, // Modalidad
            "J" => 14, // Estudios
            "K" => 14, // Pacientes
            "L" => 16, // % Cuota
        ];
    }

    /**
     * Aplica el diseño corporativo, bloques de KPIs y tablas consolidadas.
     *
     * @param  Worksheet  $sheet  Hoja de cálculo activa.
     *
     * @returns array<int, array<string, mixed>>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function styles(Worksheet $sheet): array
    {
        // 1. Título Corporativo Principal
        $sheet->setCellValue("A1", "SALUD TOLIMA - RESUMEN EJECUTIVO Y ESTADÍSTICAS DE PRODUCCIÓN RADIOLÓGICA");
        $sheet->mergeCells("A1:L1");
        $sheet->getStyle("A1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 14,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A2274"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(32);

        // 2. Subtítulo y Metadatos de Filtro
        $fechaEmision = Carbon::now()->format("Y-m-d H:i");
        $metaTexto = "Sede: {$this->sedeNombre}  |  Médico: {$this->medicoNombre}  |  Período: {$this->fechaInicio} al {$this->fechaFin}  |  Emisión: {$fechaEmision}";
        $sheet->setCellValue("A2", $metaTexto);
        $sheet->mergeCells("A2:L2");
        $sheet->getStyle("A2")->applyFromArray([
            "font" => [
                "bold" => false,
                "color" => ["rgb" => "FFFFFF"],
                "size" => 10,
                "name" => "Segoe UI",
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "283593"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // 3. Tarjetas de KPIs Ejecutivos (Filas 4 y 5)
        $sheet->setCellValue("A4", "TOTAL ESTUDIOS LECTURADOS");
        $sheet->setCellValue("A5", number_format($this->totalEstudios));
        $sheet->mergeCells("A4:B4");
        $sheet->mergeCells("A5:B5");

        $sheet->setCellValue("C4", "PACIENTES ÚNICOS");
        $sheet->setCellValue("C5", number_format($this->totalPacientes));
        $sheet->mergeCells("C4:D4");
        $sheet->mergeCells("C5:D5");

        $sheet->setCellValue("E4", "MODALIDADES ACTIVAS");
        $sheet->setCellValue("E5", (string) count($this->resumenModalidades));
        $sheet->mergeCells("E4:F4");
        $sheet->mergeCells("E5:F5");

        $sheet->setCellValue("G4", "PROMEDIO DIARIO");
        $sheet->setCellValue("G5", number_format($this->promedioDiario, 1)." est/día");
        $sheet->mergeCells("G4:H4");
        $sheet->mergeCells("G5:H5");

        $sheet->setCellValue("I4", "PROCEDIMIENTOS DISTINTOS");
        $sheet->setCellValue("I5", number_format($this->totalProcedimientosDistintos));
        $sheet->mergeCells("I4:L4");
        $sheet->mergeCells("I5:L5");

        $sheet->getStyle("A4:L4")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "555555"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F1F3F5"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]]],
        ]);
        $sheet->getStyle("A5:L5")->applyFromArray([
            "font" => ["bold" => true, "size" => 13, "color" => ["rgb" => "1A2274"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "FFFFFF"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "DFE3E6"]],
                "bottom" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(24);

        // 4. Encabezados de Sección (Fila 7)
        $sheet->setCellValue("A7", "DISTRIBUCIÓN DE PRODUCCIÓN POR MODALIDAD DIAGNÓSTICA");
        $sheet->mergeCells("A7:E7");
        $sheet->getStyle("A7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $tituloSecundario = $this->esFiltroUnSoloMedico
            ? "TOP 10 PROCEDIMIENTOS MÁS FRECUENTES"
            : "RENDIMIENTO DE PRODUCCIÓN POR MÉDICO RADIÓLOGO";
        $sheet->setCellValue("G7", $tituloSecundario);
        $sheet->mergeCells("G7:L7");
        $sheet->getStyle("G7")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(24);

        // 5. Encabezados de Columnas (Fila 8)
        $sheet->setCellValue("A8", "Código");
        $sheet->setCellValue("B8", "Modalidad Diagnóstica");
        $sheet->setCellValue("C8", "Estudios");
        $sheet->setCellValue("D8", "Pacientes");
        $sheet->setCellValue("E8", "% Cuota");
        $sheet->getStyle("A8:E8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $colNombreSec = $this->esFiltroUnSoloMedico ? "Procedimiento Radiológico" : "Médico Radiólogo";
        $sheet->setCellValue("G8", "#");
        $sheet->setCellValue("H8", $colNombreSec);
        $sheet->setCellValue("I8", "Modalidad");
        $sheet->setCellValue("J8", "Estudios");
        $sheet->setCellValue("K8", "Pacientes");
        $sheet->setCellValue("L8", "% Cuota");
        $sheet->getStyle("G8:L8")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension(8)->setRowHeight(20);

        // 6. Filas de Datos
        if ($this->totalEstudios === 0) {
            $sheet->setCellValue("A9", "No se encontraron estudios de producción en el período y filtros seleccionados.");
            $sheet->mergeCells("A9:E9");
            $sheet->getStyle("A9:E9")->applyFromArray([
                "font" => ["italic" => true, "color" => ["rgb" => "6C757D"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension(9)->setRowHeight(22);

            $sheet->setCellValue("G9", "Sin registros de productividad para graficar.");
            $sheet->mergeCells("G9:L9");
            $sheet->getStyle("G9:L9")->applyFromArray([
                "font" => ["italic" => true, "color" => ["rgb" => "6C757D"]],
                "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);

            return [];
        }

        // 6.1 Tabla 1: Modalidades Diagnósticas (Cols A a E)
        $rMod = 9;
        foreach ($this->resumenModalidades as $m) {
            $sheet->setCellValue("A{$rMod}", $m["codigo"]);
            $sheet->setCellValue("B{$rMod}", $m["nombre"]);
            $sheet->setCellValue("C{$rMod}", (int) $m["total"]);
            $sheet->setCellValue("D{$rMod}", (int) $m["pacientes"]);
            $sheet->setCellValue("E{$rMod}", (float) $m["porcentaje"]);

            $sheet->getStyle("A{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rMod}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rMod}:E{$rMod}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rMod)->setRowHeight(20);
            $rMod++;
        }

        // Fila Total Consolidado Modalidades
        $sheet->setCellValue("A{$rMod}", "TOTAL");
        $sheet->setCellValue("B{$rMod}", "Consolidado de Producción");
        $sheet->setCellValue("C{$rMod}", $this->totalEstudios);
        $sheet->setCellValue("D{$rMod}", $this->totalPacientes);
        $sheet->setCellValue("E{$rMod}", 1.0);
        $sheet->getStyle("A{$rMod}:E{$rMod}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("A{$rMod}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rMod}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("E{$rMod}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rMod)->setRowHeight(22);

        // 6.2 Tabla 2: Rendimiento Médicos / Top Procedimientos (Cols G a L)
        $rSec = 9;
        $totalSecEstudios = 0;
        $totalSecPct = 0.0;
        foreach ($this->resumenSecundario as $item) {
            $sheet->setCellValue("G{$rSec}", $item["ranking"]);
            $sheet->setCellValue("H{$rSec}", $item["nombre"]);
            $sheet->setCellValue("I{$rSec}", $item["detalle"]);
            $sheet->setCellValue("J{$rSec}", (int) $item["total"]);
            $sheet->setCellValue("K{$rSec}", (int) $item["pacientes"]);
            $sheet->setCellValue("L{$rSec}", (float) $item["porcentaje"]);

            $totalSecEstudios += (int) $item["total"];
            $totalSecPct += (float) $item["porcentaje"];

            $sheet->getStyle("G{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rSec}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rSec}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("L{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rSec}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rSec}:L{$rSec}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rSec)->setRowHeight(20);
            $rSec++;
        }

        // Fila Subtotal de Tabla 2
        $etiquetaSubtotal = $this->esFiltroUnSoloMedico ? "SUBTOTAL TOP 10" : "SUBTOTAL EQUIPO MÉDICO";
        $sheet->setCellValue("G{$rSec}", "TOTAL");
        $sheet->setCellValue("H{$rSec}", $etiquetaSubtotal);
        $sheet->setCellValue("I{$rSec}", "-");
        $sheet->setCellValue("J{$rSec}", $totalSecEstudios);
        $sheet->setCellValue("K{$rSec}", "-");
        $sheet->setCellValue("L{$rSec}", $totalSecPct);
        $sheet->getStyle("G{$rSec}:L{$rSec}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rSec}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rSec}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rSec}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rSec)->setRowHeight(22);

        $rFinSeccion1 = max($rMod, $rSec);

        // 7. Encabezados de Sección 2: Asignadores y Transcriptores
        $rSec2Header = $rFinSeccion1 + 3;
        $sheet->setCellValue("A{$rSec2Header}", "DISTRIBUCIÓN POR USUARIO QUE ASIGNÓ ESTUDIOS");
        $sheet->mergeCells("A{$rSec2Header}:E{$rSec2Header}");
        $sheet->getStyle("A{$rSec2Header}")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue("G{$rSec2Header}", "DISTRIBUCIÓN POR USUARIO QUE TRANSCRIBIÓ ESTUDIOS");
        $sheet->mergeCells("G{$rSec2Header}:L{$rSec2Header}");
        $sheet->getStyle("G{$rSec2Header}")->applyFromArray([
            "font" => ["bold" => true, "size" => 10, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A2274"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($rSec2Header)->setRowHeight(24);

        // 8. Encabezados de Columnas Sección 2
        $rSec2Cols = $rSec2Header + 1;
        $sheet->setCellValue("A{$rSec2Cols}", "#");
        $sheet->setCellValue("B{$rSec2Cols}", "Usuario Asignador");
        $sheet->setCellValue("C{$rSec2Cols}", "Estudios");
        $sheet->setCellValue("D{$rSec2Cols}", "Pacientes");
        $sheet->setCellValue("E{$rSec2Cols}", "% Cuota");
        $sheet->getStyle("A{$rSec2Cols}:E{$rSec2Cols}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);

        $sheet->setCellValue("G{$rSec2Cols}", "#");
        $sheet->setCellValue("H{$rSec2Cols}", "Usuario Transcriptor");
        $sheet->setCellValue("I{$rSec2Cols}", "Modalidad");
        $sheet->setCellValue("J{$rSec2Cols}", "Estudios");
        $sheet->setCellValue("K{$rSec2Cols}", "Pacientes");
        $sheet->setCellValue("L{$rSec2Cols}", "% Cuota");
        $sheet->getStyle("G{$rSec2Cols}:L{$rSec2Cols}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "FFFFFF"], "name" => "Segoe UI"],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "283593"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER, "vertical" => Alignment::VERTICAL_CENTER],
            "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "FFFFFF"]]],
        ]);
        $sheet->getRowDimension($rSec2Cols)->setRowHeight(20);

        // 9. Filas de Datos Sección 2
        // 9.1 Tabla 3: Usuarios Asignadores (Cols A a E)
        $rAsig = $rSec2Cols + 1;
        $totalAsigEstudios = 0;
        $totalAsigPct = 0.0;
        foreach ($this->resumenAsignadores as $asig) {
            $sheet->setCellValue("A{$rAsig}", $asig["ranking"]);
            $sheet->setCellValue("B{$rAsig}", $asig["nombre"]);
            $sheet->setCellValue("C{$rAsig}", (int) $asig["total"]);
            $sheet->setCellValue("D{$rAsig}", (int) $asig["pacientes"]);
            $sheet->setCellValue("E{$rAsig}", (float) $asig["porcentaje"]);

            $totalAsigEstudios += (int) $asig["total"];
            $totalAsigPct += (float) $asig["porcentaje"];

            $sheet->getStyle("A{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("C{$rAsig}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("D{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("D{$rAsig}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("E{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$rAsig}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("A{$rAsig}:E{$rAsig}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rAsig)->setRowHeight(20);
            $rAsig++;
        }

        // Fila Total Tabla 3
        $sheet->setCellValue("A{$rAsig}", "TOTAL");
        $sheet->setCellValue("B{$rAsig}", "Subtotal Usuarios Asignadores");
        $sheet->setCellValue("C{$rAsig}", $totalAsigEstudios);
        $sheet->setCellValue("D{$rAsig}", "-");
        $sheet->setCellValue("E{$rAsig}", $totalAsigPct);
        $sheet->getStyle("A{$rAsig}:E{$rAsig}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("A{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$rAsig}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("D{$rAsig}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$rAsig}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rAsig)->setRowHeight(22);

        // 9.2 Tabla 4: Usuarios Transcriptores (Cols G a L)
        $rTrans = $rSec2Cols + 1;
        $totalTransEstudios = 0;
        $totalTransPct = 0.0;
        foreach ($this->resumenTranscriptores as $trans) {
            $sheet->setCellValue("G{$rTrans}", $trans["ranking"]);
            $sheet->setCellValue("H{$rTrans}", $trans["nombre"]);
            $sheet->setCellValue("I{$rTrans}", $trans["detalle"]);
            $sheet->setCellValue("J{$rTrans}", (int) $trans["total"]);
            $sheet->setCellValue("K{$rTrans}", (int) $trans["pacientes"]);
            $sheet->setCellValue("L{$rTrans}", (float) $trans["porcentaje"]);

            $totalTransEstudios += (int) $trans["total"];
            $totalTransPct += (float) $trans["porcentaje"];

            $sheet->getStyle("G{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("J{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rTrans}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("K{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("K{$rTrans}")->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getStyle("L{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rTrans}")->getNumberFormat()->setFormatCode("0.0%");

            $sheet->getStyle("G{$rTrans}:L{$rTrans}")->applyFromArray([
                "borders" => ["allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]]],
            ]);
            $sheet->getRowDimension($rTrans)->setRowHeight(20);
            $rTrans++;
        }

        // Fila Total Tabla 4
        $sheet->setCellValue("G{$rTrans}", "TOTAL");
        $sheet->setCellValue("H{$rTrans}", "Subtotal Transcripción");
        $sheet->setCellValue("I{$rTrans}", "-");
        $sheet->setCellValue("J{$rTrans}", $totalTransEstudios);
        $sheet->setCellValue("K{$rTrans}", "-");
        $sheet->setCellValue("L{$rTrans}", $totalTransPct);
        $sheet->getStyle("G{$rTrans}:L{$rTrans}")->applyFromArray([
            "font" => ["bold" => true, "size" => 9, "color" => ["rgb" => "1A2274"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "F0F4FF"]],
            "borders" => [
                "allBorders" => ["borderStyle" => Border::BORDER_THIN, "color" => ["rgb" => "C5CAE9"]],
                "top" => ["borderStyle" => Border::BORDER_MEDIUM, "color" => ["rgb" => "1A2274"]],
            ],
        ]);
        $sheet->getStyle("G{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$rTrans}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("J{$rTrans}")->getNumberFormat()->setFormatCode("#,##0");
        $sheet->getStyle("L{$rTrans}")->getNumberFormat()->setFormatCode("0.0%");
        $sheet->getRowDimension($rTrans)->setRowHeight(22);

        return [];
    }

    /**
     * Genera los 4 gráficos ejecutivos nativos de Excel (Modalidades, Médicos, Asignadores y Transcriptores).
     *
     * @returns Chart[]
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-09
     */
    public function charts(): array
    {
        if ($this->totalEstudios === 0 || empty($this->resumenModalidades)) {
            return [];
        }

        $sheetTitle = $this->title();
        $rModDataStart = 9;
        $rModDataEnd = 8 + count($this->resumenModalidades);

        $rSecDataStart = 9;
        $rSecDataEnd = 8 + count($this->resumenSecundario);

        $rFinSeccion1 = max($rModDataEnd + 1, $rSecDataEnd + 1);
        $rSec2Header = $rFinSeccion1 + 3;
        $rSec2Cols = $rSec2Header + 1;
        $rSec2DataStart = $rSec2Cols + 1;

        $rAsigDataEnd = $rSec2DataStart + count($this->resumenAsignadores) - 1;
        $rTransDataEnd = $rSec2DataStart + count($this->resumenTranscriptores) - 1;

        $rFinSeccion2 = max(
            ! empty($this->resumenAsignadores) ? $rAsigDataEnd + 1 : $rSec2Cols,
            ! empty($this->resumenTranscriptores) ? $rTransDataEnd + 1 : $rSec2Cols
        );

        $chartRow1Start = $rFinSeccion2 + 3;
        $chartRow1End = $chartRow1Start + 18;

        $chartRow2Start = $chartRow1End + 3;
        $chartRow2End = $chartRow2Start + 18;

        // 1. Gráfico de Torta (Pie): Distribución por Modalidad Diagnóstica
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$8", null, 1),
        ];
        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rModDataStart}:\$B\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];
        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rModDataStart}:\$C\${$rModDataEnd}", null, count($this->resumenModalidades)),
        ];

        $pieSeries = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($pieValues) - 1),
            $pieLabels,
            $pieCategories,
            $pieValues
        );

        $piePlotArea = new PlotArea(null, [$pieSeries]);
        $pieLegend = new Legend(Legend::POSITION_RIGHT, null, false);
        $pieTitle = new Title("Distribución de Producción por Modalidad");

        $pieChart = new Chart(
            "chart_prod_modalidades",
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP
        );
        $pieChart->setTopLeftPosition("A".$chartRow1Start);
        $pieChart->setBottomRightPosition("E".$chartRow1End);

        $chartsList = [$pieChart];

        // 2. Gráfico de Barras / Columnas: Productividad por Médico o Top Procedimientos
        if (! empty($this->resumenSecundario)) {
            $barLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\$8", null, 1),
            ];
            $barCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rSecDataStart}:\$H\${$rSecDataEnd}", null, count($this->resumenSecundario)),
            ];
            $barValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rSecDataStart}:\$J\${$rSecDataEnd}", null, count($this->resumenSecundario)),
            ];

            $barSeries = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($barValues) - 1),
                $barLabels,
                $barCategories,
                $barValues
            );
            $barSeries->setPlotDirection(DataSeries::DIRECTION_COL);

            $barPlotArea = new PlotArea(null, [$barSeries]);
            $barLegend = new Legend(Legend::POSITION_TOP, null, false);
            $tituloBar = $this->esFiltroUnSoloMedico
                ? "Top Procedimientos Frecuentes del Período"
                : "Producción por Médico Radiólogo";
            $barTitle = new Title($tituloBar);

            $barChart = new Chart(
                "chart_prod_rendimiento",
                $barTitle,
                $barLegend,
                $barPlotArea,
                true,
                DataSeries::EMPTY_AS_GAP
            );
            $barChart->setTopLeftPosition("G".$chartRow1Start);
            $barChart->setBottomRightPosition("L".$chartRow1End);

            $chartsList[] = $barChart;
        }

        // 3. Gráfico de Barras / Columnas: Usuarios que Asignaron Estudios
        if (! empty($this->resumenAsignadores)) {
            $asigLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\${$rSec2Cols}", null, 1),
            ];
            $asigCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$B\${$rSec2DataStart}:\$B\${$rAsigDataEnd}", null, count($this->resumenAsignadores)),
            ];
            $asigValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$C\${$rSec2DataStart}:\$C\${$rAsigDataEnd}", null, count($this->resumenAsignadores)),
            ];

            $asigSeries = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($asigValues) - 1),
                $asigLabels,
                $asigCategories,
                $asigValues
            );
            $asigSeries->setPlotDirection(DataSeries::DIRECTION_COL);

            $asigPlotArea = new PlotArea(null, [$asigSeries]);
            $asigLegend = new Legend(Legend::POSITION_TOP, null, false);
            $asigTitle = new Title("Estudios Asignados por Usuario (Top 10)");

            $asigChart = new Chart(
                "chart_prod_asignadores",
                $asigTitle,
                $asigLegend,
                $asigPlotArea,
                true,
                DataSeries::EMPTY_AS_GAP
            );
            $asigChart->setTopLeftPosition("A".$chartRow2Start);
            $asigChart->setBottomRightPosition("E".$chartRow2End);

            $chartsList[] = $asigChart;
        }

        // 4. Gráfico de Barras / Columnas: Usuarios que Transcribieron Estudios
        if (! empty($this->resumenTranscriptores)) {
            $transLabels = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$J\${$rSec2Cols}", null, 1),
            ];
            $transCategories = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$H\${$rSec2DataStart}:\$H\${$rTransDataEnd}", null, count($this->resumenTranscriptores)),
            ];
            $transValues = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$J\${$rSec2DataStart}:\$J\${$rTransDataEnd}", null, count($this->resumenTranscriptores)),
            ];

            $transSeries = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_CLUSTERED,
                range(0, count($transValues) - 1),
                $transLabels,
                $transCategories,
                $transValues
            );
            $transSeries->setPlotDirection(DataSeries::DIRECTION_COL);

            $transPlotArea = new PlotArea(null, [$transSeries]);
            $transLegend = new Legend(Legend::POSITION_TOP, null, false);
            $transTitle = new Title("Estudios Transcritos por Usuario (Top 10)");

            $transChart = new Chart(
                "chart_prod_transcriptores",
                $transTitle,
                $transLegend,
                $transPlotArea,
                true,
                DataSeries::EMPTY_AS_GAP
            );
            $transChart->setTopLeftPosition("G".$chartRow2Start);
            $transChart->setBottomRightPosition("L".$chartRow2End);

            $chartsList[] = $transChart;
        }

        return $chartsList;
    }
}
