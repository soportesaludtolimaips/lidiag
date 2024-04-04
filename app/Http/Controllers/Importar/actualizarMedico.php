<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use App\Models\Importar\Agenda;
use App\Models\Importar\GeneralPersonal;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class ActualizarMedico extends Controller
{
    public function actualizarMedico()
    {

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_importar_dicom');

        /**
         * Listo las agendas de Dicom
         */
        $agendas = Agenda::select('study_pk', 'id_medico')
            ->where('study_pk', '=', 133447)
            ->orderBy('id_agenda', 'ASC')
            ->get();

        //return $agendas->count();

        $i = 0;

        foreach ($agendas as $agenda) {

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

            $estudio = Estudio::where('study_pk', '=', $agenda->study_pk)->get();
            if ($estudio) {
                $estudio->medico_id = $medicoLidiag[0]->id;
                $estudio->save();

                echo "Atualizado estudio " . $estudio->id . ", el medico actual es: " . $medicoDicom->nom . " " . $medicoDicom->nom . ", el nuevo medico es: " . $medicoLidiag[0]->name;
            }
        }
    }
}
