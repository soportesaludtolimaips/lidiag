<?php

namespace App\Services\Dcm4chee;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Services\BaseService;
use Illuminate\Support\Facades\Config;

class StudyService extends BaseService
{
    public function listarEstudios(array $data): array
    {
        $sedeId = (int) $data['sede_id'];
        $busqueda = trim($data['bus_nom_num_docu'] ?? '');
        $busFechaIni = $data['bus_fech_ini'] ?? null;
        $busFechaFin = $data['bus_fecha_fin'] ?? null;

        $tieneBusqueda = ! empty($busqueda);
        $tieneFechas = ! empty($busFechaIni) && ! empty($busFechaFin);

        if (! $tieneBusqueda && ! $tieneFechas) {
            return $this->error(
                'Debe indicar al menos un filtro: documento/nombre o rango de fechas.',
                422,
                ['bus_nom_num_docu' => ['Requerido si no hay fechas.'], 'bus_fech_ini' => ['Requerido si no hay búsqueda.']]
            );
        }

        $sedeActual = ConfigSede::find($sedeId);

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

        $conexionOriginal = config('database.default');
        try {
            Config::set('database.default', 'mysql_sucursal');

            $estudios = new Study;
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

            if ($tieneBusqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('patient.pat_id', 'like', "%{$busqueda}%")
                        ->orWhere('patient.pat_name', 'like', "%{$busqueda}%");
                });
            }

            if ($tieneFechas) {
                $inicio = $busFechaIni.' 00:00:00';
                $fin = date('Y-m-d', strtotime($busFechaFin.' +1 day')).' 00:00:00';
                $query->where('study.study_datetime', '>=', $inicio)
                    ->where('study.study_datetime', '<', $fin);
            }

            $query->orderBy('study.study_datetime', 'desc')->limit(500);

            $resultados = $query->get();
        } finally {
            Config::set('database.default', $conexionOriginal);
        }

        $studyPks = $resultados->pluck('study_pk')->filter()->values()->all();
        $agendados = Estudio::query()
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->where('estudios.sede_id', $sedeId)
            ->whereIn('estudios.study_pk', $studyPks)
            ->pluck('estudios.study_pk')
            ->flip();

        foreach ($resultados as $row) {
            $row->agendado = isset($agendados[$row->study_pk]) ? 1 : 0;
            foreach (['study_desc', 'mods_in_study', 'pat_name'] as $field) {
                if (isset($row->$field) && is_string($row->$field)) {
                    $row->$field = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $row->$field) ?: $row->$field;
                }
            }
        }

        return $this->success($resultados);
    }
}
