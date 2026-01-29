<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigPrioridadRequest;
use App\Models\Configuracion\ConfigPrioridad;
use Illuminate\Http\Request;

class ConfigPrioridadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Permitir lectura de catálogos a quienes asignan/leen/transcriben estudios (sin abrir CRUD completo)
        $this->middleware('permission:config.prioridades.index|estudio.asignar|estudio.transcribir|estudio.leerEstudio')->only(['index']);
        $this->middleware('permission:config.prioridades.create')->only(['store']);
        $this->middleware('permission:config.prioridades.edit')->only(['update']);
        $this->middleware('permission:config.prioridades.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las prioridades configuradas.
     *
     * @return \Illuminate\Http\JsonResponse Lista de prioridades.
     */
    public function index()
    {
        $prioridades = ConfigPrioridad::all();
        return response()->json($prioridades);
    }

    /**
     * Crea una nueva prioridad.
     *
     * @param  ConfigPrioridadRequest  $request Datos de la prioridad.
     * @param  ConfigPrioridad  $configPrioridad Instancia del modelo.
     * @return \Illuminate\Http\JsonResponse Confirmación de creación.
     */
    public function store(ConfigPrioridadRequest $request, ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza una prioridad existente.
     *
     * @param  Request  $request Datos actualizados.
     * @param  ConfigPrioridad  $configPrioridad Instancia a actualizar.
     * @return \Illuminate\Http\JsonResponse Confirmación de actualización.
     */
    public function update(Request $request, ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina una prioridad.
     *
     * @param  ConfigPrioridad  $configPrioridad Instancia a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->delete();
        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
