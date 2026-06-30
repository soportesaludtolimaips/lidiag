<?php

namespace App\Http\Controllers\Configuracion;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigPrioridadRequest;
use App\Models\Configuracion\ConfigPrioridad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigPrioridadController extends Controller
{
    public function __construct()
    {
        // Catálogo público: solo proteger escrituras
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:config.prioridades.create')->only(['store']);
        $this->middleware('permission:config.prioridades.edit')->only(['update']);
        $this->middleware('permission:config.prioridades.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las prioridades configuradas.
     *
     * @return JsonResponse Lista de prioridades.
     */
    public function index()
    {
        $prioridades = ConfigPrioridad::all();

        return response()->json($prioridades);
    }

    /**
     * Crea una nueva prioridad.
     *
     * @param  ConfigPrioridadRequest  $request  Datos de la prioridad.
     * @param  ConfigPrioridad  $configPrioridad  Instancia del modelo.
     * @return JsonResponse Confirmación de creación.
     */
    public function store(ConfigPrioridadRequest $request, ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->create($request->only(['nom_priori', 'nivel', 'tiempo', 'estado']));

        AuditLogger::crear($configPrioridad, 'Prioridad creada');

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza una prioridad existente.
     *
     * @param  Request  $request  Datos actualizados.
     * @param  ConfigPrioridad  $configPrioridad  Instancia a actualizar.
     * @return JsonResponse Confirmación de actualización.
     */
    public function update(Request $request, ConfigPrioridad $configPrioridad)
    {
        $datosViejos = $configPrioridad->toArray();
        $configPrioridad->update($request->only(['nom_priori', 'nivel', 'tiempo', 'estado']));

        AuditLogger::actualizar($configPrioridad, $datosViejos, 'Prioridad actualizada');

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina una prioridad.
     *
     * @param  ConfigPrioridad  $configPrioridad  Instancia a eliminar.
     * @return JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigPrioridad $configPrioridad)
    {
        $configPrioridad->delete();

        AuditLogger::eliminar($configPrioridad, 'Prioridad eliminada');

        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
