<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigPrioridadRequest;
use App\Models\Configuracion\ConfigPrioridad;
use Illuminate\Http\Request;

class ConfigPrioridadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $prioridades = ConfigPrioridad::all();
        return response()->json($prioridades);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ConfigPrioridadRequest $request, ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigPrioridad  $configPrioridad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigPrioridad  $configPrioridad
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->delete();
        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
