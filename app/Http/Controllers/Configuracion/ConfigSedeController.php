<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigSedeRequest;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $registro = json_decode($request->registro);

        $configSede = new ConfigSede();
        $configSede->nom_sede = $registro->nom_sede;
        $configsede->email = $request->email;
        $configSede->ip_dcm = $registro->ip_dcm;
        $configSede->bd_dcm = $registro->bd_dcm;
        $configSede->puerto_dcm = $registro->puerto_dcm;
        $configSede->usuario_dcm = $registro->usuario_dcm;
        $configSede->password_dcm = $registro->password_dcm;
        $configSede->usuario_oviyam = $registro->usuario_oviyam;
        $configSede->password_oviyam = $registro->password_oviyam;
        $configSede->url_oviyam = $registro->url_oviyam;
        $configSede->tap_oviyam = $registro->tap_oviyam;
        $configSede->encabezado = $registro->encabezado;
        $configSede->pie_pagina = $registro->pie_pagina;
        $configSede->estado = $registro->estado;

        /**
         * Almacenos los logos de la sede
         */
        if ($request->hasFile('logo1')) {
            $archivo = $request->logo1;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('logos', $nombreEncrip);
            $configSede->logo_1 = $nombreEncrip;
        }

        if ($request->hasFile('logo2')) {
            $archivo = $request->logo2;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('logos', $nombreEncrip);
            $configSede->logo_2 = $nombreEncrip;
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
        $configsede = ConfigSede::findOrFail($request->id);
        $configsede->nom_sede = $request->nom_sede;
        $configsede->email = $request->email;
        $configsede->ip_dcm = $request->ip_dcm;
        $configsede->bd_dcm = $request->bd_dcm;
        $configsede->puerto_dcm = $request->puerto_dcm;
        $configsede->usuario_dcm = $request->usuario_dcm;
        $configsede->password_dcm = $request->password_dcm;
        $configsede->usuario_oviyam = $request->usuario_oviyam;
        $configsede->password_oviyam = $request->password_oviyam;
        $configsede->url_oviyam = $request->url_oviyam;
        $configsede->tap_oviyam = $request->tap_oviyam;
        $configsede->encabezado = $request->encabezado;
        $configsede->pie_pagina = $request->pie_pagina;
        $configsede->estado = $request->estado;

        /**
         * Almacenos los logos de la sede
         */
        /* if ($request->hasFile('logo1')) {

            Storage::disk('logo1')->delete($configSede->logo_1);

            $archivo = $request->logo1;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('logos', $nombreEncrip);
            $configSede->logo_1 = $nombreEncrip;
        }

        if ($request->hasFile('logo2')) {

            Storage::disk('logo2')->delete($configSede->logo_1);

            $archivo = $request->logo2;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('logos', $nombreEncrip);
            $configSede->logo_2 = $nombreEncrip;
        }
 */
        $configsede->update();

        return response()->json(['message' => 'El registro se actualizo correctamente.']);
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
