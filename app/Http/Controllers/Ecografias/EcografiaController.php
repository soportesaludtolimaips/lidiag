<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaStoreRequest;
use App\Services\Ecografias\EcografiaService;
use App\Services\Ecografias\PlantillaEcografiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EcografiaController extends Controller
{
    public function __construct(
        private readonly EcografiaService $ecografiaService,
        private readonly PlantillaEcografiaService $plantillaEcografiaService,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.index,web')->only(['index', 'show', 'filtarEcografias']);
        $this->middleware('permission:ecografias.admision,web')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:ecografias.anular,web')->only(['anularEcografia']);
        $this->middleware('permission:ecografias.reporte,web')->only(['generarReporte']);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->ecografiaService->all([
            'bus_nom_num_docu' => $request->input('bus_nom_num_docu'),
            'bus_fech_ini' => $request->input('bus_fech_ini'),
            'bus_fecha_fin' => $request->input('bus_fecha_fin'),
        ]);

        return response()->json($result, $result['status']);
    }

    public function filtarEcografias(Request $request): JsonResponse
    {
        $result = $this->ecografiaService->filtarEcografias([
            'bus_nom_num_docu' => $request->input('bus_nom_num_docu'),
            'bus_fech_ini' => $request->input('bus_fech_ini'),
            'bus_fecha_fin' => $request->input('bus_fecha_fin'),
        ]);

        return response()->json($result, $result['status']);
    }

    public function store(EcografiaStoreRequest $request): JsonResponse
    {
        $result = $this->ecografiaService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->ecografiaService->find($id);

        return response()->json($result, $result['status']);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'medico_id' => 'nullable|integer',
            'sede_id' => 'nullable|integer',
            'prioridad_id' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $result = $this->ecografiaService->update($id, $data);

        return response()->json($result, $result['status']);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->ecografiaService->delete($id);

        return response()->json($result, $result['status']);
    }

    public function anularEcografia(int $id): JsonResponse
    {
        $result = $this->ecografiaService->anular($id, request()->input('motivo_anula'));

        return response()->json($result, $result['status']);
    }

    public function generarReporte(Request $request, int $ecografia_id): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'medico_id' => 'required|integer',
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
        $data['ecografia_id'] = $ecografia_id;

        $result = $this->plantillaEcografiaService->editWord($data);

        return response()->json($result, $result['status']);
    }
}
