<?php

namespace App\Services\Importar;

use App\Models\Configuracion\ConfigDiagnostico;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use App\Models\General\Paciente;
use App\Models\Importar\Agenda;
use App\Models\Importar\AgendaDiagnostico;
use App\Models\Importar\AgendaProducto;
use App\Models\Importar\ConfigDiagnosticoDicom;
use App\Models\Importar\ConfigProductoDicom;
use App\Models\Importar\GeneralPersonal;
use App\Models\Importar\PacienteDicom;
use App\Models\Importar\StudyDicom;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ImportarDicomService extends BaseService
{
    public function importar(): array
    {
        $fechaHoraIniEjecucion = Carbon::now();
        $startTime = microtime(true);
        $sedeActual = ConfigSede::where('estado', true)->first();

        if (! $sedeActual) {
            return $this->error('No hay una sede activa configurada para la importación.', 500);
        }

        $agendas = Agenda::orderBy('fechor_regis', 'ASC')->get();
        $i = 0;

        foreach ($agendas as $agenda) {
            $i++;

            $this->importarAgenda($agenda, $sedeActual);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $executionTimeInMinutes = $executionTime / 60;
        $fechaHoraFinEjecucion = Carbon::now();

        return $this->success([
            'total_importados' => $i,
            'inicio' => $fechaHoraIniEjecucion->toDateTimeString(),
            'fin' => $fechaHoraFinEjecucion->toDateTimeString(),
            'tiempo_segundos' => round($executionTime, 2),
            'tiempo_minutos' => round($executionTimeInMinutes, 2),
        ], 'La importación terminó con éxito. Total de estudios importados: '.$i);
    }

    private function importarAgenda(object $agenda, ConfigSede $sedeActual): void
    {
        Config::set('database.default', 'mysql_importar_dicom');
        $studyDicom = StudyDicom::where('pk', '=', $agenda->study_pk)->get();

        if ($studyDicom->isEmpty()) {
            return;
        }

        $studyDicomRow = $studyDicom[0];

        $agendaProductosDicom = AgendaProducto::where('id_agenda', '=', $agenda->id_agenda)->get();
        $agendaDiagnosticosDicom = AgendaDiagnostico::where('id_agenda', '=', $agenda->id_agenda)->get();
        $pacienteDicom = PacienteDicom::where('pk', '=', $studyDicomRow->patient_fk)->get();

        if ($pacienteDicom->isEmpty()) {
            return;
        }

        $pacienteDicomRow = $pacienteDicom[0];

        $medicoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_medico)->get();
        $quienAgendoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_quien_agendo)->get();

        if ($medicoDicom->isEmpty() || $quienAgendoDicom->isEmpty()) {
            return;
        }

        $medicoDicomRow = $medicoDicom[0];
        $quienAgendoDicomRow = $quienAgendoDicom[0];

        Config::set('database.default', 'mysql');
        $pacienteLidiag = Paciente::firstOrCreate(
            ['num_docu' => $pacienteDicomRow->pat_id],
            [
                'num_docu' => $pacienteDicomRow->pat_id,
                'nombres' => str_replace('^^^', '', $pacienteDicomRow->pat_name),
            ]
        );

        $userQuienAgendoLidiag = User::firstOrCreate(
            ['num_docu' => $quienAgendoDicomRow->num_docu],
            [
                'num_docu' => $quienAgendoDicomRow->num_docu,
                'name' => $quienAgendoDicomRow->nom." ".$quienAgendoDicomRow->ape,
                'email' => $quienAgendoDicomRow->e_mail,
                'password' => bcrypt('123456'),
            ]
        );

        $medicoLidiag = User::where('num_docu', '=', $medicoDicomRow->num_docu)->get();

        if ($medicoLidiag->count() <= 0) {
            $medicoLidiag = new User;
            $medicoLidiag->num_docu = $medicoDicomRow->num_docu;
            $medicoLidiag->reg_med = $medicoDicomRow->reg_med;
            $medicoLidiag->name = $medicoDicomRow->nom.' '.$medicoDicomRow->ape;
            $medicoLidiag->email = $medicoDicomRow->nom.$medicoDicomRow->ape."st.com";
            $medicoLidiag->password = bcrypt('123456');
            $medicoLidiag->save();
            $medicoLidiag->assignRole('Medico');
            $medicoParaInsertar = $medicoLidiag->id;
        } else {
            $medicoParaInsertar = $medicoLidiag[0]->id;
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

        Config::set('database.default', 'mysql_sucursal');
        $studyDcm = Study::select('pk', 'patient_fk', 'study_iuid', 'study_id', 'study_datetime', 'study_desc', 'mods_in_study')
            ->where('pk', '=', $agenda->study_pk)
            ->get();

        $mods_in_study = $studyDcm->count() > 0 ? $studyDcm[0]->mods_in_study : '';

        Config::set('database.default', 'mysql');

        $estudio = new Estudio;
        $estudio->study_pk = $agenda->study_pk;
        $estudio->study_iuid = $studyDicomRow->study_iuid;
        $estudio->study_id = $studyDicomRow->study_id;
        $estudio->fec_estudio = $studyDicomRow->study_datetime;
        $estudio->accession_no = $studyDicomRow->accession_no;
        $estudio->study_desc = mb_convert_encoding($studyDicomRow->study_desc, 'UTF-8', 'ISO-8859-1');
        $estudio->mods_in_study = $mods_in_study;
        $estudio->paciente_id = $pacienteLidiag->id;
        $estudio->medico_id = $medicoParaInsertar;
        $estudio->quien_registro_id = $userQuienAgendoLidiag->id;
        $estudio->sede_id = $sedeActual->id;
        $estudio->prioridad_id = $agenda->id_prioridad;
        $estudio->observaciones = $agenda->observaciones;
        $estudio->atencion = $agenda->id_atencion;
        $estudio->eps = $agenda->eps;
        $estudio->contrato = $agenda->plan_contrato;
        $estudio->plan = $agenda->plan_contrato;
        $estudio->estado = $agenda->acti;
        $estudio->created_at = $agenda->fechor_regis;
        $estudio->updated_at = $agenda->fechor_regis;
        $estudio->save();

        foreach ($agendaDiagnosticosDicom as $diganos) {
            $diagnosticoDicom = ConfigDiagnosticoDicom::where('id_diag', '=', $diganos->id_diag)->get();

            if ($diagnosticoDicom->isEmpty()) {
                continue;
            }

            ConfigDiagnostico::firstOrCreate(
                ['cod_diagnos' => $diagnosticoDicom[0]->cod_diag],
                [
                    'cod_diagnos' => $diagnosticoDicom[0]->cod_diag,
                    'nom_diagnos' => $diagnosticoDicom[0]->nom_diag,
                ]
            );

            $diganosEstudio = new EstudioDiagnostico;
            $diganosEstudio->estudio_id = $estudio->id;
            $diganosEstudio->cod_diagnos = $diagnosticoDicom[0]->cod_diag;
            $diganosEstudio->nom_diagnos = trim($diagnosticoDicom[0]->nom_diag);
            $diganosEstudio->save();
        }

        foreach ($agendaProductosDicom as $produc) {
            Config::set('database.default', 'mysql_importar_dicom');

            $configProductoDicom = ConfigProductoDicom::where('id_produc', '=', $produc->id_produc)->get();

            $productoDicom = AgendaProducto::where('id_agenda', '=', $agenda->id_agenda)
                ->where('id_produc', '=', $produc->id_produc)
                ->get();

            if ($productoDicom->isEmpty()) {
                continue;
            }

            $productoDicomRow = $productoDicom[0];

            $idTranscriptor = null;
            if ($productoDicomRow->id_transcriptor > 0) {
                $quienTranscribioDicom = GeneralPersonal::where('id_personal', '=', $productoDicomRow->id_transcriptor)->get();

                if ($quienTranscribioDicom->isNotEmpty()) {
                    Config::set('database.default', 'mysql');
                    $quienTranscribio = User::where('num_docu', '=', $quienTranscribioDicom[0]->num_docu)->get();
                    if ($quienTranscribio->isNotEmpty()) {
                        $idTranscriptor = $quienTranscribio[0]->id;
                    }
                }
            }

            Config::set('database.default', 'mysql');

            $configProductoLidiag = ConfigProducto::firstOrCreate(
                ['nom_produc' => trim($produc->nom_produc)],
                [
                    'cod_cups' => trim($productoDicomRow->cod_cups),
                    'nom_produc' => trim($productoDicomRow->nom_produc),
                    'requi_lectura' => $productoDicomRow->requi_lectura,
                ]
            );

            $prdoctoEstudio = new EstudioProducto;
            $prdoctoEstudio->estudio_id = $estudio->id;
            $prdoctoEstudio->producto_id = $configProductoLidiag->id;
            $prdoctoEstudio->transcriptor_id = $idTranscriptor;
            $prdoctoEstudio->cod_cups = $configProductoLidiag->cod_cups;
            $prdoctoEstudio->nom_produc = $configProductoLidiag->nom_produc;
            $prdoctoEstudio->fechor_lectura = $produc->fechor_lectura;
            $prdoctoEstudio->lectura = $produc->lectura;
            $prdoctoEstudio->fechor_trascrito = $produc->fechor_trascrito;
            $prdoctoEstudio->activo = $agenda->acti;
            $prdoctoEstudio->save();
        }
    }
}
