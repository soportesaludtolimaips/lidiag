<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Http\Requests\Configuracion\ConfigAdminSaludRequest;

class ConfigAdminSaludController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin_salud = ConfigAdminSalud::all();
        return response()->json($admin_salud);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigAdminSalud  $configAdminSalud
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigAdminSalud  $configAdminSalud
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->delete();
        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }
}
