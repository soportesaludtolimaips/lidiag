<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\RolActualizarRequest;
use App\Http\Requests\Seguridad\RolAgregarRequest;
use App\Services\Seguridad\RolService;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    /**
     * Inyecta el servicio de roles y configura los middlewares de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly RolService $rolService,
    ) {
        $this->middleware("auth");
        $this->middleware("permission:seguridad.roles.index")->only(["index"]);
        $this->middleware("permission:seguridad.roles.create")->only(["store"]);
        $this->middleware("permission:seguridad.roles.show")->only(["show"]);
        $this->middleware("permission:seguridad.roles.edit")->only(["update", "estadoRol"]);
        $this->middleware("permission:seguridad.roles.destroy")->only(["destroy"]);
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  void  $args  Sin parámetros.
     * @return JsonResponse Listado de roles con el catálogo de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function index(): JsonResponse
    {
        $result = $this->rolService->all();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  RolAgregarRequest  $request  Datos validados del nuevo rol.
     * @return JsonResponse Resultado de la creación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function store(RolAgregarRequest $request): JsonResponse
    {
        $result = $this->rolService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     * @return JsonResponse Rol, catálogo de permisos y permisos asignados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->rolService->find($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza los datos de un rol y sus permisos asociados.
     *
     * @param  RolActualizarRequest  $request  Cuerpo validado con `registro` (name y permisosRol).
     * @param  int  $id  Identificador del rol.
     * @return JsonResponse Resultado de la actualización.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(RolActualizarRequest $request, int $id): JsonResponse
    {
        $result = $this->rolService->update($id, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     * @return JsonResponse Resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->rolService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     * @return JsonResponse Nuevo estado del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function estadoRol(int $id): JsonResponse
    {
        $result = $this->rolService->toggleEstado($id);

        return response()->json($result, (int) $result["status"]);
    }
}
