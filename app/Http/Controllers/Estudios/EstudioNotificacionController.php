<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Mail\NotificacionDeLectura;
use App\Mail\ReportarLectura;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use Illuminate\Http\Request;
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
        $estudios = new Estudio();
        $query = $estudios->query();

        $query->select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.direccion',
            'pacien.email',
            'pacien.tel',
            'estudios_productos.cod_cups',
            'estudios_productos.nom_produc',
            'estudios_productos.fechor_lectura',
            'estudios_productos.fechor_trascrito',
            'estudios_productos.lectura',
            'medi.name as medico',
            'medi.reg_med',
            'medi.num_docu',
            'medi.firma',
            'sede.nom_sede',
            'sede.url_oviyam',
            'sede.tap_oviyam',
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_productos', 'estudios_productos.producto_id', '=', 'config_productos.id')
            ->whereNotNull('estudios_productos.transcriptor_id');

        if ($request->bus_num_docu != "") {
            $query->where('pacien.num_docu', 'like', "%$request->bus_num_docu%");
        }

        if ($request->bus_nom != "") {
            $query->where('pacien.nombres', 'like', "%$request->bus_nom%");
        }

        return $query->get();
    }

    public function notificarEmail($estudio)
    {
        $nombrePaciente = str_replace('^^^', '', $estudio->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $nomArchivoReporte = $estudio->num_docu . "-" . $nombrePaciente . ".pdf";

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

        if ($estudio->email_reportar != "") {
            $mailable = new ReportarLectura($estudio, $nomArchivoReporte);
            Mail::to($estudio->email_reportar)->send($mailable);
        }

        return response()->json(['message' => 'La notificación se generó correctamente.']);
    }
}
