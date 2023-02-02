<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudio\EstudioAsignarRequest;
use App\Mail\NotificacionAsignacionDeLectura;
use App\Models\General\Paciente;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;
use Mail;

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
        $paciente = Paciente::firstOrCreate(
            ['num_docu' => $request->num_docu],
            [
                'num_docu' => $request->num_docu,
                'nombres' => $request->nombres,
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


        if ($request->email != "") {
            $mailable = new NotificacionAsignacionDeLectura($paciente, $estudio);
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
            'produc.cod_cups',
            'produc.nom_produc',
            'produc.fechor_lectura',
            'users.name AS quien_registro',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo'
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

    public function leer()
    {
    }
}
