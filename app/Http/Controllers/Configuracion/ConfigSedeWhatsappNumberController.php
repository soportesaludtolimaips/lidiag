<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSedeWhatsappNumber;
use Illuminate\Http\Request;

class ConfigSedeWhatsappNumberController extends Controller
{
    public function index(Request $request)
    {
        $query = ConfigSedeWhatsappNumber::with('sede');

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        return response()->json($query->orderBy('descripcion')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'sede_id' => 'required|exists:config_sedes,id',
            'phone_number_id' => 'required|string|max:50|unique:config_sede_whatsapp_numbers,phone_number_id',
            'numero' => 'required|string|max:20',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $number = ConfigSedeWhatsappNumber::create([
            'sede_id' => $request->sede_id,
            'phone_number_id' => $request->phone_number_id,
            'numero' => $request->numero,
            'descripcion' => $request->descripcion,
            'activo' => $request->boolean('activo', true),
        ]);

        return response()->json($number->load('sede'), 201);
    }

    public function update(Request $request, $id)
    {
        $number = ConfigSedeWhatsappNumber::findOrFail($id);

        $request->validate([
            'sede_id' => 'sometimes|exists:config_sedes,id',
            'phone_number_id' => 'sometimes|string|max:50|unique:config_sede_whatsapp_numbers,phone_number_id,'.$id,
            'numero' => 'sometimes|string|max:20',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        $number->update($request->only([
            'sede_id', 'phone_number_id', 'numero', 'descripcion', 'activo'
        ]));

        return response()->json($number->load('sede'));
    }

    public function destroy($id)
    {
        $number = ConfigSedeWhatsappNumber::findOrFail($id);
        $number->delete();

        return response()->json(['message' => 'Número eliminado correctamente.']);
    }
}
