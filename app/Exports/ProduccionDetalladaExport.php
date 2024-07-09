<?php

namespace App\Exports;

use App\Models\Estudio\Estudio;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProduccionDetalladaExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $fechaIni;
    protected $fechaFin;
    protected $sedeId;

    public function __construct($fechaIni, $fechaFin, $sedeId)
    {
        $this->fechaIni = $fechaIni;
        $this->fechaFin = $fechaFin;
        $this->sedeId = $sedeId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        return Estudio::with([
            'paciente',
            'medico',
        ])
            ->whereBetween('created_at', [$this->fechaIni, $this->fechaFin])
            ->where('sede_id', $this->sedeId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha de Estudio',
            'Descripción',
            'Paciente',
            'Médico',
            'Sede',
            'Prioridad',
            'Observaciones'
        ];
    }

    public function map($estudio): array
    {
        return [
            $estudio->id,
            $estudio->fec_estudio,
            $estudio->study_desc,
            $estudio->paciente->nombres,
            $estudio->medico->name,
            $estudio->sede->name,
            $estudio->prioridad->name,
            $estudio->observaciones,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
            1    => ['font' => ['bold' => true]],
        ];
    }
}
