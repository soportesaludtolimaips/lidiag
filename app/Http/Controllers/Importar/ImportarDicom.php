<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportarDicom extends Controller
{
    public function importarDicom()
    {
        $sedeActual = ConfigSede::find(1);

        $agendas = Agenda::limit(100)->get();
        $i = 0;

        foreach ($agendas as $agenda) {
            $i++;

            $file = fopen("log_importar.txt", "a");
            fwrite($file, '*******************************************************************************************************************' . PHP_EOL);
            fwrite($file, '*                                             ' . $i . '                                                          *' . PHP_EOL);
            fwrite($file, '*******************************************************************************************************************' . PHP_EOL);

            /***
             * Busco el estudio el DCM
             */

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

            $studyDcm = Study::select('pk', 'patient_fk', 'study_iuid', 'study_id', 'study_datetime', 'study_desc', 'mods_in_study')
                ->where('pk', '=', $agenda->study_pk)
                ->get();

            fwrite($file, '****************** BUSQUEDA DEL ESTUDIO EN DCM ******************'  . PHP_EOL);
            fwrite($file, '$studyDcm: ' . $studyDcm  . PHP_EOL);

            /**
             * INICIO LAS BUSQUEDAS DEL DICOM
             */

            /**
             * Busco el estudio en Dicom pada saber cual es el paciente
             */
            Config::set('database.default', 'mysql_importar_dicom');
            $studyDicom = StudyDicom::where('study_iuid', '=', $studyDcm[0]->study_iuid)
                ->get();

            fwrite($file, '****************** Busco el estudio en Dicom pada saber cual es el paciente ******************'  . PHP_EOL);
            fwrite($file, '$studyDicom: ' . $studyDicom  . PHP_EOL);

            /**
             * Busco los productos en Dicom
             */
            $agendaProductosDicom = AgendaProducto::where('id_agenda', '=', $agenda->id_agenda)
                ->get();

            $productoDicom = ConfigProductoDicom::where('id_produc', '=', $agendaProductosDicom[0]->id_produc)
                ->get();

            /**
             * Busco los diagnosticos en Dicom
             */
            $agendaDiagnosticosDicom = AgendaDiagnostico::where('id_agenda', '=', $agenda->id_agenda)
                ->get();

            $diagnosticoDicom = ConfigDiagnosticoDicom::where('id_diag', '=', $agendaDiagnosticosDicom[0]->id_diag)
                ->get();

            /**
             * Busco el paciente en Dicom
             */
            $pacienteDicom = PacienteDicom::where('pk', '=', $studyDicom[0]->patient_fk)
                ->get();

            fwrite($file, '******************  pat_id ******************'  . PHP_EOL);
            fwrite($file, '$pacienteDicom: ' . $pacienteDicom  . PHP_EOL);

            /**
             * Busco el medico en Dicom
             */
            $medicoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_medico)
                ->get();

            fwrite($file, '******************  Medico Dicom ******************'  . PHP_EOL);
            fwrite($file, '$pacienteDicom: ' . $pacienteDicom  . PHP_EOL);

            /**
             * Busco quien agendo en Dicom
             */
            Config::set('database.default', 'mysql_importar_dicom');
            $quienAgendoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_quien_agendo)
                ->get();

            /**
             * INICIO LOS INSERT EN LIDIAG
             */

            /**
             * Inserto el paciente en Lidiag
             */
            fwrite($file, '****************** Inserto el paciente en Lidiag ******************'  . PHP_EOL);
            Config::set('database.default', 'mysql');
            $pacienteLidiag = Paciente::firstOrCreate(
                ['num_docu' => $pacienteDicom[0]->pat_id],
                [
                    'num_docu' => $pacienteDicom[0]->pat_id,
                    'nombres' =>  str_replace('^^^', '', $pacienteDicom[0]->pat_name)
                ]
            );

            fwrite($file, '******************  Paciente Lidiag ******************'  . PHP_EOL);
            fwrite($file, '$pacienteDicom: ' . $pacienteDicom  . PHP_EOL);

            /**
             * Busco el medico en Lidiag
             */
            $medicoLidiag = User::where('num_docu', '=', $medicoDicom[0]->num_docu)
                ->get();
            fwrite($file, '******************  Medico Dicom ******************'  . PHP_EOL);
            fwrite($file, '$pacienteDicom: ' . $medicoLidiag  . PHP_EOL);

            /**
             * Busco quien en Lidiag
             */
            $quienAgendoLidiag = User::where('num_docu', '=', $quienAgendoDicom[0]->num_docu)
                ->get();

            $estudio = new Estudio();
            $estudio->study_pk = $agenda->study_pk;
            $estudio->study_iuid = $studyDcm[0]->study_iuid;
            $estudio->study_id = $studyDcm[0]->study_id;
            $estudio->fec_estudio = $studyDcm[0]->study_datetime;
            $estudio->study_desc = $studyDcm[0]->study_desc;
            $estudio->mods_in_study = $studyDcm[0]->mods_in_study;
            //$estudio->email_reporta = $agenda->;
            $estudio->paciente_id = $pacienteLidiag->id;
            $estudio->medico_id = $medicoLidiag[0]->id;
            $estudio->quien_registro_id = $quienAgendoLidiag[0]->id;
            $estudio->sede_id = 1;
            $estudio->prioridad_id = $agenda->id_prioridad;
            $estudio->observaciones = $agenda->observaciones;
            $estudio->atencion = $agenda->id_atencion;
            //$estudio->servicio = $agenda->;
            $estudio->eps = $agenda->eps;
            $estudio->contrato = $agenda->plan_contrato;
            $estudio->plan = $agenda->plan_contrato;
            $estudio->estado = $agenda->acti;
            $estudio->save();
        }

        //return "La importacion termno con exito\n Total de estudios importador: " . $i;
    }
}
