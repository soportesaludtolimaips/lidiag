<?php

namespace App\Exports;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * Constructor de la clase de exportación de estudios PAC.
     *
     * @param  string  $fechaInicio  Fecha de inicio del rango.
     * @param  string  $fechaFin  Fecha de fin del rango.
     * @param  int  $sedeId  Identificador de la sede.
     * @param  string  $sedeNombre  Nombre de la sede.
     * @param  string  $conexionOriginal  Nombre de la conexión original de base de datos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function __construct($fechaInicio, $fechaFin, $sedeId, $sedeNombre, $conexionOriginal)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
        $this->sedeNombre = $sedeNombre;
        $this->conexionOriginal = $conexionOriginal;
    }

    /**
     * Obtiene la colección de estudios desde la base de datos de la sede configurada.
     *
     * @returns \Illuminate\Support\Collection Colección de estudios del PAC.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function collection()
    {
        $sedeActual = ConfigSede::find($this->sedeId);

        if (! $sedeActual) {
            return collect();
        }

        $this->sedeNombre = $sedeActual->nom_sede;

        config([
            'database.connections.mysql_sucursal' => [
                'driver' => 'mysql',
                'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
                'port' => env('DB_SUCURSAL_PUERTO', $sedeActual->puerto_dcm),
                'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
                'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
                'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
                'charset' => env('DB_SUCURSAL_charset', 'latin1'),
                'collation' => env('DB_SUCURSAL_collation', 'latin1_swedish_ci'),
            ],
        ]);

        try {
            $estudios = DB::connection('mysql_sucursal')
                ->table('study')
                ->select(
                    'patient.pat_id',
                    'patient.pat_name',
                    'study.study_datetime',
                    'study.study_desc',
                    'study.mods_in_study'
                )
                ->join('patient', 'patient.pk', '=', 'study.patient_fk')
                ->whereBetween('study.study_datetime', [
                    $this->fechaInicio.' 00:00:00',
                    $this->fechaFin.' 23:59:59',
                ])
                ->get();

            return $estudios;
        } catch (\Throwable $e) {
            Log::error(
                'ReportesEstudioPAC: Error querying remote PAC DB',
                ['sede_id' => $this->sedeId, 'error' => $e->getMessage()]
            );

            return collect();
        }
    }

    /**
     * Define los encabezados de las columnas del archivo Excel.
     *
     * @returns array<string> Nombres de las columnas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
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

    /**
     * Mapea cada estudio a un array de valores para las celdas del Excel.
     *
     * @param  object  $estudio  Objeto estudio con campos de la tabla study y patient.
     *
     * @returns array<string> Valores de las celdas para la fila.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function map($estudio): array
    {
        return [
            $this->sedeNombre ?? 'N/A',
            $estudio->study_datetime ?? 'N/A',
            $estudio->pat_id ?? 'N/A',
            $estudio->pat_name ?? 'N/A',
            $estudio->study_desc ?? 'N/A',
            $estudio->mods_in_study ?? 'N/A',
        ];
    }

    /**
     * Aplica estilos visuales al archivo Excel (encabezados, bordes, colores).
     *
     * @param  Worksheet  $sheet  Hoja de cálculo de PhpSpreadsheet.
     *
     * @returns array<int, array<string, mixed>> Estilos adicionales para filas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function styles(Worksheet $sheet)
    {
        // Agrega el encabezado personalizado
        $sheet->setCellValue('A1', 'Salud Tolima - Reporte de Estudios Tomados en PAC');
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
        $sheet->getStyle('A4:F'.($sheet->getHighestRow()))->applyFromArray([
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

    /**
     * Define el ancho de cada columna del archivo Excel.
     *
     * @returns array<string, int> Ancho de columna por letra.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 19, // Ancho para la columna de Sede
            'C' => 15,
            'D' => 50,
            'E' => 30,
            'F' => 9,
        ];
    }

    /**
     * Retorna la celda de inicio para los datos del reporte.
     *
     * @returns string Celda de inicio (ejemplo: 'A3').
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-27
     */
    public function startCell(): string
    {
        return 'A3'; // Los datos comenzarán desde la celda A4
    }
}
