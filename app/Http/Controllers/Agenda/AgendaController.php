<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agenda\Agenda;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Muestra el listado de agendas.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Almacena una nueva agenda.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $request;
    }

    /**
     * Muestra una agenda específica.
     *
     * @param  \App\Models\Agenda\Agenda  $agenda
     * @return \Illuminate\Http\Response
     */
    public function show(Agenda $agenda)
    {
        //
    }

    /**
     * Muestra el formulario de edición.
     *
     * @param  \App\Models\Agenda\Agenda  $agenda
     * @return \Illuminate\Http\Response
     */
    public function edit(Agenda $agenda)
    {
        //
    }

    /**
     * Actualiza una agenda.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Agenda\Agenda  $agenda
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Agenda $agenda)
    {
        //
    }

    /**
     * Elimina una agenda.
     *
     * @param  \App\Models\Agenda\Agenda  $agenda
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agenda $agenda)
    {
        //
    }
}
