<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ProduccionDetalladaExport;
use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReporteProduccion extends Controller
{
    public function reporteProduccionDetallado(Request $resquest)
    {
        $fechaInicio = $resquest->fechaIni;
        $fechaFin = $resquest->fechaFin;
        $sedeId = $resquest->sedeId;

        /* $estudios = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('config_prioridades', 'estudios.prioridad_id', '=', 'config_prioridades.id')
            ->join('users as registro', 'estudios.quien_registro_id', '=', 'registro.id')
            ->join('users as medico', 'medico.id', '=', 'estudios.medico_id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->leftJoin('users as transcrip', 'transcrip.id', '=', 'estudios_productos.transcriptor_id')
            ->whereBetween(DB::raw('DATE(estudios.created_at)'), [$fechaInicio, $fechaFin])
            ->where('estudios.sede_id', $sedeId)
            ->select(
                'estudios.id',
                'config_sedes.nom_sede',
                'estudios.fec_estudio',
                'estudios_productos.created_at',
                'estudios_productos.fechor_lectura',
                'estudios.atencion',
                'config_prioridades.nom_priori as quien_registro',
                'registro.name as medico',
                'pacientes.num_docu',
                'pacientes.nombres',
                'estudios_productos.cod_cups',
                'estudios_productos.nom_produc',
                'transcrip.name as transcriptor',
                'estudios.eps',
                'estudios.contrato',
                'estudios.plan'
            )
            ->toSql();

        return $estudios; */
        // Define el nombre y la ruta del archivo
        $fileName = 'estudios_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'reporte_lecturas/' . $fileName;

        return Excel::download(new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId),  'produccion lecturas de imagenes diagnosticas.xlsx');

        // Retorna la ruta del archivo almacenado
        //return response()->json(['file_path' => $filePath]);
    }
}
