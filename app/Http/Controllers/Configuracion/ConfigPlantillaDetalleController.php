<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\PlantillaDetalle;
use Illuminate\Http\Request;

class ConfigPlantillaDetalleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(PlantillaDetalle::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'plantilla_tipo_id' => 'required|exists:plantillas_tipos,id',
            'medico_id' => 'required|exists:users,id',
            'nom_plantilla' => 'required|string|max:150',
            'planitalla' => 'required|string',
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
            'planitalla.required' => 'El campo "planitalla" es obligatorio.',
            'planitalla.string' => 'El campo "planitalla" debe ser un texto.',
            'opinion.required' => 'El campo opinión es obligatorio.',
            'opinion.string' => 'El campo opinión debe ser un texto.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ]);

        $detalle = PlantillaDetalle::create($request->all());

        return response()->json($detalle, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json(PlantillaDetalle::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
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

        $detalle = PlantillaDetalle::findOrFail($id);
        $detalle->update($request->all());

        return response()->json($detalle);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $detalle = PlantillaDetalle::findOrFail($id);
        $detalle->delete();

        return response()->json(null, 204);
    }
}
