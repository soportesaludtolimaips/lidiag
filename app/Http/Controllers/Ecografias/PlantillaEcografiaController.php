<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlantillaEcografiaController extends Controller
{
    public function __construct(
        private readonly PlantillaEcografiaService $plantillaService,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.reporte,web')->only(['editWord']);
    }

    public function editWord(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'medico_id' => 'required|integer|exists:users,id',
            'ecografia_id' => 'required|integer',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'tipo_estudio' => 'nullable|string',
            'lectura' => 'nullable|string',
            'opinion' => 'nullable|string',
            'fec_estudio' => 'nullable|date',
            'fechor_lectura' => 'nullable|date',
            'fechor_trascrito' => 'nullable|date',
            'cod_cups' => 'nullable|string',
            'nom_produc' => 'nullable|string',
            'nom_sede' => 'nullable|string',
            'fec_naci' => 'nullable|date',
            'sexo' => 'nullable|string|max:1',
            'edad' => 'nullable|string',
            'dir' => 'nullable|string',
            'tel' => 'nullable|string',
            'cel' => 'nullable|string',
            'atencion' => 'nullable|string',
            'admin_salud' => 'nullable|string',
            'contrato' => 'nullable|string',
        ]);

        $result = $this->plantillaService->editWord($data);

        return response()->json($result, $result['status']);
    }
}
