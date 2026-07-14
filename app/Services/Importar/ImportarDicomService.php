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
        $sedeActual = ConfigSede::find(1);

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

        $agendaProductosDicom = AgendaProducto::where('id_agenda', '=', $agenda->id_agenda)->get();
        $agendaDiagnosticosDicom = AgendaDiagnostico::where('id_agenda', '=', $agenda->id_agenda)->get();
        $pacienteDicom = PacienteDicom::where('pk', '=', $studyDicom[0]->patient_fk)->get();

        Config::set('database.default', 'mysql_importar_dicom');
        $medicoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_medico)->get();
        $quienAgendoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_quien_agendo)->get();

        Config::set('database.default', 'mysql');
        $pacienteLidiag = Paciente::firstOrCreate(
            ['num_docu' => $pacienteDicom[0]->pat_id],
            [
                'num_docu' => $pacienteDicom[0]->pat_id,
                'nombres' => str_replace('^^^', '', $pacienteDicom[0]->pat_name),
            ]
        );

        $userQuienAgendoLidiag = User::firstOrCreate(
            ['num_docu' => $quienAgendoDicom[0]->num_docu],
            [
                'num_docu' => $quienAgendoDicom[0]->num_docu,
                'name' => $quienAgendoDicom[0]->nom." ".$quienAgendoDicom[0]->ape,
                'email' => $quienAgendoDicom[0]->e_mail,
                'password' => bcrypt('123456'),
            ]
        );

        $medicoLidiag = User::where('num_docu', '=', $medicoDicom[0]->num_docu)->get();

        if ($medicoLidiag->count() <= 0) {
            $medicoLidiag = new User;
            $medicoLidiag->num_docu = $medicoDicom[0]->num_docu;
            $medicoLidiag->reg_med = $medicoDicom[0]->reg_med;
            $medicoLidiag->name = $medicoDicom[0]->nom.' '.$medicoDicom[0]->ape;
            $medicoLidiag->email = $medicoDicom[0]->nom.$medicoDicom[0]->ape."st.com";
            $medicoLidiag->password = bcrypt('123456');
            $medicoLidiag->save();
            $medicoLidiag->assignRole('Medico');
            $medicoParaInsertar = $medicoLidiag->id;
        } else {
            $medicoParaInsertar = $medicoLidiag[0]->id;
        }

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

        Config::set('database.default', 'mysql_sucursal');
        $studyDcm = Study::select('pk', 'patient_fk', 'study_iuid', 'study_id', 'study_datetime', 'study_desc', 'mods_in_study')
            ->where('pk', '=', $agenda->study_pk)
            ->get();

        $mods_in_study = $studyDcm->count() > 0 ? $studyDcm[0]->mods_in_study : '';

        Config::set('database.default', 'mysql');

        $estudio = new Estudio;
        $estudio->study_pk = $agenda->study_pk;
        $estudio->study_iuid = $studyDicom[0]->study_iuid;
        $estudio->study_id = $studyDicom[0]->study_id;
        $estudio->fec_estudio = $studyDicom[0]->study_datetime;
        $estudio->accession_no = $studyDicom[0]->accession_no;
        $estudio->study_desc = mb_convert_encoding($studyDicom[0]->study_desc, 'UTF-8', 'ISO-8859-1');
        $estudio->mods_in_study = $mods_in_study;
        $estudio->paciente_id = $pacienteLidiag->id;
        $estudio->medico_id = $medicoParaInsertar;
        $estudio->quien_registro_id = $userQuienAgendoLidiag->id;
        $estudio->sede_id = 1;
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

            if ($productoDicom[0]->id_transcriptor > 0) {
                $quienTranscribioDicom = GeneralPersonal::where('id_personal', '=', $productoDicom[0]->id_transcriptor)->get();

                $idTranscriptor = null;
                if ($productoDicom[0]->id_transcriptor != 0) {
                    Config::set('database.default', 'mysql');
                    $quienTranscribio = User::where('num_docu', '=', $quienTranscribioDicom[0]->num_docu)->get();
                    $idTranscriptor = $quienTranscribio[0]->id;
                }
            } else {
                $idTranscriptor = null;
            }

            Config::set('database.default', 'mysql');

            $configProductoLidiag = ConfigProducto::firstOrCreate(
                ['nom_produc' => trim($produc->nom_produc)],
                [
                    'cod_cups' => trim($productoDicom[0]->cod_cups),
                    'nom_produc' => trim($productoDicom[0]->nom_produc),
                    'requi_lectura' => $productoDicom[0]->requi_lectura,
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
