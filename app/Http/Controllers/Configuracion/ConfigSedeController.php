<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigSedeRequest;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Str;

class ConfigSedeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sedes = ConfigSede::orderBy('nom_sede')->get();
        return response()->json($sedes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ConfigSede $configsede)
    {
        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make(
            $registro,
            [
                'nom_sede' => 'required|string|max:150',
                'ip_dcm' => 'required|string|max:15',
                'bd_dcm' => 'required|string|max:50',
                'puerto_dcm' => 'required|string|max:5',
                'usuario_dcm' => 'required|string|max:30',
                'password_dcm' => 'required|string|max:30',
                'usuario_oviyam' => 'required|string|max:20',
                'password_oviyam' => 'required|string|max:20',
                'url_oviyam' => 'required|string|max:100',
                'tap_oviyam' => 'required|string|max:100', // Validación para que no sea 0

            ],
            [
                'nom_sede.required' => 'El campo Nombre de la sede es obligatorio.',
                'ip_dcm.required' => 'El campo IP DCM es obligatorio.',
                'bd_dcm.required' => 'El campo Base de datos DCM es obligatorio.',
                'puerto_dcm.required' => 'El campo Puerto DCM es obligatorio.',
                'usuario_dcm.required' => 'El campo Usuario DCM es obligatorio.',
                'password_dcm.required' => 'El campo Contraseña DCM es obligatorio.',
                'usuario_oviyam.required' => 'El campo Usuario OVIYAM es obligatorio.',
                'password_oviyam.required' => 'El campo Contraseña OVIYAM es obligatorio.',
                'url_oviyam.required' => 'El campo URL OVIYAM es obligatorio.',
                'tap_oviyam.required' => 'El campo TAP OVIYAM es obligatorio.',
            ]
        );

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $configSede = new ConfigSede();
        $configSede->nom_sede = $registro['nom_sede'];
        $configSede->email_repor = $registro['email_repor'];
        $configSede->ip_dcm = $registro['ip_dcm'];
        $configSede->bd_dcm = $registro['bd_dcm'];
        $configSede->puerto_dcm = $registro['puerto_dcm'];
        $configSede->usuario_dcm = $registro['usuario_dcm'];
        $configSede->password_dcm = $registro['password_dcm'];
        $configSede->usuario_oviyam = $registro['usuario_oviyam'];
        $configSede->password_oviyam = $registro['password_oviyam'];
        $configSede->url_oviyam = $registro['url_oviyam'];
        $configSede->tap_oviyam = $registro['tap_oviyam'];
        $configSede->estado = $registro['estado'];

        /**
         * Almacenos lla plantilla para RX
         */
        if ($request->hasFile('plantilla_rx')) {
            $archivo = $request->plantilla_rx;

            if (!is_null($configSede->plantilla_rx) && Storage::disk('plantillas_lecturas')->exists($configSede->plantilla_rx)) {
                Storage::disk('plantillas_lecturas')->delete($configSede->plantilla_rx);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configSede->plantilla_rx = $nombreEncrip;
        }

        /**
         * Almacenos lla plantilla para EcografiasX
         */
        if ($request->hasFile('plantilla_ecografia')) {

            $archivo = $request->plantilla_ecografia;

            if (!is_null($configSede->plantilla_ecografia) && Storage::disk('plantillas_lecturas')->exists($configSede->plantilla_ecografia)) {
                Storage::disk('plantillas_lecturas')->delete($configSede->plantilla_ecografia);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configSede->plantilla_ecografia = $nombreEncrip;
        }

        $configSede->save();
        return response()->json(['message' => 'El registro se creo correctamente']);
    }

    public function buscarPorId(ConfigSede $configsede)
    {
        //return 'ok';
        return $configsede;
    }

    public function listarSedesActivas()
    {
        $sedes = ConfigSede::where('estado', '=', 1)->get();
        return response()->json($sedes);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigSede  $configSede
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        $registro = json_decode($request->input('registro'), true);

        $validator = Validator::make(
            $registro,
            [
                'nom_sede' => 'required|string|max:150',
                'ip_dcm' => 'required|string|max:15',
                'bd_dcm' => 'required|string|max:50',
                'puerto_dcm' => 'required|string|max:5',
                'usuario_dcm' => 'required|string|max:30',
                'password_dcm' => 'required|string|max:30',
                'usuario_oviyam' => 'required|string|max:20',
                'password_oviyam' => 'required|string|max:20',
                'url_oviyam' => 'required|string|max:100',
                'tap_oviyam' => 'required|string|max:100', // Validación para que no sea 0

            ],
            [
                'nom_sede.required' => 'El campo Nombre de la sede es obligatorio.',
                'ip_dcm.required' => 'El campo IP DCM es obligatorio.',
                'bd_dcm.required' => 'El campo Base de datos DCM es obligatorio.',
                'puerto_dcm.required' => 'El campo Puerto DCM es obligatorio.',
                'usuario_dcm.required' => 'El campo Usuario DCM es obligatorio.',
                'password_dcm.required' => 'El campo Contraseña DCM es obligatorio.',
                'usuario_oviyam.required' => 'El campo Usuario OVIYAM es obligatorio.',
                'password_oviyam.required' => 'El campo Contraseña OVIYAM es obligatorio.',
                'url_oviyam.required' => 'El campo URL OVIYAM es obligatorio.',
                'tap_oviyam.required' => 'El campo TAP OVIYAM es obligatorio.',
            ]
        );

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $configSede = ConfigSede::findOrFail($registro['id']);
        $configSede->nom_sede = $registro['nom_sede'];
        $configSede->email_repor = $registro['email_repor'];
        $configSede->ip_dcm = $registro['ip_dcm'];
        $configSede->bd_dcm = $registro['bd_dcm'];
        $configSede->puerto_dcm = $registro['puerto_dcm'];
        $configSede->usuario_dcm = $registro['usuario_dcm'];
        $configSede->password_dcm = $registro['password_dcm'];
        $configSede->usuario_oviyam = $registro['usuario_oviyam'];
        $configSede->password_oviyam = $registro['password_oviyam'];
        $configSede->url_oviyam = $registro['url_oviyam'];
        $configSede->tap_oviyam = $registro['tap_oviyam'];
        $configSede->estado = $registro['estado'];

        /**
         * Almacenos lla plantilla para RX
         */
        if ($request->hasFile('plantilla_rx')) {
            $archivo = $request->plantilla_rx;

            if (!is_null($configSede->plantilla_rx) && Storage::disk('plantillas_lecturas')->exists($configSede->plantilla_rx)) {
                Storage::disk('plantillas_lecturas')->delete($configSede->plantilla_rx);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configSede->plantilla_rx = $nombreEncrip;
        }

        /**
         * Almacenos lla plantilla para EcografiasX
         */
        if ($request->hasFile('plantilla_ecografia')) {

            $archivo = $request->plantilla_ecografia;

            if (!is_null($configSede->plantilla_ecografia) && Storage::disk('plantillas_lecturas')->exists($configSede->plantilla_ecografia)) {
                Storage::disk('plantillas_lecturas')->delete($configSede->plantilla_ecografia);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configSede->plantilla_ecografia = $nombreEncrip;
        }

        $configSede->update();

        return response()->json(['message' => 'El registro se actualizo correctamente.'], 200);
    }

    public function updateWitchPlantillas(Request $request)
    {
        if ($request->hasFile('plantilla')) {
            $filename = $request->plantilla->getClientOriginalName();
            info($filename); //log
        }

        return $filename;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigSede  $configsede
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigSede $configSede)
    {
        $configSede->delete();
        return response()->json(['message' => 'El registro se elimino correctamente.']);
    }
}
