<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigDiagnosticoRequest;
use App\Models\Configuracion\ConfigDiagnostico;

class ConfigDiagnosticoController extends Controller
{
    /**
     * Lista todos los diagnósticos (CIE-10) ordenados alfabéticamente.
     *
     * @return \Illuminate\Http\JsonResponse Lista de diagnósticos.
     */
    public function index()
    {
        $diagnosticos = ConfigDiagnostico::orderBy('nom_diagnos')->get();
        return response()->json($diagnosticos);
    }

    /**
     * Crea un nuevo diagnóstico.
     *
     * @param  ConfigDiagnosticoRequest  $request Datos del diagnóstico.
     * @param  ConfigDiagnostico  $configDiagnostico Instancia del modelo.
     * @return \Illuminate\Http\JsonResponse Confirmación de creación.
     */
    public function store(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza un diagnóstico existente.
     *
     * @param  ConfigDiagnosticoRequest  $request Datos actualizados.
     * @param  ConfigDiagnostico  $configDiagnostico Instancia a actualizar.
     * @return \Illuminate\Http\JsonResponse Confirmación de actualización.
     */
    public function update(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina un diagnóstico.
     *
     * @param  ConfigDiagnostico  $configDiagnostico Instancia a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->delete();
        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
