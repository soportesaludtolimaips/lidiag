<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $users = User::all();
        return response()->json($users);
    }

    public function listarUsuarios($tipo_user)
    {
        $medicos = User::where('tipo_user', '=', $tipo_user)
            ->where('estado', '=', 1)->get();
        return $medicos;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $usuario = User::find($id);
        $roles = Role::all();
        $userRole = $usuario->roles;

        foreach ($userRole as $del) {
            $RolesDeUsuario[] = $del->id;
        }

        return response()->json(['usuario' => $usuario, 'roles' => $roles, 'RolesDeUsuario' => $RolesDeUsuario]);
    }

    public function estadoUsuario(Request $request, User $seguridad_usuario)
    {
        $seguridad_usuario->estado = $request->estado;
        $seguridad_usuario->save();

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $nuevoNombreAvatar = "";

        if ($request->file) {
            $avatar = $request->file;
            $bandera = Str::random(30);
            $nombreAvatar = $avatar->getClientOriginalName();
            $nuevoNombreAvatar = $bandera . '_' . $nombreAvatar;

            Storage::putFileAs('public/img_users', $avatar, $nuevoNombreAvatar);
        }

        //User::firstOrCreate([
        $Usuario = (new User)->fill([
            'num_docu' => $request->num_docu,
            'reg_med' => $request->reg_med,
            'name' => $request->name,
            'email' => $request->email,
            'tipo_user' => $request->tipo_user,
            'password' => bcrypt($request->password),
            'imagen_perfil' => asset('storage/img_users/' . $nuevoNombreAvatar),
        ]);

        $Usuario->save();
        $Usuario->roles()->attach($request->roles);

        return response()->json(['message' => 'El usuario se creo correctamente.']);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $seguridad_usuario)
    {
        if ($request->file) {

            $avatar = $request->registro['file'];
            $bandera = Str::random(30);
            $nombreAvatar = $avatar->getClientOriginalName();
            $nuevoNombreAvatar = $bandera . '_' . $nombreAvatar;

            return $nuevoNombreAvatar;

            Storage::putFileAs('public/img_users', $avatar, $nuevoNombreAvatar);

            $seguridad_usuario->avatar = asset('storage/img_users/' . $nuevoNombreAvatar);
        }

        $seguridad_usuario->num_docu = $request->registro['num_docu'];
        $seguridad_usuario->reg_med = $request->registro['reg_med'];
        $seguridad_usuario->name = $request->registro['name'];
        $seguridad_usuario->email = $request->registro['email'];
        $seguridad_usuario->tipo_user = $request->registro['tipo_user'];

        if ($request->password) {
            $seguridad_usuario->password = bcrypt($request->registro['password']);
        }

        $seguridad_usuario->save();

        $rolesDeUsuario = $request->registro['rolesUsuario'];
        $rolesInsertar = array();
        foreach ($rolesDeUsuario as $rolesDeUsuario) {
            if ($rolesDeUsuario['checked'] == true) {
                $rolesInsertar[] = $rolesDeUsuario['id'];
            }
        }

        return $rolesInsertar;
        $seguridad_usuario->assignRole($rolesInsertar);

        return response()->json(['message' => 'Registro actualizado correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
