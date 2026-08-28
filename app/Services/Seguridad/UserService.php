<?php

namespace App\Services\Seguridad;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigModulo;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService extends BaseService
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    /**
     * Lista todos los usuarios con sus relaciones (módulos, roles, sedes, servicios).
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function all(): array
    {
        $users = User::with(['modulos', 'roles', 'sedes', 'serviciosCita'])->get();

        return $this->success($users);
    }

    /**
     * Lista usuarios por nombre de rol y módulo.
     *
     * @param  string  $roleName  Nombre del rol.
     * @param  int  $moduleId  Identificador del módulo.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Retorna los usuarios asociados a un módulo.
     *
     * @param  int  $moduleId  Identificador del módulo.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function findByModule(int $moduleId): array
    {
        $modulo = ConfigModulo::with('usuarios')->find($moduleId);

        if (! $modulo) {
            return $this->notFound('Módulo no encontrado');
        }

        return $this->success($modulo->usuarios);
    }

    /**
     * Busca un usuario por su identificador con sus relaciones.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function find(int $id): array
    {
        $user = User::find($id);

        if (! $user) {
            return $this->notFound('El usuario no se encontró');
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
     * Crea un usuario, gestionando firma, avatar y relaciones.
     *
     * @param  array  $data  Datos del usuario.
     * @param  UploadedFile|null  $firma  Archivo de firma.
     * @param  UploadedFile|null  $avatar  Archivo de avatar.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function create(array $data, ?UploadedFile $firma = null, ?UploadedFile $avatar = null): array
    {
        $user = new User;
        $this->fillUserData($user, $data);
        $user->password = Hash::make($data['password']);
        $user->estado = filter_var($data['estado'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($firma) {
            $info = $this->fileService->store($firma, 'firmas_medicos', 'firmas_medicos');
            $user->firma = $info['path'];
        }

        if ($avatar) {
            $info = $this->fileService->store($avatar, 'avatars', 'avatars');
            $user->imagen_perfil = $info['path'];
        }

        $user->save();

        $this->syncRelations($user, $data);

        AuditLogger::crear($user, 'Usuario creado: '.($data['name'] ?? ''));

        return $this->created($user->load(['modulos', 'roles', 'sedes', 'serviciosCita']));
    }

    /**
     * Actualiza un usuario, gestionando firma, avatar y relaciones.
     *
     * @param  int  $id  Identificador del usuario.
     * @param  array  $data  Datos a actualizar.
     * @param  UploadedFile|null  $firma  Archivo de firma.
     * @param  UploadedFile|null  $avatar  Archivo de avatar.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function update(int $id, array $data, ?UploadedFile $firma = null, ?UploadedFile $avatar = null): array
    {
        $user = User::findOrFail($id);
        $this->fillUserData($user, $data);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($firma) {
            $info = $this->fileService->replace('firmas_medicos', $user->firma, $firma, 'firmas_medicos');
            $user->firma = $info['path'];
        }

        if ($avatar) {
            $info = $this->fileService->replace('avatars', $user->imagen_perfil, $avatar, 'avatars');
            $user->imagen_perfil = $info['path'];
        }

        $user->save();

        $this->syncRelations($user, $data);

        AuditLogger::actualizar($user, $user->getOriginal(), 'Usuario actualizado: '.$user->name);

        return $this->updated($user->load(['modulos', 'roles', 'sedes', 'serviciosCita']));
    }

    /**
     * Activa o inactiva el estado de un usuario.
     *
     * @param  int  $id  Identificador del usuario.
     * @param  bool  $estado  Nuevo estado (true=activo).
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function toggleStatus(int $id, bool $estado): array
    {
        $user = User::findOrFail($id);
        $user->estado = $estado;
        $user->save();

        return $this->updated(null, 'Estado actualizado correctamente.');
    }

    /**
     * Elimina un usuario y limpia sus archivos y relaciones.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Actualiza el perfil propio del usuario autenticado.
     *
     * @param  int  $id  Identificador del usuario.
     * @param  array  $data  Datos del perfil.
     * @param  UploadedFile|null  $avatar  Archivo de avatar.
     * @param  UploadedFile|null  $firma  Archivo de firma.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function updateProfile(int $id, array $data, ?UploadedFile $avatar = null, ?UploadedFile $firma = null): array
    {
        $authCheck = $this->checkSelfOrPermission($id, 'seguridad.usuarios.edit');
        if ($authCheck !== null) {
            return $authCheck;
        }

        $user = User::findOrFail($id);
        $user->num_docu = $data['num_docu'];
        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($avatar) {
            $info = $this->fileService->replace('avatars', $user->imagen_perfil, $avatar, 'avatars');
            $user->imagen_perfil = $info['path'];
        }

        if ($firma) {
            $info = $this->fileService->replace('firmas_medicos', $user->firma, $firma, 'firmas_medicos');
            $user->firma = $info['path'];
        }

        $user->save();

        return $this->updated(null, 'Perfil actualizado correctamente.');
    }

    /**
     * Lista los roles y permisos de un usuario.
     *
     * @param  int  $id  Identificador del usuario.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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

    private function cleanupUserFiles(User $user): void
    {
        if ($user->firma) {
            $this->fileService->delete($user->firma, 'firmas_medicos');
        }

        if ($user->imagen_perfil) {
            $this->fileService->delete($user->imagen_perfil, 'avatars');
        }
    }

    private function detachRelations(User $user): void
    {
        collect(['roles', 'modulos', 'sedes', 'serviciosCita'])
            ->each(fn ($rel) => $user->{$rel}()->detach());
    }

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
