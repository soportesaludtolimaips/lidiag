<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudios\EstudioProducto;
use Illuminate\Http\Request;

class EstudioProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idEstudio = $request->id;
        $productos = EstudioProducto::where('lectua_id', $idEstudio);
        return response()->json(array('productos' => $productos));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, EstudioProducto $estudioProducto)
    {
        $estudioProducto->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EstudioProducto $estudioProducto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return \Illuminate\Http\Response
     */
    public function destroy(EstudioProducto $estudioProducto)
    {
        //
    }
}
