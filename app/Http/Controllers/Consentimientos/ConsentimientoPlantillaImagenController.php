<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaImagenStoreRequest;
use App\Services\Consentimientos\ConsentimientoPlantillaImagenService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ConsentimientoPlantillaImagenController extends Controller
{
    public function __construct(
        private readonly ConsentimientoPlantillaImagenService $service,
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:consentimientos.show|consentimientos.configurar,web')->only(['index', 'stream']);
        $this->middleware('permission:consentimientos.configurar,web')->only(['store', 'destroy']);
    }

    public function index(int $plantillaId): JsonResponse
    {
        $result = $this->service->index($plantillaId);

        return response()->json($result, $result['status']);
    }

    public function store(ConsentimientoPlantillaImagenStoreRequest $request, int $plantillaId): JsonResponse
    {
        $result = $this->service->store($plantillaId, $request->file('imagen'), $request->validated());

        return response()->json($result, $result['status']);
    }

    public function stream(int $id)
    {
        $result = $this->service->localizar($id);
        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($result['data']['disk']);

        return $disk->response($result['data']['rel'], null, ['Content-Type' => $result['data']['mime']]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->destroy($id);

        return response()->json($result, $result['status']);
    }
}
