<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigSedeRequest;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Http\Request;

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
        return response()->json($sedes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigSedeRequest $request, ConfigSede $configsede)
    {
        $configsede->create($request->all());
        return response()->json(['message' => 'El registro se creo correctamente']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigSede  $configSede
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigSedeRequest $request)
    {
        $configsede = ConfigSede::find($request->id);
        $configsede->nom_sede = $request->nom_sede;
        $configsede->ip_dcm = $request->ip_dcm;
        $configsede->bd_dcm = $request->bd_dcm;
        $configsede->usuario_dcm = $request->usuario_dcm;
        $configsede->password_dcm = $request->password_dcm;
        $configsede->usuario_oviyam = $request->usuario_oviyam;
        $configsede->password_oviyam = $request->password_oviyam;
        $configsede->url_oviyam = $request->url_oviyam;
        $configsede->estado = $request->estado;
        $configsede->update();

        return response()->json(['message' => 'El registro se actializo correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigSede  $configsede
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigSede $configsucursal)
    {
        $configsucursal->delete();
        return response()->json(['message' => 'El registro se elimino correctamente.']);
    }
}
