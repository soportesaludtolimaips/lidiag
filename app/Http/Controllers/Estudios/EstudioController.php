<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudio\EstudioAsignarRequest;
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
    public function store(EstudioAsignarRequest $request)
    {


        $nombrePaciente = str_replace('^^^', '', $request->nombres);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $paciente = Paciente::updateOrCreate(
            ['num_docu' => $request->num_docu],
            [
                'num_docu' => $request->num_docu,
                'nombres' => $nombrePaciente,
                'direccion' => $request->direccion,
                'sexo' => $request->sexo,
                'fec_naci' => $request->fec_naci,
                'tel' => $request->tel,
                'email' => $request->email
            ]
        );

        $estudio = Estudio::firstOrCreate(
            ['study_id' => $request->study_id],
            [
                'study_pk' => $request->study_pk,
                'study_iuid' => $request->study_iuid,
                'study_id' => $request->study_id,
                'fec_estudio' => $request->study_datetime,
                'accession_no' => $request->accession_no,
                'study_desc' => $request->study_desc,
                'paciente_id' => $paciente->id,
                'medico_id' => $request->medico_id,
                'quien_registro_id' => 1,
                'sucursal_id' => 1,
                'prioridad_id' => $request->prioridad_id,
                'observaciones' => $request->observaciones,
                'atencion' => $request->atencion
            ]
        );


        foreach ($request->productosEstudio as $Producto) {
            EstudioProducto::create([
                'estudio_id' => $estudio->id,
                'cod_cups' => $Producto['cod_cups'],
                'nom_produc' => $Producto['nom_produc'],
            ]);
        }

        foreach ($request->diagnosticosEstudio as $Diagnostico) {
            EstudioDiagnostico::create([
                'estudio_id' => $estudio->id,
                'cod_diagnos' => $Diagnostico['cod_diagnos'],
                'nom_diagnos' => $Diagnostico['nom_diagnos'],
            ]);
        }

        $sucursalEstudio = $estudio->sucursal;
        $prioridadEstudio = $estudio->prioridad;

        if ($request->email != "") {
            $mailable = new NotificacionAsignacionDeLectura($paciente, $estudio, $prioridadEstudio, $sucursalEstudio);
            Mail::to($request->email)->send($mailable);
        }

        return response()->json(['message' => 'La asignación de la lectura se realizó correctamente.']);
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
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'sucur.sucursal as nom_sucur'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
            ->join('config_sucursales AS sucur', 'estudios.sucursal_id', '=', 'sucur.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->where('estudios.medico_id', $request->id)
            ->whereNull('produc.fechor_lectura')
            ->get();
        return response()->json($misPendiente);
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
            'pacien.num_docu as num_docu_pacien',
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
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'medi.name as medico',
            'sucur.sucursal as nom_sucur'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
            ->join('config_sucursales AS sucur', 'estudios.sucursal_id', '=', 'sucur.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->whereNull('produc.fechor_trascrito')
            ->whereNotNull('produc.fechor_lectura')
            ->get();

        return response()->json($misPendiente);
    }

    public function guardarTranscripcion(Request $request)
    {
        $usuarioActual = $request->usua_actual;

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

        $transcribir = EstudioProducto::findOrFail($request->registro['id_producto_lectura']);
        $transcribir->lectura = $request->registro['lectura'];
        $transcribir->transcriptor_id = $usuarioActual;
        $transcribir->fechor_trascrito = Carbon::now();
        $transcribir->save();

        $estudio = Estudio::findOrFail($request->id_estudio);

        $sucursalEstudio = $estudio->sucursal;
        $prioridadEstudio = $estudio->prioridad;

        if ($request->email != "") {
            $mailable = new NotificacionDeLectura($paciente, $estudio, $prioridadEstudio, $sucursalEstudio);
            Mail::to($request->email)->send($mailable);
        }

        return response()->json(['message' => 'La transcripción se guardo correctamente.']);
    }
}
