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

        return $this->success($users);
    }

    /**
     * Lista los usuarios que tienen un rol y acceso a un módulo específicos.
     *
     * @param  string  $roleName  Nombre del rol a filtrar
     * @param  int  $moduleId  Identificador del módulo al que deben tener acceso
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listByRoleAndModule(string $roleName, int $moduleId): array
    {
        $users = User::select('users.id', 'users.name')
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->whereHas('modulos', fn ($q) => $q->where('config_modulos.id', $moduleId))
            ->orderBy('users.name')
            ->get();

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
            return $this->notFound('El usuario no se encontrÃ³');
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
                return $this->error('La contraseÃ±a actual no es correcta.', 422);
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
