<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Reportes\ResporteLecturaController;
use App\Mail\NotificacionDeLectura;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use App\Models\General\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EstudioNotificacionController extends Controller
{

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\Estudio  $estudio
     * @return \Illuminate\Http\Response
     */
    public function listarEstudios(Request $request)
    {
        $paciente = new Paciente();
        $query = $paciente->query();

        $query->select(
            'patient.pat_id',
            'patient.pat_name',
            'patient.pat_sex',
            'patient.pat_birthdate'
        );

        if ($request->bus_nom_num_docu) {
            $query->where('patient.pat_id', 'like', "%$request->bus_nom_num_docu%")
                ->orWhere('patient.pat_name', 'like', "%$request->bus_nom_num_docu%");
        }

        $result = $query->get();
        return json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function notificar(Request $request)
    {
        /**
         * Envio de email con el adjunto del reporte de la lectura del estudio
         */
        if ($request->email != "") {
            $mailable = new NotificacionDeLectura($reporteLectura, $nomArchivoReporte);
            Mail::to($request->email)->send($mailable);
        }
    }
}
