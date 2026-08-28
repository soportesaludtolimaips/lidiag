<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\RolAgregarRequest;
use App\Services\Seguridad\RolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    /**
     * Construye el controlador e inicializa el servicio de roles y los permisos por acción.
     *
     * @param  RolService  $rolService  Servicio encargado de la lógica de gestión de roles.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly RolService $rolService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.roles.index')->only(['index']);
        $this->middleware('permission:seguridad.roles.create')->only(['store']);
        $this->middleware('permission:seguridad.roles.show')->only(['show']);
        $this->middleware('permission:seguridad.roles.edit')->only(['update']);
        $this->middleware('permission:seguridad.roles.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los roles junto con el catálogo de permisos disponibles.
     *
     * @returns JsonResponse Respuesta JSON con los roles y los permisos del sistema.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->rolService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo rol con los permisos seleccionados validados en la petición.
     *
     * @param  RolAgregarRequest  $request  Petición validada con el nombre y permisos del rol a crear.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la creación del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(RolAgregarRequest $request): JsonResponse
    {
        $result = $this->rolService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra un rol específico junto con sus permisos asociados.
     *
     * @param  mixed  $id  Identificador del rol a consultar.
     *
     * @returns JsonResponse Respuesta JSON con el rol y sus permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function show($id): JsonResponse
    {
        $result = $this->rolService->find((int) $id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza el nombre y permisos de un rol existente.
     *
     * @param  Request  $request  Petición HTTP con los datos validados del rol a actualizar.
     * @param  mixed  $id  Identificador del rol a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la actualización del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'registro' => 'required|array',
            'registro.name' => 'required|string|min:2|max:100',
            'registro.permisosRol' => 'nullable|array',
            'registro.permisosRol.*' => 'nullable|array',
            'registro.permisosRol.*.id' => 'required_with:registro.permisosRol.*|integer',
            'registro.permisosRol.*.name' => 'required_with:registro.permisosRol.*|string',
            'registro.permisosRol.*.checked' => 'required_with:registro.permisosRol.*|boolean',
        ]);

        $result = $this->rolService->update((int) $id, $data);

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un rol siempre que no esté asignado a ningún usuario.
     *
     * @param  mixed  $id  Identificador del rol a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy($id): JsonResponse
    {
        $result = $this->rolService->delete((int) $id);

        return response()->json($result, $result['status']);
    }

    /**
     * Activa o desactiva el estado de un rol (alterna activo/inactivo).
     *
     * @param  mixed  $id  Identificador del rol cuyo estado se va a alternar.
     *
     * @returns JsonResponse Respuesta JSON con el nuevo estado del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function estadoRol($id): JsonResponse
    {
        $result = $this->rolService->toggleEstado((int) $id);

        return response()->json($result, $result['status']);
    }
}
