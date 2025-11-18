<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigPlantillaTipo;
use Illuminate\Http\Request;

class ConfigPlantillaTipoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(ConfigPlantillaTipo::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            //'producto_id' => 'required|exists:config_productos,id',
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
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json(ConfigPlantillaTipo::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plantilla = ConfigPlantillaTipo::findOrFail($id);
        $plantilla->delete();

        return response()->json(null, 204);
    }

    /**
     * Listar los tipos de plantillas activos.
     */
    public function listarActivos()
    {
        $plantillas = ConfigPlantillaTipo::where('estado', true)->get();
        return response()->json($plantillas);
    }
}
