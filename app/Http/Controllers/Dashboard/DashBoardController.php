<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashBoardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    public function __construct(
        private readonly DashBoardService $service,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $filters = [
            'days' => (int) $request->query('days', 30),
            'sede_id' => $request->query('sede_id'),
            'medico_id' => $request->query('medico_id'),
        ];

        $result = $this->service->dashboard($filters);

        return response()->json($result['data'], $result['status']);
    }
}
