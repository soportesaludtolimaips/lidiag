<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigModulo;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Str;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.usuarios.index')->only(['index', 'usuariosPorModulo']);
        $this->middleware('permission:seguridad.usuarios.create')->only(['store']);
        $this->middleware('permission:seguridad.usuarios.edit')->only(['update', 'estadoUsuario']);
        $this->middleware('permission:seguridad.usuarios.destroy')->only(['destroy']);
        // show/actualizarPerfil/listarPermisosUsuario se controlan con reglas adicionales (self vs admin)
    }

    /**
     * Lista todos los usuarios con sus módulos y roles.
     *
     * @param  Request  $request  Parámetros de filtrado (no utilizados actualmente).
     * @return JsonResponse Lista de usuarios.
     */
    public function index(Request $request)
    {
        $users = User::with(['modulos', 'roles', 'sedes', 'serviciosCita'])->get();

        return response()->json($users);
    }

    /**
     * Lista usuarios filtrados por tipo de rol y módulo.
     *
     * @param  string  $tipo_user  Nombre del rol (ej: 'Médico').
     * @param  int  $modulo  ID del módulo.
     * @return Collection Lista de usuarios.
     */
    public function listarUsuarios($tipo_user, $modulo)
    {

        $usuarios = User::select('users.id', 'users.name')
            ->whereHas('roles', function ($query) use ($tipo_user) {
                $query->where('name', $tipo_user); // Filtrar por el rol (por ejemplo, "Médico")
            })
            ->whereHas('modulos', function ($query) use ($modulo) {
                $query->where('config_modulos.id', $modulo); // Calificar la columna 'id' con el nombre de la tabla
            })
            ->orderBy('users.name', 'asc') // Calificar la columna 'name' con el nombre de la tabla
            ->get();

        return response()->json($usuarios);
    }

    /**
     * Muestra un usuario específico con sus roles.
     *
     * @param  int  $id  ID del usuario.
     * @return JsonResponse Datos del usuario, roles disponibles y roles asignados.
     */
    public function show($id)
    {
        $usuario = User::find($id);
        if (! $usuario) {
            return response()->json(['message' => 'El usuario no se encontro'], 404);
        }

        $authId = Auth::id();
        $isSelf = $authId && ((int) $authId === (int) $id);
        if (! $isSelf && ! Auth::user()->can('seguridad.usuarios.show')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $roles = Role::all();
        $userRole = $usuario->roles;
        $usuario->load(['sedes', 'serviciosCita']);

        $RolesDeUsuario = [];
        foreach ($userRole as $del) {
            $RolesDeUsuario[] = $del->id;
        }

        $SedesDeUsuario = $usuario->sedes->pluck('id')->values()->toArray();
        $ServiciosDeUsuario = $usuario->serviciosCita->pluck('id')->values()->toArray();

        return response()->json([
            'usuario' => $usuario,
            'roles' => $roles,
            'RolesDeUsuario' => $RolesDeUsuario,
            'SedesDeUsuario' => $SedesDeUsuario,
            'ServiciosDeUsuario' => $ServiciosDeUsuario,
        ]);
    }

    /**
     * Actualiza el estado activo/inactivo de un usuario.
     *
     * @param  Request  $request  ID y nuevo estado del usuario.
     * @return JsonResponse Confirmación.
     */
    public function estadoUsuario(Request $request, $idUsua = null)
    {
        $id = $idUsua ?? $request->id;
        $validator = Validator::make($request->all(), [
            'estado' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $seguridad_usuario = User::findOrFail($id);
        $seguridad_usuario->estado = (int) $request->estado;
        $seguridad_usuario->save();

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Crea un nuevo usuario con validación, asignación de roles, módulos y archivos (firma/avatar).
     *
     * @param  Request  $request  Datos del usuario, archivos de firma y avatar.
     * @return JsonResponse Confirmación o errores de validación.
     */
    public function store(Request $request)
    {
        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make(
            $registro,
            [
                'num_docu' => 'required|string|max:20',
                'reg_med' => 'nullable|string|max:20',
                'name' => 'required|string|max:50',
                'email' => 'required|email|max:255|unique:users,email',
                'tel' => 'nullable|string|max:50',
                'facebook' => 'nullable|string|max:100',
                'twitter' => 'nullable|string|max:100',
                'youtube' => 'nullable|string|max:100',
                'interface_id' => 'nullable|string|max:255',
                'permitir_transcribir' => 'nullable|boolean',
                'password' => 'required|string|min:6',
                'estado' => 'nullable|boolean',
                'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ],
            [
                'num_docu.required' => 'El número de documento es obligatorio.',
                'num_docu.max' => 'El número de documento no debe exceder los 20 caracteres.',
                'reg_med.max' => 'El número de registro médico no debe exceder los 20 caracteres.',
                'name.required' => 'El nombre es obligatorio.',
                'name.max' => 'El nombre no debe exceder los 50 caracteres.',
                'email.required' => 'El email es obligatorio.',
                'email.email' => 'El email debe ser una dirección de correo válida.',
                'email.unique' => 'El email ya está en uso.',
                'tel.max' => 'El teléfono no debe exceder los 50 caracteres.',
                'facebook.max' => 'El enlace de Facebook no debe exceder los 100 caracteres.',
                'twitter.max' => 'El enlace de Twitter no debe exceder los 100 caracteres.',
                'youtube.max' => 'El enlace de YouTube no debe exceder los 100 caracteres.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'firma.file' => 'El campo firma debe ser un archivo.',
                'firma.mimes' => 'El campo firma debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
                'firma.max' => 'El archivo de firma no debe exceder los 2048 kilobytes.',
                'avatar.file' => 'El campo avatar debe ser un archivo.',
                'avatar.mimes' => 'El campo avatar debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
                'avatar.max' => 'El archivo de avatar no debe exceder los 2048 kilobytes.',
            ]
        );

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $seguridad_usuario = new User;
        $seguridad_usuario->num_docu = $registro['num_docu'];
        $seguridad_usuario->reg_med = $registro['reg_med'] ?? null;
        $seguridad_usuario->name = $registro['name'];
        $seguridad_usuario->email = $registro['email'];
        $seguridad_usuario->tel = $registro['tel'] ?? null;
        $seguridad_usuario->interface_id = $registro['interface_id'] ?? null;
        $seguridad_usuario->transcrip_remoto = $registro['transcrip_remoto'] ?? false;

        if ($registro['password']) {
            $seguridad_usuario->password = bcrypt($registro['password']);
        }

        if ($request->hasFile('firma')) {

            $archivo = $request->firma;

            if (! is_null($seguridad_usuario->firma) && Storage::disk('firmas_medicos')->exists($seguridad_usuario->firma)) {
                Storage::disk('firmas_medicos')->delete($seguridad_usuario->firma);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarFirma = $archivo->storeAs('firmas_medicos', $nombreEncrip);
            $seguridad_usuario->firma = $cargarFirma;
        }

        if ($request->hasFile('avatar')) {
            $archivo = $request->avatar;

            if (! is_null($seguridad_usuario->imagen_perfil) && Storage::disk('avatars')->exists($seguridad_usuario->imagen_perfil)) {
                Storage::disk('avatars')->delete($seguridad_usuario->imagen_perfil);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarAvatar = $archivo->storeAs('avatars', $nombreEncrip);
            $seguridad_usuario->imagen_perfil = $cargarAvatar;
        }

        $seguridad_usuario->save();

        $rolesDeUsuario = $registro['rolesUsuario'];
        $rolesInsertar = [];
        foreach ($rolesDeUsuario as $rol) {
            if ($rol['checked'] === true) {
                $rolesInsertar[] = $rol['id'];
            }
        }

        $seguridad_usuario->assignRole($rolesInsertar);

        /**
         * Establezco los modulos del usuario
         */
        $modulosDeUsuario = $registro['modulos'];
        $modulosInsertar = [];
        foreach ($modulosDeUsuario as $modulo) {
            if ($modulo['checked'] === true) {
                $modulosInsertar[] = $modulo['id'];
            }
        }

        // Insertar módulos si el usuario no tiene asociados previamente
        if (! empty($modulosInsertar)) {
            $seguridad_usuario->modulos()->sync($modulosInsertar);
        } else {
            $seguridad_usuario->modulos()->detach();
        }

        /**
         * Establezco las sedes del usuario
         */
        $sedesInsertar = $this->extractCheckedIds($registro, 'sedes');
        if (! empty($sedesInsertar)) {
            $seguridad_usuario->sedes()->sync($sedesInsertar);
        } else {
            $seguridad_usuario->sedes()->detach();
        }

        /**
         * Establezco los servicios de cita del usuario
         */
        $serviciosCitasInsertar = $this->extractCheckedIds($registro, 'serviciosCitas');
        if (! empty($serviciosCitasInsertar)) {
            $seguridad_usuario->serviciosCita()->sync($serviciosCitasInsertar);
        } else {
            $seguridad_usuario->serviciosCita()->detach();
        }

        return response()->json(['message' => 'El usuario se creo correctamente.']);
    }

    /**
     * Actualiza un usuario existente con validación, sincronización de roles, módulos y archivos.
     *
     * @param  Request  $request  Datos actualizados del usuario.
     * @return JsonResponse Confirmación o errores de validación.
     */
    public function update(Request $request)
    {

        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make($registro, [
            // 'num_docu' => 'required|string|max:20|unique:users,num_docu,' . $request->id,
            'reg_med' => 'nullable|string|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $request->id,
            'tel' => 'nullable|string|max:50',
            'facebook' => 'nullable|string|max:100',
            'twitter' => 'nullable|string|max:100',
            'youtube' => 'nullable|string|max:100',
            'interface_id' => 'nullable|string|max:255',
            'permitir_transcribir' => 'nullable|boolean',
            'password' => 'sometimes|string|min:6',
            'estado' => 'nullable|boolean',
            'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            // 'num_docu.required' => 'El número de documento es obligatorio.',
            // 'num_docu.max' => 'El número de documento no debe exceder los 20 caracteres.',
            // 'num_docu.unique' => 'El número de documento ya está en uso.',
            'reg_med.max' => 'El número de registro médico no debe exceder los 20 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 50 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección de correo válida.',
            'email.unique' => 'El email ya está en uso.',
            'tel.max' => 'El teléfono no debe exceder los 50 caracteres.',
            'facebook.max' => 'El enlace de Facebook no debe exceder los 100 caracteres.',
            'twitter.max' => 'El enlace de Twitter no debe exceder los 100 caracteres.',
            'youtube.max' => 'El enlace de YouTube no debe exceder los 100 caracteres.',
            'password.sometimes' => 'La contraseña es opcional.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'firma.file' => 'El campo firma debe ser un archivo.',
            'firma.mimes' => 'El campo firma debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'firma.max' => 'El archivo de firma no debe exceder los 2048 kilobytes.',
            'avatar.file' => 'El campo avatar debe ser un archivo.',
            'avatar.mimes' => 'El campo avatar debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'El archivo de avatar no debe exceder los 2048 kilobytes.',
        ]);

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $seguridad_usuario = User::findOrFail($request->id);
        $seguridad_usuario->num_docu = $registro['num_docu'] ?? $seguridad_usuario->num_docu;
        $seguridad_usuario->reg_med = $registro['reg_med'];
        $seguridad_usuario->name = $registro['name'];
        $seguridad_usuario->email = $registro['email'];
        $seguridad_usuario->tel = $registro['tel'] ?? $seguridad_usuario->tel;
        $seguridad_usuario->interface_id = $registro['interface_id'] ?? $seguridad_usuario->interface_id;
        $seguridad_usuario->transcrip_remoto = $registro['transcrip_remoto'] ?? $seguridad_usuario->transcrip_remoto;

        if ($registro['password']) {
            $seguridad_usuario->password = bcrypt($registro['password']);
        }

        if ($request->hasFile('firma')) {

            $archivo = $request->firma;

            if (! is_null($seguridad_usuario->firma) && Storage::disk('firmas_medicos')->exists($seguridad_usuario->firma)) {
                Storage::disk('firmas_medicos')->delete($seguridad_usuario->firma);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarFirma = $archivo->storeAs('firmas_medicos', $nombreEncrip);
            $seguridad_usuario->firma = $cargarFirma;
        }

        if ($request->hasFile('avatar')) {
            $archivo = $request->avatar;

            if (! is_null($seguridad_usuario->imagen_perfil) && Storage::disk('avatars')->exists($seguridad_usuario->imagen_perfil)) {
                Storage::disk('avatars')->delete($seguridad_usuario->imagen_perfil);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarAvatar = $archivo->storeAs('avatars', $nombreEncrip);
            $seguridad_usuario->imagen_perfil = $cargarAvatar;
        }

        $seguridad_usuario->save();

        /**
         * Establezco los roles del usuario
         */
        $rolesDeUsuario = $registro['rolesUsuario'];
        $rolesInsertar = [];
        foreach ($rolesDeUsuario as $rol) {
            if ($rol['checked'] === true) {
                $rolesInsertar[] = $rol['id'];
            }
        }

        $seguridad_usuario->roles()->sync($rolesInsertar);

        /**
         * Establezco los modulos del usuario
         */
        $modulosDeUsuario = $registro['modulos'];
        $modulosInsertar = [];
        foreach ($modulosDeUsuario as $modulo) {
            if (isset($modulo['checked']) and $modulo['checked'] === true) {
                $modulosInsertar[] = $modulo['id'];
            }
        }

        // Insertar módulos si el usuario no tiene asociados previamente
        if (! empty($modulosInsertar)) {
            $seguridad_usuario->modulos()->sync($modulosInsertar);
        } else {
            $seguridad_usuario->modulos()->detach();
        }

        /**
         * Establezco las sedes del usuario
         */
        $sedesInsertar = $this->extractCheckedIds($registro, 'sedes');
        if (! empty($sedesInsertar)) {
            $seguridad_usuario->sedes()->sync($sedesInsertar);
        } else {
            $seguridad_usuario->sedes()->detach();
        }

        /**
         * Establezco los servicios de cita del usuario
         */
        $serviciosCitasInsertar = $this->extractCheckedIds($registro, 'serviciosCitas');
        if (! empty($serviciosCitasInsertar)) {
            $seguridad_usuario->serviciosCita()->sync($serviciosCitasInsertar);
        } else {
            $seguridad_usuario->serviciosCita()->detach();
        }

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(User $user)
    {
        // Evitar auto-eliminación
        if (Auth::id() && (int) Auth::id() === (int) $user->id) {
            return response()->json(['message' => 'No es posible eliminar su propio usuario.'], 400);
        }

        // Limpieza best-effort de archivos
        try {
            if (! is_null($user->firma)) {
                if (Storage::disk('firmas_medicos')->exists($user->firma)) {
                    Storage::disk('firmas_medicos')->delete($user->firma);
                } else {
                    $base = basename($user->firma);
                    if (Storage::disk('firmas_medicos')->exists($base)) {
                        Storage::disk('firmas_medicos')->delete($base);
                    }
                }
            }
        } catch (\Exception $e) {
        }

        try {
            if (! is_null($user->imagen_perfil)) {
                if (Storage::disk('avatars')->exists($user->imagen_perfil)) {
                    Storage::disk('avatars')->delete($user->imagen_perfil);
                } else {
                    $base = basename($user->imagen_perfil);
                    if (Storage::disk('avatars')->exists($base)) {
                        Storage::disk('avatars')->delete($base);
                    }
                }
            }
        } catch (\Exception $e) {
        }

        // Sync relaciones (best-effort)
        try {
            if (method_exists($user, 'roles')) {
                $user->roles()->detach();
            }
        } catch (\Exception $e) {
        }
        try {
            if (method_exists($user, 'modulos')) {
                $user->modulos()->detach();
            }
        } catch (\Exception $e) {
        }
        try {
            if (method_exists($user, 'sedes')) {
                $user->sedes()->detach();
            }
        } catch (\Exception $e) {
        }
        try {
            if (method_exists($user, 'serviciosCita')) {
                $user->serviciosCita()->detach();
            }
        } catch (\Exception $e) {
        }

        $user->delete();

        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }

    /**
     * Extrae los IDs marcados de un array de objetos {id, checked} recibido en el payload.
     * Si el array no existe, devuelve [].
     */
    private function extractCheckedIds(array $registro, string $key): array
    {
        if (! isset($registro[$key]) || ! is_array($registro[$key])) {
            return [];
        }

        $ids = [];
        foreach ($registro[$key] as $item) {
            if (isset($item['checked']) && $item['checked'] === true && isset($item['id'])) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }

    /**
     * Lista usuarios asociados a un módulo específico.
     *
     * @param  int  $moduloId  ID del módulo.
     * @return JsonResponse Lista de usuarios del módulo.
     */
    public function usuariosPorModulo($moduloId)
    {
        $modulo = ConfigModulo::with('usuarios')->find($moduloId);

        if (! $modulo) {
            return response()->json(['message' => 'Módulo no encontrado'], 404);
        }

        return response()->json($modulo->usuarios);
    }

    /**
     * Actualiza el perfil del usuario (self) incluyendo avatar opcional.
     */
    public function actualizarPerfil(Request $request, $idUsua)
    {
        $usuario = User::findOrFail($idUsua);

        $authId = Auth::id();
        $isSelf = $authId && ((int) $authId === (int) $idUsua);
        if (! $isSelf && ! Auth::user()->can('seguridad.usuarios.edit')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'num_docu' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:6',
            'imagen_perfil' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $usuario->num_docu = $request->num_docu;
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        if ($request->filled('password')) {
            $usuario->password = bcrypt($request->password);
        }

        if ($request->hasFile('imagen_perfil')) {
            $archivo = $request->file('imagen_perfil');
            if ($archivo && $archivo->isValid()) {
                // Borrado best-effort (considera paths viejos tipo "avatars/xxx")
                try {
                    if (! is_null($usuario->imagen_perfil) && Storage::disk('avatars')->exists($usuario->imagen_perfil)) {
                        Storage::disk('avatars')->delete($usuario->imagen_perfil);
                    } elseif (! is_null($usuario->imagen_perfil)) {
                        $base = basename($usuario->imagen_perfil);
                        if (Storage::disk('avatars')->exists($base)) {
                            Storage::disk('avatars')->delete($base);
                        }
                    }
                } catch (\Exception $e) {
                }

                $nombreEncrip = Str::random(50) . "." . $archivo->extension();
                $path = $archivo->storeAs('avatars', $nombreEncrip);
                $usuario->imagen_perfil = $path;
            }
        }

        $usuario->save();

        return response()->json(['message' => 'Perfil actualizado correctamente.']);
    }

    /**
     * Lista roles y permisos efectivos del usuario.
     */
    public function listarPermisosUsuario($id)
    {
        $usuario = User::findOrFail($id);

        $authId = Auth::id();
        $isSelf = $authId && ((int) $authId === (int) $id);
        if (! $isSelf && ! Auth::user()->can('seguridad.usuarios.show')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json([
            'usuario_id' => $usuario->id,
            'roles' => $usuario->roles ? $usuario->roles->pluck('name')->values() : [],
            'permisos' => method_exists($usuario, 'getAllPermissions')
                ? $usuario->getAllPermissions()->pluck('name')->values()
                : [],
        ]);
    }
}
