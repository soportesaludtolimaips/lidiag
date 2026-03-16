<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    protected function applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q)
    {
        return $q->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('estudios_productos as ep')
                ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
                ->whereColumn('ep.estudio_id', '=', 'estudios.id')
                ->where('ep.activo', '=', 1)
                ->where('cp.requi_lectura', '=', 1)
                ->where('cp.estado', '=', 1);
        })
            ->where('estudios.estado', '=', 1);
    }

    /**
     * Variante para queries con join de estudios como alias `e`.
     */
    protected function applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q)
    {
        return $q->whereExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('estudios_productos as ep')
                ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
                ->whereColumn('ep.estudio_id', '=', 'e.id')
                ->where('ep.activo', '=', 1)
                ->where('cp.requi_lectura', '=', 1)
                ->where('cp.estado', '=', 1);
        })
            ->where('e.estado', '=', 1);
    }

    /**
     * Endpoint principal del dashboard.
     * Mantiene compatibilidad con el frontend actual (`/reportes.dashboard`).
     * - Administrador: estadísticas globales
     * - Otros roles: estadísticas filtradas por el usuario logueado
     */
    public function dashboard(Request $request)
    {
        return response()->json($this->buildDashboardPayload($request));
    }

    /**
     * Construye el payload completo del dashboard (tarjetas + series para gráficas).
     *
     * @return array<string,mixed>
     */
    protected function buildDashboardPayload(Request $request): array
    {
        $days = (int) $request->query('days', 30);
        $days = max(1, min($days, 365)); // guardrail

        $userId = (int) auth()->id();
        $isAdmin = $this->isAdministrador();

        $scope = $this->resolveScope($request, $isAdmin, $userId);
        $catalogos = $this->getCatalogos($isAdmin, $scope);

        $indicadores = $this->getIndicadores($scope);
        $topModalidades = $this->getTopModalidades($scope, 10);
        $transcritosPorDia = $this->getTranscritosPorDia($scope, $days);
        $operacionHoy = $this->getOperacionHoy($scope);
        $calidad = $this->getCalidad($scope, $days);
        $asignadosPorDia = $this->getAsignadosPorDia($scope, $days);
        $leidosPorDia = $this->getLeidosPorDia($scope, $days);
        $porHoraHoy = $this->getEventosPorHoraHoy($scope);
        $heatmap7d = $this->getHeatmap7d($scope, 7);

        $tablas = [
            'pendientesLecturaTop' => $this->getPendientesLecturaTop($scope, 10),
            'pendientesTranscripcionTop' => $this->getPendientesTranscripcionTop($scope, 10),
        ];

        // B) Productividad: visible para todos (pero con alcance por rol + filtros)
        $productividad = [
            'rankingMedicosTranscritos' => $isAdmin
                ? $this->getRankingMedicosTranscritos($scope, $days, 10)
                : $this->getRankingUsuarioTranscritos($userId, $scope, $days),
        ];
        if ($isAdmin) {
            $productividad['transcritosPorSede'] = $this->getTranscritosPorSede($scope, $days, 10);
        }

        $sla = $this->getSla($scope, $days);
        $alerts = $this->buildAlerts($operacionHoy, $calidad, $sla);

        // Compatibilidad con el dashboard actual (nombres esperados por Vue)
        return array_merge($indicadores, [
            'filters' => [
                'selected' => $scope,
                'options' => $catalogos,
            ],
            'estudiosMasSolicitados' => $topModalidades,

            // Extras para futuras gráficas/tabla
            'series' => [
                'transcritosPorDia' => $transcritosPorDia,
                'asignadosPorDia' => $asignadosPorDia,
                'leidosPorDia' => $leidosPorDia,
                'porHoraHoy' => $porHoraHoy,
                'heatmap7d' => $heatmap7d,
                'dias' => $days,
            ],

            // A) Operación (hoy)
            'operacion' => $operacionHoy,

            // C) Calidad / tiempos / aging
            'calidad' => $calidad,
            'sla' => $sla,

            // Alertas automáticas
            'alerts' => $alerts,

            // Tablas de apoyo (top pendientes)
            'tablas' => $tablas,

            // B) Productividad (solo admin)
            'productividad' => $productividad,
        ]);
    }

    /**
     * Resuelve el "scope" del dashboard a partir de query params y rol.
     *
     * Reglas:
     * - No admin: siempre queda forzado a su propio medico_id.
     * - Admin: puede filtrar por medico_id (opcional).
     *
     * @return array{medico_id:int|null,sede_id:int|null}
     */
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
            $m = is_numeric($m) ? (int) $m : null;
            if ($m !== null && $m > 0) {
                $medicoId = $m;
            }
        } else {
            $medicoId = $userId;
        }

        return [
            'medico_id' => $medicoId,
            'sede_id' => $sedeId,
        ];
    }

    /**
     * Catálogos para poblar filtros en UI.
     *
     * @return array<string,mixed>
     */
    protected function getCatalogos(bool $isAdmin, array $scope): array
    {
        $sedes = DB::table('config_sedes')
            ->select('id', 'nom_sede')
            ->orderBy('nom_sede', 'ASC')
            ->get();

        $medicos = collect();
        if ($isAdmin) {
            // Lista de médicos relevantes (los que aparecen en estudios)
            $medicosQ = DB::table('estudios as e')
                ->join('users as u', 'u.id', '=', 'e.medico_id')
                ->select('u.id', 'u.name')
                ->distinct()
                ->orderBy('u.name', 'ASC');
            $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($medicosQ);
            $medicos = $medicosQ->get();
        }

        return [
            'sedes' => $sedes,
            'medicos' => $medicos,
        ];
    }

    /**
     * Aplica el scope a una query Eloquent de `Estudio`.
     */
    protected function applyScopeToEstudioQuery($q, array $scope)
    {
        if (! empty($scope['medico_id'])) {
            $q->where('medico_id', '=', (int) $scope['medico_id']);
        }
        if (! empty($scope['sede_id'])) {
            $q->where('sede_id', '=', (int) $scope['sede_id']);
        }
        return $q;
    }

    /**
     * Aplica el scope a una query builder con join de estudios como alias `e`.
     */
    protected function applyScopeToJoinedEstudioQuery($q, array $scope)
    {
        if (! empty($scope['medico_id'])) {
            $q->where('e.medico_id', '=', (int) $scope['medico_id']);
        }
        if (! empty($scope['sede_id'])) {
            $q->where('e.sede_id', '=', (int) $scope['sede_id']);
        }
        return $q;
    }

    /**
     * Indicadores principales del dashboard.
     *
     * @return array<string,int>
     */
    public function getIndicadores(array $scope): array
    {
        $estudiosAsignados = $this->countEstudiosAsignados($scope);
        $estudiosLeidos = $this->countEstudiosTranscritos($scope);
        $estudiosPorTranscribir = $this->countEstudiosPendientesTranscripcion($scope);

        // Si no existe una métrica real aún, devolvemos 0 para mantener contrato
        $estudiosDescargados = 0;

        return [
            'estudiosAsignados' => $estudiosAsignados,
            'estudiosLeidos' => $estudiosLeidos,
            'estudiosPorTranscribir' => $estudiosPorTranscribir,
            'estudiosDescargados' => $estudiosDescargados,
        ];
    }

    /**
     * Total de estudios asignados (global o por médico).
     */
    public function countEstudiosAsignados(array $scope): int
    {
        $q = Estudio::query();
        $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
        $this->applyScopeToEstudioQuery($q, $scope);

        return (int) $q->count();
    }

    /**
     * Total de estudios transcritos (leídos).
     */
    public function countEstudiosTranscritos(array $scope): int
    {
        $q = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNotNull('fechor_trascrito');

        $q->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $q->whereHas('estudio', function ($estudioQ) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($estudioQ);
            $this->applyScopeToEstudioQuery($estudioQ, $scope);
        });

        return (int) $q->count();
    }

    /**
     * Total de estudios pendientes de transcripción.
     */
    public function countEstudiosPendientesTranscripcion(array $scope): int
    {
        $q = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNull('fechor_trascrito');

        $q->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $q->whereHas('estudio', function ($estudioQ) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($estudioQ);
            $this->applyScopeToEstudioQuery($estudioQ, $scope);
        });

        return (int) $q->count();
    }

    /**
     * Total de estudios pendientes de lectura.
     */
    public function countEstudiosPendientesLectura(array $scope): int
    {
        $q = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNull('fechor_lectura');

        $q->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $q->whereHas('estudio', function ($estudioQ) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($estudioQ);
            $this->applyScopeToEstudioQuery($estudioQ, $scope);
        });

        return (int) $q->count();
    }

    /**
     * Métricas operativas para HOY (00:00 - 23:59) para tarjetas/alertas.
     *
     * @return array<string,int>
     */
    public function getOperacionHoy(array $scope): array
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        $asignadosHoyQ = Estudio::query()
            ->whereBetween('created_at', [$from, $to]);
        $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($asignadosHoyQ);
        $this->applyScopeToEstudioQuery($asignadosHoyQ, $scope);

        $pendientesLectura = $this->countEstudiosPendientesLectura($scope);

        $pendientesLecturaHoyQ = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNull('fechor_lectura')
            ->whereHas('estudio', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            });
        $pendientesLecturaHoyQ->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $pendientesLecturaHoyQ->whereHas('estudio', function ($q) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
            $this->applyScopeToEstudioQuery($q, $scope);
        });

        $leidosHoyQ = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNotNull('fechor_lectura')
            ->whereBetween('fechor_lectura', [$from, $to]);
        $leidosHoyQ->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $leidosHoyQ->whereHas('estudio', function ($q) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
            $this->applyScopeToEstudioQuery($q, $scope);
        });

        $transcritosHoyQ = EstudioProducto::query()
            ->where('activo', '=', 1)
            ->whereNotNull('fechor_trascrito')
            ->whereBetween('fechor_trascrito', [$from, $to]);
        $transcritosHoyQ->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $transcritosHoyQ->whereHas('estudio', function ($q) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
            $this->applyScopeToEstudioQuery($q, $scope);
        });

        return [
            'asignadosHoy' => (int) $asignadosHoyQ->count(),
            'pendientesLectura' => (int) $pendientesLectura,
            'pendientesLecturaHoy' => (int) $pendientesLecturaHoyQ->count(),
            'leidosHoy' => (int) $leidosHoyQ->count(),
            'pendientesTranscripcion' => (int) $this->countEstudiosPendientesTranscripcion($scope),
            'transcritosHoy' => (int) $transcritosHoyQ->count(),
        ];
    }

    /**
     * Calidad: tiempos promedio y distribución de antigüedad (aging) en pendientes.
     *
     * @return array<string,mixed>
     */
    public function getCalidad(array $scope, int $days = 30): array
    {
        return [
            'tiempos' => $this->getTiemposPromedio($scope, $days),
            'agingLectura' => $this->getAgingPendientesLectura($scope),
            'agingTranscripcion' => $this->getAgingPendientesTranscripcion($scope),
        ];
    }

    /**
     * Cumplimiento de tiempos por prioridad (lectura y transcripción)
     * basado en `config_prioridades.tiempo` (minutos).
     *
     * @return array<string,mixed>
     */
    public function getSla(array $scope, int $days = 30): array
    {
        $curStart = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $prioridades = DB::table('config_prioridades')
            ->select('id', 'nom_priori', 'tiempo')
            ->orderBy('nivel', 'ASC')
            ->get();

        $lectura = [];
        $transcripcion = [];

        foreach ($prioridades as $p) {
            $thresholdMin = is_numeric($p->tiempo) ? (int) $p->tiempo : 0;
            if ($thresholdMin <= 0) {
                continue;
            }

            // Lectura: created_at -> fechor_lectura
            $qL = DB::table('estudios_productos as ep')
                ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
                ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
                ->where('ep.activo', '=', 1)
                ->where('cp.requi_lectura', '=', 1)
                ->where('e.prioridad_id', '=', $p->id)
                ->whereNotNull('ep.fechor_lectura')
                ->where('ep.fechor_lectura', '>=', $curStart);
            $this->applyScopeToJoinedEstudioQuery($qL, $scope);
            $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($qL);

            $rowL = $qL->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, e.created_at, ep.fechor_lectura) > ? THEN 1 ELSE 0 END) as breach
            ", [$thresholdMin])->first();

            $totalL = (int) ($rowL->total ?? 0);
            $breachL = (int) ($rowL->breach ?? 0);
            $lectura[] = [
                'prioridad_id' => (int) $p->id,
                'prioridad' => (string) $p->nom_priori,
                'thresholdMin' => $thresholdMin,
                'total' => $totalL,
                'breach' => $breachL,
                'breachRate' => $totalL > 0 ? round($breachL / $totalL, 4) : 0,
            ];

            // Transcripción: fechor_lectura -> fechor_trascrito
            $qT = DB::table('estudios_productos as ep')
                ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
                ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
                ->where('ep.activo', '=', 1)
                ->where('cp.requi_lectura', '=', 1)
                ->where('e.prioridad_id', '=', $p->id)
                ->whereNotNull('ep.fechor_lectura')
                ->whereNotNull('ep.fechor_trascrito')
                ->where('ep.fechor_trascrito', '>=', $curStart);
            $this->applyScopeToJoinedEstudioQuery($qT, $scope);
            $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($qT);

            $rowT = $qT->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito) > ? THEN 1 ELSE 0 END) as breach
            ", [$thresholdMin])->first();

            $totalT = (int) ($rowT->total ?? 0);
            $breachT = (int) ($rowT->breach ?? 0);
            $transcripcion[] = [
                'prioridad_id' => (int) $p->id,
                'prioridad' => (string) $p->nom_priori,
                'thresholdMin' => $thresholdMin,
                'total' => $totalT,
                'breach' => $breachT,
                'breachRate' => $totalT > 0 ? round($breachT / $totalT, 4) : 0,
            ];
        }

        return [
            'days' => $days,
            'lectura' => $lectura,
            'transcripcion' => $transcripcion,
        ];
    }

    /**
     * Construye alertas automáticas a partir de métricas.
     *
     * @return array<int,array<string,string|int|float>>
     */
    protected function buildAlerts(array $operacion, array $calidad, array $sla): array
    {
        $alerts = [];

        $agingLectura24 = 0;
        foreach (($calidad['agingLectura'] ?? []) as $b) {
            if (($b['bucket'] ?? null) === '24h+') {
                $agingLectura24 = (int) ($b['total'] ?? 0);
            }
        }
        if ($agingLectura24 > 0) {
            $alerts[] = [
                'level' => 'danger',
                'title' => 'Pendientes de lectura',
                'message' => "Hay {$agingLectura24} estudios con antigüedad 24h+ en lectura.",
            ];
        }

        $agingTrans24 = 0;
        foreach (($calidad['agingTranscripcion'] ?? []) as $b) {
            if (($b['bucket'] ?? null) === '24h+') {
                $agingTrans24 = (int) ($b['total'] ?? 0);
            }
        }
        if ($agingTrans24 > 0) {
            $alerts[] = [
                'level' => 'warning',
                'title' => 'Pendientes de transcripción',
                'message' => "Hay {$agingTrans24} estudios con antigüedad 24h+ en transcripción.",
            ];
        }

        // Alerta: incumplimiento alto (solo si hay muestra suficiente)
        foreach (($sla['lectura'] ?? []) as $row) {
            $total = (int) ($row['total'] ?? 0);
            $rate = (float) ($row['breachRate'] ?? 0);
            if ($total >= 10 && $rate >= 0.2) {
                $p = (string) ($row['prioridad'] ?? 'Prioridad');
                $pct = (int) round($rate * 100);
                $alerts[] = [
                    'level' => 'warning',
                    'title' => 'Tiempos de lectura',
                    'message' => "Incumplimiento alto en lectura ({$p}): {$pct}% (últimos {$sla['days']} días).",
                ];
            }
        }
        foreach (($sla['transcripcion'] ?? []) as $row) {
            $total = (int) ($row['total'] ?? 0);
            $rate = (float) ($row['breachRate'] ?? 0);
            if ($total >= 10 && $rate >= 0.2) {
                $p = (string) ($row['prioridad'] ?? 'Prioridad');
                $pct = (int) round($rate * 100);
                $alerts[] = [
                    'level' => 'info',
                    'title' => 'Tiempos de transcripción',
                    'message' => "Incumplimiento en transcripción ({$p}): {$pct}% (últimos {$sla['days']} días).",
                ];
            }
        }

        // Operación: muchos pendientes vs transcritos hoy
        $pend = (int) ($operacion['pendientesLectura'] ?? 0) + (int) ($operacion['pendientesTranscripcion'] ?? 0);
        $done = (int) ($operacion['transcritosHoy'] ?? 0);
        if ($pend > 0 && $done === 0) {
            $alerts[] = [
                'level' => 'info',
                'title' => 'Operación',
                'message' => 'Hay pendientes pero aún no hay transcripciones hoy.',
            ];
        }

        return $alerts;
    }

    /**
     * Promedios (minutos) para:
     * - asignado -> leído (created_at -> fechor_lectura)
     * - leído -> transcrito (fechor_lectura -> fechor_trascrito)
     *
     * @return array<string,mixed>
     */
    public function getTiemposPromedio(array $scope, int $days = 30): array
    {
        $from = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $base = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1);

        $this->applyScopeToJoinedEstudioQuery($base, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($base);

        // asignado -> leído
        $aLectura = (clone $base)
            ->whereNotNull('ep.fechor_lectura')
            ->where('ep.fechor_lectura', '>=', $from)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, e.created_at, ep.fechor_lectura)) as avg_min, COUNT(*) as n')
            ->first();

        // leído -> transcrito
        $lecturaATranscrito = (clone $base)
            ->whereNotNull('ep.fechor_lectura')
            ->whereNotNull('ep.fechor_trascrito')
            ->where('ep.fechor_trascrito', '>=', $from)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito)) as avg_min, COUNT(*) as n')
            ->first();

        return [
            'asignadoALectura' => [
                'avgMin' => (int) round((float) ($aLectura->avg_min ?? 0)),
                'n' => (int) ($aLectura->n ?? 0),
            ],
            'lecturaATranscrito' => [
                'avgMin' => (int) round((float) ($lecturaATranscrito->avg_min ?? 0)),
                'n' => (int) ($lecturaATranscrito->n ?? 0),
            ],
            'dias' => $days,
        ];
    }

    /**
     * Distribución de antigüedad para pendientes de lectura (según created_at del estudio).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAgingPendientesLectura(array $scope): array
    {
        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNull('ep.fechor_lectura');

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        $rows = $q->selectRaw("
            CASE
                WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 2 THEN '0-2h'
                WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 6 THEN '2-6h'
                WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 24 THEN '6-24h'
                ELSE '24h+'
            END as bucket,
            COUNT(*) as total
        ")
            ->groupBy('bucket')
            ->orderByRaw("
                FIELD(bucket, '0-2h', '2-6h', '6-24h', '24h+')
            ")
            ->get();

        return $rows->map(fn($r) => ['bucket' => $r->bucket, 'total' => (int) $r->total])->all();
    }

    /**
     * Distribución de antigüedad para pendientes de transcripción (según fechor_lectura).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAgingPendientesTranscripcion(array $scope): array
    {
        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_lectura')
            ->whereNull('ep.fechor_trascrito');

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        $rows = $q->selectRaw("
            CASE
                WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 2 THEN '0-2h'
                WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 6 THEN '2-6h'
                WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 24 THEN '6-24h'
                ELSE '24h+'
            END as bucket,
            COUNT(*) as total
        ")
            ->groupBy('bucket')
            ->orderByRaw("
                FIELD(bucket, '0-2h', '2-6h', '6-24h', '24h+')
            ")
            ->get();

        return $rows->map(fn($r) => ['bucket' => $r->bucket, 'total' => (int) $r->total])->all();
    }

    /**
     * Top N estudios pendientes de lectura (más antiguos primero).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getPendientesLecturaTop(array $scope, int $limit = 10)
    {
        $q = DB::table('estudios as e')
            ->join('estudios_productos as ep', 'ep.estudio_id', '=', 'e.id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->join('pacientes as p', 'p.id', '=', 'e.paciente_id')
            ->join('config_sedes as s', 's.id', '=', 'e.sede_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNull('ep.fechor_lectura');

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        return $q->select([
            'e.id',
            'e.study_id',
            'e.accession_no',
            'e.study_desc',
            'e.mods_in_study',
            'e.created_at',
            'e.sede_id',
            's.nom_sede as sede',
            'p.num_docu as paciente_num_docu',
            'p.nombres as paciente_nombres',
            'p.seg_nom as paciente_seg_nom',
            'p.pri_ape as paciente_pri_ape',
            'p.seg_ape as paciente_seg_ape',
        ])
            ->selectRaw('TIMESTAMPDIFF(MINUTE, e.created_at, NOW()) as edad_min')
            ->groupBy(
                'e.id',
                'e.study_id',
                'e.accession_no',
                'e.study_desc',
                'e.mods_in_study',
                'e.created_at',
                'e.sede_id',
                's.nom_sede',
                'p.num_docu',
                'p.nombres',
                'p.seg_nom',
                'p.pri_ape',
                'p.seg_ape'
            )
            ->orderByDesc('edad_min')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Top N estudios pendientes de transcripción (más antiguos desde lectura).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getPendientesTranscripcionTop(array $scope, int $limit = 10)
    {
        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->join('pacientes as p', 'p.id', '=', 'e.paciente_id')
            ->join('config_sedes as s', 's.id', '=', 'e.sede_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_lectura')
            ->whereNull('ep.fechor_trascrito');

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        return $q->select([
            'e.id',
            'e.study_id',
            'e.accession_no',
            'e.study_desc',
            'e.mods_in_study',
            'ep.fechor_lectura',
            'e.sede_id',
            's.nom_sede as sede',
            'p.num_docu as paciente_num_docu',
            'p.nombres as paciente_nombres',
            'p.seg_nom as paciente_seg_nom',
            'p.pri_ape as paciente_pri_ape',
            'p.seg_ape as paciente_seg_ape',
        ])
            ->selectRaw('TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, NOW()) as edad_min')
            ->groupBy(
                'e.id',
                'e.study_id',
                'e.accession_no',
                'e.study_desc',
                'e.mods_in_study',
                'ep.fechor_lectura',
                'e.sede_id',
                's.nom_sede',
                'p.num_docu',
                'p.nombres',
                'p.seg_nom',
                'p.pri_ape',
                'p.seg_ape'
            )
            ->orderByDesc('edad_min')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Ranking de médicos por transcripciones en el período.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getRankingMedicosTranscritos(array $scope, int $days = 30, int $limit = 10)
    {
        $curStart = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));
        $prevStart = (clone $curStart)->subDays(max(1, $days));

        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->join('users as u', 'u.id', '=', 'e.medico_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_trascrito')
            ->where('ep.fechor_trascrito', '>=', $prevStart)
            ->selectRaw(
                'e.medico_id as medico_id, u.name as medico, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito >= ? THEN 1 ELSE 0 END) as total, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito < ? THEN 1 ELSE 0 END) as prev_total',
                [$curStart, $curStart]
            )
            ->groupBy('e.medico_id', 'u.name')
            ->orderByDesc('total')
            ->limit(max(1, $limit));

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        $rows = $q->get();

        return $rows->map(function ($r) {
            $total = (int) ($r->total ?? 0);
            $prev = (int) ($r->prev_total ?? 0);
            $delta = $total - $prev;
            $deltaPct = $prev > 0 ? round($delta / $prev, 4) : null;

            return [
                'medico_id' => (int) $r->medico_id,
                'medico' => (string) $r->medico,
                'total' => $total,
                'prev_total' => $prev,
                'delta' => $delta,
                'deltaPct' => $deltaPct,
            ];
        });
    }

    /**
     * "Ranking" (una fila) para el usuario logueado: transcritos en el período.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getRankingUsuarioTranscritos(int $userId, array $scope, int $days = 30)
    {
        $curStart = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));
        $prevStart = (clone $curStart)->subDays(max(1, $days));

        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->join('users as u', 'u.id', '=', 'e.medico_id')
            ->where('e.medico_id', '=', $userId)
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_trascrito')
            ->where('ep.fechor_trascrito', '>=', $prevStart)
            ->selectRaw(
                'e.medico_id as medico_id, u.name as medico, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito >= ? THEN 1 ELSE 0 END) as total, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito < ? THEN 1 ELSE 0 END) as prev_total',
                [$curStart, $curStart]
            )
            ->groupBy('e.medico_id', 'u.name');

        // aplica sede/modalidad si vienen en scope
        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        $rows = $q->get();

        return $rows->map(function ($r) {
            $total = (int) ($r->total ?? 0);
            $prev = (int) ($r->prev_total ?? 0);
            $delta = $total - $prev;
            $deltaPct = $prev > 0 ? round($delta / $prev, 4) : null;

            return [
                'medico_id' => (int) $r->medico_id,
                'medico' => (string) $r->medico,
                'total' => $total,
                'prev_total' => $prev,
                'delta' => $delta,
                'deltaPct' => $deltaPct,
            ];
        });
    }

    /**
     * Modalidades más solicitadas (global o por médico).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getTopModalidades(array $scope, int $limit = 10)
    {
        $q = Estudio::query()
            ->select(DB::raw('count(mods_in_study) as total'), 'mods_in_study');
        $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
        $this->applyScopeToEstudioQuery($q, $scope);

        return $q->groupBy('mods_in_study')
            ->orderBy('total', 'DESC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * Serie diaria de transcripciones (para gráficas).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getTranscritosPorDia(array $scope, int $days = 30)
    {
        $from = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $q = EstudioProducto::query()
            ->select(DB::raw('DATE(fechor_trascrito) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('activo', '=', 1)
            ->whereNotNull('fechor_trascrito')
            ->where('fechor_trascrito', '>=', $from)
            ->groupBy(DB::raw('DATE(fechor_trascrito)'))
            ->orderBy(DB::raw('DATE(fechor_trascrito)'), 'ASC');

        $q->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $q->whereHas('estudio', function ($estudioQ) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($estudioQ);
            $this->applyScopeToEstudioQuery($estudioQ, $scope);
        });

        return $q->get();
    }

    /**
     * Serie diaria de asignaciones (según estudios.created_at).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getAsignadosPorDia(array $scope, int $days = 30)
    {
        $from = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $q = Estudio::query()
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'), 'ASC');

        $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($q);
        $this->applyScopeToEstudioQuery($q, $scope);

        return $q->get();
    }

    /**
     * Serie diaria de lecturas (según estudios_productos.fechor_lectura).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getLeidosPorDia(array $scope, int $days = 30)
    {
        $from = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $q = EstudioProducto::query()
            ->select(DB::raw('DATE(fechor_lectura) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('activo', '=', 1)
            ->whereNotNull('fechor_lectura')
            ->where('fechor_lectura', '>=', $from)
            ->groupBy(DB::raw('DATE(fechor_lectura)'))
            ->orderBy(DB::raw('DATE(fechor_lectura)'), 'ASC');

        $q->whereHas('producto', function ($productoQ) {
            $productoQ->where('requi_lectura', '=', 1)
                ->where('estado', '=', 1);
        });
        $q->whereHas('estudio', function ($estudioQ) use ($scope) {
            $this->applyFiltroProductosActivosRequierenLecturaToEstudioQuery($estudioQ);
            $this->applyScopeToEstudioQuery($estudioQ, $scope);
        });

        return $q->get();
    }

    /**
     * Distribución por hora (HOY) para asignados / leídos / transcritos.
     *
     * @return array<string, \Illuminate\Support\Collection<int, object>>
     */
    public function getEventosPorHoraHoy(array $scope): array
    {
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        $asignadosQ = DB::table('estudios as e')
            ->whereBetween('e.created_at', [$from, $to]);
        $this->applyScopeToJoinedEstudioQuery($asignadosQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($asignadosQ);
        $asignados = $asignadosQ
            ->selectRaw('HOUR(e.created_at) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('HOUR(e.created_at)'))
            ->orderBy('hora', 'ASC')
            ->get();

        $leidosQ = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_lectura')
            ->whereBetween('ep.fechor_lectura', [$from, $to]);
        $this->applyScopeToJoinedEstudioQuery($leidosQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($leidosQ);
        $leidos = $leidosQ
            ->selectRaw('HOUR(ep.fechor_lectura) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('HOUR(ep.fechor_lectura)'))
            ->orderBy('hora', 'ASC')
            ->get();

        $transcritosQ = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_trascrito')
            ->whereBetween('ep.fechor_trascrito', [$from, $to]);
        $this->applyScopeToJoinedEstudioQuery($transcritosQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($transcritosQ);
        $transcritos = $transcritosQ
            ->selectRaw('HOUR(ep.fechor_trascrito) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('HOUR(ep.fechor_trascrito)'))
            ->orderBy('hora', 'ASC')
            ->get();

        return [
            'asignados' => $asignados,
            'leidos' => $leidos,
            'transcritos' => $transcritos,
        ];
    }

    /**
     * Transcritos por sede (admin) en el período.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getTranscritosPorSede(array $scope, int $days = 30, int $limit = 10)
    {
        $curStart = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));
        $prevStart = (clone $curStart)->subDays(max(1, $days));

        $q = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->join('config_sedes as s', 's.id', '=', 'e.sede_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_trascrito')
            ->where('ep.fechor_trascrito', '>=', $prevStart)
            ->selectRaw(
                'e.sede_id as sede_id, s.nom_sede as sede, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito >= ? THEN 1 ELSE 0 END) as total, ' .
                    'SUM(CASE WHEN ep.fechor_trascrito < ? THEN 1 ELSE 0 END) as prev_total',
                [$curStart, $curStart]
            )
            ->groupBy('e.sede_id', 's.nom_sede')
            ->orderByDesc('total')
            ->limit(max(1, $limit));

        $this->applyScopeToJoinedEstudioQuery($q, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($q);

        $rows = $q->get();

        return $rows->map(function ($r) {
            $total = (int) ($r->total ?? 0);
            $prev = (int) ($r->prev_total ?? 0);
            $delta = $total - $prev;
            $deltaPct = $prev > 0 ? round($delta / $prev, 4) : null;

            return [
                'sede_id' => (int) $r->sede_id,
                'sede' => (string) $r->sede,
                'total' => $total,
                'prev_total' => $prev,
                'delta' => $delta,
                'deltaPct' => $deltaPct,
            ];
        });
    }

    /**
     * Heatmap (últimos N días) Hora x Día para asignados / leídos / transcritos.
     *
     * @return array<string, \Illuminate\Support\Collection<int, object>>
     */
    public function getHeatmap7d(array $scope, int $days = 7): array
    {
        $days = max(1, min($days, 31));
        $from = Carbon::now()->startOfDay()->subDays(max(0, $days - 1));

        $asigQ = DB::table('estudios as e')
            ->where('e.created_at', '>=', $from);
        $this->applyScopeToJoinedEstudioQuery($asigQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($asigQ);
        $asignados = $asigQ
            ->selectRaw('DATE(e.created_at) as fecha, HOUR(e.created_at) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(e.created_at)'), DB::raw('HOUR(e.created_at)'))
            ->orderBy('fecha', 'ASC')
            ->orderBy('hora', 'ASC')
            ->get();

        $leidosQ = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_lectura')
            ->where('ep.fechor_lectura', '>=', $from);
        $this->applyScopeToJoinedEstudioQuery($leidosQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($leidosQ);
        $leidos = $leidosQ
            ->selectRaw('DATE(ep.fechor_lectura) as fecha, HOUR(ep.fechor_lectura) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(ep.fechor_lectura)'), DB::raw('HOUR(ep.fechor_lectura)'))
            ->orderBy('fecha', 'ASC')
            ->orderBy('hora', 'ASC')
            ->get();

        $transQ = DB::table('estudios_productos as ep')
            ->join('estudios as e', 'e.id', '=', 'ep.estudio_id')
            ->join('config_productos as cp', 'cp.id', '=', 'ep.producto_id')
            ->where('ep.activo', '=', 1)
            ->where('cp.requi_lectura', '=', 1)
            ->whereNotNull('ep.fechor_trascrito')
            ->where('ep.fechor_trascrito', '>=', $from);
        $this->applyScopeToJoinedEstudioQuery($transQ, $scope);
        $this->applyFiltroProductosActivosRequierenLecturaToJoinedEstudioQuery($transQ);
        $transcritos = $transQ
            ->selectRaw('DATE(ep.fechor_trascrito) as fecha, HOUR(ep.fechor_trascrito) as hora, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(ep.fechor_trascrito)'), DB::raw('HOUR(ep.fechor_trascrito)'))
            ->orderBy('fecha', 'ASC')
            ->orderBy('hora', 'ASC')
            ->get();

        return [
            'days' => $days,
            'asignados' => $asignados,
            'leidos' => $leidos,
            'transcritos' => $transcritos,
        ];
    }

    /**
     * Determina si el usuario logueado tiene rol Administrador.
     */
    protected function isAdministrador(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $role = $user->roles()->first();

        return $role && $role->name === 'Administrador';
    }
}
