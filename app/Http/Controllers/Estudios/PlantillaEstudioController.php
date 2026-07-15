<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Services\Estudios\PlantillaEstudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantillaEstudioController extends Controller
{
    public function __construct(
        private readonly PlantillaEstudioService $plantillaEstudioService,
    ) {}

    public function editWord(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'estudio_producto_id' => 'required|integer',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'lectura' => 'required|string',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'id_estudio' => 'nullable|integer',
            'sexo' => 'nullable|string|max:1',
            'edad' => 'nullable|string',
            'eps' => 'nullable|string',
            'contrato' => 'nullable|string',
            'fec_estudio' => 'nullable|date',
            'fechor_lectura' => 'nullable|date',
            'fechor_trascrito' => 'nullable|date',
            'atencion' => 'nullable|string',
            'nom_sede' => 'nullable|string',
            'nombres_medico' => 'nullable|string',
            'reg_medico' => 'nullable|string',
            'num_docu_medico' => 'nullable|string',
            'firma_medico' => 'nullable|string',
        ]);

        $result = $this->plantillaEstudioService->editWord($data);

        return response()->json($result, $result['status']);
    }
}
