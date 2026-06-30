<?php

namespace App\Http\Controllers\Configuracion;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigDiagnosticoRequest;
use App\Models\Configuracion\ConfigDiagnostico;
use Illuminate\Http\JsonResponse;

class ConfigDiagnosticoController extends Controller
{
    public function __construct()
    {
        // Catálogo público: solo proteger escrituras
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:config.diagnosticos.create')->only(['store']);
        $this->middleware('permission:config.diagnosticos.edit')->only(['update']);
        $this->middleware('permission:config.diagnosticos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los diagnósticos (CIE-10) ordenados alfabéticamente.
     *
     * @return JsonResponse Lista de diagnósticos.
     */
    public function index()
    {
        $diagnosticos = ConfigDiagnostico::orderBy('nom_diagnos')->get();

        return response()->json($diagnosticos);
    }

    /**
     * Crea un nuevo diagnóstico.
     *
     * @param  ConfigDiagnosticoRequest  $request  Datos del diagnóstico.
     * @param  ConfigDiagnostico  $configDiagnostico  Instancia del modelo.
     * @return JsonResponse Confirmación de creación.
     */
    public function store(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->create($request->only(['cod_diagnos', 'nom_diagnos', 'estado']));

        AuditLogger::crear($configDiagnostico, 'Diagnóstico creado');

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza un diagnóstico existente.
     *
     * @param  ConfigDiagnosticoRequest  $request  Datos actualizados.
     * @param  ConfigDiagnostico  $configDiagnostico  Instancia a actualizar.
     * @return JsonResponse Confirmación de actualización.
     */
    public function update(ConfigDiagnosticoRequest $request, ConfigDiagnostico $configDiagnostico)
    {
        $datosViejos = $configDiagnostico->toArray();
        $configDiagnostico->update($request->only(['cod_diagnos', 'nom_diagnos', 'estado']));

        AuditLogger::actualizar($configDiagnostico, $datosViejos, 'Diagnóstico actualizado');

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina un diagnóstico.
     *
     * @param  ConfigDiagnostico  $configDiagnostico  Instancia a eliminar.
     * @return JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigDiagnostico $configDiagnostico)
    {
        $configDiagnostico->delete();

        AuditLogger::eliminar($configDiagnostico, 'Diagnóstico eliminado');

        return response()->json(['message' => 'Registro eliminado crrectamente.']);
    }
}
