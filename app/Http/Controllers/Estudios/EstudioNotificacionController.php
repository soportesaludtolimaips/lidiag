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

        $estudios = Estudio::select(
            'pacientes.id AS paciente_id',
            'pacientes.num_docu',
            'pacientes.nombres',
            'pacientes.tel',
            'pacientes.email',
            'estudios.id AS estudio_id',
            'estudios.fec_estudio',
            'estudios_productos.id AS id_producto_lectura',
            'estudios_productos.nom_produc',
            'estudios_productos.fechor_lectura'
        )
            ->join('pacientes', 'estudios.paciente_id', 'pacientes.id')
            ->join('estudios_productos', 'estudios_productos.estudio_id', 'estudios.id')
            ->where('pacientes.num_docu', 'like', "%$request->bus_nom_num_docu%")
            ->orWhere('pacientes.nombres', 'like', "%$request->bus_nom_num_docu%")
            ->get();

        return $estudios;
    }

    public function notificarEmail(Request $request)
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
