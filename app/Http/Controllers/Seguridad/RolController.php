<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\Segudiada\RolAgregarRequest;
use Illuminate\Http\Request;
use DB;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $registros = Role::all();
        $permission = Permission::get();

        return response()->json([$registros, $permission]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RolAgregarRequest $request)
    {
        $role = Role::create(['name' => $request->input('name')]);
        //$role->syncPermissions($request->input('permisosRol'));

        $permisos = $request->permisosRol;

        $permisosInsertar = array();

        foreach ($permisos as $permiso) {

            if ($permiso['checked'] == true) {
                /* $role->syncPermissions([
                    'permission_id' => $permiso['id'],
                ]); */
                /* DB::table('role_has_permissions')->insert([
                    'permission_id' => $permiso['id'],
                    'role_id' => $role->id,
                ]); */

                $permisosInsertar[] = $permiso['id'];
            }
        }

        $role->syncPermissions(['permission_id' => $permisosInsertar, 'checked' => 1]);

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Seguridad\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $seguridad_role = Role::find($id);
        $permission = Permission::orderby('name')->get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return response()->json(['seguridad_role' => $seguridad_role, 'permission' => $permission, 'rolePermissions' => $rolePermissions]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Seguridad\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rol $id)
    {
        return $id;
        /*  $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();
        $role->syncPermissions($request->input('permission'));

        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']); */
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Seguridad\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rol $rol)
    {
        //
    }
}
