<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Services\HelpDesk\HelpDeskUsuarioService;
use Illuminate\Http\JsonResponse;

class HelpDeskUsuariosController extends Controller
{
    public function __construct(
        private readonly HelpDeskUsuarioService $usuarioService,
    ) {}

    public function asignables(): JsonResponse
    {
        $result = $this->usuarioService->asignables();

        return response()->json($result['data'], $result['status']);
    }
}
