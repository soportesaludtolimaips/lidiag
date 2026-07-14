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
            'bus_fehc_ini' => $request->input('bus_fehc_ini'),
            'bus_fehc_fin' => $request->input('bus_fehc_fin'),
        ]);

        return response()->json($result, $result['status']);
    }

    public function filtarEcografias(Request $request): JsonResponse
    {
        $result = $this->ecografiaService->filtarEcografias([
            'bus_nom_num_docu' => $request->input('bus_nom_num_docu'),
            'bus_fehc_ini' => $request->input('bus_fehc_ini'),
            'bus_fehc_fin' => $request->input('bus_fehc_fin'),
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
        $result = $this->ecografiaService->update($id, $request->all());

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
        $result = $this->plantillaEcografiaService->editWord($request->all());

        return response()->json($result, $result['status']);
    }
}
