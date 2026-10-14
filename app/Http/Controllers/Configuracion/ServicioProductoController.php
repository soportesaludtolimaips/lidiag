<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ServicioProductoNoVinculadosRequest;
use App\Http\Requests\Configuracion\StoreServicioProductoRequest;
use App\Services\Configuracion\ServicioProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicioProductoController extends Controller
{
    /**
     * Constructor del controlador que inicializa el servicio de vinculación de servicios y productos.
     *
     * @param  ServicioProductoService  $servicioProductoService  Servicio encargado de la lógica de servicios y productos.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function __construct(
        private readonly ServicioProductoService $servicioProductoService,
    ) {}

    /**
     * Obtiene la lista de productos vinculados, filtrados por sede y opcionalmente por servicio.
     *
     * @param  Request  $request  Solicitud HTTP que puede contener los parámetros sede_id y servicio_id.
     *
     * @returns JsonResponse Respuesta JSON con la lista de pivotes y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->servicioProductoService->all(
            $request->integer("sede_id") ?: null,
            $request->integer("servicio_id") ?: null,
        );

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea o reactiva la vinculación entre una sede, un servicio y un producto.
     *
     * @param  StoreServicioProductoRequest  $request  Solicitud HTTP validada con los datos de la vinculación.
     *
     * @returns JsonResponse Respuesta JSON con la vinculación creada o reactivada y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function store(StoreServicioProductoRequest $request): JsonResponse
    {
        $result = $this->servicioProductoService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Desactiva la vinculación entre un servicio de cita y un producto.
     *
     * @param  int  $id  Identificador de la vinculación a desactivar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la desactivación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->servicioProductoService->delete($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Lista los productos que aún no están vinculados en la sede (y servicio) indicados.
     *
     * @param  ServicioProductoNoVinculadosRequest  $request  Solicitud HTTP validada con los parámetros sede_id y servicio_id (opcional).
     *
     * @returns JsonResponse Respuesta JSON con los productos no vinculados y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function productosNoVinculados(ServicioProductoNoVinculadosRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->servicioProductoService->productosNoVinculados(
            (int) $validated["sede_id"],
            isset($validated["servicio_id"]) ? (int) $validated["servicio_id"] : null,
        );

        return response()->json($result, (int) $result["status"]);
    }
}
