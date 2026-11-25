<?php

namespace App\Services\Dashboard;

use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashBoardService extends BaseService
{
    private const BASE_FROM = "estudios_productos ep
        STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1
        STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1";

    private const FROM_EP = "estudios_productos ep
        STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1
        STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1";

    private const FROM_E = "estudios e
        STRAIGHT_JOIN estudios_productos ep ON ep.estudio_id = e.id AND ep.activo = 1
        STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1";

    /**
     * Construye el payload completo del panel de control con caché y alcance por usuario.
     *
     * Flujo del proceso:
     * 1. Valida y acota el rango de días permitido (1 a 365).
     * 2. Resuelve el perfil y permisos del usuario (Administrador, Coordinación/Gestión o Médico puro).
     * 3. Establece el alcance de sedes asignadas con fallback a todas las sedes activas.
     * 4. Recupera o calcula el payload de métricas mediante caché optimizada por perfil y alcance.
     *
     * @param  array  $filters  Filtros: days, sede_id, medico_id
     * @return array Respuesta estructurada con métricas clínicas y operativas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    public function dashboard(array $filters): array
    {
        $days = (int) ($filters["days"] ?? 30);
        $days = max(1, min($days, 365));

        $user = auth()->user();
        if (! $user) {
            return $this->unauthorized("Usuario no autenticado");
        }

        $canViewGlobal = $this->canViewGlobalDashboard($user);
        $userSedesIds = $this->getUserAssignedSedes($user->id);

        $scope = $this->resolveScope($filters, $canViewGlobal, (int) $user->id, $userSedesIds);

        try {
            $sedesHash = ! empty($scope["allowed_sedes"]) ? implode("-", $scope["allowed_sedes"]) : "all";
            $cacheKey = "dashboard:v2:{$days}:".($scope["medico_id"] ?? "null").":".($scope["sede_id"] ?? "null").":{$sedesHash}:".($canViewGlobal ? "g1" : "g0");

            $payload = Cache::remember($cacheKey, 180, function () use ($scope, $days, $canViewGlobal, $userSedesIds) {
                return $this->buildDashboardPayload($scope, $days, $canViewGlobal, $userSedesIds);
            });
        } catch (\Throwable $e) {
            Log::error("Dashboard error: ".$e->getMessage(), [
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString(),
            ]);

            return $this->error($e->getMessage(), 500);
        }

        return $this->success($payload);
    }

    /**
     * Determina si el usuario tiene rol de Administrador.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function isAdministrador(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole("Administrador");
    }

    /**
     * Determina si el usuario tiene rol o permisos para ver el Dashboard de coordinación/gestión global.
     *
     * @param  mixed  $user  Usuario autenticado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    public function canViewGlobalDashboard($user): bool
    {
        if (! $user) {
            return false;
        }

        $globalRoles = [
            "Administrador",
            "Super Admin",
            "super-admin",
            "Administrativo",
            "Estadistico",
            "Reportes",
            "Transcriptor RX",
            "Ecografias Transriptor",
            "Transcriptor",
        ];

        if ($user->hasAnyRole($globalRoles)) {
            return true;
        }

        if ($user->can("reportes.indicadores") || $user->can("reportes.detallados")) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene los IDs de sedes asignadas al usuario en user_sedes.
     *
     * @param  int  $userId  Identificador de usuario
     * @return array IDs de sedes asignadas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getUserAssignedSedes(int $userId): array
    {
        return DB::table("user_sedes")
            ->where("user_id", $userId)
            ->pluck("sede_id")
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    /**
     * Resuelve el alcance de datos asegurando que las coordinadoras vean datos generales o de sus sedes.
     *
     * @param  array  $filters  Filtros enviados (days, sede_id, medico_id)
     * @param  bool  $canViewGlobal  Indica si el usuario tiene permiso para vista de coordinación/global
     * @param  int  $userId  Identificador del usuario autenticado
     * @param  array  $userSedesIds  Sedes asignadas al usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function resolveScope(array $filters, bool $canViewGlobal, int $userId, array $userSedesIds): array
    {
        $sedeId = $filters["sede_id"] ?? null;
        $sedeId = is_numeric($sedeId) ? (int) $sedeId : null;
        if ($sedeId !== null && $sedeId <= 0) {
            $sedeId = null;
        }

        $allowedSedes = [];
        if (! empty($userSedesIds) && ! auth()->user()->hasRole("Administrador")) {
            $allowedSedes = $userSedesIds;
            if ($sedeId !== null && ! in_array($sedeId, $allowedSedes, true)) {
                $sedeId = $allowedSedes[0] ?? null;
            }
        }

        $medicoId = null;
        if ($canViewGlobal) {
            $m = $filters["medico_id"] ?? null;
            $medicoId = is_numeric($m) ? (int) $m : null;
            if ($medicoId !== null && $medicoId <= 0) {
                $medicoId = null;
            }
        } else {
            $medicoId = $userId;
        }

        return [
            "medico_id" => $medicoId,
            "sede_id" => $sedeId,
            "allowed_sedes" => $allowedSedes,
        ];
    }

    /**
     * Construye la cláusula WHERE y los parámetros de alcance por médico, sede y sedes asignadas.
     *
     * @param  array  $scope  Alcance con medico_id, sede_id y allowed_sedes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function scopeWhere(array $scope): array
    {
        $sql = "";
        $params = [];

        if (! empty($scope["medico_id"])) {
            $sql .= " AND e.medico_id = ?";
            $params[] = (int) $scope["medico_id"];
        }

        if (! empty($scope["sede_id"])) {
            $sql .= " AND e.sede_id = ?";
            $params[] = (int) $scope["sede_id"];
        } elseif (! empty($scope["allowed_sedes"])) {
            $placeholders = implode(",", array_fill(0, count($scope["allowed_sedes"]), "?"));
            $sql .= " AND e.sede_id IN ({$placeholders})";
            foreach ($scope["allowed_sedes"] as $sId) {
                $params[] = (int) $sId;
            }
        }

        return [$sql, $params];
    }

    /**
     * Ensambla todas las secciones del payload del dashboard en un único arreglo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function buildDashboardPayload(array $scope, int $days, bool $canViewGlobal, array $userSedesIds): array
    {
        $indicadores = $this->getIndicadores($scope, $days);
        $operacion = $this->getOperacion($scope);
        $catalogos = $this->getCatalogos($canViewGlobal, $userSedesIds);
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
            "filters" => [
                "selected" => $scope,
                "options" => $catalogos,
            ],
            "operacion" => $operacion,
            "series" => $series,
            "calidad" => $calidad,
            "estudiosMasSolicitados" => $estudiosMasSolicitados,
            "tablas" => $tablas,
            "productividad" => $productividad,
            "sla" => $sla,
            "rankingTranscriptores" => $rankingTranscriptores,
            "prioridades" => $prioridades,
            "embudo" => $embudo,
            "alerts" => [],
        ]);
    }

    /**
     * Calcula los indicadores principales: estudios asignados, leídos y por transcribir.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getIndicadores(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();

        $asignadosQuery = DB::table("estudios as e")
            ->where("e.estado", 1)
            ->whereBetween("e.created_at", [$fechaInicio, $fechaFin])
            ->when(! empty($scope["medico_id"]), fn ($q) => $q->where("e.medico_id", "=", (int) $scope["medico_id"]))
            ->when(! empty($scope["sede_id"]), fn ($q) => $q->where("e.sede_id", "=", (int) $scope["sede_id"]))
            ->when(empty($scope["sede_id"]) && ! empty($scope["allowed_sedes"]), fn ($q) => $q->whereIn("e.sede_id", $scope["allowed_sedes"]));
        $estudiosAsignados = (int) $asignadosQuery->count();

        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $leidosSql = "SELECT COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            .$scopeSql;
        $leidos = DB::selectOne($leidosSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        $porTranscribirSql = "SELECT COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql;
        $porTranscribir = DB::selectOne($porTranscribirSql, $scopeParams);

        return [
            "estudiosAsignados" => $estudiosAsignados,
            "estudiosLeidos" => (int) ($leidos->total ?? 0),
            "estudiosPorTranscribir" => (int) ($porTranscribir->total ?? 0),
            "estudiosDescargados" => 0,
        ];
    }

    /**
     * Obtiene los datos de operación del día: asignados, leídos y pendientes en tiempo real.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getOperacion(array $scope): array
    {
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);
        $from = Carbon::now()->startOfDay();
        $to = Carbon::now()->endOfDay();

        $sqlAsignadosHoy = "SELECT"
            ." COUNT(*) as asignadosHoy,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NULL THEN 1 END) as pendientesLecturaHoy"
            ." FROM ".self::FROM_E
            ." WHERE e.created_at >= ? AND e.created_at <= ?".$scopeSql;
        $asignadosHoyRow = DB::selectOne($sqlAsignadosHoy, array_merge([$from, $to], $scopeParams));

        $sqlLeidosHoy = "SELECT COUNT(*) as leidosHoy"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?".$scopeSql;
        $leidosHoyRow = DB::selectOne($sqlLeidosHoy, array_merge([$from, $to], $scopeParams));

        $sqlTranscritosHoy = "SELECT COUNT(*) as transcritosHoy"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?".$scopeSql;
        $transcritosHoyRow = DB::selectOne($sqlTranscritosHoy, array_merge([$from, $to], $scopeParams));

        $sqlPendientes = "SELECT"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NULL THEN 1 END) as pendLectura,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL THEN 1 END) as pendTransc"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1".$scopeSql;
        $pendientesRow = DB::selectOne($sqlPendientes, $scopeParams);

        return [
            "asignadosHoy" => (int) ($asignadosHoyRow->asignadosHoy ?? 0),
            "pendientesLectura" => (int) ($pendientesRow->pendLectura ?? 0),
            "pendientesLecturaHoy" => (int) ($asignadosHoyRow->pendientesLecturaHoy ?? 0),
            "leidosHoy" => (int) ($leidosHoyRow->leidosHoy ?? 0),
            "pendientesTranscripcion" => (int) ($pendientesRow->pendTransc ?? 0),
            "transcritosHoy" => (int) ($transcritosHoyRow->transcritosHoy ?? 0),
        ];
    }

    /**
     * Genera las series temporales diarias, por hora y heatmap de asignados, leídos y transcritos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getSeries(array $scope, int $days): array
    {
        $maxDays = max($days, 7);
        $fechaInicio = Carbon::now()->subDays($maxDays)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        $hoyStr = Carbon::now()->toDateString();
        $heatmapStart = Carbon::now()->subDays(6)->toDateString();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $megaQuery = function (string $from, string $dateField, string $extraWhere = "") use ($fechaInicio, $fechaFin, $scopeSql, $scopeParams) {
            $sql = "SELECT DATE($dateField) as fecha, HOUR($dateField) as hora, COUNT(*) as total"
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
                    $heatmap[] = (object) ["fecha" => $d, "hora" => $h, "total" => $t];
                }
            }

            ksort($daily);
            ksort($hourly);
            usort($heatmap, fn ($a, $b) => $a->fecha <=> $b->fecha ?: $a->hora <=> $b->hora);

            $dailyArr = [];
            foreach ($daily as $fecha => $total) {
                $dailyArr[] = (object) ["fecha" => $fecha, "total" => $total];
            }

            $hourlyArr = [];
            foreach ($hourly as $hora => $total) {
                $hourlyArr[] = (object) ["hora" => $hora, "total" => $total];
            }

            return [$dailyArr, $hourlyArr, $heatmap];
        };

        [$asignadosPorDia, $asignadosHourly, $asignadosHeatmap] = $splitSeries($megaQuery(self::FROM_E, "e.created_at"));
        [$leidosPorDia, $leidosHourly, $leidosHeatmap] = $splitSeries($megaQuery(self::FROM_EP, "ep.fechor_lectura", " AND ep.fechor_lectura IS NOT NULL"));
        [$transcritosPorDia, $transcritosHourly, $transcritosHeatmap] = $splitSeries($megaQuery(self::FROM_EP, "ep.fechor_trascrito", " AND ep.fechor_trascrito IS NOT NULL"));

        return [
            "asignadosPorDia" => $asignadosPorDia,
            "leidosPorDia" => $leidosPorDia,
            "transcritosPorDia" => $transcritosPorDia,
            "porHoraHoy" => [
                "asignados" => $asignadosHourly,
                "leidos" => $leidosHourly,
                "transcritos" => $transcritosHourly,
            ],
            "heatmap7d" => [
                "days" => 7,
                "asignados" => $asignadosHeatmap,
                "leidos" => $leidosHeatmap,
                "transcritos" => $transcritosHeatmap,
            ],
        ];
    }

    /**
     * Calcula los indicadores de calidad: aging de pendientes y tiempos promedio de oportunidad (TAT clínico).
     *
     * Flujo del proceso:
     * 1. Asignado -> Lectura mide TAT clínico usando COALESCE(e.fec_estudio, e.created_at).
     * 2. Descarta tiempos negativos o inconsistentes y outliers extremos (> 45 días).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getCalidad(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $agingLecturaSql = "SELECT CASE"
            ." WHEN TIMESTAMPDIFF(HOUR, COALESCE(e.fec_estudio, e.created_at), NOW()) < 2 THEN '0-2h'"
            ." WHEN TIMESTAMPDIFF(HOUR, COALESCE(e.fec_estudio, e.created_at), NOW()) < 6 THEN '2-6h'"
            ." WHEN TIMESTAMPDIFF(HOUR, COALESCE(e.fec_estudio, e.created_at), NOW()) < 24 THEN '6-24h'"
            ." ELSE '24h+' END as bucket, COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NULL"
            .$scopeSql
            ." GROUP BY bucket ORDER BY bucket";
        $agingLectura = DB::select($agingLecturaSql, $scopeParams);

        $agingTranscripcionSql = "SELECT CASE"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 2 THEN '0-2h'"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 6 THEN '2-6h'"
            ." WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) < 24 THEN '6-24h'"
            ." ELSE '24h+' END as bucket, COUNT(*) as total"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql
            ." GROUP BY bucket ORDER BY bucket";
        $agingTranscripcion = DB::select($agingTranscripcionSql, $scopeParams);

        $asignadoALecturaSql = "SELECT"
            ." AVG(TIMESTAMPDIFF(MINUTE, COALESCE(e.fec_estudio, e.created_at), ep.fechor_lectura)) as avgMin,"
            ." COUNT(*) as n"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL"
            ." AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            ." AND COALESCE(e.fec_estudio, e.created_at) <= ep.fechor_lectura"
            ." AND TIMESTAMPDIFF(DAY, COALESCE(e.fec_estudio, e.created_at), ep.fechor_lectura) <= 45"
            .$scopeSql;
        $asignadoALectura = DB::selectOne($asignadoALecturaSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        $lecturaATranscritoSql = "SELECT"
            ." AVG(TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito)) as avgMin,"
            ." COUNT(*) as n"
            ." FROM ".self::FROM_EP
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            ." AND ep.fechor_lectura <= ep.fechor_trascrito"
            ." AND TIMESTAMPDIFF(DAY, ep.fechor_lectura, ep.fechor_trascrito) <= 45"
            .$scopeSql;
        $lecturaATranscrito = DB::selectOne($lecturaATranscritoSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return [
            "agingLectura" => $agingLectura,
            "agingTranscripcion" => $agingTranscripcion,
            "tiempos" => [
                "asignadoALectura" => [
                    "avgMin" => round((float) ($asignadoALectura->avgMin ?? 0)),
                    "n" => (int) ($asignadoALectura->n ?? 0),
                ],
                "lecturaATranscrito" => [
                    "avgMin" => round((float) ($lecturaATranscrito->avgMin ?? 0)),
                    "n" => (int) ($lecturaATranscrito->n ?? 0),
                ],
            ],
        ];
    }

    /**
     * Obtiene los 10 estudios más solicitados por modalidad en el rango de fechas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getEstudiosMasSolicitados(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();

        return DB::table("estudios as e")
            ->select("e.mods_in_study", DB::raw("COUNT(*) as total"))
            ->where("e.estado", 1)
            ->whereBetween("e.created_at", [$fechaInicio, Carbon::now()->endOfDay()])
            ->when(! empty($scope["medico_id"]), fn ($q) => $q->where("e.medico_id", "=", (int) $scope["medico_id"]))
            ->when(! empty($scope["sede_id"]), fn ($q) => $q->where("e.sede_id", "=", (int) $scope["sede_id"]))
            ->when(empty($scope["sede_id"]) && ! empty($scope["allowed_sedes"]), fn ($q) => $q->whereIn("e.sede_id", $scope["allowed_sedes"]))
            ->groupBy("e.mods_in_study")
            ->orderByDesc("total")
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Obtiene las tablas de los 10 primeros pendientes de lectura y transcripción optimizado en 2 pasos.
     *
     * Flujo del proceso:
     * 1. Obtiene los primeros 10 IDs mediante STRAIGHT_JOIN ligero (evita escanear pacientes y sedes en la ordenación).
     * 2. Hidrata los 10 registros exactos con datos de paciente y sede mediante IN () preservando el orden.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getTablas(array $scope): array
    {
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        // Paso 1: Top 10 IDs pendientes de lectura
        $idsLecturaSql = "SELECT ep.id FROM estudios_productos ep"
            ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NULL"
            .$scopeSql
            ." ORDER BY e.created_at ASC LIMIT 10";
        $idsLectura = DB::select($idsLecturaSql, $scopeParams);
        $idListLectura = array_map(fn ($r) => (int) $r->id, $idsLectura);

        $pendientesLecturaTop = [];
        if (! empty($idListLectura)) {
            $placeholders = implode(",", array_fill(0, count($idListLectura), "?"));
            $orderField = implode(",", $idListLectura);
            $fullLecturaSql = "SELECT"
                ." ep.id, ep.estudio_id, ep.producto_id, ep.nom_produc,"
                ." e.accession_no, e.study_id, e.mods_in_study, e.sede_id,"
                ." TIMESTAMPDIFF(MINUTE, COALESCE(e.fec_estudio, e.created_at), NOW()) as edad_min,"
                ." p.num_docu as paciente_num_docu,"
                ." p.nombres as paciente_nombres, p.seg_nom as paciente_seg_nom, p.pri_ape as paciente_pri_ape, p.seg_ape as paciente_seg_ape,"
                ." s.nom_sede as sede"
                ." FROM estudios_productos ep"
                ." INNER JOIN estudios e ON e.id = ep.estudio_id"
                ." INNER JOIN pacientes p ON p.id = e.paciente_id"
                ." INNER JOIN config_sedes s ON s.id = e.sede_id"
                ." WHERE ep.id IN ({$placeholders})"
                ." ORDER BY FIELD(ep.id, {$orderField})";
            $pendientesLecturaTop = DB::select($fullLecturaSql, $idListLectura);
        }

        // Paso 2: Top 10 IDs pendientes de transcripción
        $idsTranscSql = "SELECT ep.id FROM estudios_productos ep"
            ." STRAIGHT_JOIN estudios e ON e.id = ep.estudio_id AND e.estado = 1"
            ." STRAIGHT_JOIN config_productos cp ON cp.id = ep.producto_id AND cp.requi_lectura = 1 AND cp.estado = 1"
            ." WHERE ep.activo = 1 AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NULL"
            .$scopeSql
            ." ORDER BY ep.fechor_lectura ASC LIMIT 10";
        $idsTransc = DB::select($idsTranscSql, $scopeParams);
        $idListTransc = array_map(fn ($r) => (int) $r->id, $idsTransc);

        $pendientesTranscripcionTop = [];
        if (! empty($idListTransc)) {
            $placeholders = implode(",", array_fill(0, count($idListTransc), "?"));
            $orderField = implode(",", $idListTransc);
            $fullTranscSql = "SELECT"
                ." ep.id, ep.estudio_id, ep.producto_id, ep.nom_produc,"
                ." e.accession_no, e.study_id, e.mods_in_study, e.sede_id,"
                ." TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, NOW()) as edad_min,"
                ." p.num_docu as paciente_num_docu,"
                ." p.nombres as paciente_nombres, p.seg_nom as paciente_seg_nom, p.pri_ape as paciente_pri_ape, p.seg_ape as paciente_seg_ape,"
                ." s.nom_sede as sede"
                ." FROM estudios_productos ep"
                ." INNER JOIN estudios e ON e.id = ep.estudio_id"
                ." INNER JOIN pacientes p ON p.id = e.paciente_id"
                ." INNER JOIN config_sedes s ON s.id = e.sede_id"
                ." WHERE ep.id IN ({$placeholders})"
                ." ORDER BY FIELD(ep.id, {$orderField})";
            $pendientesTranscripcionTop = DB::select($fullTranscSql, $idListTransc);
        }

        return [
            "pendientesLecturaTop" => $pendientesLecturaTop,
            "pendientesTranscripcionTop" => $pendientesTranscripcionTop,
        ];
    }

    /**
     * Calcula la productividad por médico y sede comparando el período actual con el anterior sin solapamientos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getProductividad(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $periodoAnteriorInicio = Carbon::now()->subDays($days * 2)->startOfDay();
        $periodoAnteriorFin = Carbon::now()->subDays($days)->subSecond();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $countGrouped = function (string $groupField, string $alias, string $start, string $end) use ($scopeSql, $scopeParams) {
            $sql = "SELECT $groupField as $alias, COUNT(*) as total"
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

        $currentMedicos = $countGrouped("e.medico_id", "medico_id", $fechaInicio, $fechaFin);
        $prevMedicos = $countGrouped("e.medico_id", "medico_id", $periodoAnteriorInicio, $periodoAnteriorFin);

        $medicosIds = $currentMedicos->keys()->merge($prevMedicos->keys())->unique()->filter();

        $medicosNombres = collect();
        if ($medicosIds->isNotEmpty()) {
            $medicosNombres = DB::table("users")
                ->select("id", "name")
                ->whereIn("id", $medicosIds)
                ->get()
                ->keyBy("id");
        }

        $rankingMedicosTranscritos = $medicosIds->map(function ($id) use ($currentMedicos, $prevMedicos, $medicosNombres) {
            $current = (int) ($currentMedicos[$id]->total ?? 0);
            $prev = (int) ($prevMedicos[$id]->total ?? 0);

            return [
                "medico_id" => $id,
                "medico" => $medicosNombres[$id]->name ?? "Desconocido",
                "total" => $current,
                "prev_total" => $prev,
                "delta" => $current - $prev,
                "deltaPct" => $prev > 0 ? round(($current - $prev) / $prev, 4) : null,
            ];
        })->sortByDesc("total")->values();

        $currentSedes = $countGrouped("e.sede_id", "sede_id", $fechaInicio, $fechaFin);
        $prevSedes = $countGrouped("e.sede_id", "sede_id", $periodoAnteriorInicio, $periodoAnteriorFin);

        $sedesIds = $currentSedes->keys()->merge($prevSedes->keys())->unique()->filter();

        $sedesNombres = collect();
        if ($sedesIds->isNotEmpty()) {
            $sedesNombres = DB::table("config_sedes")
                ->select("id", "nom_sede")
                ->whereIn("id", $sedesIds)
                ->get()
                ->keyBy("id");
        }

        $transcritosPorSede = $sedesIds->map(function ($id) use ($currentSedes, $prevSedes, $sedesNombres) {
            $current = (int) ($currentSedes[$id]->total ?? 0);
            $prev = (int) ($prevSedes[$id]->total ?? 0);

            return [
                "sede_id" => $id,
                "sede" => $sedesNombres[$id]->nom_sede ?? "Desconocida",
                "total" => $current,
                "prev_total" => $prev,
                "delta" => $current - $prev,
                "deltaPct" => $prev > 0 ? round(($current - $prev) / $prev, 4) : null,
            ];
        })->sortByDesc("total")->values();

        return [
            "rankingMedicosTranscritos" => $rankingMedicosTranscritos,
            "transcritosPorSede" => $transcritosPorSede,
        ];
    }

    /**
     * Calcula el cumplimiento de SLA por prioridad convirtiendo horas y días a minutos reales.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getSla(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $metaExpr = "CASE"
            ." WHEN LOWER(prio.tipo_tiempo) LIKE 'hora%' THEN prio.tiempo * 60"
            ." WHEN LOWER(prio.tipo_tiempo) LIKE 'dia%' THEN prio.tiempo * 1440"
            ." ELSE prio.tiempo END";

        $lecturaSql = "SELECT prio.id as prioridad_id, prio.nom_priori as prioridad,"
            ." ({$metaExpr}) as tiempo_meta_min, COUNT(*) as total,"
            ." SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, COALESCE(e.fec_estudio, e.created_at), ep.fechor_lectura) > ({$metaExpr}) THEN 1 ELSE 0 END) as breaches"
            ." FROM ".self::FROM_EP
            ." INNER JOIN config_prioridades prio ON prio.id = e.prioridad_id"
            ." WHERE ep.activo = 1"
            ." AND ep.fechor_lectura IS NOT NULL"
            ." AND ep.fechor_lectura >= ? AND ep.fechor_lectura <= ?"
            .$scopeSql
            ." GROUP BY prio.id, prio.nom_priori, prio.tiempo, prio.tipo_tiempo ORDER BY prio.id";
        $lectura = DB::select($lecturaSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));
        $lectura = array_map(function ($row) {
            return [
                "prioridad_id" => (int) $row->prioridad_id,
                "prioridad" => $row->prioridad,
                "total" => (int) $row->total,
                "breaches" => (int) ($row->breaches ?? 0),
                "breachRate" => (int) $row->total > 0
                    ? round((int) ($row->breaches ?? 0) / (int) $row->total, 4)
                    : 0,
            ];
        }, $lectura);

        $transcripcionSql = "SELECT prio.id as prioridad_id, prio.nom_priori as prioridad,"
            ." ({$metaExpr}) as tiempo_meta_min, COUNT(*) as total,"
            ." SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito) > ({$metaExpr}) THEN 1 ELSE 0 END) as breaches"
            ." FROM ".self::FROM_EP
            ." INNER JOIN config_prioridades prio ON prio.id = e.prioridad_id"
            ." WHERE ep.activo = 1"
            ." AND ep.fechor_lectura IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            .$scopeSql
            ." GROUP BY prio.id, prio.nom_priori, prio.tiempo, prio.tipo_tiempo ORDER BY prio.id";
        $transcripcion = DB::select($transcripcionSql, array_merge([$fechaInicio, $fechaFin], $scopeParams));
        $transcripcion = array_map(function ($row) {
            return [
                "prioridad_id" => (int) $row->prioridad_id,
                "prioridad" => $row->prioridad,
                "total" => (int) $row->total,
                "breaches" => (int) ($row->breaches ?? 0),
                "breachRate" => (int) $row->total > 0
                    ? round((int) ($row->breaches ?? 0) / (int) $row->total, 4)
                    : 0,
            ];
        }, $transcripcion);

        return [
            "lectura" => $lectura,
            "transcripcion" => $transcripcion,
        ];
    }

    /**
     * Obtiene el ranking de los 10 transcriptores con más estudios transcritos y su tiempo promedio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getRankingTranscriptores(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT ep.transcriptor_id, u.name,"
            ." COUNT(*) as total,"
            ." ROUND(AVG(TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito))) as avgMin"
            ." FROM ".self::FROM_EP
            ." INNER JOIN users u ON u.id = ep.transcriptor_id AND u.estado = 1"
            ." WHERE ep.activo = 1 AND ep.transcriptor_id IS NOT NULL AND ep.fechor_trascrito IS NOT NULL"
            ." AND ep.fechor_trascrito >= ? AND ep.fechor_trascrito <= ?"
            .$scopeSql
            ." GROUP BY ep.transcriptor_id, u.name"
            ." ORDER BY total DESC"
            ." LIMIT 10";
        $rows = DB::select($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return array_map(fn ($r) => [
            "transcriptor_id" => (int) $r->transcriptor_id,
            "name" => $r->name,
            "total" => (int) $r->total,
            "avgMin" => (int) ($r->avgMin ?? 0),
        ], $rows);
    }

    /**
     * Obtiene los indicadores de lectura y transcripción agrupados por prioridad con TAT clínico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getPrioridades(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT prio.id as prioridad_id, prio.nom_priori as prioridad,"
            ." COUNT(DISTINCT e.id) as total_estudios,"
            ." COUNT(*) as total_productos,"
            ." COUNT(CASE WHEN ep.fechor_lectura IS NOT NULL THEN 1 END) as leidos,"
            ." COUNT(CASE WHEN ep.fechor_trascrito IS NOT NULL THEN 1 END) as transcritos,"
            ." ROUND(AVG(CASE WHEN ep.fechor_lectura IS NOT NULL AND COALESCE(e.fec_estudio, e.created_at) <= ep.fechor_lectura THEN TIMESTAMPDIFF(MINUTE, COALESCE(e.fec_estudio, e.created_at), ep.fechor_lectura) END)) as avgLecturaMin,"
            ." ROUND(AVG(CASE WHEN ep.fechor_trascrito IS NOT NULL AND ep.fechor_lectura <= ep.fechor_trascrito THEN TIMESTAMPDIFF(MINUTE, ep.fechor_lectura, ep.fechor_trascrito) END)) as avgTranscripcionMin"
            ." FROM ".self::FROM_E
            ." INNER JOIN config_prioridades prio ON prio.id = e.prioridad_id"
            ." WHERE e.created_at >= ? AND e.created_at <= ?"
            .$scopeSql
            ." GROUP BY prio.id, prio.nom_priori"
            ." ORDER BY prio.id";
        $rows = DB::select($sql, array_merge([$fechaInicio, $fechaFin], $scopeParams));

        return array_map(fn ($r) => [
            "prioridad_id" => (int) $r->prioridad_id,
            "prioridad" => $r->prioridad,
            "total_estudios" => (int) $r->total_estudios,
            "total_productos" => (int) $r->total_productos,
            "leidos" => (int) ($r->leidos ?? 0),
            "transcritos" => (int) ($r->transcritos ?? 0),
            "tasaLectura" => (int) $r->total_productos > 0
                ? round((int) ($r->leidos ?? 0) / (int) $r->total_productos, 4) : 0,
            "tasaTranscripcion" => (int) $r->total_productos > 0
                ? round((int) ($r->transcritos ?? 0) / (int) $r->total_productos, 4) : 0,
            "avgLecturaMin" => (int) ($r->avgLecturaMin ?? 0),
            "avgTranscripcionMin" => (int) ($r->avgTranscripcionMin ?? 0),
        ], $rows);
    }

    /**
     * Calcula el embudo de conversión total, leídos y transcritos con sus tasas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getEmbudo(array $scope, int $days): array
    {
        $fechaInicio = Carbon::now()->subDays($days)->startOfDay();
        $fechaFin = Carbon::now()->endOfDay();
        [$scopeSql, $scopeParams] = $this->scopeWhere($scope);

        $sql = "SELECT"
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
            "total" => $total,
            "leidos" => $leidos,
            "transcritos" => $transcritos,
            "tasaLectura" => $total > 0 ? round($leidos / $total, 4) : 0,
            "tasaTranscripcion" => $total > 0 ? round($transcritos / $total, 4) : 0,
            "tasaCompletitud" => $leidos > 0 ? round($transcritos / $leidos, 4) : 0,
            "pendientesLectura" => $total - $leidos,
            "pendientesTranscripcion" => $leidos - $transcritos,
        ];
    }

    /**
     * Obtiene los catálogos de sedes activas y médicos filtrando según sedes permitidas del usuario.
     *
     * @param  bool  $canViewGlobal  Indica si el usuario tiene vista de coordinación/administración
     * @param  array  $userSedesIds  Sedes asignadas al usuario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-25
     */
    private function getCatalogos(bool $canViewGlobal, array $userSedesIds = []): array
    {
        $sedesQuery = DB::table("config_sedes")
            ->select("id", "nom_sede")
            ->where("estado", 1);

        if (! empty($userSedesIds) && ! auth()->user()->hasRole("Administrador")) {
            $sedesQuery->whereIn("id", $userSedesIds);
        }

        $sedes = $sedesQuery->orderBy("nom_sede", "ASC")->get();

        $medicos = collect();
        if ($canViewGlobal) {
            $medicos = DB::table("users as u")
                ->select("u.id", "u.name")
                ->where("u.estado", "=", 1)
                ->whereExists(function ($sub) {
                    $sub->selectRaw("1")
                        ->from("model_has_roles as mhr")
                        ->join("roles as r", "r.id", "=", "mhr.role_id")
                        ->whereColumn("mhr.model_id", "=", "u.id")
                        ->where("r.name", "like", "%Medico%");
                })
                ->orderBy("u.name", "ASC")
                ->limit(100)
                ->get();
        }

        return [
            "sedes" => $sedes,
            "medicos" => $medicos,
        ];
    }
}
