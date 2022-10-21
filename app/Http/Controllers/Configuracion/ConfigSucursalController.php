<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigSucursalRequest;
use App\Models\Configuracion\ConfigSucursal;

class ConfigSedeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sedes = ConfigSede::all();
        return $sedes;
        return response()->json($sedes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigSedeRequest $request, ConfigSede  $configSede)
    {
        $configSede->create($request->all());
        return response()->json(['message' => 'El registro se creo correctamente']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigSede  $configSede
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigSedeRequest $request, ConfigSede $configSede)
    {
        $configSede->update($request->all());
        return response()->json(['message' => 'El registro se actializo correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigSede  $configSede
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigSede $configSede)
    {
        $configSede->delete();
        return response()->json(['message' => 'El registro se elimino correctamente.']);
    }
}
