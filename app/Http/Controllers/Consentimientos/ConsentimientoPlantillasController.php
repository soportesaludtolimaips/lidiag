<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaIndexRequest;
use App\Http\Requests\Consentimientos\ConsentimientoPlantillaRequest;
use App\Services\Consentimientos\ConsentimientoPlantillaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsentimientoPlantillasController extends Controller
{
    /**
     * Inyecta el servicio de plantillas de consentimiento y configura los middlewares.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly ConsentimientoPlantillaService $service,
    ) {
        $this->middleware('auth');
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.consentimientos.plantillas.index,web')->only(['index', 'show', 'versions']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.create,web')->only(['store']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.edit,web')->only(['update', 'setVigente']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.destroy,web')->only(['destroy']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.preview_pdf,web')->only(['previewPdf']);
    }

    /**
     * Lista las plantillas de consentimiento según módulo, búsqueda y vigencia.
     *
     * @param  ConsentimientoPlantillaIndexRequest  $request  Solicitud validada con los filtros de listado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function index(ConsentimientoPlantillaIndexRequest $request): JsonResponse
    {
        $result = $this->service->list([
            'config_modulo_id' => $request->config_modulo_id,
            'q' => $request->query('q', ''),
            'vigentes' => $request->query('vigentes', '1'),
        ]);

        return response()->json($result, $result["status"]);
    }

    /**
     * Muestra los detalles de una plantilla de consentimiento.
     *
     * @param  int  $id  Identificador de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->service->find($id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Crea una nueva plantilla de consentimiento.
     *
     * @param  ConsentimientoPlantillaRequest  $request  Solicitud validada con los datos de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function store(ConsentimientoPlantillaRequest $request): JsonResponse
    {
        $result = $this->service->create($request->validated(), $request->user()->id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Actualiza una plantilla de consentimiento existente.
     *
     * @param  ConsentimientoPlantillaRequest  $request  Solicitud validada con los datos a actualizar.
     * @param  int  $id  Identificador de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function update(ConsentimientoPlantillaRequest $request, int $id): JsonResponse
    {
        $result = $this->service->update($id, $request->validated(), $request->user()->id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Lista las versiones de plantilla de consentimiento de un producto en un módulo.
     *
     * @param  int  $productoId  Identificador del producto.
     * @param  Request  $request  Solicitud HTTP con el parámetro obligatorio config_modulo_id.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function versions(int $productoId, Request $request): JsonResponse
    {
        $moduloId = (int) $request->query('config_modulo_id', 0);
        if ($moduloId <= 0) {
            return response()->json(['message' => 'config_modulo_id es obligatorio'], 422);
        }
        $result = $this->service->versions($productoId, $moduloId);

        return response()->json($result, $result["status"]);
    }

    /**
     * Marca una plantilla de consentimiento como vigente.
     *
     * @param  int  $id  Identificador de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function setVigente(int $id): JsonResponse
    {
        $result = $this->service->setVigente($id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Elimina una plantilla de consentimiento.
     *
     * @param  int  $id  Identificador de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);

        return response()->json(['ok' => true], $result['status']);
    }

    /**
     * Genera la vista previa en PDF de una plantilla de consentimiento.
     *
     * @param  int  $id  Identificador de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function previewPdf(int $id)
    {
        return $this->service->previewPdf($id);
    }
}
