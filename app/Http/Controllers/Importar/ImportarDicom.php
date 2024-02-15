<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Models\Importar\Agenda;
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



        $agendas = Agenda::limit(10)->get();

        foreach ($agendas as $agenda) {
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
            //return $studyDcm;

            /**
             * Busco el estudio en Dicom pada saber cual es el paciente
             */
            Config::set('database.default', 'mysql_importar_dicom');
            $studyDicom = StudyDicom::where('study_iuid', '=', $studyDcm[0]->study_iuid)
                ->get();
            //return $studyDicom[0];

            /**
             * Busco el paciente en Dicom
             */
            $paciente = PacienteDicom::where('pat_id', '=', $studyDicom[0]->patient_fk)
                ->get();
            //return $paciente;

            /**
             * Busco el medico en Dicom
             */
            $medicoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_medico)
                ->get();
            //return $medicoDicom;

            /**
             * Busco el medico en Lidiag
             */
            Config::set('database.default', 'mysql');
            $medicoLidiag = User::where('num_docu', '=', $medicoDicom[0]->num_docu)
                ->get();
            //return $medicoLidiag;

            /**
             * Busco quien agendo en Dicom
             */
            Config::set('database.default', 'mysql_importar_dicom');
            $quienAgendoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_quien_agendo)
                ->get();
            //return $quienAgendoDicom[0]->num_docu;

            /**
             * Busco quien agendo en Lidiag
             */
            Config::set('database.default', 'mysql');
            $quienAgendoLidiag = User::where('num_docu', '=', $quienAgendoDicom[0]->num_docu)
                ->get();
            //return $quienAgendoLidiag;

            $estudio = new Estudio();
            $estudio->study_pk = $agenda->study_pk;
            $estudio->study_iuid = $agenda->study_iuid;
            $estudio->study_id = $agenda->study_id;
            $estudio->fec_estudio = $agenda->study_datetime;
            $estudio->study_desc = $agenda->study_desc;
            $estudio->mods_in_study = $agenda->mods_in_study;
            //$estudio->email_reporta = $agenda->;
            $estudio->paciente_id = $pacienteLidiag->id;
            $estudio->medico_id = $medicoLidiag->id;
            $estudio->quien_registro_id = $quienAgendo->id;
            $estudio->sede_id = 1;
            $estudio->prioridad_id = $agenda->id_prioridad;
            $estudio->observaciones = $agenda->observaciones;
            $estudio->atencion = $agenda->id_atencion;
            //$estudio->servicio = $agenda->;
            $estudio->eps = $agenda->eps;
            $estudio->contrato = $agenda->plan_contrato;
            $estudio->plan = $agenda->plan_contrato;
            $estudio->estado = $agenda->acti;
        }

        return $agenda;
    }
}
