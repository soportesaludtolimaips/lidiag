<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ProduccionDetalladaExport;
use App\Exports\ProduccionDetalladaEcografiasExport;
use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReporteProduccion extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:reportes.detallados|reportes.indicadores')->only(['reporteProduccionDetallado']);
    }

    private function normalizeNullable($value)
    {
        if ($value === null) return null;
        $v = is_string($value) ? trim($value) : $value;
        if ($v === '' || $v === '0' || $v === 0 || $v === 'null') return null;
        return $v;
    }

    /**
     * Genera y descarga un reporte detallado de producción en Excel.
     *
     * Permite generar reportes para 'Rx' o 'Ecografia', filtrando por fechas,
     * sede, usuario que asignó y médico.
     *
     * @param Request $resquest Filtros: `fechaIni`, `fechaFin`, `sedeId`, `quien_asigno`, `medico`, `tipo`.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo Excel descargable.
     */
    public function reporteProduccionDetallado(Request $resquest)
    {
        $validator = Validator::make($resquest->all(), [
            'tipo' => 'required|in:Rx,Ecografia',
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
            'sedeId' => 'nullable',
            'quien_asigno' => 'nullable',
            'medico' => 'nullable',
        ], [
            'tipo.required' => 'Debes seleccionar el tipo de reporte.',
            'tipo.in' => 'Tipo de reporte inválido.',
            'fechaIni.required' => 'Debes seleccionar la fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar la fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $fechaInicio = $resquest->fechaIni;
        $fechaFin    = $resquest->fechaFin;

        $sedeId = $this->normalizeNullable($resquest->sedeId);
        $quienAsignoId = $this->normalizeNullable($resquest->quien_asigno);
        $medicoId = $this->normalizeNullable($resquest->medico);

        /* $estudios = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('config_prioridades', 'estudios.prioridad_id', '=', 'config_prioridades.id')
            ->join('users as registro', 'estudios.quien_registro_id', '=', 'registro.id')
            ->join('users as medico', 'medico.id', '=', 'estudios.medico_id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->leftJoin('users as transcrip', 'transcrip.id', '=', 'estudios_productos.transcriptor_id')
            ->whereBetween(DB::raw('DATE(estudios_productos.fechor_lectura)'), [$fechaInicio, $fechaFin])
            ->when($sedeId, function ($query) use ($sedeId) {
                return $query->where('estudios.sede_id', $sedeId);
            })
            ->when($quienAsignoId, function ($query) use ($quienAsignoId) {
                return $query->where('estudios.quien_registro_id', $quienAsignoId);
            })
            ->when($medicoId, function ($query) use ($medicoId) {
                return $query->where('estudios.medico_id', $medicoId);
            })
            ->where('estudios_productos.activo', '=', 1)
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
            ->toSql();
        return $estudios;  */

        if ($resquest->tipo === 'Rx') {
            return Excel::download(new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),  'produccion lecturas de imagenes diagnosticas.xlsx');
        }
        if ($resquest->tipo === 'Ecografia') {
            return Excel::download(new ProduccionDetalladaEcografiasExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),  'produccion lecturas de ecografias.xlsx');
        }

        return response()->json(['message' => 'Tipo de reporte inválido.'], 422);
    }
}
