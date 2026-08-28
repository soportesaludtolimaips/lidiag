<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreUserRequest;
use App\Http\Requests\Seguridad\UpdateUserRequest;
use App\Services\Seguridad\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.usuarios.index')->only(['index', 'usuariosPorModulo']);
        $this->middleware('permission:seguridad.usuarios.create')->only(['store']);
        $this->middleware('permission:seguridad.usuarios.edit')->only(['update', 'estadoUsuario']);
        $this->middleware('permission:seguridad.usuarios.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los usuarios con sus relaciones.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function index(): JsonResponse
    {
        $result = $this->userService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de un usuario específico.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->userService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo usuario con su firma y avatar.
     *
     * @param  StoreUserRequest  $request  Solicitud validada con los datos del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $result = $this->userService->create(
            $request->decoded(),
            $request->file('firma'),
            $request->file('avatar'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza los datos de un usuario existente.
     *
     * @param  UpdateUserRequest  $request  Solicitud validada con los datos del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $result = $this->userService->update(
            $request->targetId(),
            $request->decoded(),
            $request->file('firma'),
            $request->file('avatar'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un usuario existente.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->userService->delete($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Activa o inactiva el estado de un usuario.
     *
     * @param  Request  $request  Solicitud HTTP con id y estado.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function estadoUsuario(Request $request): JsonResponse
    {
        $result = $this->userService->toggleStatus(
            (int) $request->input('id'),
            (bool) $request->input('estado'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza el perfil propio del usuario autenticado.
     *
     * @param  Request  $request  Solicitud HTTP con los datos del perfil.
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function actualizarPerfil(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'num_docu' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:6',
            'imagen_perfil' => 'nullable|image|max:2048',
            'firma' => 'nullable|image|max:2048',
        ]);

        $result = $this->userService->updateProfile(
            $id,
            $data,
            $request->file('imagen_perfil'),
            $request->file('firma'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los roles y permisos de un usuario.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function listarPermisosUsuario(int $id): JsonResponse
    {
        $result = $this->userService->listPermissions($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los usuarios filtrados por rol y módulo (ej. médicos de ecografías).
     *
     * @param  string  $tipoUser  Nombre del rol o tipo de usuario.
     * @param  int  $modulo  Identificador del módulo.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function listarUsuarios(string $tipoUser, int $modulo): JsonResponse
    {
        $result = $this->userService->listByRoleAndModule($tipoUser, $modulo);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los usuarios asociados a un módulo específico.
     *
     * @param  int  $moduloId  Identificador del módulo.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function usuariosPorModulo(int $moduloId): JsonResponse
    {
        $result = $this->userService->findByModule($moduloId);

        return response()->json($result, $result['status']);
    }
}
