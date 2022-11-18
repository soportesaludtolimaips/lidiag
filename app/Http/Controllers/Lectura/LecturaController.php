<?php

namespace App\Http\Controllers\Lectura;

use App\Http\Controllers\Controller;
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $paciente = Paciente::firstOrCreate(
            ['num_docu' => $request->num_docu],
            ['num_docu' => $request->num_docu, 'nombres' => $request->nombres, 'direccion' => $request->direccion, 'sexo' => $request->sexo, 'fec_naci' => $request->fec_naci, 'tel' => $request->tel]
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

        $mailable = new NotificacionAsignacionDeLectura($paciente, $lectura);
        Mail::to('jhonlozano2000@gmail.com')->send($mailable);
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
