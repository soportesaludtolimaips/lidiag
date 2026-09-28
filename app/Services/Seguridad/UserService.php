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
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    public function all(): array
    {
        $users = User::with(['modulos', 'roles', 'sedes', 'serviciosCita'])->get();

        return $this->success($users);
    }

    public function listByRoleAndModule(string $roleName, int $moduleId): array
    {
        $users = User::select('users.id', 'users.name')
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->whereHas('modulos', fn ($q) => $q->where('config_modulos.id', $moduleId))
            ->orderBy('users.name')
            ->get();

        return $this->success($users);
    }

    public function findByModule(int $moduleId): array
    {
        $modulo = ConfigModulo::with('usuarios')->find($moduleId);

        if (! $modulo) {
            return $this->notFound('MÃ³dulo no encontrado');
        }

        return $this->success($modulo->usuarios);
    }

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
            $this->fileService->delete($user->firma, 'local');
        }

        if ($user->imagen_perfil) {
            $this->fileService->delete($user->imagen_perfil, 'local');
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
