<?php

namespace App\Http\Controllers\Agenda;

use App\Models\Agenda\AgendaProducto;
use Illuminate\Http\Request;

class AgendaProductoController extends Controller
{
    /**
     * Muestra el listado de productos de agenda.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Muestra el formulario de creación.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Almacena un nuevo producto de agenda.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Muestra un producto de agenda específico.
     *
     * @param  \App\Models\Agenda\AgendaProducto  $agendaProducto
     * @return \Illuminate\Http\Response
     */
    public function show(AgendaProducto $agendaProducto)
    {
        //
    }

    /**
     * Muestra el formulario de edición.
     *
     * @param  \App\Models\Agenda\AgendaProducto  $agendaProducto
     * @return \Illuminate\Http\Response
     */
    public function edit(AgendaProducto $agendaProducto)
    {
        //
    }

    /**
     * Actualiza un producto de agenda.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Agenda\AgendaProducto  $agendaProducto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgendaProducto $agendaProducto)
    {
        //
    }

    /**
     * Elimina un producto de agenda.
     *
     * @param  \App\Models\Agenda\AgendaProducto  $agendaProducto
     * @return \Illuminate\Http\Response
     */
    public function destroy(AgendaProducto $agendaProducto)
    {
        //
    }
}
