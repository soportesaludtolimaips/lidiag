<?php

namespace App\Services\Dcm4chee;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Services\BaseService;
use Illuminate\Support\Facades\Config;

class StudyService extends BaseService
{
    /**
     * Lista los estudios DICOM de la sede consultando la base dcm4chee remota con filtros
     * y reconcilia el estado de agendamiento/asignación clínica frente a la base local de LIDiag.
     *
     * Flujo del proceso:
     * 1. Valida filtros mínimos requeridos (búsqueda por paciente/documento o rango de fechas).
     * 2. Localiza la configuración de red y credenciales de la sede solicitada en config_sedes.
     * 3. Configura temporalmente la conexión dinámica 'mysql_sucursal' apuntando al servidor PACS dcm4chee.
     * 4. Ejecuta consulta filtrada sobre las tablas study y patient del PACS remoto.
     * 5. Restituye la conexión default a la base central de LIDiag.
     * 6. Reconcilia de forma resiliente el estado de asignación (agendado) comparando por study_pk,
     *    study_iuid (Study Instance UID global) o accession_no contra la tabla local estudios.
     * 7. Sanitiza y normaliza encodings de caracteres ISO-8859-1 a UTF-8 para nombres y descripciones.
     *
     * @param  array  $data  Filtros: sede_id, bus_nom_num_docu, bus_fech_ini, bus_fecha_fin
     * @return array<string, mixed> Resultado estructurado con listado de estudios y estado de asignación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
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
        if (! $sedeActual) {
            return $this->error('Sede no encontrada.', 404);
        }

        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => $sedeActual->ip_dcm,
            'port' => $sedeActual->puerto_dcm,
            'database' => $sedeActual->bd_dcm,
            'username' => $sedeActual->usuario_dcm,
            'password' => $sedeActual->password_dcm,
            'charset' => 'latin1',
            'collation' => 'latin1_swedish_ci',
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
                $safeBusqueda = str_replace(['%', '_'], ['\\%', '\\_'], $busqueda);
                $query->where(function ($q) use ($safeBusqueda) {
                    $q->where('patient.pat_id', 'like', "%{$safeBusqueda}%")
                        ->orWhere('patient.pat_name', 'like', "%{$safeBusqueda}%");
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

        // Reconciliación multi-criterio de asignación clínica sin acoplamientos rígidos:
        // Se extraen identificadores de study_pk, study_iuid (DICOM UID global) y accession_no.
        $studyPks = $resultados->pluck('study_pk')->filter()->map(fn ($v) => (int) $v)->values()->all();
        $studyIuids = $resultados->pluck('study_iuid')->filter()->map(fn ($v) => trim((string) $v))->values()->all();
        $accessionNos = $resultados->pluck('accession_no')
            ->filter(fn ($v) => ! empty($v) && trim((string) $v) !== '' && trim((string) $v) !== '0')
            ->map(fn ($v) => trim((string) $v))
            ->values()
            ->all();

        $agendadosEstudios = Estudio::query()
            ->where(function ($q) use ($studyPks, $studyIuids, $accessionNos, $sedeId) {
                $hasCond = false;
                if (! empty($studyPks)) {
                    $q->where(function ($sub) use ($studyPks, $sedeId) {
                        $sub->whereIn('estudios.study_pk', $studyPks)
                            ->where('estudios.sede_id', $sedeId);
                    });
                    $hasCond = true;
                }
                if (! empty($studyIuids)) {
                    $method = $hasCond ? 'orWhereIn' : 'whereIn';
                    $q->$method('estudios.study_iuid', $studyIuids);
                    $hasCond = true;
                }
                if (! empty($accessionNos)) {
                    $method = $hasCond ? 'orWhere' : 'where';
                    $q->$method(function ($sub) use ($accessionNos, $sedeId) {
                        $sub->whereIn('estudios.accession_no', $accessionNos)
                            ->where('estudios.sede_id', $sedeId);
                    });
                }
            })
            ->get(['estudios.study_pk', 'estudios.study_iuid', 'estudios.accession_no']);

        $agendadosPks = [];
        $agendadosIuids = [];
        $agendadosAccessions = [];

        foreach ($agendadosEstudios as $ag) {
            if ($ag->study_pk) {
                $agendadosPks[(int) $ag->study_pk] = true;
            }
            if (! empty($ag->study_iuid)) {
                $agendadosIuids[trim((string) $ag->study_iuid)] = true;
            }
            if (! empty($ag->accession_no) && trim((string) $ag->accession_no) !== '') {
                $agendadosAccessions[trim((string) $ag->accession_no)] = true;
            }
        }

        foreach ($resultados as $row) {
            $pk = isset($row->study_pk) ? (int) $row->study_pk : 0;
            $iuid = isset($row->study_iuid) ? trim((string) $row->study_iuid) : '';
            $acc = isset($row->accession_no) ? trim((string) $row->accession_no) : '';

            $isAgendado = (
                ($pk > 0 && isset($agendadosPks[$pk])) ||
                ($iuid !== '' && isset($agendadosIuids[$iuid])) ||
                ($acc !== '' && isset($agendadosAccessions[$acc]))
            );

            $row->agendado = $isAgendado ? 1 : 0;
            foreach (['study_desc', 'mods_in_study', 'pat_name'] as $field) {
                if (isset($row->$field) && is_string($row->$field)) {
                    $row->$field = @iconv('ISO-8859-1', 'UTF-8//TRANSLIT//IGNORE', $row->$field) ?: $row->$field;
                }
            }
        }

        return $this->success($resultados);
    }
}
