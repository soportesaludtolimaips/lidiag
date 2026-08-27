<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Facades\Config;
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

class ReportesEstudioPAC implements FromCollection, WithColumnWidths, WithCustomStartCell, WithHeadings, WithMapping, WithStyles
{
    protected $fechaInicio;

    protected $fechaFin;

    protected $sedeId;

    protected $sedeNombre;

    protected $conexionOriginal;

    public function __construct($fechaInicio, $fechaFin, $sedeId, $sedeNombre, $conexionOriginal)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->sedeNombre = $sedeNombre;
        $this->conexionOriginal = $conexionOriginal;
    }

    public function collection()
    {
        $sedeActual = ConfigSede::find($this->sedeId);

        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
            'port' => env('DB_SUCURSAL_PUERTO', $sedeActual->puerto_dcm),
            'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
            'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
            'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
            'charset' => env('DB_SUCURSAL_charset', 'latin1'),
            'collation' => env('DB_SUCURSAL_collation', 'latin1_swedish_ci'),
        ]]);

        $this->sedeNombre = $sedeActual->nom_sede;

        $conexionOriginal = config('database.default');

        try {

            Config::set('database.default', 'mysql_sucursal');

            $estudios = DB::table('study')
                ->select('patient.pat_id, patient.pat_name, study.study_datetime, study.study_desc, study.mods_in_study')
                ->join('patient', 'patient.pk', '=', 'study.patient_fk')
                ->whereBetween(DB::raw('DATE(study.study_datetime'), [$this->fechaInicio, $this->fechaFin])
                ->get();

            return $estudios;
        } finally {
            Config::set('database.default', $conexionOriginal);
        }
    }

    public function headings(): array
    {
        return [
            'Sede',
            'Fecha de Estudio',
            'Documento del Paciente',
            'Paciente',
            'Descripción del Estudio',
            'Modalidad',
        ];
    }

    public function map($estudio): array
    {
        return [
            $estudio->sedeNombre ?? 'N/A',
            $estudio->study_datetime ?? 'N/A',
            $estudio->pat_id ?? 'N/A',
            $estudio->pat_name ?? 'N/A',
            $estudio->study_desc ?? 'N/A',
            $estudio->mods_in_study ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Agrega el encabezado personalizado
        $sheet->setCellValue('A1', 'Salud Tolima - Reporte de Producción');
        $sheet->mergeCells('A1:F1'); // A1 to F1 to include the new column
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
        $sheet->getStyle('A3:F3')->applyFromArray([
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
        $sheet->getStyle('A4:S'.($sheet->getHighestRow()))->applyFromArray([
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
            'A' => 30,
            'B' => 10, // Ancho para la columna de Sede
            'C' => 10,
            'D' => 25,
            'E' => 30,
            'F' => 9,
        ];
    }

    public function startCell(): string
    {
        return 'A3'; // Los datos comenzarán desde la celda A4
    }
}
