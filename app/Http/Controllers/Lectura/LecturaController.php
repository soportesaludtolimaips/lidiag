<?php

namespace App\Http\Controllers\Lectura;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lectura\LecturaAsignarRequest;
use App\Mail\NotificacionAsignacionDeLectura;
use App\Models\General\Paciente;
use App\Models\Lecturas\Lectura;
use Illuminate\Http\Request;
use Mail;

class LecturaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lecturas = Lectura::all();
        return response()->json($lecturas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LecturaAsignarRequest $request)
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

        $lectura = Lectura::firstOrCreate(
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


        if ($request->email != "") {
            $mailable = new NotificacionAsignacionDeLectura($paciente, $lectura);
            Mail::to($request->email)->send($mailable);
        }

        return response()->json(['message' => 'La asignación de la lectura se realizó correctamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturas\Lectura  $lectura
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lectura $lectura)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturas\Lectura  $lectura
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lectura $lectura)
    {
        //
    }
}
