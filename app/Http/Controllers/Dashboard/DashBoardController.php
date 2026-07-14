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
        $days = (int) $request->query('days', 30);
        $days = max(1, min($days, 365));

        $userId = (int) auth()->id();
        $isAdmin = $this->service->isAdministrador();

        $scope = $this->service->resolveScope(
            $request->query('sede_id'),
            $isAdmin ? $request->query('medico_id') : null,
            $isAdmin,
            $userId
        );

        $result = $this->service->dashboard($scope, $days, $isAdmin);

        return response()->json($result['data'], $result['status']);
    }
}
