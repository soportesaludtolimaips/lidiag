<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaProductoRequest;
use App\Services\Ecografias\EcografiaProductoService;
use Illuminate\Http\JsonResponse;

class EcografiaProductoController extends Controller
{
    /**
     * Constructor del controlador que inyecta el servicio de productos de ecografía.
     *
     * @param  EcografiaProductoService  $productoService  Servicio encargado de la lógica de negocio de los productos de ecografía.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function __construct(
        private readonly EcografiaProductoService $productoService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:ecografias.index,web')->only(['index', 'show']);
        $this->middleware('permission:ecografias.admision,web')->only(['store', 'update', 'destroy']);
    }

    /**
     * Obtiene y retorna la lista completa de productos de ecografía registrados en el sistema.
     *
     * @returns JsonResponse Respuesta JSON con el listado de productos de ecografía y su respectivo código de estado HTTP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function index(): JsonResponse
    {
        $result = $this->productoService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo producto de ecografía validando los datos de entrada y delegando el registro al servicio correspondiente.
     *
     * @param  EcografiaProductoRequest  $request  Solicitud HTTP con los datos validados del producto de ecografía a crear.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la creación y su respectivo código de estado HTTP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function store(EcografiaProductoRequest $request): JsonResponse
    {
        $result = $this->productoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra los detalles de un producto de ecografía específico identificado por su identificador.
     *
     * @param  int  $id  Identificador único del producto de ecografía a consultar.
     *
     * @returns JsonResponse Respuesta JSON con los datos del producto de ecografía y su respectivo código de estado HTTP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->productoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza los datos de un producto de ecografía existente según su identificador y la información validada.
     *
     * @param  EcografiaProductoRequest  $request  Solicitud HTTP con los datos validados a actualizar del producto de ecografía.
     * @param  int  $id  Identificador único del producto de ecografía a actualizar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la actualización y su respectivo código de estado HTTP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function update(EcografiaProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->productoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un producto de ecografía del sistema según su identificador único.
     *
     * @param  int  $id  Identificador único del producto de ecografía a eliminar.
     *
     * @returns JsonResponse Respuesta JSON con el resultado de la eliminación y su respectivo código de estado HTTP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-05-14
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->productoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
