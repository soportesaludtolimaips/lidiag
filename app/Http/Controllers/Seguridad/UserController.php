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
        return $request;
        $Usuario = (new User)->fill([
            'num_docu' => $request->num_docu,
            'reg_med' => $request->reg_med,
            'name' => $request->name,
            'email' => $request->email,
            'tipo_user' => $request->tipo_user,
        ]);

        if ($request->file) {
            $avatar = $request->file;
            $bandera = Str::random(30);
            $nombreAvatar = $avatar->getClientOriginalName();
            $nuevoNombreAvatar = $bandera . '_' . $nombreAvatar;

            Storage::putFileAs('public/img_users', $avatar, $nuevoNombreAvatar);

            $Usuario->avatar = asset('storage/img_users/' . $nuevoNombreAvatar);
        }

        if ($request->password) {
            $Usuario->password = bcrypt($request->password);
        }

        $Usuario->save();

        /* User::updateOrCreate(
            [
                'email' => $request->email
            ],
            [
                'num_docu' => $request->num_docu,
                'reg_med' => $request->reg_med,
                'name' => $request->name,
                'email' => $request->email,
                'tipo_user' => $request->tipo_user,
                'password' => bcrypt($request->password),
                'imagen_perfil' => asset('storage/img_users/' . $nuevoNombreAvatar),
            ]
        ); */
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
