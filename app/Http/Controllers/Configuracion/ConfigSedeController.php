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
    public function __construct()
    {
        $this->middleware('auth');
        // Permitir lectura de sedes a quienes asignan/leen/transcriben estudios (sin abrir CRUD completo)
        $this->middleware('permission:config.sedes.index|estudio.asignar|estudio.transcribir|estudio.leerEstudio')->only(['index', 'buscarPorId', 'listarSedesActivas']);
        $this->middleware('permission:config.sedes.create')->only(['store']);
        $this->middleware('permission:config.sedes.edit')->only(['update']);
        $this->middleware('permission:config.sedes.destroy')->only(['destroy']);
    }

    /**
     * Lista todas las sedes ordenadas alfabéticamente.
     *
     * @return \Illuminate\Http\JsonResponse Lista de sedes.
     */
    public function index()
    {
        $sedes = ConfigSede::orderBy('nom_sede')->get();
        return response()->json($sedes);
    }

    /**
     * Crea una nueva sede con configuración de PACS y plantillas.
     *
     * Flujo del proceso:
     * 1. Decodifica el JSON 'registro' del request.
     * 2. Valida los datos de configuración (DCM, OVIYAM, etc.).
     * 3. Crea la instancia de ConfigSede.
     * 4. Procesa y guarda la plantilla RX si se adjunta (encripta nombre).
     * 5. Procesa y guarda la plantilla Ecografía si se adjunta (encripta nombre).
     * 6. Guarda el registro en la base de datos.
     *
     * @param Request $request Datos de la sede y archivos.
     * @param ConfigSede $configsede Instancia vacía (no usada).
     * @return \Illuminate\Http\JsonResponse Confirmación de creación o errores de validación.
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

    /**
     * Retorna la sede buscada por ID.
     *
     * @param ConfigSede $configsede Instancia de la sede.
     * @return ConfigSede Datos de la sede.
     */
    public function buscarPorId(ConfigSede $configsede)
    {
        //return 'ok';
        return $configsede;
    }

    /**
     * Lista solo las sedes activas.
     *
     * @return \Illuminate\Http\JsonResponse Lista de sedes activas.
     */
    public function listarSedesActivas()
    {
        $sedes = ConfigSede::where('estado', '=', 1)->get();
        return response()->json($sedes);
    }

    /**
     * Actualiza una sede existente y sus plantillas.
     *
     * Flujo del proceso:
     * 1. Decodifica el JSON 'registro'.
     * 2. Valida los datos.
     * 3. Busca la sede por ID.
     * 4. Actualiza campos de configuración.
     * 5. Reemplaza plantillas (RX/Ecografía) si se envían nuevas, eliminando las anteriores.
     * 6. Guarda los cambios.
     *
     * @param Request $request Datos actualizados y archivos.
     * @return \Illuminate\Http\JsonResponse Confirmación de actualización o errores.
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

    /**
     * Método auxiliar para depuración de carga de archivos (No usado en producción).
     *
     * @param Request $request
     * @return mixed Nombre del archivo o null.
     */
    public function updateWitchPlantillas(Request $request)
    {
        if ($request->hasFile('plantilla')) {
            $filename = $request->plantilla->getClientOriginalName();
            info($filename); //log
        }

        return $filename;
    }

    /**
     * Elimina una sede.
     *
     * @param ConfigSede $configSede Instancia a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigSede $configSede)
    {
        $configSede->delete();
        return response()->json(['message' => 'El registro se elimino correctamente.']);
    }
}
