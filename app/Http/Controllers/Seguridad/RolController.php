<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\RolAgregarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.roles.index')->only(['index']);
        $this->middleware('permission:seguridad.roles.create')->only(['store']);
        $this->middleware('permission:seguridad.roles.show')->only(['show']);
        $this->middleware('permission:seguridad.roles.edit')->only(['update']);
        $this->middleware('permission:seguridad.roles.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los roles y permisos del sistema.
     *
     * @return JsonResponse Array con roles y permisos.
     */
    public function index()
    {
        $registros = Role::all();
        $permission = Permission::get();

        return response()->json([$registros, $permission]);
    }

    /**
     * Crea un nuevo rol y le asigna los permisos seleccionados.
     *
     * @param  RolAgregarRequest  $request  Datos del rol y permisos.
     * @return JsonResponse Confirmación.
     */
    public function store(RolAgregarRequest $request)
    {
        // Forzar guard web: el frontend usa sesiones y el middleware de permisos está configurado para web.
        // Evita que una petición autenticada por Sanctum cree roles con guard_name=sanctum.
        $role = Role::create([
            'name' => $request->input('name'),
            'guard_name' => 'web',
        ]);

        $permisos = $request->permisosRol;
        $permisosInsertar = [];

        foreach ($permisos as $permiso) {
            if ($permiso['checked'] == true) {
                $permisosInsertar[] = $permiso['id'];
            }
        }

        // Resolver permisos solo del guard web para evitar PermissionDoesNotExist (guard sanctum vs web)
        $permissionNames = Permission::query()
            ->whereIn('id', $permisosInsertar)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->values()
            ->all();

        if (count($permissionNames) !== count($permisosInsertar)) {
            return response()->json([
                'message' => 'Uno o más permisos no existen o no pertenecen al guard web.',
            ], 400);
        }

        $role->syncPermissions($permissionNames);

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Muestra un rol específico junto con sus permisos asignados y la lista total de permisos.
     *
     * @param  int  $id  ID del rol.
     * @return JsonResponse Datos del rol, permisos y permisos asignados.
     */
    public function show($id)
    {
        $seguridad_role = Role::find($id);

        if (! $seguridad_role) {
            return response()->json(['message' => 'El rol no se encontro'], 404);
        }

        $permisos = Permission::orderby('name')->get();
        $permisosRol = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return response()->json(['seguridad_role' => $seguridad_role, 'permisos' => $permisos, 'permisosRol' => $permisosRol]);
    }

    /**
     * Actualiza el nombre del rol y sincroniza sus permisos.
     *
     * @param  Request  $request  Datos actualizados del rol.
     * @param  int  $id  ID del rol.
     * @return JsonResponse Confirmación.
     */
    public function update(Request $request, $id)
    {

        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'El rol no se encontro'], 404);
        }

        $role->name = $request->registro['name'];
        // Forzar guard web por consistencia
        $role->guard_name = 'web';
        $role->save();

        $permisos = $request->registro['permisosRol'];
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
            return response()->json([
                'message' => 'Uno o más permisos no existen o no pertenecen al guard web.',
            ], 400);
        }

        $role->syncPermissions($permissionNames);

        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    /**
     * Elimina un rol.
     *
     * @param  Rol  $rol  Instancia del rol a eliminar.
     * @return JsonResponse Confirmación.
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (! $role) {
            return response()->json(['message' => 'El rol no se encontro'], 404);
        }
        $role->delete();

        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }
}
