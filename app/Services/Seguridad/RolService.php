<?php

namespace App\Services\Seguridad;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolService extends BaseService
{
    /**
     * Obtiene todos los roles y el catálogo completo de permisos del sistema.
     *
     * @returns array Resultado con la lista de roles y la lista de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        $roles = Role::all();
        $permissions = Permission::get();

        return $this->success(['roles' => $roles, 'permissions' => $permissions]);
    }

    /**
     * Crea un nuevo rol y sincroniza los permisos marcados como seleccionados.
     *
     * @param  array  $data  Datos del rol: name y permisosRol (lista de permisos con checked).
     *
     * @returns array Resultado de la operación de creación del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Busca un rol por su identificador y retorna sus permisos asociados.
     *
     * @param  int  $id  Identificador del rol a consultar.
     *
     * @returns array Resultado con el rol, todos los permisos y los permisos asignados al rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Actualiza el nombre de un rol y sincroniza sus permisos seleccionados.
     *
     * @param  int  $id  Identificador del rol a actualizar.
     * @param  array  $data  Datos validados con registro.name y registro.permisosRol.
     *
     * @returns array Resultado de la operación de actualización del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Elimina un rol si no está asignado a ningún usuario del sistema.
     *
     * @param  int  $id  Identificador del rol a eliminar.
     *
     * @returns array Resultado de la operación de eliminación del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFound('El rol no se encontró');
        }

        $usersCount = DB::table('model_has_roles')
            ->where('role_id', $id)
            ->count();

        if ($usersCount > 0) {
            return $this->error("No se puede eliminar el rol \"{$role->name}\" porque está asignado a {$usersCount} usuario(s).", 422);
        }

        $role->delete();

        return $this->deleted('Registro eliminado satisfactoriamente.');
    }

    /**
     * Alterna el estado (activo/inactivo) de un rol existente.
     *
     * @param  int  $id  Identificador del rol cuyo estado se va a alternar.
     *
     * @returns array Resultado con el mensaje y el nuevo estado del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function toggleEstado(int $id): array
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->notFound('El rol no se encontró');
        }

        $role->estado = $role->estado ? 0 : 1;
        $role->save();

        return $this->success([
            'message' => $role->estado ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.',
            'estado' => $role->estado,
        ]);
    }
}
