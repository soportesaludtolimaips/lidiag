<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Mail\NotificacionDeLectura;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class EstudioNotificacionController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', -1);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\Estudio  $estudio
     * @return \Illuminate\Http\Response
     */
    public function listarEstudios(Request $request)
    {
        $query = Estudio::query();

        $query->select(
            'estudios.id',
            'estudios.atencion',
            'estudios.fec_estudio',
            'estudios_productos.fechor_lectura',
            'pacientes.num_docu',
            'pacientes.nombres as nom_pacien',
            'pacientes.direccion',
            'pacientes.email',
            'estudios_productos.id AS estudio_producto_id',
            'estudios_productos.lectura',
            'estudios_productos.cod_cups',
            'estudios_productos.nom_produc',
            'users.num_docu as num_docu_medico',
            'users.name as nombres_medico',
            'users.reg_med as reg_medico',
            'users.firma as firma_medico',
            'config_sedes.id as sede_id',
            'config_sedes.nom_sede',
            'config_sedes.url_oviyam',
            'config_sedes.tap_oviyam',
            'config_sedes.plantilla'
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('users', 'users.id', '=', 'estudios.medico_id')
            ->whereNotNull('estudios_productos.transcriptor_id');

        if ($request->bus_num_docu != "") {
            $query->where('pacientes.num_docu', '=', $request->bus_num_docu);
        }

        if ($request->bus_nom != "") {
            $query->where('pacientes.nombres', 'like', "%$request->bus_nom%");
        }

        $registros = $query->get();
        return response()->json($registros);
    }

    public function notificarEmail($estudio)
    {
        $nombrePaciente = str_replace('^^^', '', $estudio->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $nomArchivoReporte = $estudio->id . "-" . $estudio->num_docu . "-" . $nombrePaciente . ".pdf";

        /**
         * Envio la lectura a Lidiag-reportes para que este diponible para la descarga por parte del paciente
         */
        $urlApiReportes = config('app.URL_API_REPORTES') . "api/almacenarLectura";

        $response = Http::timeout(60)->attach(
            'file_reporte',
            file_get_contents(storage_path('app/reporte_lecturas/' . $nomArchivoReporte)),
            $nomArchivoReporte
        )->post($urlApiReportes, [
            'num_docu' => $estudio->num_docu,
            'name' => $estudio->nom_pacien,
            'fec_naci' => $estudio->fec_naci,
            'study_iuid' => $estudio->study_iuid,
            'nom_produc' => $estudio->nom_produc,
            'fechor_lectura' => $estudio->fechor_lectura,
            'nom_sede' => $estudio->nom_sede,
            'url_oviyam' => $estudio->url_oviyam,
            'tap_oviyam' => $estudio->tap_oviyam,
        ]);

        /**
         * Actualizo el nombre y correo electronico del paciente
         */
        Paciente::updateOrCreate(
            ['num_docu' => $estudio->num_docu],
            [
                'nombres' => $estudio->nom_pacien,
                'email' => $estudio->email
            ]
        );

        /**
         * Envio de email con el adjunto del reporte de la lectura del estudio
         */
        if ($estudio->email != "") {
            $mailable = new NotificacionDeLectura($estudio, $nomArchivoReporte);
            Mail::to($estudio->email)->send($mailable);
        }

        /* if ($estudio->email_reportar != "") {
            $mailable = new ReportarLectura($estudio, $nomArchivoReporte);
            Mail::to($estudio->email_reportar)->send($mailable);
        } */

        return response()->json(['message' => 'La notificación se generó correctamente.']);
    }
}
