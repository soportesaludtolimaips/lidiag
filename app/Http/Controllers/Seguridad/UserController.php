<?php

namespace App\Http\Controllers\Seguridad;

use App\Helpers\PasswordRules;
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
        // listarUsuarios/usuariosPorModulo quedan solo con 'auth': los consumen los
        // selectores globales del header (médicos/asignadores) para cualquier perfil autenticado.
        $this->middleware('permission:seguridad.usuarios.index')->only(['index']);
        $this->middleware('permission:seguridad.usuarios.create')->only(['store']);
        $this->middleware('permission:seguridad.usuarios.edit')->only(['update', 'estadoUsuario']);
        $this->middleware('permission:seguridad.usuarios.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->userService->all();

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->userService->find($id);

        return response()->json($result, $result['status']);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $result = $this->userService->create(
            $request->decoded(),
            $request->file('firma'),
            $request->file('avatar'),
        );

        return response()->json($result, $result['status']);
    }

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

    public function destroy(int $id): JsonResponse
    {
        $result = $this->userService->delete($id);

        return response()->json($result, $result['status']);
    }

    public function estadoUsuario(Request $request): JsonResponse
    {
        $result = $this->userService->toggleStatus(
            (int) $request->input('id'),
            (bool) $request->input('estado'),
        );

        return response()->json($result, $result['status']);
    }

    public function actualizarPerfil(Request $request, int $id): JsonResponse
    {
        if ((int) $id !== (int) auth()->id()) {
            abort(403, 'No autorizado para modificar este perfil');
        }

        $data = $request->validate([
            'num_docu' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => ['nullable', 'string', PasswordRules::nueva()],
            'imagen_perfil' => 'nullable|image|max:2048',
            'firma' => 'nullable|image|max:2048',
        ], PasswordRules::mensajes());

        $result = $this->userService->updateProfile(
            $id,
            $data,
            $request->file('imagen_perfil'),
            $request->file('firma'),
        );

        return response()->json($result, $result['status']);
    }

    public function listarPermisosUsuario(int $id): JsonResponse
    {
        $result = $this->userService->listPermissions($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista usuarios por rol y módulo (ej. médicos del módulo de estudios).
     *
     * @param  string  $tipoUser  Nombre del rol a filtrar (ej. "Medico").
     * @param  int  $modulo  ID del módulo al que pertenecen los usuarios.
     *
     * @returns {JsonResponse} Respuesta JSON con el listado de usuarios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function listarUsuarios(string $tipoUser, int $modulo): JsonResponse
    {
        $result = $this->userService->listByRoleAndModule($tipoUser, $modulo);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los usuarios asociados a un módulo específico.
     * Solo requiere autenticación: lo consume el selector global de usuarios del header.
     *
     * @param  int  $moduloId  ID del módulo.
     *
     * @returns {JsonResponse} Respuesta JSON con el listado de usuarios del módulo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function usuariosPorModulo(int $moduloId): JsonResponse
    {
        $result = $this->userService->findByModule($moduloId);

        return response()->json($result, $result['status']);
    }
}
