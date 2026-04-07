<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    public function dashboard(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = max(1, min($days, 365));

        $userId = (int) auth()->id();
        $isAdmin = $this->isAdministrador();

        $scope = $this->resolveScope($request, $isAdmin, $userId);

        $payload = $this->buildDashboardPayload($request, $scope, $days, $isAdmin, $userId);

        return response()->json($payload);
    }

    protected function resolveScope(Request $request, bool $isAdmin, int $userId): array
    {
        $sedeId = $request->query('sede_id');
        $sedeId = is_numeric($sedeId) ? (int) $sedeId : null;
        if ($sedeId !== null && $sedeId <= 0) {
            $sedeId = null;
        }

        $medicoId = null;
        if ($isAdmin) {
            $m = $request->query('medico_id');
            $medicoId = is_numeric($m) ? (int) $m : null;
            if ($medicoId !== null && $medicoId <= 0) {
                $medicoId = null;
            }
        } else {
            $medicoId = $userId;
        }

        return [
            'medico_id' => $medicoId,
            'sede_id' => $sedeId,
        ];
    }

    protected function buildDashboardPayload(Request $request, array $scope, int $days, bool $isAdmin, int $userId): array
    {
        $indicadores = $this->getIndicadores($scope, $days);
        return $indicadores;
        \Log::info('Indicadores:', $indicadores);

        $operacion = $this->getOperacion($scope);
        \Log::info('Operacion:', $operacion);

        $catalogos = $this->getCatalogos($isAdmin, $scope);

        return array_merge($indicadores, [
            'filters' => [
                'selected' => $scope,
                'options' => $catalogos,
            ],
            'operacion' => $operacion,
            'series' => [],
            'calidad' => [],
            'tablas' => [],
            'productividad' => [],
            'sla' => [],
            'alerts' => [],
        ]);
    }

    protected function getIndicadores(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays(30)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();

        $baseQuery = DB::table('v_dashboard_indicadores_base')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if (!empty($scope['medico_id'])) {
            $baseQuery->where('medico_id', '=', (int) $scope['medico_id']);
        }
        if (!empty($scope['sede_id'])) {
            $baseQuery->where('sede_id', '=', (int) $scope['sede_id']);
        }

        $baseQuery = DB::table('v_dashboard_indicadores_base')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

        if (!empty($scope['medico_id'])) {
            $baseQuery->where('medico_id', '=', (int) $scope['medico_id']);
        }
        if (!empty($scope['sede_id'])) {
            $baseQuery->where('sede_id', '=', (int) $scope['sede_id']);
        }
        return [$baseQuery->toSql(), $fechaInicio, $fechaFin];

        $estudiosAsignados = (int) $baseQuery->clone()->count();
        $estudiosLeidos = (int) DB::table('v_dashboard_indicadores_base')
            ->whereBetween('fechor_trascrito', [$fechaInicio, $fechaFin])
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();
        $estudiosPorTranscribir = (int) DB::table('v_dashboard_indicadores_base')
            ->whereNull('fechor_trascrito')
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        return [
            'estudiosAsignados' => $estudiosAsignados,
            'estudiosLeidos' => $estudiosLeidos,
            'estudiosPorTranscribir' => $estudiosPorTranscribir,
            'estudiosDescargados' => 0,
        ];
    }

    protected function getOperacion(array $scope): array
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        $asignadosHoy = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereBetween('fecha_creacion', [$from, $to])
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        $pendientesLectura = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereNull('fechor_lectura')
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        $pendientesLecturaHoy = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereNull('fechor_lectura')
            ->whereBetween('fecha_creacion', [$from, $to])
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        $leidosHoy = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereNotNull('fechor_lectura')
            ->whereBetween('fechor_lectura', [$from, $to])
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        $pendientesTranscripcion = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereNull('fechor_trascrito')
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        $transcritosHoy = (int) DB::table('v_dashboard_operacion_hoy_base')
            ->whereNotNull('fechor_trascrito')
            ->whereBetween('fechor_trascrito', [$from, $to])
            ->when(!empty($scope['medico_id']), fn($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(!empty($scope['sede_id']), fn($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->count();

        return [
            'asignadosHoy' => $asignadosHoy,
            'pendientesLectura' => $pendientesLectura,
            'pendientesLecturaHoy' => $pendientesLecturaHoy,
            'leidosHoy' => $leidosHoy,
            'pendientesTranscripcion' => $pendientesTranscripcion,
            'transcritosHoy' => $transcritosHoy,
        ];
    }

    protected function getCatalogos(bool $isAdmin, array $scope): array
    {
        $sedes = DB::table('config_sedes')
            ->select('id', 'nom_sede')
            ->orderBy('nom_sede', 'ASC')
            ->get();

        $medicos = collect();
        if ($isAdmin) {
            $medicos = DB::table('users as u')
                ->select('u.id', 'u.name')
                ->where('u.estado', '=', 1)
                ->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('model_has_roles as mhr')
                        ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                        ->whereColumn('mhr.model_id', '=', 'u.id')
                        ->where('r.name', 'like', '%Medico%');
                })
                ->orderBy('u.name', 'ASC')
                ->limit(100)
                ->get();
        }

        return [
            'sedes' => $sedes,
            'medicos' => $medicos,
        ];
    }

    protected function isAdministrador(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole('Administrador');
    }
}
