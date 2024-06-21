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
        $configsede->nom_sede = $registro->nom_sede;
        $configsede->email_repor = $registro->email_repor;
        $configsede->ip_dcm = $registro->ip_dcm;
        $configsede->bd_dcm = $registro->bd_dcm;
        $configsede->puerto_dcm = $registro->puerto_dcm;
        $configsede->usuario_dcm = $registro->usuario_dcm;
        $configsede->password_dcm = $registro->password_dcm;
        $configsede->usuario_oviyam = $registro->usuario_oviyam;
        $configsede->password_oviyam = $registro->password_oviyam;
        $configsede->url_oviyam = $registro->url_oviyam;
        $configsede->tap_oviyam = $registro->tap_oviyam;
        $configsede->estado = $registro->estado;

        /**
         * Almacenos los logos de la sede
         */
        if ($request->hasFile('plantilla')) {
            $archivo = $request->logo1;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configsede->plantilla = $nombreEncrip;
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
        $registro = json_decode($request->registro);

        $configsede = ConfigSede::findOrFail($registro->id);
        $configsede->nom_sede = $registro->nom_sede;
        $configsede->email_repor = $registro->email_repor;
        $configsede->ip_dcm = $registro->ip_dcm;
        $configsede->bd_dcm = $registro->bd_dcm;
        $configsede->puerto_dcm = $registro->puerto_dcm;
        $configsede->usuario_dcm = $registro->usuario_dcm;
        $configsede->password_dcm = $registro->password_dcm;
        $configsede->usuario_oviyam = $registro->usuario_oviyam;
        $configsede->password_oviyam = $registro->password_oviyam;
        $configsede->url_oviyam = $registro->url_oviyam;
        $configsede->tap_oviyam = $registro->tap_oviyam;
        $configsede->estado = $registro->estado;

        /**
         * Almacenos los logos de la sede
         */
        if ($request->hasFile('plantilla')) {
            $archivo = $request->plantilla;
            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

            /** Elimino el archivo de la plantilla actual */
            if ($configsede->plantilla <> '') {
                Storage::disk('plantillas_lecturas')->delete($configsede->plantilla);
            }

            $archivo->storeAs('plantillas_lecturas', $nombreEncrip);
            $configsede->plantilla = $nombreEncrip;
        }

        $configsede->update();

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
