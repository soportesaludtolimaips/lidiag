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
     * Display the specified resource.
     *
     * @param  \App\Models\Seguridad\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $seguridad_role = Role::find($id);
        $permisos = Permission::orderby('name')->get();
        $permisosRol = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return response()->json(['seguridad_role' => $seguridad_role, 'permisos' => $permisos, 'permisosRol' => $permisosRol]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Seguridad\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $role = Role::find($id);
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
