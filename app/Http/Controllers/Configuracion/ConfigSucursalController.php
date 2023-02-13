<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigSucursalRequest;
use App\Models\Configuracion\ConfigSucursale;

class ConfigSucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sedes = ConfigSucursale::all();
        return response()->json($sedes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigSucursalRequest $request, ConfigSucursale  $configsucursal)
    {
        $configsucursal->create($request->all());
        return response()->json(['message' => 'El registro se creo correctamente']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigSede  $configSede
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigSucursalRequest $request, ConfigSucursale $configsucursal)
    {
        $configsucursal->update($request->all());
        return response()->json(['message' => 'El registro se actializo correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigSucursal  $configsucursal
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigSucursale $configsucursal)
    {
        $configsucursal->delete();
        return response()->json(['message' => 'El registro se elimino correctamente.']);
    }
}
