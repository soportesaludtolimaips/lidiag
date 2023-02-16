<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Str;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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

        User::firstOrCreate([
            'num_docu' => $request->num_docu,
            'reg_med' => $request->reg_med,
            'name' => $request->name,
            'email' => $request->email,
            'tipo_user' => $request->tipo_user,
            'password' => bcrypt($request->password),
            'imagen_perfil' => asset('storage/img_users/' . $nuevoNombreAvatar),
        ]);

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
        return $request->formData;

        if ($request->file) {
            return "ok";
            $avatar = $request->file;
            $bandera = Str::random(30);
            $nombreAvatar = $avatar->getClientOriginalName();
            $nuevoNombreAvatar = $bandera . '_' . $nombreAvatar;

            return $nuevoNombreAvatar;

            Storage::putFileAs('public/img_users', $avatar, $nuevoNombreAvatar);

            $seguridad_usuario->avatar = asset('storage/img_users/' . $nuevoNombreAvatar);
        }

        $seguridad_usuario->num_docu = $request->num_docu;
        $seguridad_usuario->reg_med = $request->reg_med;
        $seguridad_usuario->name = $request->name;
        $seguridad_usuario->email = $request->email;
        $seguridad_usuario->tipo_user = $request->tipo_user;

        if ($request->password) {
            $seguridad_usuario->password = bcrypt($request->password);
        }

        $seguridad_usuario->save();

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
