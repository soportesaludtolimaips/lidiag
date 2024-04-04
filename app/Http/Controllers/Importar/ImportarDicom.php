<?php

namespace App\Http\Controllers\Importar;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ImportarDicom extends Controller
{
    public function importarDicom()
    {
        $fechaHoraIniEjecucion = Carbon::now();

        $startTime = microtime(true);
        $sedeActual = ConfigSede::find(1);

        $agendas = Agenda::orderBy('fechor_regis', 'ASC')
            ->where('study_pk', '>', 135262)
            ->get();
        //return $agendas->count();
        $i = 0;

        foreach ($agendas as $agenda) {

            $i++;
            echo "Importando el estudio " . $agenda . "<br><br>Total de estudios: " . $i . "<br><br><br>";

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

            if ($studyDcm->count() > 0) {
                /**
                 * INICIO LAS BUSQUEDAS DEL DICOM
                 */

                /**
                 * Busco el estudio en Dicom pada saber cual es el paciente
                 */
                echo '************************************************************************<br>';
                echo '$studyDcm: ' . $studyDcm . "<br>";
                echo '************************************************************************<br>';

                Config::set('database.default', 'mysql_importar_dicom');
                $studyDicom = StudyDicom::where('study_iuid', '=', $studyDcm[0]->study_iuid)
                    ->get();

                if ($studyDicom->count() > 0) {
                    echo '************************************************************************<br>';
                    echo '$studyDicom: ' . $studyDicom . "<br>";
                    echo '************************************************************************<br>';
                    /**
                     * Busco los productos en Dicom
                     */
                    $agendaProductosDicom = AgendaProducto::where('id_agenda', '=', $agenda->id_agenda)
                        ->get();

                    /**
                     * Busco los diagnosticos en Dicom
                     */
                    $agendaDiagnosticosDicom = AgendaDiagnostico::where('id_agenda', '=', $agenda->id_agenda)
                        ->get();

                    /**
                     * Busco el paciente en Dicom
                     */
                    $pacienteDicom = PacienteDicom::where('pk', '=', $studyDicom[0]->patient_fk)
                        ->get();

                    /**
                     * Busco el medico en Dicom
                     */
                    Config::set('database.default', 'mysql_importar_dicom');
                    $medicoDicom = GeneralPersonal::where('id_personal', '=', $agenda->id_medico)
                        ->get();

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
                    Config::set('database.default', 'mysql');
                    $pacienteLidiag = Paciente::firstOrCreate(
                        ['num_docu' => $pacienteDicom[0]->pat_id],
                        [
                            'num_docu' => $pacienteDicom[0]->pat_id,
                            'nombres' =>  str_replace('^^^', '', $pacienteDicom[0]->pat_name)
                        ]
                    );

                    /**
                     * Busco el medico en Lidiag
                     */
                    $medicoLidiag = User::where('num_docu', '=', $medicoDicom[0]->num_docu)
                        ->get();

                    if ($medicoLidiag->count() <= 0) {

                        $medicoLidiag  = new User();
                        $medicoLidiag->num_docu = $medicoDicom[0]->num_docu;
                        $medicoLidiag->reg_med = $medicoDicom[0]->reg_med;
                        $medicoLidiag->name = $medicoDicom[0]->nom . ' ' . $medicoDicom[0]->ape;
                        $medicoLidiag->email = $medicoDicom[0]->nom . $medicoDicom[0]->ape . "st.com";
                        $medicoLidiag->password = bcrypt('123456');
                        $medicoLidiag->save();
                        $medicoLidiag->assignRole('Medico');

                        $medicoParaInsertar = $medicoLidiag->id;
                    } else {
                        $medicoParaInsertar = $medicoLidiag[0]->id;
                    }

                    /**
                     * Busco quien agendo en Lidiag
                     */
                    $quienAgendoLidiag = User::where('num_docu', '=', $quienAgendoDicom[0]->num_docu)
                        ->get();

                    /**
                     * Inserto el estudio en Lidiag
                     */
                    $estudio = new Estudio();
                    $estudio->study_pk = $agenda->study_pk;
                    $estudio->study_iuid = $studyDcm[0]->study_iuid;
                    $estudio->study_id = $studyDcm[0]->study_id;
                    $estudio->fec_estudio = $studyDcm[0]->study_datetime;
                    $estudio->accession_no = $studyDcm[0]->accession_no;
                    $estudio->study_desc = mb_convert_encoding($studyDcm[0]->study_desc, 'UTF-8', 'ISO-8859-1');
                    $estudio->mods_in_study = $studyDcm[0]->mods_in_study;
                    //$estudio->email_reporta = $agenda->;
                    $estudio->paciente_id = $pacienteLidiag->id;
                    $estudio->medico_id = $medicoParaInsertar;
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

                    /**
                     * Inserto los diagnosticos en Lidiag
                     */
                    foreach ($agendaDiagnosticosDicom as $diganos) {

                        $diagnosticoDicom = ConfigDiagnosticoDicom::where('id_diag', '=', $diganos->id_diag)
                            ->get();

                        $configDiganosticoLidiag = ConfigDiagnostico::firstOrCreate(
                            ['cod_diagnos' => $diagnosticoDicom[0]->cod_diag],
                            [
                                'cod_diagnos' => $diagnosticoDicom[0]->cod_diag,
                                'nom_diagnos' => $diagnosticoDicom[0]->nom_diag
                            ]
                        );

                        /**
                         * Relaciono el diagnostico con el estudio
                         */
                        $diganosEstudio = new EstudioDiagnostico();
                        $diganosEstudio->estudio_id = $estudio->id;
                        $diganosEstudio->cod_diagnos = $diagnosticoDicom[0]->cod_diag;
                        $diganosEstudio->nom_diagnos = trim($diagnosticoDicom[0]->nom_diag);
                        $diganosEstudio->save();
                    }

                    /**
                     * Inserto los productos en Lidiag
                     */
                    foreach ($agendaProductosDicom as $produc) {

                        $productoDicom = ConfigProductoDicom::where('id_produc', '=', $produc->id_produc)
                            ->get();

                        /**
                         * Busco quien transcribio en Dicom
                         */
                        Config::set('database.default', 'mysql_importar_dicom');
                        $quienAgendoDicom = GeneralPersonal::where('id_personal', '=', $produc->id_transcriptor)
                            ->get();

                        /**
                         * Busco quien hizo la transcripcion
                         */
                        Config::set('database.default', 'mysql');
                        $quienTranscribio = User::where('num_docu', '=', 1006147882)
                            ->get();

                        $configProductoLidiag = ConfigProducto::firstOrCreate(
                            ['nom_produc' => trim($produc->nom_produc)],
                            [
                                'cod_cups' => trim($productoDicom[0]->cod_cups),
                                'nom_produc' => trim($productoDicom[0]->nom_produc),
                                'requi_lectura' => $productoDicom[0]->requi_lectura,
                            ]
                        );

                        /**
                         * Relacion el producto con el estudio
                         */
                        $prdoctoEstudio = new EstudioProducto();
                        $prdoctoEstudio->estudio_id = $estudio->id;
                        $prdoctoEstudio->transcriptor_id = $quienTranscribio[0]->id;
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
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Convertir el tiempo a minutos
        $executionTimeInMinutes = $executionTime / 60;

        $fechaHoraFinEjecucion = Carbon::now();
        echo "<br><br><br><br><br><br>";
        echo 'Inicio: ' . $fechaHoraIniEjecucion . "<br>";
        echo 'Fin: ' . $fechaHoraFinEjecucion . "<br>";

        // Puedes imprimir o registrar el tiempo
        echo "Tiempo de ejecución: $executionTime segundos";
        echo "Tiempo de ejecución en minutos: $executionTimeInMinutes minutos";
        return "<br><br><br><br>La importacion termno con exito\n Total de estudios importador: " . $i;
    }
}
