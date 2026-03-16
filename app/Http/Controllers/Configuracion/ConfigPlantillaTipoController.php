<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigPlantillaTipo;
use Illuminate\Http\Request;

class ConfigPlantillaTipoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:config.plantillas.index|ecografias.leer')->only(['listarActivos']);
        $this->middleware('permission:config.plantillas.create')->only(['store']);
        $this->middleware('permission:config.plantillas.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.delete')->only(['destroy']);
    }

    /**
     * Lista todos los tipos de plantillas.
     *
     * @return \Illuminate\Http\JsonResponse Lista de tipos de plantillas.
     */
    public function index()
    {
        return response()->json(ConfigPlantillaTipo::all());
    }

    /**
     * Crea un nuevo tipo de plantilla.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada (nombre, estado).
     * 2. Crea el registro.
     * 3. Retorna el tipo creado.
     *
     * @param  Request  $request  Datos del tipo de plantilla.
     * @return \Illuminate\Http\JsonResponse Datos del tipo creado.
     */
    public function store(Request $request)
    {
        $request->validate([
            // 'producto_id' => 'required|exists:config_productos,id',
            'nom_tipo' => 'required|string|max:70',
            'estado' => 'boolean',
        ], [
            /* 'producto_id.required' => 'El campo producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no es válido.', */
            'nom_tipo.required' => 'El nombre de la plantilla es obligatorio.',
            'nom_tipo.string' => 'El nombre de la plantilla debe ser un texto.',
            'nom_tipo.max' => 'El nombre de la plantilla no puede superar los 70 caracteres.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ]);

        $plantilla = ConfigPlantillaTipo::create($request->all());

        return response()->json($plantilla, 201);
    }

    /**
     * Muestra un tipo de plantilla específico.
     *
     * @param  int  $id  ID del tipo de plantilla.
     * @return \Illuminate\Http\JsonResponse Datos del tipo de plantilla.
     */
    public function show($id)
    {
        return response()->json(ConfigPlantillaTipo::findOrFail($id));
    }

    /**
     * Actualiza un tipo de plantilla.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada.
     * 2. Busca el tipo por ID.
     * 3. Actualiza el registro.
     * 4. Retorna el tipo actualizado.
     *
     * @param  Request  $request  Datos a actualizar.
     * @param  int  $id  ID del tipo de plantilla.
     * @return \Illuminate\Http\JsonResponse Datos actualizados.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'producto_id' => 'exists:config_productos,id',
            'nom_plantilla' => 'string|max:70',
            'estado' => 'boolean',
        ], [
            'producto_id.exists' => 'El producto seleccionado no es válido.',
            'nom_plantilla.string' => 'El nombre de la plantilla debe ser un texto.',
            'nom_plantilla.max' => 'El nombre de la plantilla no puede superar los 70 caracteres.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ]);

        $plantilla = ConfigPlantillaTipo::findOrFail($id);
        $plantilla->update($request->all());

        return response()->json($plantilla);
    }

    /**
     * Elimina un tipo de plantilla.
     *
     * @param  int  $id  ID del tipo de plantilla.
     * @return \Illuminate\Http\JsonResponse Respuesta vacía (204).
     */
    public function destroy($id)
    {
        $plantilla = ConfigPlantillaTipo::findOrFail($id);
        $plantilla->delete();

        return response()->json(null, 204);
    }

    /**
     * Lista los tipos de plantillas activos.
     *
     * @return \Illuminate\Http\JsonResponse Lista de tipos activos.
     */
    public function listarActivos()
    {
        $plantillas = ConfigPlantillaTipo::where('estado', true)->get();

        return response()->json($plantillas);
    }
}
