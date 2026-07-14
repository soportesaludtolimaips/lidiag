<?php

namespace App\Services\Seguridad;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolService extends BaseService
{
    public function all(): array
    {
        $roles = Role::all();
        $permissions = Permission::get();

        return $this->success(['roles' => $roles, 'permissions' => $permissions]);
    }

    public function create(array $data): array
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $permisos = $data['permisosRol'] ?? [];
        $permisosInsertar = [];

        foreach ($permisos as $permiso) {
            if ($permiso['checked'] == true) {
                $permisosInsertar[] = $permiso['id'];
            }
        }

        $permissionNames = Permission::query()
            ->whereIn('id', $permisosInsertar)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->values()
            ->all();

        if (count($permissionNames) !== count($permisosInsertar)) {
            return $this->error('Uno o más permisos no existen o no pertenecen al guard web.', 400);
        }

        $role->syncPermissions($permissionNames);

        return $this->created(['message' => 'Registro creado satisfactoriamente.']);
    }

    public function find(int $id): array
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFound('El rol no se encontró');
        }

        $permisos = Permission::orderby('name')->get();
        $permisosRol = DB::table('role_has_permissions')
            ->where('role_has_permissions.role_id', $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return $this->success(['seguridad_role' => $role, 'permisos' => $permisos, 'permisosRol' => $permisosRol]);
    }

    public function update(int $id, array $data): array
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFound('El rol no se encontró');
        }

        $role->name = $data['registro']['name'];
        $role->guard_name = 'web';
        $role->save();

        $permisos = $data['registro']['permisosRol'] ?? [];
        $permisosInsertar = [];

        foreach ($permisos as $permiso) {
            if ($permiso['checked'] == true) {
                $permisosInsertar[] = $permiso['id'];
            }
        }

        $permissionNames = Permission::query()
            ->whereIn('id', $permisosInsertar)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->values()
            ->all();

        if (count($permissionNames) !== count($permisosInsertar)) {
            return $this->error('Uno o más permisos no existen o no pertenecen al guard web.', 400);
        }

        $role->syncPermissions($permissionNames);

        return $this->updated(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    public function delete(int $id): array
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFound('El rol no se encontró');
        }

        $role->delete();

        return $this->deleted('Registro eliminado satisfactoriamente.');
    }
}
