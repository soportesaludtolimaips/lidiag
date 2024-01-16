<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Interface\SAHIController;
use App\Http\Controllers\Interface\SAHITranscibirController;
use App\Http\Requests\Estudio\estudioLeerRequest;
use App\Mail\NotificacionAsignacionDeLectura;
use App\Mail\NotificacionDeLectura;
use App\Models\General\Paciente;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Reportes\ResporteLecturaController;
use App\Http\Requests\Estudio\estudioAsignarRequest;
use App\Mail\ReportarLectura;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioSoportesHC;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Str;

class EstudioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $estudios = Estudio::all();
        return response()->json($estudios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $registro = json_decode($request->registro);

        $nombrePaciente = str_replace('^^^', '', $registro->nombres);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $paciente = Paciente::updateOrCreate(
            ['num_docu' => $registro->num_docu],
            [
                'num_docu' => $registro->num_docu,
                'nombres' => $nombrePaciente,
                'direccion' => $registro->direccion,
                'sexo' => $registro->sexo,
                'fec_naci' => $registro->fec_naci,
                'tel' => $registro->telefono,
                'email' => $registro->email
            ]
        );

        /**
         * Si la sede tien interface relleno la informacion necesaria con los datos de la Eps, Plan y contrato
         */
        $eps = "";
        $contrato = "";
        $plan = "";
        $servicio = "";

        $sede = ConfigSede::findOrfail($registro->sede_id)->all();

        if ($sede[0]->interface_software == 'SAHI') {
            $buscarDatos = (new SAHIController)->buscarEpsPlanContrato($registro->atencion_id);

            $eps = $buscarDatos[0]->NomTercero;
            $contrato = $buscarDatos[0]->DesContrato;
            $plan = $buscarDatos[0]->DesPlan;
            $servicio = $buscarDatos[0]->NomUbicacion;
        }

        $estudio = Estudio::firstOrCreate(
            ['study_id' => $registro->study_pk],
            [
                'study_pk' => $registro->study_pk,
                'study_iuid' => $registro->study_iuid,
                'study_id' => $registro->study_id,
                'fec_estudio' => $registro->study_datetime,
                'accession_no' => $registro->accession_no,
                'study_desc' => $registro->study_desc,
                'mods_in_study' => $registro->mods_in_study,
                //'email_reportar' => $registro->email_reportar,
                'paciente_id' => $paciente->id,
                'medico_id' => $registro->medico_id,
                'quien_registro_id' => auth()->user()->id,
                'sede_id' => $registro->sede_id,
                'prioridad_id' => $registro->prioridad_id,
                'observaciones' => $registro->observaciones,
                'atencion' => $registro->atencion_id,
                'servicio' => $servicio,
                'eps' => $eps,
                'contrato' => $contrato,
                'plan' => $plan,
            ]
        );

        /**
         * Elimino los productos existentes para almacenar los nuevos
         */
        $productos = EstudioProducto::where('estudio_id', '=', $estudio->id);
        $productos->delete();
        foreach ($registro->productosEstudio as $Producto) {
            if ($Producto->checked == true) {
                EstudioProducto::create([
                    'estudio_id' => $estudio->id,
                    'cod_cups' => $Producto->cod_cups,
                    'nom_produc' => $Producto->nom_produc,
                ]);
            }
        }

        /**
         * Elimino los diagnosticos existentes para almacenar los nuevos
         */
        $diagnosticos = EstudioDiagnostico::where('estudio_id', '=', $estudio->id);
        $diagnosticos->delete();
        foreach ($registro->diagnosticosEstudio as $Diagnostico) {
            EstudioDiagnostico::create([
                'estudio_id' => $estudio->id,
                'cod_diagnos' => $Diagnostico->cod_diagnos,
                'nom_diagnos' => $Diagnostico->nom_diagnos,
            ]);
        }

        /**
         * Almacenos los soportes de la HC
         */
        if ($request->hasFile('archivo1')) {
            $archivo = $request->archivo1;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();
            $nombreOriginal = $archivo->getClientOriginalName();

            $archivoSoporteHC = new EstudioSoportesHC();
            $archivoSoporteHC->estudio_id = $estudio->id;
            $archivoSoporteHC->archivo_original = $nombreOriginal;
            $archivoSoporteHC->archivo_encrip = $nombreEncrip;
            $archivoSoporteHC->save();

            Storage::putFileAs('soportes_hc', $archivo, $nombreEncrip);
        }

        if ($request->hasFile('archivo2')) {
            $archivo = $request->archivo2;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();
            $nombreOriginal = $archivo->getClientOriginalName();

            $archivoSoporteHC = new EstudioSoportesHC();
            $archivoSoporteHC->estudio_id = $estudio->id;
            $archivoSoporteHC->archivo_original = $nombreOriginal;
            $archivoSoporteHC->archivo_encrip = $nombreEncrip;
            $archivoSoporteHC->save();

            Storage::putFileAs('soportes_hc', $archivo, $nombreEncrip);
        }

        if ($request->hasFile('archivo3')) {
            $archivo = $request->archivo3;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();
            $nombreOriginal = $archivo->getClientOriginalName();

            $archivoSoporteHC = new EstudioSoportesHC();
            $archivoSoporteHC->estudio_id = $estudio->id;
            $archivoSoporteHC->archivo_original = $nombreOriginal;
            $archivoSoporteHC->archivo_encrip = $nombreEncrip;
            $archivoSoporteHC->save();

            Storage::putFileAs('soportes_hc', $archivo, $nombreEncrip);
        }

        if ($request->email != "") {
            $mailable = new NotificacionAsignacionDeLectura($paciente, $estudio);
            Mail::to($request->email)->send($mailable);
        }

        return response()->json(['message' => 'La asignación de la lectura se realizó correctamente.']);
    }

    public function uploadSoportesHC(Request $request)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Estudios\Estudio  $estudio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Estudio $estudio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\Estudio  $estudio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Estudio $estudio)
    {
        //
    }

    public function listarPendientesMedico(Request $request, Estudio $estudio)
    {
        $misPendiente = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'produc.id as id_producto_lectura',
            'produc.cod_cups',
            'produc.nom_produc',
            'produc.fechor_lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede',
            'sesde.url_oviyam',
            'sesde.tap_oviyam'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->where('estudios.medico_id', $request->id)
            ->whereNull('produc.fechor_lectura')
            ->orderBy('priori.nom_priori')->get();

        return response()->json($misPendiente, 200);
    }

    public function listarDiagnosticoPorEstudio(Request $request)
    {
        $diagnosticos = EstudioDiagnostico::where('estudio_id', '=', $request->id)->get();
        return response()->json($diagnosticos);
    }

    public function leerEstudio(estudioLeerRequest $request, EstudioProducto $estudioProducto)
    {
        $productoLectura = EstudioProducto::findOrFail($request->id_producto_lectura);
        $productoLectura->lectura = $request->lectura;
        $productoLectura->fechor_lectura = Carbon::now();
        $productoLectura->save();

        return response()->json(['message' => 'La lectura se realizó correctamente.']);
    }

    public function listarPendientesTrascribir(Request $request)
    {
        $misPendiente = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.direccion',
            'pacien.email',
            'pacien.tel',
            'produc.id as id_producto_lectura',
            'produc.cod_cups',
            'produc.nom_produc',
            'produc.fechor_lectura',
            'produc.lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'medi.name as medico',
            'sede.nom_sede as nom_sede',
            'sede.url_oviyam',
            'sede.tap_oviyam'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->whereNull('produc.fechor_trascrito')
            ->whereNotNull('produc.fechor_lectura')
            ->get();
        return $misPendiente;
        return response()->json($misPendiente);
    }

    public function guardarTranscripcion(Request $request)
    {
        /**
         * Actualizo los datos del paciente
         */
        $paciente = Paciente::updateOrCreate(
            ['num_docu' => $request->num_docu],
            [
                'num_docu' => $request->num_docu,
                'nombres' => $request->nom_pacien,
                'direccion' => $request->direccion,
                'sexo' => $request->sexo,
                'fec_naci' => $request->fec_naci,
                'tel' => $request->tel,
                'email' => $request->email
            ]
        );


        /**
         * Actualizo la lectura del estudio
         */
        $transcribirEstudios = EstudioProducto::findOrFail($request->id_producto_lectura);

        /*
        $transcribirEstudios->lectura = $request->lectura;
        $transcribirEstudios->transcriptor_id = auth()->user()->id;
        $transcribirEstudios->fechor_trascrito = Carbon::now();
        $transcribirEstudios->save(); */

        /**
         * Si la sede donde se realizó el estidoi tiene interface hago el envio del reporte a la HC del hospital
         */
        $sede = ConfigSede::findOrfail($request->sede_id);
        if ($sede->interface_software === 'SAHI') {

            $transcribir = (new SAHITranscibirController())->enviarEstudio($request->atencion, $request->medico_id, $request->lectura, $request->cod_cups);
            /* $transcribir = (new SAHITranscibirController());
            $transcribir->set_idAtencion($request->atencion);
            $transcribir->set_idMedico($request->medico_id);
            $transcribir->set_reporteEstudio($request->lectura);
            $transcribir->set_codCups($request->cod_cups);
            $transcribir->enviarEstudio($request->atencion, $request->medico_id, $request->lectura, $request->cod_cups); */
            return $transcribir;

            return $transcribir->pr($transcribir->getMessages()[1]);
            if ($transcribir->pr($transcribir->getMessages()[1] == true)) {
                return "Todo bien";
            }
            /* $transcribir->set_idAtencion(1);
            $transcribir->set_idMedico($request->medico_id); */
            //$transcribir->enviarEstudio($request->atencion_id, $request->medico_id);

            return $transcribir;
        }

        return "Ok";

        /**
         * Genero el PDF del reporte de la lectura del estudio
         */
        $reporteLectura = EstudioProducto::with('estudio.paciente')->with('estudio.medico')->with('estudio.sede')->findOrFail($request->id_producto_lectura);
        $nomArchivoReporte = $reporteLectura->id . "-" . $reporteLectura->estudio->paciente->num_docu . "-" . $reporteLectura->nom_produc . ".pdf";
        $generarReporte = new ResporteLecturaController($reporteLectura, $nomArchivoReporte);
        $generarReporte->generar_reporte();

        /**
         * Envio la lectura a Lidiag-reportes para que este diponible para la descarga por parte del paciente
         */
        $urlApiReportes = config('app.URL_API_REPORTES') . "api/almacenarLectura";

        $response = Http::attach(
            'file_reporte',
            file_get_contents(storage_path('app/reporte_lecturas/' . $nomArchivoReporte)),
            $nomArchivoReporte
        )->post($urlApiReportes, [
            'num_docu' => $request->num_docu,
            'name' => $request->nom_pacien,
            'fec_naci' => $request->fec_naci,
            'study_iuid' => $reporteLectura->estudio->study_iuid,
            'nom_produc' => $reporteLectura->nom_produc,
            'fechor_lectura' => $reporteLectura->fechor_lectura,
            'nom_sede' => $reporteLectura->estudio->sede->nom_sede,
            'url_oviyam' => $reporteLectura->estudio->sede->url_oviyam,
            'tap_oviyam' => $reporteLectura->estudio->sede->tap_oviyam,
        ]);

        /**
         * Envio de email con el adjunto del reporte de la lectura del estudio
         */
        if ($request->email != "") {
            $mailable = new NotificacionDeLectura($reporteLectura, $nomArchivoReporte);
            Mail::to($request->email)->send($mailable);
        }

        if ($request->email_reportar != "") {
            $mailable = new ReportarLectura($reporteLectura, $nomArchivoReporte);
            Mail::to($request->email_reportar)->send($mailable);
        }

        return response()->json(['message' => 'La transcripción se guardo correctamente.']);
    }

    public function uploadAudio(Request $request)
    {
        $audio = $request->file('audio');
        return $audio;
        $path = $audio->store('audio', 'public');
        return $path;

        // Puedes almacenar el $path en la base de datos o realizar otras acciones necesarias.

        return response()->json(['message' => 'Audio subido con éxito']);
    }
}
