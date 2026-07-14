<?php

namespace App\Services\Dashboard;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService extends BaseService
{
    private const BASE_FROM = 'estudios_productos ep
        INNER JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1
        INNER JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1';

    private const FROM_EP = 'estudios_productos ep
        STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1
        STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1';

    private const FROM_E = 'estudios e
        STRAIGHT_JOIN estudios_productos ep ON ep.estudio_id = e.id AND ep.activo = 1
        STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1';

    public function dashboard(array $filters): array
    {
        $days = (int) ($filters['days'] ?? 30);
        $days = max(1, min($days, 365));

        $userId = (int) auth()->id();
        $isAdmin = $this->isAdministrador();

        $scope = $this->resolveScope($filters, $isAdmin, $userId);

        try {
            $cacheKey = 'dashboard:'.$days.':'.($scope['medico_id'] ?? 'null').':'.($scope['sede_id'] ?? 'null');
            $payload = Cache::remember($cacheKey, 300, function () use ($scope, $days, $isAdmin) {
                return $this->buildDashboardPayload($scope, $days, $isAdmin);
            });
        } catch (\Throwable $e) {
            Log::error('Dashboard error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error($e->getMessage(), 500);
        }

        return $this->success($payload);
    }

    public function isAdministrador(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrador');
    }

    private function resolveScope(array $filters, bool $isAdmin, int $userId): array
    {
        $sedeId = $filters['sede_id'] ?? null;
        $sedeId = is_numeric($sedeId) ? (int) $sedeId : null;
        if ($sedeId !== null && $sedeId <= 0) {
            $sedeId = null;
        }

        $medicoId = null;
        if ($isAdmin) {
            $m = $filters['medico_id'] ?? null;
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

    private function scopeWhere(array $scope): array
    {
        $sql = '';
        $params = [];
        if (! empty($scope['medico_id'])) {
            $sql .= ' AND e.medico_id = ?';
            $params[] = (int) $scope['medico_id'];
        }
        if (! empty($scope['sede_id'])) {
            $sql .= ' AND e.sede_id = ?';
            $params[] = (int) $scope['sede_id'];
        }

        return [$sql, $params];
    }

    private function buildDashboardPayload(array $scope, int $days, bool $isAdmin): array
    {
        $indicadores = $this->getIndicadores($scope, $days);
        $operacion = $this->getOperacion($scope);
        $catalogos = $this->getCatalogos($isAdmin);
        $series = $this->getSeries($scope, $days);
        $calidad = $this->getCalidad($scope, $days);
        $estudiosMasSolicitados = $this->getEstudiosMasSolicitados($scope, $days);
        $tablas = $this->getTablas($scope);
        $productividad = $this->getProductividad($scope, $days);
        $sla = $this->getSla($scope, $days);
        $rankingTranscriptores = $this->getRankingTranscriptores($scope, $days);
        $prioridades = $this->getPrioridades($scope, $days);
        $embudo = $this->getEmbudo($scope, $days);

        return array_merge($indicadores, [
            'filters' => [
                'selected' => $scope,
                'options' => $catalogos,
            ],
            'operacion' => $operacion,
            'series' => $series,
            'calidad' => $calidad,
            'estudiosMasSolicitados' => $estudiosMasSolicitados,
            'tablas' => $tablas,
            'productividad' => $productividad,
            'sla' => $sla,
            'rankingTranscriptores' => $rankingTranscriptores,
            'prioridades' => $prioridades,
            'embudo' => $embudo,
            'alerts' => [],
        ]);
    }

    private function getIndicadores(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();

        $asignadosQuery = DB::table('estudios')
            ->where('estado', 1)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->when(! empty($scope['medico_id']), fn ($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(! empty($scope['sede_id']), fn ($q) => $q->where('sede_id', '=', (int) $scope['sede_id']));
        $estudiosAsignados = (int) $asignadosQuery->count();

        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $leidosSql = "SELECT STRAIGHT_JOIN COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            .$scopeSql;
        $leidos = DB::selectOne($leidosSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        $porTranscribirSql = "SELECT STRAIGHT_JOIN COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql;
        $porTranscribir = DB::selectOne($porTranscribirSql, $scopeParams);

        return [
            'estudiosAsignados' => $estudiosAsignados,
            'estudiosLeidos' => (int) ($leidos->total ?? 0),
            'estudiosPorTranscribir' => (int) ($porTranscribir->total ?? 0),
            'estudiosDescargados' => 0,
        ];
    }

    private function getOperacion(array $scope): array
    {
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        $sqlHoy = "SELECT STRAIGHT_JOIN"
            ." COUNT(*) as asignadosHoy,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NULL THEN 1 END) as pendientesLecturaHoy,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NOT NULL AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ? THEN 1 END) as leidosHoy,"
            ." COUNT(CASE WHEN ep.fechor_trascrito IS NOT NULL AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ? THEN 1 END) as transcritosHoy"
            ." FROM ".self::FROM_E
            ." WHERE e.created_at >= ? AND e.created_at <= ?".$scopeSql;
        $hoy = DB::selectOne($sqlHoy, array_merge(
            [$from, $to, $from, $to, $from, $to], $scopeParams
        ));

        $sqlPendLectura = "SELECT STRAIGHT_JOIN COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NULL"
            .$scopeSql;
        $pendLectura = DB::selectOne($sqlPendLectura, $scopeParams);

        $sqlPendTransc = "SELECT STRAIGHT_JOIN COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_trascrito IS NULL"
            .$scopeSql;
        $pendTransc = DB::selectOne($sqlPendTransc, $scopeParams);

        return [
            'asignadosHoy' => (int) ($hoy->asignadosHoy ?? 0),
            'pendientesLectura' => (int) ($pendLectura->total ?? 0),
            'pendientesLecturaHoy' => (int) ($hoy->pendientesLecturaHoy ?? 0),
            'leidosHoy' => (int) ($hoy->leidosHoy ?? 0),
            'pendientesTranscripcion' => (int) ($pendTransc->total ?? 0),
            'transcritosHoy' => (int) ($hoy->transcritosHoy ?? 0),
        ];
    }

    private function getSeries(array $scope, int $days): array
    {
        $maxDays = max($days, 7);
        $fechaInicio = Carbon::now()->subDays($maxDays)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        $hoyStr = Carbon::now()->toDateString();
        $heatmapStart = Carbon::now()->subDays(6)->toDateString();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $megaQuery = function (string $from, string $dateField, string $extraWhere = '') use ($fechaInicio, $fechaFin, $scopeSql, $scopeParams) {
            $sql = "SELECT STRAIGHT_JOIN DATE($dateField) as fecha, HOUR($dateField) as hora, COUNT(*) as total"
                ." FROM ".$from
                ." WHERE ep.activo = 1"
                ." AND $dateField >= ? AND $dateField <= ?"
                .$extraWhere.$scopeSql
                ." GROUP BY DATE($dateField), HOUR($dateField) ORDER BY fecha, hora";

            return DB::select($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));
        };

        $splitSeries = function (array $rows) use ($hoyStr, $heatmapStart) {
            $daily = [];
            $hourly = [];
            $heatmap = [];

            foreach ($rows as $row) {
                $d = $row->fecha;
                $h = (int) $row->hora;
                $t = (int) $row->total;

                $daily[$d] = ($daily[$d] ?? 0) + $t;

                if ($d === $hoyStr) {
                    $hourly[$h] = ($hourly[$h] ?? 0) + $t;
                }

                if ($d >= $heatmapStart) {
                    $heatmap[] = (object) ['fecha' => $d, 'hora' => $h, 'total' => $t];
                }
            }

            ksort($daily);
            ksort($hourly);
            usort($heatmap, fn ($a, $b) => $a->fecha <=> $b->fecha ?: $a->hora <=> $b->hora);

            $dailyArr = [];
            foreach ($daily as $fecha => $total) {
                $dailyArr[] = (object) ['fecha' => $fecha, 'total' => $total];
            }

            $hourlyArr = [];
            foreach ($hourly as $hora => $total) {
                $hourlyArr[] = (object) ['hora' => $hora, 'total' => $total];
            }

            return [$dailyArr, $hourlyArr, $heatmap];
        };

        [$asignadosPorDia, $asignadosHourly, $asignadosHeatmap] = $splitSeries($megaQuery(self::FROM_E, 'e.created_at'));
        [$leidosPorDia, $leidosHourly, $leidosHeatmap] = $splitSeries($megaQuery(self::FROM_EP, 'ep.fechor_lectura', ' AND ep.fechor_lectura IS NOT NULL'));
        [$transcritosPorDia, $transcritosHourly, $transcritosHeatmap] = $splitSeries($megaQuery(self::FROM_EP, 'ep.fechor_trascrito', ' AND ep.fechor_trascrito IS NOT NULL'));

        return [
            'asignadosPorDia' => $asignadosPorDia,
            'leidosPorDia' => $leidosPorDia,
            'transcritosPorDia' => $transcritosPorDia,
            'porHoraHoy' => [
                'asignados' => $asignadosHourly,
                'leidos' => $leidosHourly,
                'transcritos' => $transcritosHourly,
            ],
            'heatmap7d' => [
                'days' => 7,
                'asignados' => $asignadosHeatmap,
                'leidos' => $leidosHeatmap,
                'transcritos' => $transcritosHeatmap,
            ],
        ];
    }

    private function getCalidad(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $agingLecturaSql = "SELECT STRAIGHT_JOIN CASE"
            ." WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 2 THEN '0-2h'"
            ." WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 6 THEN '2-6h'"
            ." WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) < 24 THEN '6-24h'"
            ." ELSE '24h+' END as bucket, COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NULL"
            .$scopeSql
            ." GROUP BY bucket ORDER BY bucket";
        $agingLectura = DB::select($agingLecturaSql, $scopeParams);

        $agingTranscripcionSql = "SELECT STRAIGHT_JOIN CASE"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 2 THEN '0-2h'"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 6 THEN '2-6h'"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 24 THEN '6-24h'"
            ." ELSE '24h+' END as bucket, COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql
            ." GROUP BY bucket ORDER BY bucket";
        $agingTranscripcion = DB::select($agingTranscripcionSql, $scopeParams);

        $asignadoALecturaSql = "SELECT STRAIGHT_JOIN AVG(TIMESTAMPDIFF(MINUTE, e.created_at, ep.fechor_lectura)) as avgMin, COUNT(*) as n"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL"
            ." AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            .$scopeSql;
        $asignadoALectura = DB::selectOne($asignadoALecturaSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        $lecturaATranscritoSql = "SELECT STRAIGHT_JOIN AVG(TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito)) as avgMin, COUNT(*) as n"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            .$scopeSql;
        $lecturaATranscrito = DB::selectOne($lecturaATranscritoSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return [
            'agingLectura' => $agingLectura,
            'agingTranscripcion' => $agingTranscripcion,
            'tiempos' => [
                'asignadoALectura' => [
                    'avgMin' => round((float) ($asignadoALectura->avgMin ?? 0)),
                    'n' => (int) ($asignadoALectura->n ?? 0),
                ],
                'lecturaATranscrito' => [
                    'avgMin' => round((float) ($lecturaATranscrito->avgMin ?? 0)),
                    'n' => (int) ($lecturaATranscrito->n ?? 0),
                ],
            ],
        ];
    }

    private function getEstudiosMasSolicitados(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();

        return DB::table('estudios')
            ->select('mods_in_study', DB::raw('COUNT(*) as total'))
            ->where('estado', 1)
            ->whereBetween('created_at', [$fechaInicio, Carbon::now()->endOfDay()])
            ->when(! empty($scope['medico_id']), fn ($q) => $q->where('medico_id', '=', (int) $scope['medico_id']))
            ->when(! empty($scope['sede_id']), fn ($q) => $q->where('sede_id', '=', (int) $scope['sede_id']))
            ->groupBy('mods_in_study')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getTablas(array $scope): array
    {
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $idsSql = "SELECT ep.id FROM estudios_productos ep"
            ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NULL"
            .$scopeSql
            ." ORDER BY e.created_at LIMIT 10";
        $ids = DB::select($idsSql, $scopeParams);
        $idList = array_map(fn ($r) => $r->id, $ids);

        $pendientesLecturaTop = [];
        if (! empty($idList)) {
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $fullSql = "SELECT STRAIGHT_JOIN e.*, ep.*,"
                ." p.num_docu, p.nombres, p.seg_nom, p.pri_ape, p.seg_ape,"
                ." s.nom_sede,"
                ." CONCAT(p.nombres, ' ', p.seg_nom, ' ', p.pri_ape, ' ', p.seg_ape) as paciente_nombres,"
                ." p.num_docu as paciente_num_docu,"
                ." s.nom_sede as sede"
                ." FROM estudios_productos ep"
                ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
                ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
                ." INNER JOIN pacientes p ON p.id = e.paciente_id"
                ." INNER JOIN config_sedes s ON s.id = e.sede_id"
                ." WHERE ep.id IN ($placeholders)";
            $pendientesLecturaTop = DB::select($fullSql, $idList);
        }

        $idsSql = "SELECT ep.id FROM estudios_productos ep"
            ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql
            ." ORDER BY ep.fechor_lectura LIMIT 10";
        $ids = DB::select($idsSql, $scopeParams);
        $idList = array_map(fn ($r) => $r->id, $ids);

        $pendientesTranscripcionTop = [];
        if (! empty($idList)) {
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $fullSql = "SELECT STRAIGHT_JOIN e.*, ep.*,"
                ." p.num_docu, p.nombres, p.seg_nom, p.pri_ape, p.seg_ape,"
                ." s.nom_sede,"
                ." CONCAT(p.nombres, ' ', p.seg_nom, ' ', p.pri_ape, ' ', p.seg_ape) as paciente_nombres,"
                ." p.num_docu as paciente_num_docu,"
                ." s.nom_sede as sede"
                ." FROM estudios_productos ep"
                ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
                ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
                ." INNER JOIN pacientes p ON p.id = e.paciente_id"
                ." INNER JOIN config_sedes s ON s.id = e.sede_id"
                ." WHERE ep.id IN ($placeholders)";
            $pendientesTranscripcionTop = DB::select($fullSql, $idList);
        }

        return [
            'pendientesLecturaTop' => $pendientesLecturaTop,
            'pendientesTranscripcionTop' => $pendientesTranscripcionTop,
        ];
    }

    private function getProductividad(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $periodoAnteriorInicio = Carbon::now()->subDays($days * 2)->startOfDay();
        $periodoAnteriorFin = Carbon::now()->subDays($days)->endOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $countGrouped = function (string $groupField, string $alias, string $start, string $end) use ($scopeSql, $scopeParams) {
            $sql = "SELECT STRAIGHT_JOIN $groupField as $alias, COUNT(*) as total"
                ." FROM ".self::FROM_EP
                ." WHERE ep.activo = 1 AND ep.fechor_trascrito IS NOT NULL"
                ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
                .$scopeSql
                ." GROUP BY $groupField";
            $rows = DB::select($sql, array_merge([$start, $end], $scopeParams));
            $result = collect();
            foreach ($rows as $row) {
                $result[$row->$alias] = $row;
            }

            return $result;
        };

        $currentMedicos = $countGrouped('e.medico_id', 'medico_id', $fechaInicio, $fechaFin);
        $prevMedicos = $countGrouped('e.medico_id', 'medico_id', $periodoAnteriorInicio, $periodoAnteriorFin);

        $medicosIds = $currentMedicos->keys()->merge($prevMedicos->keys())->unique()->filter();

        $medicosNombres = collect();
        if ($medicosIds->isNotEmpty()) {
            $medicosNombres = DB::table('users')
                ->select('id', 'name')
                ->whereIn('id', $medicosIds)
                ->get()
                ->keyBy('id');
        }

        $rankingMedicosTranscritos = $medicosIds->map(function ($id) use ($currentMedicos, $prevMedicos, $medicosNombres) {
            $current = (int) ($currentMedicos[$id]->total ?? 0);
            $prev = (int) ($prevMedicos[$id]->total ?? 0);

            return [
                'medico_id' => $id,
                'medico' => $medicosNombres[$id]->name ?? 'Desconocido',
                'total' => $current,
                'prev_total' => $prev,
                'delta' => $current - $prev,
                'deltaPct' => $prev > 0 ? round(($current - $prev) / $prev, 4) : null,
            ];
        })->sortByDesc('total')->values();

        $currentSedes = $countGrouped('e.sede_id', 'sede_id', $fechaInicio, $fechaFin);
        $prevSedes = $countGrouped('e.sede_id', 'sede_id', $periodoAnteriorInicio, $periodoAnteriorFin);

        $sedesIds = $currentSedes->keys()->merge($prevSedes->keys())->unique()->filter();

        $sedesNombres = collect();
        if ($sedesIds->isNotEmpty()) {
            $sedesNombres = DB::table('config_sedes')
                ->select('id', 'nom_sede')
                ->whereIn('id', $sedesIds)
                ->get()
                ->keyBy('id');
        }

        $transcritosPorSede = $sedesIds->map(function ($id) use ($currentSedes, $prevSedes, $sedesNombres) {
            $current = (int) ($currentSedes[$id]->total ?? 0);
            $prev = (int) ($prevSedes[$id]->total ?? 0);

            return [
                'sede_id' => $id,
                'sede' => $sedesNombres[$id]->nom_sede ?? 'Desconocida',
                'total' => $current,
                'prev_total' => $prev,
                'delta' => $current - $prev,
                'deltaPct' => $prev > 0 ? round(($current - $prev) / $prev, 4) : null,
            ];
        })->sortByDesc('total')->values();

        return [
            'rankingMedicosTranscritos' => $rankingMedicosTranscritos,
            'transcritosPorSede' => $transcritosPorSede,
        ];
    }

    private function getSla(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $lecturaSql = "SELECT STRAIGHT_JOIN cp.id as prioridad_id, cp.nom_priori as prioridad,"
            ." cp.tiempo as tiempo_meta_min, COUNT(*) as total,"
            ." SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, e.created_at, ep.fechor_lectura) > cp.tiempo THEN 1 ELSE 0 END) as breaches"
            ." FROM estudios_productos ep"
            ." INNER JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." INNER JOIN config_prioridades cp ON cp.id = e.prioridad_id"
            ." WHERE ep.activo = 1"
            ." AND ep.fechor_lectura IS NOT NULL"
            ." AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            .$scopeSql
            ." GROUP BY cp.id, cp.nom_priori, cp.tiempo ORDER BY cp.id";
        $lectura = DB::select($lecturaSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));
        $lectura = array_map(function ($row) {
            return [
                'prioridad_id' => (int) $row->prioridad_id,
                'prioridad' => $row->prioridad,
                'total' => (int) $row->total,
                'breaches' => (int) ($row->breaches ?? 0),
                'breachRate' => (int) $row->total > 0
                    ? round((int) ($row->breaches ?? 0) / (int) $row->total, 4)
                    : 0,
            ];
        }, $lectura);

        $transcripcionSql = "SELECT STRAIGHT_JOIN cp.id as prioridad_id, cp.nom_priori as prioridad,"
            ." cp.tiempo as tiempo_meta_min, COUNT(*) as total,"
            ." SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito) > cp.tiempo THEN 1 ELSE 0 END) as breaches"
            ." FROM estudios_productos ep"
            ." INNER JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." INNER JOIN config_prioridades cp ON cp.id = e.prioridad_id"
            ." WHERE ep.activo = 1"
            ." AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            .$scopeSql
            ." GROUP BY cp.id, cp.nom_priori, cp.tiempo ORDER BY cp.id";
        $transcripcion = DB::select($transcripcionSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));
        $transcripcion = array_map(function ($row) {
            return [
                'prioridad_id' => (int) $row->prioridad_id,
                'prioridad' => $row->prioridad,
                'total' => (int) $row->total,
                'breaches' => (int) ($row->breaches ?? 0),
                'breachRate' => (int) $row->total > 0
                    ? round((int) ($row->breaches ?? 0) / (int) $row->total, 4)
                    : 0,
            ];
        }, $transcripcion);

        return [
            'lectura' => $lectura,
            'transcripcion' => $transcripcion,
        ];
    }

    private function getRankingTranscriptores(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT STRAIGHT_JOIN ep.transcriptor_id, u.name,"
            ." COUNT(*) as total,"
            ." ROUND(AVG(TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito))) as avgMin"
            ." FROM estudios_productos ep"
            ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
            ." INNER JOIN users u ON u.id = ep.transcriptor_id AND u.estado = 1"
            ." WHERE ep.activo = 1 AND ep.transcriptor_id IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            .$scopeSql
            ." GROUP BY ep.transcriptor_id, u.name"
            ." ORDER BY total DESC"
            ." LIMIT 10";
        $rows = DB::select($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return array_map(fn ($r) => [
            'transcriptor_id' => (int) $r->transcriptor_id,
            'name' => $r->name,
            'total' => (int) $r->total,
            'avgMin' => (int) ($r->avgMin ?? 0),
        ], $rows);
    }

    private function getPrioridades(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT STRAIGHT_JOIN prio.id as prioridad_id, prio.nom_priori as prioridad,"
            ." COUNT(DISTINCT e.id) as total_estudios,"
            ." COUNT(*) as total_productos,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NOT NULL THEN 1 END) as leidos,"
            ." COUNT(CASE WHEN ep.fechor_trascrito IS NOT NULL THEN 1 END) as transcritos,"
            ." ROUND(AVG(CASE WHEN ep.fechor_lectura IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, e.created_at, ep.fechor_lectura) END)) as avgLecturaMin,"
            ." ROUND(AVG(CASE WHEN ep.fechor_trascrito IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito) END)) as avgTranscripcionMin"
            ." FROM ".self::FROM_E
            ." INNER JOIN config_prioridades prio ON prio.id = e.prioridad_id"
            ." WHERE e.created_at >= ? AND e.created_at <= ?"
            .$scopeSql
            ." GROUP BY prio.id, prio.nom_priori"
            ." ORDER BY prio.id";
        $rows = DB::select($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return array_map(fn ($r) => [
            'prioridad_id' => (int) $r->prioridad_id,
            'prioridad' => $r->prioridad,
            'total_estudios' => (int) $r->total_estudios,
            'total_productos' => (int) $r->total_productos,
            'leidos' => (int) ($r->leidos ?? 0),
            'transcritos' => (int) ($r->transcritos ?? 0),
            'tasaLectura' => (int) $r->total_productos > 0
                ? round((int) ($r->leidos ?? 0) / (int) $r->total_productos, 4) : 0,
            'tasaTranscripcion' => (int) $r->total_productos > 0
                ? round((int) ($r->transcritos ?? 0) / (int) $r->total_productos, 4) : 0,
            'avgLecturaMin' => (int) ($r->avgLecturaMin ?? 0),
            'avgTranscripcionMin' => (int) ($r->avgTranscripcionMin ?? 0),
        ], $rows);
    }

    private function getEmbudo(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT STRAIGHT_JOIN"
            ." COUNT(*) as total,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NOT NULL THEN 1 END) as leidos,"
            ." COUNT(CASE WHEN ep.fechor_trascrito IS NOT NULL THEN 1 END) as transcritos"
            ." FROM ".self::FROM_E
            ." WHERE e.created_at >= ? AND e.created_at <= ?"
            .$scopeSql;
        $row = DB::selectOne($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        $total = (int) ($row->total ?? 0);
        $leidos = (int) ($row->leidos ?? 0);
        $transcritos = (int) ($row->transcritos ?? 0);

        return [
            'total' => $total,
            'leidos' => $leidos,
            'transcritos' => $transcritos,
            'tasaLectura' => $total > 0 ? round($leidos / $total, 4) : 0,
            'tasaTranscripcion' => $total > 0 ? round($transcritos / $total, 4) : 0,
            'tasaCompletitud' => $leidos > 0 ? round($transcritos / $leidos, 4) : 0,
            'pendientesLectura' => $total - $leidos,
            'pendientesTranscripcion' => $leidos - $transcritos,
        ];
    }

    private function getCatalogos(bool $isAdmin): array
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
}
