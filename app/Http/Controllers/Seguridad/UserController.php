<?php

namespace App\Http\Controllers\Seguridad;

use App\Helpers\PasswordRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\StoreUserRequest;
use App\Http\Requests\Seguridad\UpdateUserRequest;
use App\Services\Seguridad\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Inyecta el servicio de usuarios y configura los middlewares de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Lista todos los usuarios registrados en el sistema.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
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
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->userService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo usuario con su firma y avatar opcionales.
     *
     * @param  StoreUserRequest  $request  Solicitud validada con los datos del usuario y archivos adjuntos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->decoded();

        $this->validarPasswordUsuario($data, requerida: true);

        $result = $this->userService->create(
            $data,
            $request->file('firma'),
            $request->file('avatar'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza los datos de un usuario existente.
     *
     * @param  UpdateUserRequest  $request  Solicitud validada con los datos a actualizar y archivos adjuntos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $data = $request->decoded();

        $this->validarPasswordUsuario($data, requerida: false);

        $result = $this->userService->update(
            $request->targetId(),
            $data,
            $request->file('firma'),
            $request->file('avatar'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Valida la política de contraseñas de los datos decodificados del modal
     * de usuarios (crear/editar): longitud 8+, mayúscula, minúscula, número,
     * símbolo y confirmación coincidente.
     *
     * @param  array  $data  datos decodificados del campo `registro`
     * @param  bool  $requerida  true al crear, false al editar (opcional)
     *
     * @throws ValidationException
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-29
     */
    private function validarPasswordUsuario(array $data, bool $requerida): void
    {
        Validator::make($data, [
            'password' => [$requerida ? 'required' : 'nullable', 'string', PasswordRules::nueva()],
            'confirmarPassword' => [$requerida ? 'required' : 'required_with:password', 'string', 'same:password'],
        ], PasswordRules::mensajes() + [
            'password.required' => 'La contraseña es obligatoria.',
            'confirmarPassword.required' => 'Debe confirmar la contraseña.',
            'confirmarPassword.required_with' => 'Debe confirmar la contraseña.',
            'confirmarPassword.same' => 'La confirmación de la contraseña no coincide.',
        ])->validate();
    }

    /**
     * Elimina un usuario del sistema.
     *
     * @param  int  $id  Identificador del usuario a eliminar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->userService->delete($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Cambia el estado (activo/inactivo) de un usuario.
     *
     * @param  Request  $request  Solicitud HTTP con los campos id y estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
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
     * Actualiza el perfil del usuario autenticado (datos personales, contraseña e imagen).
     *
     * @param  Request  $request  Solicitud validada con los datos del perfil y archivos adjuntos.
     * @param  int  $id  Identificador del usuario; debe coincidir con el usuario autenticado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function actualizarPerfil(Request $request, int $id): JsonResponse
    {
        $id = (int) $id;
        if ($id !== (int) auth()->id()) {
            abort(403, 'No autorizado para modificar este perfil');
        }

        $data = $request->validate([
            'num_docu' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,'.$id,
            'password' => ['nullable', 'string', PasswordRules::nueva()],
            'current_password' => 'required_with:password|string|max:255',
            'imagen_perfil' => 'nullable|image|max:2048',
            'firma' => 'nullable|image|max:2048',
        ], PasswordRules::mensajes() + [
            'current_password.required_with' => 'La contraseña actual es obligatoria para cambiar la contraseña.',
            'email.unique' => 'El email ya está registrado por otro usuario.',
            'name.max' => 'El nombre no debe exceder 50 caracteres.',
            'num_docu.max' => 'El número de documento no debe exceder 20 caracteres.',
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
     * Lista los permisos asignados a un usuario específico.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
