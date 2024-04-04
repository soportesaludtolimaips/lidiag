<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigDiagnosticoRequest;
use App\Models\Configuracion\ConfigDiagnostico;

class ConfigDiagnosticoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $diagnosticos = ConfigDiagnostico::orderBy('nom_diagnos')->get();
        return response()->json($diagnosticos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigDiagnostico  $configDiagnostico
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigDiagnostico  $configDiagnostico
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->delete();
        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
