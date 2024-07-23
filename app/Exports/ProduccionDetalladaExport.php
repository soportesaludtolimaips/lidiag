<?php

namespace App\Exports;

use App\Models\Estudio\Estudio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProduccionDetalladaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $sedeId;

    public function __construct($fechaInicio, $fechaFin, $sedeId)
    {
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
    }

    public function collection()
    {
        $estudios = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('config_prioridades', 'estudios.prioridad_id', '=', 'config_prioridades.id')
            ->join('users as registro', 'estudios.quien_registro_id', '=', 'registro.id')
            ->join('users as medico', 'medico.id', '=', 'estudios.medico_id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->leftJoin('users as transcrip', 'transcrip.id', '=', 'estudios_productos.transcriptor_id')
            ->whereBetween(DB::raw('DATE(estudios.fec_estudio)'), [$this->fechaInicio, $this->fechaFin])
            ->where('estudios.sede_id', $this->sedeId)
            ->select(
                'estudios.id',
                'config_sedes.nom_sede',
                'estudios.fec_estudio',
                'estudios_productos.created_at',
                'estudios_productos.fechor_lectura',
                'estudios.atencion',
                'config_prioridades.nom_priori',
                'registro.name as quien_registro',
                'medico.name as medico',
                'pacientes.num_docu',
                'pacientes.nombres',
                'estudios_productos.cod_cups',
                'estudios_productos.nom_produc',
                'transcrip.name as transcriptor',
                'estudios.eps',
                'estudios.contrato',
                'estudios.plan'
            )
            ->get();

        return new Collection($estudios);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Sede',
            'Fecha de Estudio',
            'Fecha de Asignacion',
            'Fecha de Lectura',
            'Atención',
            'Prioridad',
            'Quien Registró',
            'Médico',
            'Documento Paciente',
            'Nombre Paciente',
            'Código CUPS',
            'Nombre Producto',
            'Transcriptor',
            'EPS',
            'Contrato',
            'Plan'
        ];
    }

    public function map($estudio): array
    {
        return [
            $estudio->id ?? 'N/A',
            $estudio->nom_sede ?? 'N/A',
            $estudio->fec_estudio ?? 'N/A',
            $estudio->created_at ?? 'N/A',
            $estudio->fechor_lectura ?? 'N/A',
            $estudio->atencion ?? 'N/A',
            $estudio->nom_priori ?? 'N/A',
            $estudio->quien_registro ?? 'N/A',
            $estudio->medico ?? 'N/A',
            $estudio->num_docu ?? 'N/A',
            $estudio->nombres ?? 'N/A',
            $estudio->cod_cups ?? 'N/A',
            $estudio->nom_produc ?? 'N/A',
            $estudio->transcriptor ?? 'N/A',
            $estudio->eps ?? 'N/A',
            $estudio->contrato ?? 'N/A',
            $estudio->plan ?? 'N/A',

        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Agrega el encabezado personalizado
        $sheet->setCellValue('A1', 'Salud Tolima - Reporte de Producción');
        $sheet->mergeCells('A1:Q1'); // A1 to P1 to include the new column
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece el estilo para el encabezado de los datos
        $sheet->getStyle('A3:Q3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1A2274'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Establece bordes para todas las celdas de datos
        $sheet->getStyle('A4:P' . ($sheet->getHighestRow()))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
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
            'B' => 20, // Ancho para la columna de Sede
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 9,
            'G' => 9,
            'H' => 20,
            'I' => 25,
            'J' => 20,
            'K' => 30,
            'L' => 12,
            'M' => 30,
            'N' => 25,
            'O' => 35,
            'P' => 35,
            'Q' => 35,
        ];
    }

    public function startCell(): string
    {
        return 'A3'; // Los datos comenzarán desde la celda A4
    }
}
