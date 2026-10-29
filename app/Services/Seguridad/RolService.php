<?php

namespace App\Services\Seguridad;

use App\Helpers\AuditLogger;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolService extends BaseService
{
    /**
     * Detalle y descripción de la función
     *
     * @param  void  $args  Sin parámetros.
     *
     * @returns array Roles y catálogo de permisos del guard web.
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
     * Extrae los identificadores de los permisos marcados en el formulario.
     *
     * @param  array  $permisosRol  Lista recibida con la forma [{id, name, checked}].
     * @return array Identificadores de los permisos marcados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-29
     */
    private function permisosMarcados(array $permisosRol): array
    {
        $ids = [];

        foreach ($permisosRol as $permiso) {
            if (! empty($permiso['checked'])) {
                $ids[] = (int) $permiso['id'];
            }
        }

        return $ids;
    }

    /**
     * Resuelve los nombres de los permisos indicados y verifica que todos
     * existan y pertenezcan al guard web.
     *
     * @param  array  $permisosInsertar  Identificadores de los permisos marcados.
     * @return array|null Nombres resueltos o null si alguno no es válido.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-29
     */
    private function resolverNombresPermisos(array $permisosInsertar): ?array
    {
        if (empty($permisosInsertar)) {
            return null;
        }

        $permissionNames = Permission::query()
            ->whereIn('id', $permisosInsertar)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->values()
            ->all();

        if (count($permissionNames) !== count($permisosInsertar)) {
            return null;
        }

        return $permissionNames;
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  array  $data  Datos validados con `name` y `permisosRol`.
     *
     * @returns array Resultado de la creación del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $permisosInsertar = $this->permisosMarcados($data['permisosRol'] ?? []);

        if (empty($permisosInsertar)) {
            return $this->error('Debes elegir al menos un permiso para el rol.', 422);
        }

        $permissionNames = $this->resolverNombresPermisos($permisosInsertar);

        if ($permissionNames === null) {
            return $this->error('Uno o más permisos no existen o no pertenecen al guard web.', 400);
        }

        return $this->transaction(function () use ($data, $permissionNames) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissionNames);

            AuditLogger::crear(
                $role,
                "Rol creado: '{$role->name}' con ".count($permissionNames).' permisos por '.(Auth::user()?->name ?? ('ID: '.Auth::id()))
            );

            return $this->created(['message' => 'Registro creado satisfactoriamente.']);
        });
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     *
     * @returns array Rol, catálogo de permisos y los ids asignados al rol.
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
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     * @param  array  $data  Datos validados con `registro.name` y `registro.permisosRol`.
     *
     * @returns array Resultado de la actualización del rol.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $permisosInsertar = $this->permisosMarcados($data['registro']['permisosRol'] ?? []);

        if (empty($permisosInsertar)) {
            return $this->error('Debes elegir al menos un permiso para el rol.', 422);
        }

        $permissionNames = $this->resolverNombresPermisos($permisosInsertar);

        if ($permissionNames === null) {
            return $this->error('Uno o más permisos no existen o no pertenecen al guard web.', 400);
        }

        return $this->transaction(function () use ($id, $data, $permissionNames) {
            $role = Role::find($id);

            if (! $role) {
                return $this->notFound('El rol no se encontró');
            }

            $oldName = $role->name;
            $role->name = $data['registro']['name'];
            $role->guard_name = 'web';
            $role->save();

            $role->syncPermissions($permissionNames);

            AuditLogger::actualizar(
                $role,
                ['name' => $oldName],
                "Rol '{$role->name}' actualizado y permisos sincronizados (".count($permissionNames).' permisos) por '.(Auth::user()?->name ?? ('ID: '.Auth::id()))
            );

            return $this->updated(['message' => 'Registro actualizado satisfactoriamente.']);
        });
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     *
     * @returns array Resultado de la eliminación.
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

        AuditLogger::eliminar(
            $role,
            "Rol '{$role->name}' eliminado por ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        $role->delete();

        return $this->deleted('Registro eliminado satisfactoriamente.');
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  int  $id  Identificador del rol.
     *
     * @returns array Nuevo estado del rol.
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

        AuditLogger::log(
            'cambio_estado_rol',
            $role,
            "Rol '{$role->name}' ".($role->estado ? 'activado' : 'desactivado').' por '.(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => $role->estado ? 'Rol activado correctamente.' : 'Rol desactivado correctamente.',
            'estado' => $role->estado,
        ]);
    }
}
