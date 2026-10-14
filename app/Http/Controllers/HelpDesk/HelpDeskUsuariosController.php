<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Services\HelpDesk\HelpDeskUsuarioService;
use Illuminate\Http\JsonResponse;

class HelpDeskUsuariosController extends Controller
{
    /**
     * Construye el controlador inyectando el servicio de usuarios del help desk.
     *
     * @param  HelpDeskUsuarioService  $usuarioService  servicio encargado de la lógica de usuarios.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskUsuarioService $usuarioService,
    ) {}

    /**
     * Lista los usuarios que pueden ser asignados como técnicos de tickets en el help desk.
     *
     * @returns JsonResponse respuesta JSON con los usuarios asignables y sus roles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function asignables(): JsonResponse
    {
        $result = $this->usuarioService->asignables();

        return response()->json($result, (int) $result["status"]);
    }
}
