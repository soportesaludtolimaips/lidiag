<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\UserAgregarRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

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
        $usurios = User::whereHas('roles', function ($query) use ($tipo_user) {
            $query->where('name', $tipo_user);
        })->orderBy('name', 'asc')->get();

        return $usurios;
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

        $RolesDeUsuario = array();
        foreach ($userRole as $del) {
            $RolesDeUsuario[] = $del->id;
        }

        return response()->json(['usuario' => $usuario, 'roles' => $roles, 'RolesDeUsuario' => $RolesDeUsuario]);
    }

    public function estadoUsuario(Request $request)
    {
        $seguridad_usuario = User::find($request->id);
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
        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make(
            $registro,
            [
                'num_docu' => 'required|string|max:20',
                'reg_med' => 'nullable|string|max:20',
                'name' => 'required|string|max:50',
                'email' => 'required|email|max:255|unique:users,email',
                'tel' => 'nullable|string|max:50',
                'facebook' => 'nullable|string|max:100',
                'twitter' => 'nullable|string|max:100',
                'youtube' => 'nullable|string|max:100',
                'interface_id' => 'nullable|string|max:255',
                'permitir_transcribir' => 'nullable|boolean',
                'password' => 'required|string|min:6',
                'estado' => 'nullable|boolean',
                'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ],
            [
                'num_docu.required' => 'El número de documento es obligatorio.',
                'num_docu.max' => 'El número de documento no debe exceder los 20 caracteres.',
                'reg_med.max' => 'El número de registro médico no debe exceder los 20 caracteres.',
                'name.required' => 'El nombre es obligatorio.',
                'name.max' => 'El nombre no debe exceder los 50 caracteres.',
                'email.required' => 'El email es obligatorio.',
                'email.email' => 'El email debe ser una dirección de correo válida.',
                'email.unique' => 'El email ya está en uso.',
                'tel.max' => 'El teléfono no debe exceder los 50 caracteres.',
                'facebook.max' => 'El enlace de Facebook no debe exceder los 100 caracteres.',
                'twitter.max' => 'El enlace de Twitter no debe exceder los 100 caracteres.',
                'youtube.max' => 'El enlace de YouTube no debe exceder los 100 caracteres.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'firma.file' => 'El campo firma debe ser un archivo.',
                'firma.mimes' => 'El campo firma debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
                'firma.max' => 'El archivo de firma no debe exceder los 2048 kilobytes.',
                'avatar.file' => 'El campo avatar debe ser un archivo.',
                'avatar.mimes' => 'El campo avatar debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
                'avatar.max' => 'El archivo de avatar no debe exceder los 2048 kilobytes.',
            ]
        );

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $seguridad_usuario = new User();
        $seguridad_usuario->num_docu = $registro['num_docu'];
        $seguridad_usuario->reg_med = $registro['reg_med'];
        $seguridad_usuario->name = $registro['name'];
        $seguridad_usuario->email = $registro['email'];

        if ($registro['password']) {
            $seguridad_usuario->password = bcrypt($registro['password']);
        }

        if ($request->hasFile('firma')) {

            $archivo = $request->firma;

            if (!is_null($seguridad_usuario->firma) && Storage::disk('firmas_medicos')->exists($seguridad_usuario->firma)) {
                Storage::disk('firmas_medicos')->delete($seguridad_usuario->firma);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarFirma = $archivo->storeAs('firmas_medicos', $nombreEncrip);
            $seguridad_usuario->firma = $cargarFirma;
        }

        if ($request->hasFile('avatar')) {
            $archivo = $request->avatar;

            if (!is_null($seguridad_usuario->imagen_perfil) && Storage::disk('avatars')->exists($seguridad_usuario->imagen_perfil)) {
                Storage::disk('avatars')->delete($seguridad_usuario->imagen_perfil);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarAvatar = $archivo->storeAs('avatars', $nombreEncrip);
            $seguridad_usuario->imagen_perfil = $cargarAvatar;
        }

        $seguridad_usuario->save();

        $rolesDeUsuario = $registro['rolesUsuario'];
        $rolesInsertar = array();
        foreach ($rolesDeUsuario as $rol) {
            if ($rol['checked'] === true) {
                $rolesInsertar[] = $rol['id'];
            }
        }

        $seguridad_usuario->assignRole($rolesInsertar);

        return response()->json(['message' => 'El usuario se creo correctamente.']);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make($registro, [
            //'num_docu' => 'required|string|max:20|unique:users,num_docu,' . $request->id,
            'reg_med' => 'nullable|string|max:20',
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255|unique:users,email,' . $request->id,
            'tel' => 'nullable|string|max:50',
            'facebook' => 'nullable|string|max:100',
            'twitter' => 'nullable|string|max:100',
            'youtube' => 'nullable|string|max:100',
            'interface_id' => 'nullable|string|max:255',
            'permitir_transcribir' => 'nullable|boolean',
            'password' => 'sometimes|string|min:6',
            'estado' => 'nullable|boolean',
            'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            //'num_docu.required' => 'El número de documento es obligatorio.',
            //'num_docu.max' => 'El número de documento no debe exceder los 20 caracteres.',
            //'num_docu.unique' => 'El número de documento ya está en uso.',
            'reg_med.max' => 'El número de registro médico no debe exceder los 20 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no debe exceder los 50 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser una dirección de correo válida.',
            'email.unique' => 'El email ya está en uso.',
            'tel.max' => 'El teléfono no debe exceder los 50 caracteres.',
            'facebook.max' => 'El enlace de Facebook no debe exceder los 100 caracteres.',
            'twitter.max' => 'El enlace de Twitter no debe exceder los 100 caracteres.',
            'youtube.max' => 'El enlace de YouTube no debe exceder los 100 caracteres.',
            'password.sometimes' => 'La contraseña es opcional.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'firma.file' => 'El campo firma debe ser un archivo.',
            'firma.mimes' => 'El campo firma debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'firma.max' => 'El archivo de firma no debe exceder los 2048 kilobytes.',
            'avatar.file' => 'El campo avatar debe ser un archivo.',
            'avatar.mimes' => 'El campo avatar debe ser un archivo de tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'El archivo de avatar no debe exceder los 2048 kilobytes.',
        ]);

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $seguridad_usuario = User::find($request->id);
        $seguridad_usuario->num_docu = $registro['num_docu'];
        $seguridad_usuario->reg_med = $registro['reg_med'];
        $seguridad_usuario->name = $registro['name'];
        $seguridad_usuario->email = $registro['email'];

        if ($registro['password']) {
            $seguridad_usuario->password = bcrypt($registro['password']);
        }

        if ($request->hasFile('firma')) {

            $archivo = $request->firma;

            if (!is_null($seguridad_usuario->firma) && Storage::disk('firmas_medicos')->exists($seguridad_usuario->firma)) {
                Storage::disk('firmas_medicos')->delete($seguridad_usuario->firma);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarFirma = $archivo->storeAs('firmas_medicos', $nombreEncrip);
            $seguridad_usuario->firma = $cargarFirma;
        }

        if ($request->hasFile('avatar')) {
            $archivo = $request->avatar;

            if (!is_null($seguridad_usuario->imagen_perfil) && Storage::disk('avatars')->exists($seguridad_usuario->imagen_perfil)) {
                Storage::disk('avatars')->delete($seguridad_usuario->imagen_perfil);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $cargarAvatar = $archivo->storeAs('avatars', $nombreEncrip);
            $seguridad_usuario->imagen_perfil = $cargarAvatar;
        }

        $seguridad_usuario->save();

        $rolesDeUsuario = $registro['rolesUsuario'];
        $rolesInsertar = array();
        foreach ($rolesDeUsuario as $rol) {
            if ($rol['checked'] === true) {
                $rolesInsertar[] = $rol['id'];
            }
        }

        $seguridad_usuario->roles()->sync($rolesInsertar);

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
