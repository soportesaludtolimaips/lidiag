<?php

namespace App\Services\Seguridad;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigModulo;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserService extends BaseService
{
    /**
     * Inicializa el servicio inyectando el servicio de manejo de archivos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    /**
     * Lista todos los usuarios con sus módulos, roles, sedes y servicios cargados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function all(): array
    {
        $users = User::with(['modulos', 'roles', 'sedes', 'serviciosCita'])->get();

        $storageLocal = Storage::disk('local');
        $storageFirmas = Storage::disk('firmas_medicos');

        foreach ($users as $user) {
            if ($user->firma) {
                $relative = str_replace('firmas_medicos/', '', $user->firma);
                $user->firma_disponible = $storageFirmas->exists($relative) || $storageLocal->exists($user->firma);
            } else {
                $user->firma_disponible = false;
            }
        }

        return $this->success($users);
    }

    /**
     * Lista los usuarios que tienen un rol y acceso a un módulo específicos, con filtro opcional por sede.
     *
     * Flujo del proceso:
     * 1. Inicializa la consulta base sobre usuarios filtrando por asociación al módulo especificado.
     * 2. Si $onlyActive es verdadero, aplica el filtro restrictivo de estado activo (users.estado = 1).
     * 3. Si $sedeId está definido, filtra por pertenencia de sede en 'user_sedes' o por haber registrado o tener estudios asignados en dicha sede.
     * 4. Permite filtrado múltiple por lista de roles separados por coma o filtrado estándar por rol único.
     * 5. Retorna el listado ordenado alfabéticamente encapsulado en respuesta exitosa.
     *
     * @param  string  $roleName  Nombre del rol o lista de roles separados por coma.
     * @param  int  $moduleId  Identificador del módulo al que deben tener acceso.
     * @param  bool  $onlyActive  Indica si se deben filtrar únicamente usuarios activos (por defecto true).
     * @param  int|null  $sedeId  Identificador opcional de la sede para filtrar usuarios.
     * @return array Respuesta estructurada con los usuarios encontrados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    public function listByRoleAndModule(string $roleName, int $moduleId, bool $onlyActive = true, ?int $sedeId = null): array
    {
        $query = User::select("users.id", "users.name", "users.estado")
            ->whereHas("modulos", fn ($q) => $q->where("config_modulos.id", $moduleId))
            ->orderBy("users.name");

        if ($onlyActive) {
            $query->where("users.estado", 1);
        }

        if ($sedeId !== null && $sedeId > 0) {
            $query->where(function ($q) use ($sedeId) {
                $q->whereHas("sedes", fn ($s) => $s->where("config_sedes.id", $sedeId))
                    ->orWhereIn("users.id", function ($sub) use ($sedeId) {
                        $sub->select("quien_registro_id")
                            ->from("estudios")
                            ->where("sede_id", $sedeId)
                            ->whereNotNull("quien_registro_id");
                    })
                    ->orWhereIn("users.id", function ($sub) use ($sedeId) {
                        $sub->select("medico_id")
                            ->from("estudios")
                            ->where("sede_id", $sedeId)
                            ->whereNotNull("medico_id");
                    });
            });
        }

        $roles = str_contains($roleName, ",")
            ? array_filter(array_map("trim", explode(",", $roleName)))
            : [trim($roleName)];

        // En la base de datos el rol puede llamarse "Transcriptor" o "Transcriptor RX"
        if (in_array("Transcriptor", $roles, true) && ! in_array("Transcriptor RX", $roles, true)) {
            $roles[] = "Transcriptor RX";
        }

        $query->whereHas("roles", fn ($q) => $q->whereIn("name", $roles));

        $users = $query->distinct()->get();

        return $this->success($users);
    }

    /**
     * Obtiene los usuarios asociados a un módulo de configuración específico.
     *
     * @param  int  $moduleId  Identificador del módulo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function findByModule(int $moduleId): array
    {
        $modulo = ConfigModulo::with('usuarios')->find($moduleId);

        if (! $modulo) {
            return $this->notFound('MÃ³dulo no encontrado');
        }

        return $this->success($modulo->usuarios);
    }

    /**
     * Obtiene un usuario por su identificador junto con roles, sedes y servicios asignados.
     *
     * @param  int  $id  Identificador del usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function find(int $id): array
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound("El usuario no se encontró");
        }

        $authCheck = $this->checkSelfOrPermission($id, 'seguridad.usuarios.show');
        if ($authCheck !== null) {
            return $authCheck;
        }

        $user->load(['sedes', 'serviciosCita']);

        return $this->success([
            'usuario' => $user,
            'roles' => Role::all(),
            'RolesDeUsuario' => $user->roles->pluck('id')->values()->toArray(),
            'SedesDeUsuario' => $user->sedes->pluck('id')->values()->toArray(),
            'ServiciosDeUsuario' => $user->serviciosCita->pluck('id')->values()->toArray(),
        ]);
    }

    /**
     * Crea un nuevo usuario con sus archivos, relaciones y registro de auditoría.
     *
     * @param  array  $data  Datos del usuario: name, email, password, estado, roles, módulos, sedes
     * @param  UploadedFile|null  $firma  Archivo de la firma del médico
     * @param  UploadedFile|null  $avatar  Archivo del avatar del usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function create(array $data, ?UploadedFile $firma = null, ?UploadedFile $avatar = null): array
    {
        return $this->transaction(function () use ($data, $firma, $avatar) {
            $user = new User;
            $this->fillUserData($user, $data);
            $user->password = Hash::make($data['password']);
            $user->password_changed_at = null;
            $user->estado = filter_var($data['estado'] ?? true, FILTER_VALIDATE_BOOLEAN);

            if ($firma) {
                $info = $this->fileService->store($firma, 'local', 'firmas_medicos');
                $user->firma = $info['path'];
            }

            if ($avatar) {
                $info = $this->fileService->store($avatar, 'local', 'avatars');
                $user->imagen_perfil = $info['path'];
            }

            $user->save();

            $this->syncRelations($user, $data);

            AuditLogger::crear($user, 'Usuario creado: '.($data['name'] ?? ''));

            return $this->created($user->load(['modulos', 'roles', 'sedes', 'serviciosCita']));
        });
    }

    /**
     * Actualiza un usuario existente con sus archivos, relaciones y registro de auditoría.
     *
     * @param  int  $id  Identificador del usuario a actualizar
     * @param  array  $data  Nuevos datos del usuario
     * @param  UploadedFile|null  $firma  Nueva archivo de la firma del médico
     * @param  UploadedFile|null  $avatar  Nuevo archivo del avatar del usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function update(int $id, array $data, ?UploadedFile $firma = null, ?UploadedFile $avatar = null): array
    {
        return $this->transaction(function () use ($id, $data, $firma, $avatar) {
            $user = User::findOrFail($id);
            $this->fillUserData($user, $data);

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                if (! empty($data['forzar_cambio_password'])) {
                    $user->password_changed_at = null;
                    DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->where('id', '!=', session()->getId())
                        ->delete();
                    $user->tokens()->delete();
                }
            }

            if ($firma) {
                $info = $this->fileService->replace('local', $user->firma, $firma, 'firmas_medicos');
                $user->firma = $info['path'];
            }

            if ($avatar) {
                $info = $this->fileService->replace('local', $user->imagen_perfil, $avatar, 'avatars');
                $user->imagen_perfil = $info['path'];
            }

            $user->save();

            $this->syncRelations($user, $data);

            AuditLogger::actualizar($user, $user->getOriginal(), 'Usuario actualizado: '.$user->name);

            return $this->updated($user->load(['modulos', 'roles', 'sedes', 'serviciosCita']));
        });
    }

    /**
     * Cambia el estado (activo/inactivo) de un usuario, evictando el cambio del propio usuario.
     *
     * @param  int  $id  Identificador del usuario
     * @param  bool  $estado  Nuevo estado a asignar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toggleStatus(int $id, bool $estado): array
    {
        if ((int) Auth::id() === $id) {
            return $this->error('No es posible cambiar su propio estado.', 400);
        }

        $user = User::findOrFail($id);
        $user->estado = $estado;
        $user->save();

        return $this->updated(null, 'Estado actualizado correctamente.');
    }

    /**
     * Restablece la contraseña de un usuario por parte de un administrador,
     * invalidando sesiones previas y forzando el cambio de contraseña al iniciar sesión.
     *
     * Flujo del proceso:
     * 1. Localiza al usuario objetivo en la base de datos o lanza excepción 404.
     * 2. Actualiza el hash de la contraseña y asigna password_changed_at a null si forzarCambio es verdadero.
     * 3. Revoca todas las sesiones web activas y tokens Sanctum del usuario para exigir nuevo inicio de sesión.
     * 4. Registra el evento en la bitácora de auditoría ISO 27001.
     * 5. Retorna respuesta estructurada con los datos del usuario.
     *
     * @param  int  $id  Identificador del usuario cuya contraseña se restablece
     * @param  string  $newPassword  Nueva contraseña temporal
     * @param  bool  $forzarCambio  Indica si se obliga al usuario a cambiarla en el siguiente inicio de sesión
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function resetPassword(int $id, string $newPassword, bool $forzarCambio = true): array
    {
        return $this->transaction(function () use ($id, $newPassword, $forzarCambio) {
            $user = User::findOrFail($id);

            $user->password = Hash::make($newPassword);
            $user->password_changed_at = $forzarCambio ? null : now();
            $user->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            $user->tokens()->delete();

            AuditLogger::actualizar(
                $user,
                ['password_changed_at' => $user->getOriginal('password_changed_at')],
                'Contraseña restablecida por administrador para el usuario: '.$user->name
            );

            return $this->updated(
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'forzar_cambio' => $forzarCambio,
                ],
                'Contraseña restablecida correctamente. El usuario deberá cambiarla al iniciar sesión.'
            );
        });
    }

    /**
     * Cambia la contraseña del usuario autenticado previa verificación de su contraseña actual,
     * actualizando la fecha de cambio a now() y revocando otras sesiones web activas por seguridad.
     *
     * Flujo del proceso:
     * 1. Localiza al usuario o lanza excepción si no existe.
     * 2. Comprueba mediante Hash::check que la contraseña actual sea correcta.
     * 3. Verifica que la nueva contraseña no sea idéntica a la contraseña actual.
     * 4. Asigna el nuevo hash a password y actualiza password_changed_at a now().
     * 5. Invalida otras sesiones en la tabla sessions y revoca tokens Sanctum del usuario.
     * 6. Registra la auditoría del cambio de credenciales bajo estándar ISO 27001.
     * 7. Retorna confirmación de actualización exitosa.
     *
     * @param  int  $id  Identificador del usuario autenticado
     * @param  string  $currentPassword  Contraseña actual suministrada
     * @param  string  $newPassword  Nueva contraseña segura
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function changeOwnPassword(int $id, string $currentPassword, string $newPassword): array
    {
        return $this->transaction(function () use ($id, $currentPassword, $newPassword) {
            $user = User::findOrFail($id);

            if (! Hash::check($currentPassword, $user->password)) {
                return $this->error('La contraseña actual no es correcta.', 422);
            }

            if (Hash::check($newPassword, $user->password)) {
                return $this->error('La nueva contraseña debe ser diferente a la contraseña actual.', 422);
            }

            $user->password = Hash::make($newPassword);
            $user->password_changed_at = now();
            $user->save();

            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();

            $user->tokens()->delete();

            AuditLogger::actualizar(
                $user,
                ['password_changed_at' => $user->getOriginal('password_changed_at')],
                'Contraseña cambiada por el usuario desde su perfil: '.$user->name
            );

            return $this->updated(null, 'Contraseña actualizada correctamente.');
        });
    }

    /**
     * Elimina un usuario, sus archivos y relaciones, registrando la acción en auditoría.
     *
     * @param  int  $id  Identificador del usuario a eliminar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function delete(int $id): array
    {
        $user = User::findOrFail($id);

        if (Auth::id() && (int) Auth::id() === $id) {
            return $this->error('No es posible eliminar su propio usuario.', 400);
        }

        $this->cleanupUserFiles($user);
        $this->detachRelations($user);

        $userName = $user->name;
        $user->delete();

        AuditLogger::eliminar($user, 'Usuario eliminado: '.$userName);

        return $this->deleted();
    }

    /**
     * Actualiza el perfil propio del usuario con verificación de contraseña actual para cambios sensibles.
     *
     * @param  int  $id  Identificador del usuario cuyo perfil se actualiza
     * @param  array  $data  Datos del perfil: num_docu, name, email, password, current_password
     * @param  UploadedFile|null  $avatar  Nuevo archivo del avatar del usuario
     * @param  UploadedFile|null  $firma  Nueva archivo de la firma del médico
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function updateProfile(int $id, array $data, ?UploadedFile $avatar = null, ?UploadedFile $firma = null): array
    {
        $authCheck = $this->checkSelfOrPermission($id, 'seguridad.usuarios.edit');
        if ($authCheck !== null) {
            return $authCheck;
        }

        return $this->transaction(function () use ($id, $data, $avatar, $firma) {
            $user = User::findOrFail($id);
            $cambiarPassword = ! empty($data['password']);

            if ($cambiarPassword && ! Hash::check($data['current_password'] ?? '', $user->password)) {
                return $this->error("La contraseña actual no es correcta.", 422);
            }

            $datosViejos = Arr::except($user->getOriginal(), ['password', 'remember_token']);
            $viejoAvatar = $user->imagen_perfil;
            $viejaFirma = $user->firma;

            $user->num_docu = $data['num_docu'];
            $user->name = $data['name'];
            $user->email = $data['email'];

            if ($cambiarPassword) {
                $user->password = Hash::make($data['password']);
                $user->password_changed_at = now();
            }

            if ($avatar) {
                $info = $this->fileService->store($avatar, 'local', 'avatars');
                $user->imagen_perfil = $info['path'];
            }

            if ($firma) {
                $info = $this->fileService->store($firma, 'local', 'firmas_medicos');
                $user->firma = $info['path'];
            }

            $user->save();

            if ($avatar && $viejoAvatar) {
                $this->fileService->delete($viejoAvatar, 'local');
            }

            if ($firma && $viejaFirma) {
                $this->fileService->delete($viejaFirma, 'local');
            }

            if ($cambiarPassword) {
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', session()->getId())
                    ->delete();
                $user->tokens()->delete();
            }

            AuditLogger::actualizar($user, $datosViejos, 'Perfil actualizado: '.$user->name);

            return $this->updated(null, 'Perfil actualizado correctamente.');
        });
    }

    /**
     * Obtiene los roles y permisos asignados a un usuario específico.
     *
     * @param  int  $id  Identificador del usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listPermissions(int $id): array
    {
        $user = User::findOrFail($id);

        $authCheck = $this->checkSelfOrPermission($id, 'seguridad.usuarios.show');
        if ($authCheck !== null) {
            return $authCheck;
        }

        return $this->success([
            'usuario_id' => $user->id,
            'roles' => $user->roles?->pluck('name')->values() ?? [],
            'permisos' => method_exists($user, 'getAllPermissions')
                ? $user->getAllPermissions()->pluck('name')->values()
                : [],
        ]);
    }

    /**
     * Rellena los campos básicos de un usuario a partir de los datos enviados.
     *
     * @param  User  $user  Instancia del usuario a completar
     * @param  array  $data  Datos enviados con los campos a actualizar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function fillUserData(User $user, array $data): void
    {
        $user->num_docu = $data['num_docu'] ?? $user->num_docu;
        $user->reg_med = $data['reg_med'] ?? $user->reg_med;
        $user->name = $data['name'] ?? $user->name;
        $user->email = $data['email'] ?? $user->email;
        $user->tel = $data['tel'] ?? $user->tel;
        $user->interface_id = $data['interface_id'] ?? $user->interface_id;
        $user->transcrip_remoto = filter_var($data['transcrip_remoto'] ?? $user->transcrip_remoto, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sincroniza las relaciones many-to-many del usuario: roles, módulos, sedes y servicios.
     *
     * @param  User  $user  Usuario cuyas relaciones se sincronizan
     * @param  array  $data  Datos que contienen las listas de relaciones a sincronizar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function syncRelations(User $user, array $data): void
    {
        if (isset($data['rolesUsuario'])) {
            $roleIds = $this->extractCheckedIds($data, 'rolesUsuario');
            $user->roles()->sync($roleIds);
        }

        if (isset($data['modulos'])) {
            $moduleIds = $this->extractCheckedIds($data, 'modulos');
            $user->modulos()->sync($moduleIds);
        }

        if (isset($data['sedes'])) {
            $sedeIds = $this->extractCheckedIds($data, 'sedes');
            $user->sedes()->sync($sedeIds);
        }

        if (isset($data['serviciosCitas'])) {
            $servicioIds = $this->extractCheckedIds($data, 'serviciosCitas');
            $user->serviciosCita()->sync($servicioIds);
        }
    }

    /**
     * Elimina los archivos asociados al usuario (firma y avatar) del almacenamiento.
     *
     * @param  User  $user  Usuario cuyos archivos se eliminan
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function cleanupUserFiles(User $user): void
    {
        if ($user->firma) {
            $this->fileService->delete($user->firma, 'local');
        }

        if ($user->imagen_perfil) {
            $this->fileService->delete($user->imagen_perfil, 'local');
        }
    }

    /**
     * Desvincula todas las relaciones many-to-many del usuario.
     *
     * @param  User  $user  Usuario cuyas relaciones se desvinculan
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function detachRelations(User $user): void
    {
        collect(['roles', 'modulos', 'sedes', 'serviciosCita'])
            ->each(fn ($rel) => $user->{$rel}()->detach());
    }

    /**
     * Verifica que el usuario sea el propio o tenga el permiso indicado para la operación.
     *
     * @param  int  $id  Identificador del usuario objetivo
     * @param  string  $permission  Permiso requerido para usuarios distintos al propio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function checkSelfOrPermission(int $id, string $permission): ?array
    {
        $authId = Auth::id();
        $isSelf = $authId && (int) $authId === $id;

        if ($isSelf) {
            return null;
        }

        if (! Auth::user()?->can($permission)) {
            return $this->unauthorized();
        }

        return null;
    }
}
