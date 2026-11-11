<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\StoreEstudioProductoRequest;
use App\Http\Requests\Estudios\UpdateEstudioProductoRequest;
use App\Services\Estudios\EstudioProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioProductoController extends Controller
{
    /**
     * Constructor del controlador de productos de estudio
     *
     * @param  EstudioProductoService  $estudioProductoService  Servicio de gestión de productos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function __construct(
        private readonly EstudioProductoService $estudioProductoService,
    ) {}

    /**
     * Lista los productos asociados a un estudio específico
     *
     * @param  Request  $request  Solicitud con el ID del estudio
     *
     * @returns JsonResponse Respuesta con la lista de productos del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->estudioProductoService->listarPorEstudio($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo producto asociado a un estudio
     *
     * @param  StoreEstudioProductoRequest  $request  Solicitud con los datos del producto
     *
     * @returns JsonResponse Respuesta con el producto creado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function store(StoreEstudioProductoRequest $request): JsonResponse
    {
        $result = $this->estudioProductoService->create($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un producto de estudio existente
     *
     * @param  UpdateEstudioProductoRequest  $request  Solicitud con los datos a actualizar
     * @param  int  $id  ID del producto a actualizar
     *
     * @returns JsonResponse Respuesta con el producto actualizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function update(UpdateEstudioProductoRequest $request, int $id): JsonResponse
    {
        $result = $this->estudioProductoService->update($id, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un producto de estudio existente
     *
     * @param  int  $id  ID del producto a eliminar
     *
     * @returns JsonResponse Respuesta de confirmación de eliminación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->estudioProductoService->delete($id);

        return response()->json($result, $result['status']);
    }
}
