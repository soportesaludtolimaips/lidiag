<?php

namespace App\Exports;

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

class ProduccionDetalladaEcografiasExport implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected $fechaInicio;

    protected $fechaFin;

    protected $sedeId;

    protected $quienAsignoId;

    protected $medicoId;

    public function __construct($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->quienAsignoId = $quienAsignoId;
        $this->medicoId = $medicoId;
    }

    public function collection()
    {
        $quien_asigno_id = $this->quienAsignoId;
        $medico_id = $this->medicoId;

        $ecografias = DB::table('view_reporte_ecografias')
            ->when($this->fechaInicio, fn ($q) => $q->whereBetween(DB::raw('DATE(fechor_lectura)'), [$this->fechaInicio, $this->fechaFin]))
            // ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
            ->when($this->quienAsignoId, fn ($q) => $q->where('usua_regis_id', $this->quienAsignoId))
            ->when($this->medicoId, fn ($q) => $q->where('medico_id', $this->medicoId))
            ->select('id', 'fec_cita_sahi', 'fec_regis_cita_sahi', 'fechor_lectura', 'atencion_id', 'quien_registro', 'medico', 'documento_paciente',
                'nombre_paciente', 'cod_cups', 'nom_produc', 'eps', 'contrato', 'plan', 'quien_ordena', 'lectura', 'opinion')
            ->get();

        return $ecografias;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha Cita',
            'Fecha Registro',
            'Fecha Lectura',
            'Atención',
            'Quien Registró',
            'Médico',
            'Documento Paciente',
            'Nombre Paciente',
            'Código CUPS',
            'Nombre Producto',
            'EPS',
            'Contrato',
            'Plan',
            'Quien Ordena',
            'Lectura',
            'Opinión',
        ];
    }

    public function map($ecografia): array
    {
        return [
            $ecografia->id ?? 'N/A',
            $ecografia->fec_cita_sahi ?? 'N/A',
            $ecografia->fec_regis_cita_sahi ?? 'N/A',
            $ecografia->fechor_lectura ?? 'N/A',
            $ecografia->atencion_id ?? 'N/A',
            $ecografia->quien_registro ?? 'N/A',
            $ecografia->medico ?? 'N/A',
            $ecografia->documento_paciente ?? 'N/A',
            $ecografia->nombre_paciente ?? 'N/A',
            $ecografia->cod_cups ?? 'N/A',
            $ecografia->nom_produc ?? 'N/A',
            $ecografia->eps ?? 'N/A',
            $ecografia->contrato ?? 'N/A',
            $ecografia->plan ?? 'N/A',
            $ecografia->quien_ordena ?? 'N/A',
            strip_tags($ecografia->lectura ?? 'N/A'), // Limpiar HTML si es necesario
            strip_tags($ecografia->opinion ?? 'N/A'), // Limpiar HTML si es necesario
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Agrega el encabezado personalizado
        $sheet->setCellValue('A1', 'Salud Tolima - Reporte de Producción Ecografías');
        $sheet->mergeCells('A1:Q1'); // Ajustado al número de columnas (A-Q)
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece el estilo para el encabezado de los datos
        $sheet->getStyle('A3:Q3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece bordes para todas las celdas de datos
        $sheet->getStyle('A4:Q'.($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [
            1 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]], // Estilo para el encabezado de los datos
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 12,
            'F' => 20,
            'G' => 25,
            'H' => 20,
            'I' => 30,
            'J' => 12,
            'K' => 30,
            'L' => 25,
            'M' => 20,
            'N' => 20,
            'O' => 25,
            'P' => 40,
            'Q' => 40,
        ];
    }

    public function startCell(): string
    {
        return 'A3'; // Los datos comenzarán desde la celda A4
    }
}
