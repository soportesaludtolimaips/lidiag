<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskCategoriaRequest;
use App\Models\HelpDesk\HelpDeskCategoria;
use App\Services\HelpDesk\HelpDeskCategoriaService;
use Illuminate\Http\JsonResponse;

class HelpDeskCategoriasController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de categorías.
     *
     * @param  HelpDeskCategoriaService  $categoriaService  Servicio encargado de la lógica de categorías.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskCategoriaService $categoriaService,
    ) {}

    /**
     * Lista todas las categorías de help desk ordenadas por nombre.
     *
     * @returns JsonResponse Respuesta JSON con la lista de categorías.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function index(): JsonResponse
    {
        $result = $this->categoriaService->all();

        return response()->json($result, $result["status"]);
    }

    /**
     * Crea una nueva categoría de help desk validando los datos de entrada.
     *
     * @param  HelpDeskCategoriaRequest  $request  Petición validada con el nombre y estado de la categoría.
     *
     * @returns JsonResponse Respuesta JSON con la categoría creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function store(HelpDeskCategoriaRequest $request): JsonResponse
    {
        $result = $this->categoriaService->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Muestra el detalle de una categoría específica.
     *
     * @param  HelpDeskCategoria  $categoria  Categoría a consultar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con la categoría solicitada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function show(HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->find($categoria->id);

        return response()->json($result, $result["status"]);
    }

    /**
     * Actualiza los datos de una categoría existente.
     *
     * @param  HelpDeskCategoriaRequest  $request  Petición validada con los campos a actualizar.
     * @param  HelpDeskCategoria  $categoria  Categoría a actualizar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con la categoría actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function update(HelpDeskCategoriaRequest $request, HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->update($categoria->id, $request->validated());

        return response()->json($result, $result["status"]);
    }

    /**
     * Desactiva (elimina lógicamente) una categoría de help desk.
     *
     * @param  HelpDeskCategoria  $categoria  Categoría a desactivar (inyectada por route model binding).
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function destroy(HelpDeskCategoria $categoria): JsonResponse
    {
        $result = $this->categoriaService->delete($categoria->id);

        return response()->json(['ok' => true], $result['status']);
    }
}
