<?php

namespace App\Http\Controllers\Dcm4chee;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StudyController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:estudio.asignar,web');
    }

    /**
     * Lista estudios desde la base de datos DCM4CHEE (PACS) de una sede específica.
     *
     * Flujo del proceso:
     * 1. Configura conexión dinámica a la base de datos DCM4CHEE de la sede.
     * 2. Consulta estudios con filtros por documento/nombre y rango de fechas.
     * 3. Restaura conexión a base de datos principal.
     * 4. Cruza resultados con Lidiag para marcar estudios ya agendados.
     * 5. Limpia caracteres inválidos UTF-8 antes de retornar JSON.
     *
     * @param Request $request Filtros: `sede_id`, `bus_nom_num_docu`, `bus_fehc_ini`, `bus_fecha_fin`.
     * @param Study $study Modelo de estudio DCM4CHEE.
     * @return \Illuminate\Http\Response Lista de estudios en formato JSON.
     */
    public function listarEstudios(Request $request, Study $study)
    {
        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'bus_fehc_ini' => 'nullable|date|required_with:bus_fecha_fin',
            'bus_fecha_fin' => 'nullable|date|required_with:bus_fehc_ini|after_or_equal:bus_fehc_ini',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $sedeActual = ConfigSede::find($request->sede_id);

        //Configuramos la segunda base de datos
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

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');

        $estudios = new Study();
        $query = $estudios->query();

        $query->select(
            'study.pk as study_pk',
            'study.study_iuid',
            'study.study_id',
            'study.study_datetime',
            'study.accession_no',
            'study.study_desc',
            'study.mods_in_study',
            'patient.pat_id',
            'patient.pat_name',
            'patient.pat_sex',
            'patient.pat_birthdate',
        )
            ->join('patient', 'study.patient_fk', 'patient.pk');

        if (!empty($request->bus_nom_num_docu)) {
            $needle = $request->bus_nom_num_docu;
            $query->where(function ($q) use ($needle) {
                $q->where('patient.pat_id', 'like', "%{$needle}%")
                    ->orWhere('patient.pat_name', 'like', "%{$needle}%");
            });
        }

        if (!empty($request->bus_fehc_ini) && !empty($request->bus_fecha_fin)) {
            // Filtrar por fecha (solo la parte DATE)
            $query->whereBetween(DB::raw('DATE(study.study_datetime)'), [$request->bus_fehc_ini, $request->bus_fecha_fin]);
        }

        $resultados = $query->get();

        Config::set('database.default', 'mysql');

        // Evitar N+1: traer todos los study_pk ya agendados en una sola consulta
        $studyPks = $resultados->pluck('study_pk')->filter()->values()->all();
        $agendados = Estudio::query()
            ->where('sede_id', $request->sede_id)
            ->whereIn('study_pk', $studyPks)
            ->pluck('study_pk')
            ->flip(); // set rápido

        // Marcar agendado y convertir strings latin1 -> utf8 si aplica
        foreach ($resultados as $row) {
            $row->agendado = isset($agendados[$row->study_pk]) ? 1 : 0;
            foreach (['study_desc', 'mods_in_study', 'pat_name'] as $field) {
                if (isset($row->$field) && is_string($row->$field)) {
                    $row->$field = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $row->$field) ?: $row->$field;
                }
            }
        }

        return response()->json($resultados, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
