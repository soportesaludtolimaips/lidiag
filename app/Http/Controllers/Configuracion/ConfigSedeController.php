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
            $archivo = $request->plantilla;

            if (!is_null($configSede->plantilla) && Storage::disk('plantillas_lecturas')->exists($configsede->plantilla)) {
                Storage::disk('plantillas_lecturas')->delete($configsede->plantilla);
            }

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

        $registro = json_decode($request->imput('registro'), true);

        /* $validator = Validator::make(
            $registro,
            [
                'medico_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
                'prioridad_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
                'num_docu' => 'required',
                'nombres' => 'required',
                'productosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
                'diagnosticosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
            ],
            [
                'medico_id.required' => 'Te hizo falta el medico.',
                'medico_id.not_in' => 'Te hizo falta el medico.',
                'prioridad_id.required' => 'Te hizo falta la prioridad del estudio.',
                'prioridad_id.not_in' => 'Te hizo falta la prioridad del estudio.',
                'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
                'nombres.required' => 'Te hizo falta los nombres del paciente.',
                'productosEstudio.required' => 'Debe elegir al menos un producto.',
                'productosEstudio.min' => 'Debe elegir al menos un producto.',
                'diagnosticosEstudio.required' => 'Debe elegir al menos un diagnostico.',
                'diagnosticosEstudio.min' => 'Debe elegir al menos un diagnostico.',
            ]
        );

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        } */

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

            if (!is_null($configsede->plantilla) && Storage::disk('plantillas_lecturas')->exists($configsede->plantilla)) {
                Storage::disk('plantillas_lecturas')->delete($configsede->plantilla);
            }

            $nombreEncrip = Str::random(50) . "." . $archivo->extension();

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
