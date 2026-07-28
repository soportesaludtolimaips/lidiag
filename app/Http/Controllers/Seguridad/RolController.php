<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\RolAgregarRequest;
use App\Services\Seguridad\RolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RolController extends Controller
{
    public function __construct(
        private readonly RolService $rolService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.roles.index')->only(['index']);
        $this->middleware('permission:seguridad.roles.create')->only(['store']);
        $this->middleware('permission:seguridad.roles.show')->only(['show']);
        $this->middleware('permission:seguridad.roles.edit')->only(['update']);
        $this->middleware('permission:seguridad.roles.destroy')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        $result = $this->rolService->all();

        return response()->json($result, $result['status']);
    }

    public function store(RolAgregarRequest $request): JsonResponse
    {
        $result = $this->rolService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    public function show($id): JsonResponse
    {
        $result = $this->rolService->find((int) $id);

        return response()->json($result, $result['status']);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'registro' => 'required|array',
            'registro.name' => 'required|string|min:2|max:100',
            'registro.permisosRol' => 'nullable|array',
            'registro.permisosRol.*' => 'nullable|array',
            'registro.permisosRol.*.id' => 'required_with:registro.permisosRol.*|integer',
            'registro.permisosRol.*.name' => 'required_with:registro.permisosRol.*|string',
            'registro.permisosRol.*.checked' => 'required_with:registro.permisosRol.*|boolean',
        ]);

        $result = $this->rolService->update((int) $id, $data);

        return response()->json($result, $result['status']);
    }

    public function destroy($id): JsonResponse
    {
        $result = $this->rolService->delete((int) $id);

        return response()->json($result, $result['status']);
    }
}
