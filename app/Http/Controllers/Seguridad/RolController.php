<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\Seguridad\RolAgregarRequest;
use Illuminate\Http\Request;
use DB;

class RolController extends Controller
{
    /**
     * Lista todos los roles y permisos del sistema.
     *
     * @return \Illuminate\Http\JsonResponse Array con roles y permisos.
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
     * @param RolAgregarRequest $request Datos del rol y permisos.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function store(RolAgregarRequest $request)
    {
        $role = Role::create(['name' => $request->input('name')]);

        $permisos = $request->permisosRol;
        $permisosInsertar = array();

        foreach ($permisos as $permiso) {
            if ($permiso['checked'] == true) {
                $permisosInsertar[] = $permiso['id'];
            }
        }

        $role->syncPermissions(['permission_id' => $permisosInsertar]);

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Muestra un rol específico junto con sus permisos asignados y la lista total de permisos.
     *
     * @param int $id ID del rol.
     * @return \Illuminate\Http\JsonResponse Datos del rol, permisos y permisos asignados.
     */
    public function show($id)
    {
        $seguridad_role = Role::find($id);

        if (!$seguridad_role) {
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
     * @param Request $request Datos actualizados del rol.
     * @param int $id ID del rol.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function update(Request $request, $id)
    {

        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'El rol no se encontro'], 404);
        }

        $role->name = $request->registro['name'];
        $role->save();

        $permisos = $request->registro['permisosRol'];
        $permisosInsertar = array();

        foreach ($permisos as $permiso) {
            if ($permiso['checked'] == true) {
                $permisosInsertar[] = $permiso['id'];
            }
        }

        $role->syncPermissions(['permission_id' => $permisosInsertar]);

        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    /**
     * Elimina un rol.
     *
     * @param Rol $rol Instancia del rol a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function destroy(Rol $rol)
    {
        if (!$rol) {
            return response()->json(['message' => 'El rol no se encontro'], 404);
        }

        $rol->delete();

        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }
}
