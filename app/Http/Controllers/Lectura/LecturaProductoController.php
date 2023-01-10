<?php

namespace App\Http\Controllers\Lectura;

use App\Http\Controllers\Controller;
use App\Models\Lecturas\LecturaProducto;
use Illuminate\Http\Request;

class LecturaProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idLectura = $request->id;
        $productos = LecturaProducto::where('lectua_id', $idLectura);
        return response()->json(array('productos' => $productos));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, LecturaProducto $lecturaProducto)
    {
        $lecturaProducto->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lecturas\LecturaProducto  $LecturaProducto
     * @return \Illuminate\Http\Response
     */
    public function show(LecturaProducto $LecturaProducto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lecturas\LecturaProducto  $LecturaProducto
     * @return \Illuminate\Http\Response
     */
    public function edit(LecturaProducto $LecturaProducto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturas\LecturaProducto  $LecturaProducto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LecturaProducto $LecturaProducto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturas\LecturaProducto  $LecturaProducto
     * @return \Illuminate\Http\Response
     */
    public function destroy(LecturaProducto $LecturaProducto)
    {
        //
    }
}
