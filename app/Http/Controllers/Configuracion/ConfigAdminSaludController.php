<?php

namespace App\Http\Controllers\Configuracion;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigAdminSaludRequest;
use App\Models\Configuracion\ConfigAdminSalud;
use Illuminate\Http\JsonResponse;

class ConfigAdminSaludController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:config.admin.salud.create')->only(['store']);
        $this->middleware('permission:config.admin.salud.edit')->only(['update']);
        $this->middleware('permission:config.admin.salud.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las Administradoras de Salud (EPS).
     *
     * @return JsonResponse Lista de administradoras de salud.
     */
    public function index()
    {
        $admin_salud = ConfigAdminSalud::all();

        return response()->json($admin_salud);
    }

    /**
     * Crea una nueva Administradora de Salud.
     *
     * @param  ConfigAdminSaludRequest  $request  Datos de la administradora.
     * @param  ConfigAdminSalud  $configAdminSalud  Instancia del modelo (inyección de dependencia vacía).
     * @return JsonResponse Confirmación de creación.
     */
    public function store(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->create($request->only(['cod_admin_salud', 'nit_admin_salud', 'nom_admin_salud', 'estado']));

        AuditLogger::crear($configAdminSalud, 'Administradora de Salud creada');

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza una Administradora de Salud.
     *
     * @param  ConfigAdminSaludRequest  $request  Datos actualizados.
     * @param  ConfigAdminSalud  $configAdminSalud  Instancia a actualizar.
     * @return JsonResponse Confirmación de actualización.
     */
    public function update(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $datosViejos = $configAdminSalud->toArray();
        $configAdminSalud->update($request->only(['cod_admin_salud', 'nit_admin_salud', 'nom_admin_salud', 'estado']));

        AuditLogger::actualizar($configAdminSalud, $datosViejos, 'Administradora de Salud actualizada');

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina una Administradora de Salud.
     *
     * @param  ConfigAdminSalud  $configAdminSalud  Instancia a eliminar.
     * @return JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->delete();

        AuditLogger::eliminar($configAdminSalud, 'Administradora de Salud eliminada');

        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }
}
