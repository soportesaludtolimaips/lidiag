<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigProductoRequest;
use App\Models\Configuracion\ConfigProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $producto = ConfigProducto::all();
        return response()->json($producto);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $configProducto->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigProducto  $configProducto
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $configProducto->update($request->all());
        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigProducto  $configProducto
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigProducto $configProducto)
    {
        $configProducto->delete();
        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }
}
