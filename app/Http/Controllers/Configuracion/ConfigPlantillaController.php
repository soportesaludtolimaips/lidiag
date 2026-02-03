<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigPlantilla;
use Illuminate\Http\Request;

class ConfigPlantillaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // Permisos por acción (Spatie)
        $this->middleware('permission:config.plantillas.index')->only(['index', 'show', 'listarActivos']);
        $this->middleware('permission:config.plantillas.create')->only(['store']);
        $this->middleware('permission:config.plantillas.edit')->only(['update']);
        $this->middleware('permission:config.plantillas.delete')->only(['destroy']);
    }

    /**
     * Lista todas las plantillas de lectura.
     *
     * @return \Illuminate\Http\JsonResponse Lista de plantillas con relaciones (tipo, médico).
     */
    public function index()
    {
        $plantillas = ConfigPlantilla::with(['plantillaTipo', 'medico'])->get();
        return response()->json($plantillas);
    }

    /**
     * Crea una nueva plantilla de lectura.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada (tipo, médico, nombre, contenido, opinión, estado).
     * 2. Crea el registro en la base de datos.
     * 3. Retorna la plantilla creada.
     *
     * @param Request $request Datos de la plantilla.
     * @return \Illuminate\Http\JsonResponse Datos de la plantilla creada.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plantilla_tipo_id' => 'required|exists:config_plantillas_tipos,id',
            'medico_id' => 'required|exists:users,id',
            'nom_plantilla' => 'required|string|max:150',
            'plantilla' => 'required|string',
            'opinion' => 'required|string',
            'estado' => 'boolean',
        ], [
            'plantilla_tipo_id.required' => 'El tipo de plantilla es obligatorio.',
            'plantilla_tipo_id.exists' => 'El tipo de plantilla seleccionado no es válido.',
            'medico_id.required' => 'El campo médico es obligatorio.',
            'medico_id.exists' => 'El médico seleccionado no es válido.',
            'nom_plantilla.required' => 'El nombre de la plantilla es obligatorio.',
            'nom_plantilla.string' => 'El nombre de la plantilla debe ser un texto.',
            'nom_plantilla.max' => 'El nombre de la plantilla no puede superar los 150 caracteres.',
            'plantilla.required' => 'El campo "plantilla" es obligatorio.',
            'plantilla.string' => 'El campo "plantilla" debe ser un texto.',
            'opinion.required' => 'El campo opinión es obligatorio.',
            'opinion.string' => 'El campo opinión debe ser un texto.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ]);

        $detalle = ConfigPlantilla::create($request->all());

        return response()->json($detalle, 201);
    }

    /**
     * Muestra una plantilla específica.
     *
     * @param int $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse Datos de la plantilla.
     */
    public function show($id)
    {
        return response()->json(ConfigPlantilla::findOrFail($id));
    }

    /**
     * Actualiza una plantilla existente.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada.
     * 2. Busca la plantilla por ID.
     * 3. Actualiza el registro.
     * 4. Retorna la plantilla actualizada.
     *
     * @param Request $request Datos a actualizar.
     * @param int $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse Datos actualizados.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'plantilla_tipo_id' => 'exists:plantillas_tipos,id',
            'medico_id' => 'exists:users,id',
            'nom_plantilla' => 'string|max:150',
            'planitalla' => 'string',
            'opinion' => 'string',
            'estado' => 'boolean',
        ], [
            'plantilla_tipo_id.exists' => 'El tipo de plantilla seleccionado no es válido.',
            'medico_id.exists' => 'El médico seleccionado no es válido.',
            'nom_plantilla.string' => 'El nombre de la plantilla debe ser un texto.',
            'nom_plantilla.max' => 'El nombre de la plantilla no puede superar los 150 caracteres.',
            'planitalla.string' => 'El campo "planitalla" debe ser un texto.',
            'opinion.string' => 'El campo opinión debe ser un texto.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ]);

        $detalle = ConfigPlantilla::findOrFail($id);
        $detalle->update($request->all());

        return response()->json($detalle);
    }

    /**
     * Elimina una plantilla.
     *
     * @param int $id ID de la plantilla.
     * @return \Illuminate\Http\JsonResponse Respuesta vacía (204).
     */
    public function destroy($id)
    {
        $detalle = ConfigPlantilla::findOrFail($id);
        $detalle->delete();

        return response()->json(null, 204);
    }

    /**
     * Lista las plantillas activas.
     *
     * @return \Illuminate\Http\JsonResponse Lista de plantillas activas.
     */
    public function listarActivos()
    {
        $plantillas = ConfigPlantilla::with(['plantillaTipo', 'medico'])
            ->where('estado', true)
            ->get();
        return response()->json($plantillas);
    }
}
