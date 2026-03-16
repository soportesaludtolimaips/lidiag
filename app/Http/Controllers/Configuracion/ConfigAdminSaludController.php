<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Http\Requests\Configuracion\ConfigAdminSaludRequest;

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
     * @return \Illuminate\Http\JsonResponse Lista de administradoras de salud.
     */
    public function index()
    {
        $admin_salud = ConfigAdminSalud::all();
        return response()->json($admin_salud);
    }

    /**
     * Crea una nueva Administradora de Salud.
     *
     * @param  ConfigAdminSaludRequest  $request Datos de la administradora.
     * @param  ConfigAdminSalud  $configAdminSalud Instancia del modelo (inyección de dependencia vacía).
     * @return \Illuminate\Http\JsonResponse Confirmación de creación.
     */
    public function store(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza una Administradora de Salud.
     *
     * @param  ConfigAdminSaludRequest  $request Datos actualizados.
     * @param  ConfigAdminSalud  $configAdminSalud Instancia a actualizar.
     * @return \Illuminate\Http\JsonResponse Confirmación de actualización.
     */
    public function update(ConfigAdminSaludRequest $request, ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->update($request->all());
        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Elimina una Administradora de Salud.
     *
     * @param  ConfigAdminSalud  $configAdminSalud Instancia a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigAdminSalud $configAdminSalud)
    {
        $configAdminSalud->delete();
        return response()->json(['message' => 'Registro eliminado correctamente.']);
    }
}
