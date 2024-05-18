<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Mail\NotificacionDeLectura;
use App\Mail\ReportarLectura;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
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
            'sede.nom_sede as nom_sede',
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

    public function notificarEmail($id_producto_lectura, $num_docu, $nom_pacien, $fec_naci, $email, $email_reportar)
    {
        $reporteLectura = EstudioProducto::with('estudio.paciente')->with('estudio.medico')->with('estudio.sede')->findOrFail($id_producto_lectura);
        $nomArchivoReporte = $reporteLectura->id . "-" . $reporteLectura->estudio->paciente->num_docu . "-" . $reporteLectura->nom_produc . ".pdf";

        /**
         * Envio la lectura a Lidiag-reportes para que este diponible para la descarga por parte del paciente
         */
        $urlApiReportes = config('app.URL_API_REPORTES') . "api/almacenarLectura";

        $response = Http::timeout(60)->attach(
            'file_reporte',
            file_get_contents(storage_path('app/reporte_lecturas/' . $nomArchivoReporte)),
            $nomArchivoReporte
        )->post($urlApiReportes, [
            'num_docu' => $num_docu,
            'name' => $nom_pacien,
            'fec_naci' => $fec_naci,
            'study_iuid' => $reporteLectura->estudio->study_iuid,
            'nom_produc' => $reporteLectura->nom_produc,
            'fechor_lectura' => $reporteLectura->fechor_lectura,
            'nom_sede' => $reporteLectura->estudio->sede->nom_sede,
            'url_oviyam' => $reporteLectura->estudio->sede->url_oviyam,
            'tap_oviyam' => $reporteLectura->estudio->sede->tap_oviyam,
        ]);

        /**
         * Actualizo los datos del paciente
         */
        /* $pacienteUpdate = Paciente::where('num_docu', '=', $num_docu)->get();
        $pacienteUpdate->name = $nom_pacien;
        $pacienteUpdate->fec_naci = $fec_naci;
        $pacienteUpdate->email = $email;
        $pacienteUpdate->save(); */

        /**
         * Envio de email con el adjunto del reporte de la lectura del estudio
         */
        if ($email != "") {
            $mailable = new NotificacionDeLectura($reporteLectura, $nomArchivoReporte);
            Mail::to($email)->send($mailable);
        }

        if ($email_reportar != "") {
            $mailable = new ReportarLectura($reporteLectura, $nomArchivoReporte);
            Mail::to($email_reportar)->send($mailable);
        }

        return response()->json(['message' => 'La notificación se generó correctamente.']);
    }
}
