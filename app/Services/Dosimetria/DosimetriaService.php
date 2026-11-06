<?php

namespace App\Services\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dosimetria\EquipoEmisor;
use App\Models\Dosimetria\RegistroDosisRadiacion;
use App\Models\Dosimetria\UmbralNrd;
use App\Services\BaseService;
use Carbon\Carbon;
use Database\Seeders\UmbralesNrdSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DosimetriaService extends BaseService
{
    /**
     * Procesa y persiste un lote masivo de mediciones dosimétricas proveniente del Agente Edge.
     *
     * Flujo del proceso:
     * 1. Valida si el lote ya fue procesado mediante batch_hash (Idempotencia). Si existe, retorna 200 sin tocar BD.
     * 2. Inicia transacción de base de datos con reintentos defensivos.
     * 3. Resuelve o auto-registra los equipos emisores asociados según AET Title y modalidad.
     * 4. Ejecuta un upsert eficiente para evitar duplicados en study_uid y tipo_metrica.
     * 5. Guarda la huella del lote en caché por 24 horas para garantizar idempotencia futura.
     * 6. Registra auditoría clínica del volumen de dosis recibido.
     *
     * @param  int  $sedeId  Identificador de la sede hospitalaria
     * @param  array  $registros  Arreglo de mediciones dosimétricas anonimizadas
     * @param  string|null  $batchHash  Hash SHA-256 único del lote para idempotencia
     * @return array Respuesta estructurada con resultado del guardado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function guardarLote(int $sedeId, array $registros, ?string $batchHash = null): array
    {
        if (! empty($batchHash)) {
            $cacheKey = "dosimetria_batch_{$sedeId}_{$batchHash}";
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info("Dosimetría: Lote {$batchHash} de sede {$sedeId} ya procesado previamente. Respuesta idempotente despachada sin tocar MySQL.");

                return $this->success($cached, 'Lote ya procesado previamente (idempotente)', 200);
            }
        }

        return $this->transaction(function () use ($sedeId, $registros, $batchHash) {
            $now = Carbon::now();
            $equiposCache = [];
            $filasParaUpsert = [];

            foreach ($registros as $reg) {
                $aetTitle = ! empty($reg['aet_title']) ? trim($reg['aet_title']) : 'EQUIPO_DEFAULT';
                $modalidad = strtoupper($reg['modalidad']);
                $keyEquipo = "{$sedeId}_{$aetTitle}_{$modalidad}";

                if (! isset($equiposCache[$keyEquipo])) {
                    $equipo = EquipoEmisor::firstOrCreate(
                        [
                            'sede_id' => $sedeId,
                            'aet_title' => $aetTitle,
                            'modalidad' => $modalidad,
                        ],
                        [
                            'nombre' => "Equipo {$modalidad} - {$aetTitle}",
                            'marca' => 'PACS Import',
                            'modelo' => 'Dicom RDSR',
                            'estado' => true,
                        ]
                    );
                    $equiposCache[$keyEquipo] = $equipo->id;
                }

                $descRaw = $reg['study_desc'] ?? null;
                $region = $reg['region_anatomica'] ?? self::clasificarRegionAnatomica($descRaw, $modalidad);
                $prefixRdsr = ! empty($reg['es_rdsr']) ? '[RDSR]' : '';
                $descFinal = $descRaw;
                if ($descFinal && ! str_contains($descFinal, "[{$region}]")) {
                    $tags = trim("{$prefixRdsr} [{$region}]");
                    $descFinal = substr("{$tags} {$descFinal}", 0, 255);
                } elseif (! empty($prefixRdsr) && ! str_contains($descFinal ?? '', '[RDSR]')) {
                    $descFinal = substr("[RDSR] {$descFinal}", 0, 255);
                }

                $filasParaUpsert[] = [
                    'sede_id' => $sedeId,
                    'equipo_emisor_id' => $equiposCache[$keyEquipo],
                    'study_uid' => $reg['study_uid'],
                    'patient_id_hashed' => $reg['patient_id_hashed'],
                    'tipo_metrica' => $reg['tipo_metrica'],
                    'valor_dosis' => (float) $reg['valor_dosis'],
                    'unidad_medida' => $reg['unidad_medida'] ?? 'mGy',
                    'fecha_estudio' => Carbon::parse($reg['fecha_estudio'])->format('Y-m-d H:i:s'),
                    'accession_no' => $reg['accession_no'] ?? null,
                    'study_desc' => $descFinal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Upsert en bloques de 200 registros para máximo rendimiento en MySQL
            $chunks = array_chunk($filasParaUpsert, 200);
            $totalProcesados = 0;

            foreach ($chunks as $chunk) {
                RegistroDosisRadiacion::upsert(
                    $chunk,
                    ['study_uid', 'tipo_metrica'],
                    [
                        'sede_id',
                        'equipo_emisor_id',
                        'patient_id_hashed',
                        'valor_dosis',
                        'unidad_medida',
                        'fecha_estudio',
                        'accession_no',
                        'study_desc',
                        'updated_at',
                    ]
                );
                $totalProcesados += count($chunk);
            }

            Log::info("Dosimetría: Procesados {$totalProcesados} registros para sede {$sedeId}.");

            $resultadoData = [
                'sede_id' => $sedeId,
                'guardados' => $totalProcesados,
                'equipos_identificados' => count($equiposCache),
                'batch_hash' => $batchHash,
            ];

            if (! empty($batchHash)) {
                Cache::put("dosimetria_batch_{$sedeId}_{$batchHash}", $resultadoData, now()->addHours(24));
            }

            Cache::put("telemetria_sede_{$sedeId}_dosimetria", [
                'servicio' => 'dosimetria',
                'estado' => 'OK',
                'mensaje' => "Lote de {$totalProcesados} registros procesado exitosamente.",
                'detalles' => $resultadoData,
                'fecha' => now()->toIso8601String(),
            ], now()->addDays(30));

            return $this->success(
                $resultadoData,
                "Se procesaron {$totalProcesados} mediciones de dosis exitosamente.",
                201
            );
        });
    }

    /**
     * Obtiene el consolidado analítico para el dashboard de dosimetría radiológica.
     *
     * Flujo del proceso:
     * 1. Aplica filtros opcionales de sede, modalidad, métrica y rango de fechas.
     * 2. Calcula promedios y máximos mensuales agrupados por sede y modalidad.
     * 3. Contrasta los promedios contra los Niveles de Referencia para Diagnóstico (Resolución 482).
     * 4. Retorna estructura lista para Chart.js y tarjetas de alerta clínica.
     *
     * @param  array  $filtros  Criterios de filtrado seleccionados por el usuario
     * @return array Consolidado analítico y métricas de radiación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function obtenerDashboard(array $filtros): array
    {
        try {
            $sedeId = ! empty($filtros['sede_id']) ? (int) $filtros['sede_id'] : null;
            $modalidad = ! empty($filtros['modalidad']) && $filtros['modalidad'] !== 'TODAS' ? $filtros['modalidad'] : null;
            $tipoMetrica = ! empty($filtros['tipo_metrica']) && $filtros['tipo_metrica'] !== 'TODAS' ? $filtros['tipo_metrica'] : null;
            $regionAnatomica = ! empty($filtros['region_anatomica']) && $filtros['region_anatomica'] !== 'TODAS' ? $filtros['region_anatomica'] : null;
            $fechaDesde = ! empty($filtros['fecha_desde']) ? Carbon::parse($filtros['fecha_desde'])->startOfDay() : Carbon::now()->subMonths(6)->startOfMonth();
            $fechaHasta = ! empty($filtros['fecha_hasta']) ? Carbon::parse($filtros['fecha_hasta'])->endOfDay() : Carbon::now()->endOfDay();

            // 1. Cargar umbrales NRD regulatorios con soporte multidimensional (Modalidad + Métrica + Región)
            [$mapaUmbrales, $umbralesTodos] = $this->obtenerCatalogoUmbrales();

            // 1.1 Asegurar sincronización transparente de mamografías clínicas y telemetría de sedes
            $this->asegurarTelemetriaMamografias();
            $this->asegurarTelemetriaSedes();

            // 2. Consulta agrupada por Sede, Modalidad, Región y Mes
            $sqlRegionCase = "CASE 
                WHEN e.modalidad = 'MG' OR r.tipo_metrica = 'DGM' OR (r.study_desc LIKE '%[MAMA]%' AND r.study_desc NOT LIKE '%PARAN%') OR r.study_desc LIKE '%MAMOGRAF%' THEN 'MAMA'
                WHEN r.study_desc LIKE '%[CRANEO]%' OR r.study_desc LIKE '%PARANAS%' OR r.study_desc LIKE '%PARANAL%' THEN 'CRANEO'
                WHEN r.study_desc LIKE '%[TORAX]%' THEN 'TORAX'
                WHEN r.study_desc LIKE '%[ABDOMEN]%' THEN 'ABDOMEN'
                WHEN r.study_desc LIKE '%[PELVIS]%' THEN 'PELVIS'
                WHEN r.study_desc LIKE '%[COLUMNA]%' THEN 'COLUMNA'
                WHEN r.study_desc LIKE '%[EXTREMIDADES]%' THEN 'EXTREMIDADES'
                ELSE 'GENERAL'
            END";

            $query = DB::table('medicion_dosis_radiacion as r')
                ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
                ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
                ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
                ->select(
                    'r.sede_id',
                    's.nom_sede',
                    DB::raw("COALESCE(e.modalidad, 'OTRA') as modalidad"),
                    'r.tipo_metrica',
                    DB::raw("{$sqlRegionCase} as region_anatomica"),
                    DB::raw("DATE_FORMAT(r.fecha_estudio, '%Y-%m') as mes_periodo"),
                    DB::raw('COUNT(*) as total_estudios'),
                    DB::raw('ROUND(AVG(r.valor_dosis), 2) as dosis_promedio'),
                    DB::raw('ROUND(MAX(r.valor_dosis), 2) as dosis_maxima'),
                    DB::raw('ROUND(MIN(r.valor_dosis), 2) as dosis_minima'),
                    'r.unidad_medida'
                )
                ->groupBy('r.sede_id', 's.nom_sede', 'modalidad', 'r.tipo_metrica', DB::raw($sqlRegionCase), 'mes_periodo', 'r.unidad_medida')
                ->orderBy('mes_periodo', 'asc');

            if ($sedeId) {
                $query->where('r.sede_id', $sedeId);
            }
            if ($modalidad) {
                $query->where('e.modalidad', $modalidad);
            }
            if ($tipoMetrica) {
                $query->where('r.tipo_metrica', $tipoMetrica);
            }
            if ($regionAnatomica) {
                $query->where('r.study_desc', 'like', "%[{$regionAnatomica}]%");
            }

            $filas = $query->get();

            // 3. Evaluar alertas de superación de NRD con discriminación anatómica
            $alertas = [];
            $seriesChart = [];
            $mesesSet = [];
            $sedesSet = [];

            foreach ($filas as $fila) {
                $mesesSet[$fila->mes_periodo] = true;
                $sedesSet[$fila->sede_id] = $fila->nom_sede;

                $regFila = $fila->region_anatomica ?? 'GENERAL';
                $keyConRegion = "{$fila->modalidad}_{$fila->tipo_metrica}_{$regFila}";
                $keyGeneral = "{$fila->modalidad}_{$fila->tipo_metrica}_GENERAL";
                $keyBase = "{$fila->modalidad}_{$fila->tipo_metrica}";

                $umbral = $mapaUmbrales[$keyConRegion] ?? $mapaUmbrales[$keyGeneral] ?? $mapaUmbrales[$keyBase] ?? null;

                $superaNrd = false;
                $superaAlerta = false;
                $limiteNrd = null;

                if ($umbral) {
                    $limiteNrd = (float) $umbral->valor_limite_nrd;
                    $valorAlerta = (float) $umbral->valor_alerta;

                    if ((float) $fila->dosis_promedio >= $limiteNrd) {
                        $superaNrd = true;
                    } elseif ((float) $fila->dosis_promedio >= $valorAlerta) {
                        $superaAlerta = true;
                    }
                }

                $fila->supera_nrd = $superaNrd;
                $fila->supera_alerta = $superaAlerta;
                $fila->limite_nrd = $limiteNrd;

                if ($superaNrd || $superaAlerta) {
                    $porcentajeNrd = $limiteNrd > 0 ? round(((float) $fila->dosis_promedio / $limiteNrd) * 100, 1) : 100;
                    $alertas[] = [
                        'sede_id' => $fila->sede_id,
                        'nom_sede' => $fila->nom_sede,
                        'modalidad' => $fila->modalidad,
                        'tipo_metrica' => $fila->tipo_metrica,
                        'region_anatomica' => $fila->region_anatomica,
                        'mes_periodo' => $fila->mes_periodo,
                        'dosis_promedio' => $fila->dosis_promedio,
                        'limite_nrd' => $limiteNrd,
                        'valor_alerta' => $valorAlerta,
                        'porcentaje_nrd' => $porcentajeNrd,
                        'unidad_medida' => $fila->unidad_medida,
                        'gravedad' => $superaNrd ? 'PELIGRO_NRD' : 'ALERTA_PREVENTIVA',
                        'mensaje' => $superaNrd
                            ? "Sede {$fila->nom_sede} superó el Nivel de Referencia ({$fila->dosis_promedio} >= {$limiteNrd} {$fila->unidad_medida}) en {$fila->modalidad} {$fila->region_anatomica} ({$fila->tipo_metrica})."
                            : "Sede {$fila->nom_sede} en nivel de advertencia ({$fila->dosis_promedio} {$fila->unidad_medida}, {$porcentajeNrd}% del límite) para {$fila->modalidad} {$fila->region_anatomica}.",
                    ];
                }
            }

            // 4. Últimos registros individuales para la tabla detallada
            $ultimosRegistrosQuery = DB::table('medicion_dosis_radiacion as r')
                ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
                ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
                ->select(
                    'r.id',
                    'r.sede_id',
                    's.nom_sede',
                    DB::raw("COALESCE(e.nombre, 'Equipo Emisor') as equipo_nombre"),
                    DB::raw("COALESCE(e.modalidad, 'CT') as modalidad"),
                    'r.study_uid',
                    'r.patient_id_hashed',
                    'r.tipo_metrica',
                    'r.valor_dosis',
                    'r.unidad_medida',
                    'r.fecha_estudio',
                    'r.accession_no',
                    'r.study_desc'
                )
                ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
                ->orderBy('r.fecha_estudio', 'desc')
                ->limit(50);

            if ($sedeId) {
                $ultimosRegistrosQuery->where('r.sede_id', $sedeId);
            }
            if ($modalidad) {
                $ultimosRegistrosQuery->where('e.modalidad', $modalidad);
            }
            if ($tipoMetrica) {
                $ultimosRegistrosQuery->where('r.tipo_metrica', $tipoMetrica);
            }

            $ultimosRegistros = $ultimosRegistrosQuery->get();

            // 5. Clasificación anatómica y enriquecimiento de registros
            $conteoRegiones = [
                'CRANEO' => 0,
                'TORAX' => 0,
                'ABDOMEN' => 0,
                'PELVIS' => 0,
                'COLUMNA' => 0,
                'EXTREMIDADES' => 0,
                'MAMA' => 0,
                'GENERAL' => 0,
            ];

            foreach ($ultimosRegistros as $reg) {
                $region = self::clasificarRegionAnatomica($reg->study_desc, $reg->modalidad);
                $reg->region_anatomica = $region;
                $reg->es_rdsr = str_contains($reg->study_desc ?? '', '[RDSR]') || str_contains($reg->equipo_nombre ?? '', 'RDSR');

                $keyConRegion = "{$reg->modalidad}_{$reg->tipo_metrica}_{$region}";
                $keyGeneral = "{$reg->modalidad}_{$reg->tipo_metrica}_GENERAL";
                $keyBase = "{$reg->modalidad}_{$reg->tipo_metrica}";
                $umbralReg = $mapaUmbrales[$keyConRegion] ?? $mapaUmbrales[$keyGeneral] ?? $mapaUmbrales[$keyBase] ?? null;

                $reg->limite_nrd = $umbralReg ? (float) $umbralReg->valor_limite_nrd : null;
                $reg->valor_alerta = $umbralReg ? (float) $umbralReg->valor_alerta : null;

                if (isset($conteoRegiones[$region])) {
                    $conteoRegiones[$region]++;
                } else {
                    $conteoRegiones['GENERAL']++;
                }
            }

            if ($regionAnatomica) {
                $ultimosRegistros = $ultimosRegistros->filter(fn ($r) => $r->region_anatomica === $regionAnatomica)->values();
            }

            // 6. Detección heurística de Deriva Técnica (Drift) en Equipos Emisores
            // Filtrado clínico contra "sesgo de base pequeña" (scout views / topogramas < 1.0 mGy)
            $alertasDeriva = [];
            $diasPeriodo = $fechaDesde->diffInDays($fechaHasta);
            if ($diasPeriodo >= 14) {
                $puntoCorte = $fechaDesde->copy()->addDays((int) ($diasPeriodo / 2));

                $equiposDosis = DB::table('medicion_dosis_radiacion as r')
                    ->join('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
                    ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
                    ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
                    ->when($sedeId, fn ($q) => $q->where('r.sede_id', $sedeId))
                    ->when($modalidad, fn ($q) => $q->where('e.modalidad', $modalidad))
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->where('r.tipo_metrica', 'CTDIvol')->where('r.valor_dosis', '>=', 1.0);
                        })->orWhere(function ($sub) {
                            $sub->where('r.tipo_metrica', 'DLP')->where('r.valor_dosis', '>=', 20.0);
                        })->orWhere(function ($sub) {
                            $sub->where('r.tipo_metrica', 'DGM')->where('r.valor_dosis', '>=', 0.4);
                        })->orWhere(function ($sub) {
                            $sub->where('r.tipo_metrica', 'DAP')->where('r.valor_dosis', '>=', 0.05);
                        })->orWhereNotIn('r.tipo_metrica', ['CTDIvol', 'DLP', 'DGM', 'DAP']);
                    })
                    ->select(
                        'r.equipo_emisor_id',
                        'e.nombre as equipo_nombre',
                        'e.modalidad',
                        's.nom_sede',
                        'r.tipo_metrica',
                        'r.unidad_medida',
                        DB::raw("ROUND(AVG(CASE WHEN r.fecha_estudio < '{$puntoCorte->format('Y-m-d H:i:s')}' THEN r.valor_dosis ELSE NULL END), 2) as media_inicial"),
                        DB::raw("ROUND(AVG(CASE WHEN r.fecha_estudio >= '{$puntoCorte->format('Y-m-d H:i:s')}' THEN r.valor_dosis ELSE NULL END), 2) as media_reciente"),
                        DB::raw("COUNT(CASE WHEN r.fecha_estudio < '{$puntoCorte->format('Y-m-d H:i:s')}' THEN 1 ELSE NULL END) as disparos_iniciales"),
                        DB::raw("COUNT(CASE WHEN r.fecha_estudio >= '{$puntoCorte->format('Y-m-d H:i:s')}' THEN 1 ELSE NULL END) as disparos_recientes"),
                        DB::raw('COUNT(*) as total_disparos')
                    )
                    ->groupBy('r.equipo_emisor_id', 'e.nombre', 'e.modalidad', 's.nom_sede', 'r.tipo_metrica', 'r.unidad_medida')
                    ->having('disparos_iniciales', '>=', 3)
                    ->having('disparos_recientes', '>=', 3)
                    ->get();

                foreach ($equiposDosis as $eq) {
                    if ($eq->media_inicial && $eq->media_reciente && (float) $eq->media_inicial > 0) {
                        $deltaAbsoluto = round((float) $eq->media_reciente - (float) $eq->media_inicial, 3);
                        $porcentajeCambio = round(($deltaAbsoluto / (float) $eq->media_inicial) * 100, 1);

                        // Umbral clínico absoluto según métrica para erradicar ruido estadístico
                        $umbralDeltaMinimo = match ($eq->tipo_metrica) {
                            'CTDIvol' => 1.5,
                            'DLP' => 25.0,
                            'DGM' => 0.3,
                            'DAP' => 0.04,
                            default => 0.1,
                        };

                        if ($porcentajeCambio >= 20.0 && $deltaAbsoluto >= $umbralDeltaMinimo) {
                            $alertasDeriva[] = [
                                'equipo_nombre' => $eq->equipo_nombre,
                                'nom_sede' => $eq->nom_sede,
                                'modalidad' => $eq->modalidad,
                                'tipo_metrica' => $eq->tipo_metrica,
                                'media_inicial' => (float) $eq->media_inicial,
                                'media_reciente' => (float) $eq->media_reciente,
                                'delta_absoluto' => $deltaAbsoluto,
                                'cambio_porcentaje' => $porcentajeCambio,
                                'unidad_medida' => $eq->unidad_medida,
                                'disparos_iniciales' => (int) $eq->disparos_iniciales,
                                'disparos_recientes' => (int) $eq->disparos_recientes,
                                'mensaje' => "El equipo {$eq->equipo_nombre} ({$eq->nom_sede}) registra un incremento atípico del {$porcentajeCambio}% (+{$deltaAbsoluto} {$eq->unidad_medida}) en {$eq->tipo_metrica} (de {$eq->media_inicial} a {$eq->media_reciente} {$eq->unidad_medida}), sugiriendo posible descalibración o fatiga del tubo.",
                            ];
                        }
                    }
                }
            }

            // 7. Sedes disponibles enriquecidas con su monitor de latidos (Heartbeat / Dead Man's Switch)
            $sedesDisponibles = ConfigSede::select('id', 'nom_sede')->where('estado', true)->orderBy('nom_sede')->get();
            $sedesConEstado = $sedesDisponibles->map(function ($s) {
                $hb = Cache::get("dosimetria_heartbeat_{$s->id}");
                $estadoConexion = 'OFFLINE'; // OFFLINE, DELAYED, ONLINE
                $minutosSinContacto = null;

                if ($hb && ! empty($hb['ultimo_contacto'])) {
                    $ultimo = Carbon::parse($hb['ultimo_contacto']);
                    $minutosSinContacto = (int) $ultimo->diffInMinutes(Carbon::now());
                    if ($minutosSinContacto <= 15) {
                        $estadoConexion = 'ONLINE';
                    } elseif ($minutosSinContacto <= 60) {
                        $estadoConexion = 'DELAYED';
                    } else {
                        $estadoConexion = 'OFFLINE';
                    }
                }

                return [
                    'id' => $s->id,
                    'nom_sede' => $s->nom_sede,
                    'estado_conexion' => $estadoConexion,
                    'minutos_sin_contacto' => $minutosSinContacto,
                    'heartbeat' => $hb,
                ];
            });

            // 8. Ranking de Equipos Emisores (Adaptado a plantilla "Projects of the Month")
            $equiposRanking = DB::table('medicion_dosis_equipos_emisores as e')
                ->join('config_sedes as s', 's.id', '=', 'e.sede_id')
                ->leftJoin('medicion_dosis_radiacion as r', function ($join) use ($fechaDesde, $fechaHasta) {
                    $join->on('r.equipo_emisor_id', '=', 'e.id')
                        ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta]);
                })
                ->when($sedeId, fn ($q) => $q->where('e.sede_id', $sedeId))
                ->when($modalidad, fn ($q) => $q->where('e.modalidad', $modalidad))
                ->select(
                    'e.id',
                    'e.nombre',
                    'e.modalidad',
                    'e.marca',
                    'e.modelo',
                    'e.aet_title',
                    'e.estado',
                    's.nom_sede',
                    DB::raw('COUNT(r.id) as total_estudios'),
                    DB::raw('ROUND(AVG(r.valor_dosis), 2) as dosis_promedio'),
                    DB::raw("COALESCE(MAX(r.unidad_medida), 'mGy') as unidad_medida"),
                    DB::raw('MAX(r.fecha_estudio) as ultimo_disparo')
                )
                ->groupBy('e.id', 'e.nombre', 'e.modalidad', 'e.marca', 'e.modelo', 'e.aet_title', 'e.estado', 's.nom_sede')
                ->orderByDesc('total_estudios')
                ->limit(8)
                ->get()
                ->map(function ($eq) use ($alertasDeriva, $alertas) {
                    $tieneDeriva = collect($alertasDeriva)->firstWhere('equipo_nombre', $eq->nombre);
                    $tieneAlertaNrd = collect($alertas)->firstWhere('modalidad', $eq->modalidad);

                    if ($tieneAlertaNrd) {
                        $eq->estado_evaluacion = 'SUPERA NRD';
                        $eq->badge_class = 'label-danger';
                    } elseif ($tieneDeriva) {
                        $eq->estado_evaluacion = 'DERIVA (+20%)';
                        $eq->badge_class = 'label-warning';
                    } elseif ($eq->total_estudios > 0) {
                        $eq->estado_evaluacion = 'CONFORME';
                        $eq->badge_class = 'label-success';
                    } else {
                        $eq->estado_evaluacion = 'SIN DATOS';
                        $eq->badge_class = 'label-inverse';
                    }

                    return $eq;
                });

            // 9. Timeline de Auditoría y Eventos Dosimétricos ("Today's Schedule")
            $eventosTimeline = [];
            foreach (array_slice($alertas, 0, 4) as $al) {
                $eventosTimeline[] = [
                    'tipo' => 'NRD',
                    'clase_bg' => 'bg-danger',
                    'icono' => 'fa fa-exclamation-triangle',
                    'titulo' => "Superó Límite NRD ({$al['modalidad']})",
                    'descripcion' => "{$al['nom_sede']}: Promedio {$al['dosis_promedio']} {$al['unidad_medida']} vs límite {$al['limite_nrd']}.",
                    'tiempo' => $al['mes_periodo'],
                ];
            }
            foreach (array_slice($alertasDeriva, 0, 3) as $ad) {
                $eventosTimeline[] = [
                    'tipo' => 'DERIVA',
                    'clase_bg' => 'bg-warning',
                    'icono' => 'fa fa-wrench',
                    'titulo' => "Deriva Técnica en {$ad['equipo_nombre']}",
                    'descripcion' => "+{$ad['cambio_porcentaje']}% de incremento en {$ad['tipo_metrica']} ({$ad['nom_sede']}).",
                    'tiempo' => 'Reciente',
                ];
            }
            foreach ($ultimosRegistros as $ur) {
                if ($ur->es_rdsr && count($eventosTimeline) < 8) {
                    $eventosTimeline[] = [
                        'tipo' => 'RDSR',
                        'clase_bg' => 'bg-info',
                        'icono' => 'fa fa-certificate',
                        'titulo' => "Informe RDSR ({$ur->modalidad})",
                        'descripcion' => "{$ur->nom_sede}: {$ur->tipo_metrica}={$ur->valor_dosis} {$ur->unidad_medida} en {$ur->region_anatomica}.",
                        'tiempo' => Carbon::parse($ur->fecha_estudio)->diffForHumans(),
                    ];
                }
            }
            if (empty($eventosTimeline)) {
                $eventosTimeline[] = [
                    'tipo' => 'INFO',
                    'clase_bg' => 'bg-success',
                    'icono' => 'fa fa-check-circle',
                    'titulo' => 'Operación Dosimétrica Conforme',
                    'descripcion' => 'Todas las sedes operan dentro de los Niveles de Referencia para Diagnóstico.',
                    'tiempo' => 'Al día',
                ];
            }

            // 10. Resumen de KPIs Ampliado
            $totalEstudiosPeriodo = DB::table('medicion_dosis_radiacion')
                ->whereBetween('fecha_estudio', [$fechaDesde, $fechaHasta])
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            $totalEquiposActivos = EquipoEmisor::where('estado', true)
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            $sedesOnline = $sedesConEstado->where('estado_conexion', 'ONLINE')->count();

            $totalPacientes = DB::table('medicion_dosis_radiacion')
                ->whereBetween('fecha_estudio', [$fechaDesde, $fechaHasta])
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->distinct()
                ->count('patient_id_hashed');

            $dosisEfectivaMedia = DB::table('medicion_dosis_radiacion as r')
                ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
                ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
                ->when($sedeId, fn ($q) => $q->where('r.sede_id', $sedeId))
                ->when($modalidad, fn ($q) => $q->where('e.modalidad', $modalidad))
                ->select(DB::raw("ROUND(AVG(
                    CASE 
                        -- 1. Tomografía con DLP (mGy*cm -> mSv mediante coeficientes k de ICRP 103)
                        WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0021
                        WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.0140
                        WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.0150
                        WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.0150
                        WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.0160
                        WHEN r.tipo_metrica = 'DLP' THEN r.valor_dosis * 0.0150
                        
                        -- 2. Tomografía con CTDIvol (Estimación normalizada E = CTDIvol * L_aprox * k_DLP)
                        WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0315
                        WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.4200
                        WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.5250
                        WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.3750
                        WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.3200
                        WHEN r.tipo_metrica = 'CTDIvol' THEN r.valor_dosis * 0.2500
                        
                        -- 3. Radiología Convencional y Digital con DAP (Gy*cm2 -> mSv mediante coeficientes ICRP 103)
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.1500
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.2500
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.2000
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.1800
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0500
                        WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[EXTREMIDADES]%' THEN r.valor_dosis * 0.0100
                        WHEN r.tipo_metrica = 'DAP' THEN r.valor_dosis * 0.1400
                        
                        -- 4. Mamografía Digital DGM (mGy glandular -> mSv ponderado)
                        WHEN r.tipo_metrica = 'DGM' THEN r.valor_dosis * 0.1200
                        
                        ELSE r.valor_dosis * 0.0500
                    END
                ), 2) as e_media"))
                ->value('e_media') ?? '0.00';

            $totalGrupos = count($filas);
            $totalSuperacionesCount = collect($alertas)->where('gravedad', 'PELIGRO_NRD')->count();
            $totalAdvertenciasCount = collect($alertas)->where('gravedad', 'ALERTA_PREVENTIVA')->count();
            $tasaConformidad = $totalGrupos > 0 ? round(max(0, ($totalGrupos - $totalSuperacionesCount) / $totalGrupos) * 100, 1) : 100.0;

            // 11. Matriz de Riesgo Dosimétrico y Mapa de Calor (Sede vs. Región Anatómica)
            $regionesCatalogo = ['CRANEO', 'TORAX', 'ABDOMEN', 'PELVIS', 'COLUMNA', 'EXTREMIDADES', 'MAMA'];
            $sedesAgrupadas = [];

            foreach ($filas as $fila) {
                $sId = $fila->sede_id;
                if (! isset($sedesAgrupadas[$sId])) {
                    $sedesAgrupadas[$sId] = [
                        'sede_id' => $sId,
                        'nom_sede' => $fila->nom_sede,
                        'regiones' => [],
                        'total_estudios' => 0,
                        'conformes' => 0,
                        'superaciones' => 0,
                        'alertas' => 0,
                    ];
                    foreach ($regionesCatalogo as $rc) {
                        $sedesAgrupadas[$sId]['regiones'][$rc] = [
                            'total_estudios' => 0,
                            'pct_suma' => 0,
                            'pct_estudios' => 0,
                            'dosis_suma' => 0,
                            'limite_nrd' => null,
                            'porcentaje_nrd' => null,
                            'estado' => 'SIN_DATOS',
                        ];
                    }
                }

                $reg = $fila->region_anatomica ?? 'GENERAL';
                if (isset($sedesAgrupadas[$sId]['regiones'][$reg])) {
                    $cant = (int) $fila->total_estudios;
                    $prom = (float) $fila->dosis_promedio;
                    $lim = $fila->limite_nrd ? (float) $fila->limite_nrd : null;
                    $pctFila = ($lim !== null && $lim > 0) ? round(($prom / $lim) * 100, 1) : null;

                    $sedesAgrupadas[$sId]['regiones'][$reg]['total_estudios'] += $cant;
                    if ($pctFila !== null) {
                        $sedesAgrupadas[$sId]['regiones'][$reg]['pct_suma'] += ($pctFila * $cant);
                        $sedesAgrupadas[$sId]['regiones'][$reg]['pct_estudios'] += $cant;
                        $sedesAgrupadas[$sId]['regiones'][$reg]['dosis_suma'] += ($prom * $cant);
                        $sedesAgrupadas[$sId]['regiones'][$reg]['limite_nrd'] = $lim;
                    }
                }

                $sedesAgrupadas[$sId]['total_estudios'] += (int) $fila->total_estudios;
                if ($fila->supera_nrd) {
                    $sedesAgrupadas[$sId]['superaciones']++;
                } elseif ($fila->supera_alerta) {
                    $sedesAgrupadas[$sId]['alertas']++;
                } else {
                    $sedesAgrupadas[$sId]['conformes']++;
                }
            }

            $matrizRiesgo = [];
            foreach ($sedesAgrupadas as $sId => $sData) {
                $totalEvaluados = $sData['conformes'] + $sData['superaciones'] + $sData['alertas'];
                $tasaConf = $totalEvaluados > 0
                    ? round(($sData['conformes'] / $totalEvaluados) * 100, 1)
                    : 100.0;

                $filaMatriz = [
                    'sede_id' => $sId,
                    'nom_sede' => $sData['nom_sede'],
                    'total_estudios' => $sData['total_estudios'],
                    'tasa_conformidad' => $tasaConf,
                    'regiones' => [],
                ];

                foreach ($regionesCatalogo as $rc) {
                    $rData = $sData['regiones'][$rc];
                    if ($rData['pct_estudios'] > 0 && $rData['limite_nrd'] !== null && $rData['limite_nrd'] > 0) {
                        $pctNrd = round($rData['pct_suma'] / $rData['pct_estudios'], 1);
                        $promDosis = round($rData['dosis_suma'] / $rData['pct_estudios'], 2);
                        $estado = ($pctNrd >= 100) ? 'CRÍTICO' : (($pctNrd >= 80) ? 'ALERTA' : 'SEGURO');

                        $filaMatriz['regiones'][$rc] = [
                            'pct_nrd' => $pctNrd,
                            'promedio_dosis' => $promDosis,
                            'limite_nrd' => $rData['limite_nrd'],
                            'total_estudios' => $rData['total_estudios'],
                            'estado' => $estado,
                        ];
                    } else {
                        $filaMatriz['regiones'][$rc] = [
                            'pct_nrd' => null,
                            'promedio_dosis' => null,
                            'limite_nrd' => null,
                            'total_estudios' => 0,
                            'estado' => 'SIN_DATOS',
                        ];
                    }
                }

                if ($sData['superaciones'] > 0) {
                    $filaMatriz['nivel_riesgo'] = 'ALTO (Superó NRD)';
                    $filaMatriz['badge_riesgo'] = 'label-danger';
                } elseif ($sData['alertas'] > 0) {
                    $filaMatriz['nivel_riesgo'] = 'MEDIO (En Alerta)';
                    $filaMatriz['badge_riesgo'] = 'label-warning';
                } else {
                    $filaMatriz['nivel_riesgo'] = 'BAJO (Seguro)';
                    $filaMatriz['badge_riesgo'] = 'label-success';
                }

                $matrizRiesgo[] = $filaMatriz;
            }

            return $this->success([
                'consolidado' => $filas,
                'alertas' => $alertas,
                'alertas_deriva' => $alertasDeriva,
                'equipos_ranking' => $equiposRanking,
                'eventos_timeline' => $eventosTimeline,
                'distribucion_regiones' => $conteoRegiones,
                'ultimos_registros' => $ultimosRegistros,
                'umbrales_nrd' => $umbralesTodos,
                'matriz_riesgo' => $matrizRiesgo,
                'sedes' => $sedesConEstado,
                'periodo' => [
                    'desde' => $fechaDesde->format('Y-m-d'),
                    'hasta' => $fechaHasta->format('Y-m-d'),
                    'meses' => array_keys($mesesSet),
                ],
                'kpis' => [
                    'total_estudios' => $totalEstudiosPeriodo,
                    'total_equipos' => $totalEquiposActivos,
                    'total_alertas' => $totalSuperacionesCount,
                    'total_advertencias' => $totalAdvertenciasCount,
                    'total_derivas' => count($alertasDeriva),
                    'total_pacientes' => $totalPacientes,
                    'dosis_efectiva_promedio' => $dosisEfectivaMedia,
                    'tasa_conformidad' => $tasaConformidad,
                    'sedes_online' => $sedesOnline,
                    'sedes_total' => $sedesConEstado->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en DosimetriaService::obtenerDashboard: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('No se pudo generar el consolidado de dosimetría: '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtiene y estructura los datos consolidados y registros detallados para la exportación a Excel.
     *
     * Flujo del proceso:
     * 1. Resuelve filtros de sede, modalidad, métrica y ventana temporal.
     * 2. Consulta los umbrales NRD regulatorios activos (Resolución 482 de 2018).
     * 3. Calcula las métricas estadísticas agrupadas por sede, mes, modalidad y métrica.
     * 4. Obtiene el histórico detallado de mediciones con trazabilidad de equipo emisor y hash de paciente.
     * 5. Clasifica cada registro frente a los umbrales NRD (Conforme, Alerta preventiva, Supera NRD).
     * 6. Retorna estructura lista para las dos hojas del libro Excel.
     *
     * @param  array  $filtros  Criterios de filtrado seleccionados por el usuario
     * @return array Conjunto de datos estadísticos y registros individuales para Excel
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function obtenerDatosExportacion(array $filtros): array
    {
        $sedeId = ! empty($filtros['sede_id']) ? (int) $filtros['sede_id'] : null;
        $modalidad = ! empty($filtros['modalidad']) && $filtros['modalidad'] !== 'TODAS' ? $filtros['modalidad'] : null;
        $tipoMetrica = ! empty($filtros['tipo_metrica']) && $filtros['tipo_metrica'] !== 'TODAS' ? $filtros['tipo_metrica'] : null;
        $fechaDesde = ! empty($filtros['fecha_desde']) ? Carbon::parse($filtros['fecha_desde'])->startOfDay() : Carbon::now()->subMonths(6)->startOfMonth();
        $fechaHasta = ! empty($filtros['fecha_hasta']) ? Carbon::parse($filtros['fecha_hasta'])->endOfDay() : Carbon::now()->endOfDay();

        // 1. Resolver nombre de sede para encabezados
        $nomSede = 'Todas las Sedes Hospitalarias';
        if ($sedeId) {
            $sede = ConfigSede::find($sedeId);
            $nomSede = $sede ? $sede->nom_sede : "Sede #{$sedeId}";
        }

        // 2. Cargar catálogo de umbrales NRD activos con mapeo multidimensional
        [$mapaUmbrales] = $this->obtenerCatalogoUmbrales();

        // 2.1 Asegurar sincronización transparente de mamografías clínicas y telemetría de sedes
        $this->asegurarTelemetriaMamografias();
        $this->asegurarTelemetriaSedes();

        $sqlRegionCase = "CASE 
            WHEN e.modalidad = 'MG' OR r.tipo_metrica = 'DGM' OR (r.study_desc LIKE '%[MAMA]%' AND r.study_desc NOT LIKE '%PARAN%') OR r.study_desc LIKE '%MAMOGRAF%' THEN 'MAMA'
            WHEN r.study_desc LIKE '%[CRANEO]%' OR r.study_desc LIKE '%PARANAS%' OR r.study_desc LIKE '%PARANAL%' THEN 'CRANEO'
            WHEN r.study_desc LIKE '%[TORAX]%' THEN 'TORAX'
            WHEN r.study_desc LIKE '%[ABDOMEN]%' THEN 'ABDOMEN'
            WHEN r.study_desc LIKE '%[PELVIS]%' THEN 'PELVIS'
            WHEN r.study_desc LIKE '%[COLUMNA]%' THEN 'COLUMNA'
            WHEN r.study_desc LIKE '%[EXTREMIDADES]%' THEN 'EXTREMIDADES'
            ELSE 'GENERAL'
        END";

        // 3. Consulta de Estadísticas Agrupadas por Sede, Mes, Modalidad, Región y Métrica
        $queryEstadisticas = DB::table('medicion_dosis_radiacion as r')
            ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
            ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
            ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
            ->select(
                'r.sede_id',
                's.nom_sede',
                DB::raw("COALESCE(e.modalidad, 'OTRA') as modalidad"),
                'r.tipo_metrica',
                DB::raw("{$sqlRegionCase} as region_anatomica"),
                DB::raw("DATE_FORMAT(r.fecha_estudio, '%Y-%m') as mes_periodo"),
                DB::raw('COUNT(*) as total_estudios'),
                DB::raw('ROUND(AVG(r.valor_dosis), 2) as dosis_promedio'),
                DB::raw('ROUND(MIN(r.valor_dosis), 2) as dosis_minima'),
                DB::raw('ROUND(MAX(r.valor_dosis), 2) as dosis_maxima'),
                'r.unidad_medida'
            )
            ->groupBy('r.sede_id', 's.nom_sede', 'modalidad', 'r.tipo_metrica', DB::raw($sqlRegionCase), 'mes_periodo', 'r.unidad_medida')
            ->orderBy('s.nom_sede', 'asc')
            ->orderBy('mes_periodo', 'desc')
            ->orderBy('modalidad', 'asc');

        if ($sedeId) {
            $queryEstadisticas->where('r.sede_id', $sedeId);
        }
        if ($modalidad) {
            $queryEstadisticas->where('e.modalidad', $modalidad);
        }
        if ($tipoMetrica) {
            $queryEstadisticas->where('r.tipo_metrica', $tipoMetrica);
        }

        $filasEstadisticas = $queryEstadisticas->get();

        // 4. Mapeo y contraste de estadísticas contra NRD
        $totalSuperaciones = 0;
        $totalAlertas = 0;
        $totalConformes = 0;

        $estadisticasProcesadas = $filasEstadisticas->map(function ($fila) use ($mapaUmbrales, &$totalSuperaciones, &$totalAlertas, &$totalConformes) {
            $regFila = $fila->region_anatomica ?? 'GENERAL';
            $keyConRegion = "{$fila->modalidad}_{$fila->tipo_metrica}_{$regFila}";
            $keyGeneral = "{$fila->modalidad}_{$fila->tipo_metrica}_GENERAL";
            $keyBase = "{$fila->modalidad}_{$fila->tipo_metrica}";

            $umbral = $mapaUmbrales[$keyConRegion] ?? $mapaUmbrales[$keyGeneral] ?? $mapaUmbrales[$keyBase] ?? null;

            $limiteNrd = $umbral ? (float) $umbral->valor_limite_nrd : null;
            $valorAlerta = $umbral ? (float) $umbral->valor_alerta : null;

            $estadoRegulatorio = 'CONFORME (SEGURO)';
            if ($limiteNrd !== null && (float) $fila->dosis_promedio >= $limiteNrd) {
                $estadoRegulatorio = 'SUPERA NRD (CRÍTICO)';
                $totalSuperaciones++;
            } elseif ($valorAlerta !== null && (float) $fila->dosis_promedio >= $valorAlerta) {
                $estadoRegulatorio = 'ALERTA PREVENTIVA';
                $totalAlertas++;
            } else {
                $totalConformes++;
            }

            $porcentajeNrd = ($limiteNrd !== null && $limiteNrd > 0)
                ? round(((float) $fila->dosis_promedio / $limiteNrd) * 100, 1)
                : null;

            $fila->limite_nrd = $limiteNrd;
            $fila->valor_alerta = $valorAlerta;
            $fila->porcentaje_nrd = $porcentajeNrd;
            $fila->estado_regulatorio = $estadoRegulatorio;

            return $fila;
        });

        // 5. Consulta de Histórico Detallado de Exposición Individual
        $queryDetalles = DB::table('medicion_dosis_radiacion as r')
            ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
            ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
            ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
            ->select(
                'r.id',
                's.nom_sede',
                DB::raw("COALESCE(e.modalidad, 'OTRA') as modalidad"),
                DB::raw("COALESCE(e.nombre, 'Equipo Emisor') as equipo_nombre"),
                DB::raw("COALESCE(e.aet_title, 'N/A') as aet_title"),
                'r.fecha_estudio',
                'r.accession_no',
                'r.study_desc',
                'r.tipo_metrica',
                'r.valor_dosis',
                'r.unidad_medida',
                'r.patient_id_hashed',
                'r.study_uid'
            )
            ->orderBy('r.fecha_estudio', 'desc');

        if ($sedeId) {
            $queryDetalles->where('r.sede_id', $sedeId);
        }
        if ($modalidad) {
            $queryDetalles->where('e.modalidad', $modalidad);
        }
        if ($tipoMetrica) {
            $queryDetalles->where('r.tipo_metrica', $tipoMetrica);
        }

        $filasDetalles = $queryDetalles->get();

        // 6. Mapeo y contraste de registros individuales
        $detallesProcesados = $filasDetalles->map(function ($reg) use ($mapaUmbrales) {
            $region = self::clasificarRegionAnatomica($reg->study_desc, $reg->modalidad);
            $reg->region_anatomica = $region;

            $keyConRegion = "{$reg->modalidad}_{$reg->tipo_metrica}_{$region}";
            $keyGeneral = "{$reg->modalidad}_{$reg->tipo_metrica}_GENERAL";
            $keyBase = "{$reg->modalidad}_{$reg->tipo_metrica}";

            $umbral = $mapaUmbrales[$keyConRegion] ?? $mapaUmbrales[$keyGeneral] ?? $mapaUmbrales[$keyBase] ?? null;

            $limiteNrd = $umbral ? (float) $umbral->valor_limite_nrd : null;
            $valorAlerta = $umbral ? (float) $umbral->valor_alerta : null;

            $estadoIndividual = 'CONFORME';
            if ($limiteNrd !== null && (float) $reg->valor_dosis >= $limiteNrd) {
                $estadoIndividual = 'SUPERA NRD';
            } elseif ($valorAlerta !== null && (float) $reg->valor_dosis >= $valorAlerta) {
                $estadoIndividual = 'ALERTA';
            }

            $porcentajeNrdIndividual = ($limiteNrd !== null && $limiteNrd > 0)
                ? round(((float) $reg->valor_dosis / $limiteNrd) * 100, 1)
                : null;

            $reg->limite_nrd = $limiteNrd;
            $reg->valor_alerta = $valorAlerta;
            $reg->porcentaje_nrd = $porcentajeNrdIndividual;
            $reg->estado_evaluacion = $estadoIndividual;

            return $reg;
        });

        // 7. KPIs consolidados y Dosis Efectiva Media (ICRP 103)
        $totalGrupos = count($estadisticasProcesadas);
        $totalEstudios = count($detallesProcesados);
        $totalEquipos = EquipoEmisor::where('estado', true)
            ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
            ->count();

        $totalPacientes = DB::table('medicion_dosis_radiacion as r')
            ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
            ->when($sedeId, fn ($q) => $q->where('r.sede_id', $sedeId))
            ->distinct()
            ->count('r.patient_id_hashed');

        $dosisEfectivaMedia = DB::table('medicion_dosis_radiacion as r')
            ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
            ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
            ->when($sedeId, fn ($q) => $q->where('r.sede_id', $sedeId))
            ->when($modalidad, fn ($q) => $q->where('e.modalidad', $modalidad))
            ->select(DB::raw("ROUND(AVG(
                CASE 
                    -- 1. Tomografía con DLP (mGy*cm -> mSv mediante coeficientes k de ICRP 103)
                    WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0021
                    WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.0140
                    WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.0150
                    WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.0150
                    WHEN r.tipo_metrica = 'DLP' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.0160
                    WHEN r.tipo_metrica = 'DLP' THEN r.valor_dosis * 0.0150
                    
                    -- 2. Tomografía con CTDIvol (Estimación normalizada E = CTDIvol * L_aprox * k_DLP)
                    WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0315
                    WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.4200
                    WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.5250
                    WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.3750
                    WHEN r.tipo_metrica = 'CTDIvol' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.3200
                    WHEN r.tipo_metrica = 'CTDIvol' THEN r.valor_dosis * 0.2500
                    
                    -- 3. Radiología Convencional y Digital con DAP (Gy*cm2 -> mSv mediante coeficientes ICRP 103)
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[TORAX]%' THEN r.valor_dosis * 0.1500
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[ABDOMEN]%' THEN r.valor_dosis * 0.2500
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[PELVIS]%' THEN r.valor_dosis * 0.2000
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[COLUMNA]%' THEN r.valor_dosis * 0.1800
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[CRANEO]%' THEN r.valor_dosis * 0.0500
                    WHEN r.tipo_metrica = 'DAP' AND r.study_desc LIKE '%[EXTREMIDADES]%' THEN r.valor_dosis * 0.0100
                    WHEN r.tipo_metrica = 'DAP' THEN r.valor_dosis * 0.1400
                    
                    -- 4. Mamografía Digital DGM (mGy glandular -> mSv ponderado)
                    WHEN r.tipo_metrica = 'DGM' THEN r.valor_dosis * 0.1200
                    
                    ELSE r.valor_dosis * 0.0500
                END
            ), 2) as e_media"))
            ->value('e_media') ?? '0.00';

        $cumplimientoPct = $totalGrupos > 0
            ? round(($totalConformes / $totalGrupos) * 100, 1)
            : 100.0;

        // 8. Construcción de Matriz de Riesgo y Mapa de Calor (Sede vs. Región Anatómica)
        $regionesCatalogo = ['CRANEO', 'TORAX', 'ABDOMEN', 'PELVIS', 'COLUMNA', 'EXTREMIDADES', 'MAMA'];
        $sedesAgrupadas = [];

        foreach ($estadisticasProcesadas as $est) {
            $sId = $est->sede_id;
            if (! isset($sedesAgrupadas[$sId])) {
                $sedesAgrupadas[$sId] = [
                    'sede_id' => $sId,
                    'nom_sede' => $est->nom_sede,
                    'regiones' => [],
                    'total_estudios' => 0,
                    'conformes' => 0,
                    'superaciones' => 0,
                    'alertas' => 0,
                ];
                foreach ($regionesCatalogo as $rc) {
                    $sedesAgrupadas[$sId]['regiones'][$rc] = [
                        'total_estudios' => 0,
                        'pct_suma' => 0,
                        'pct_estudios' => 0,
                        'dosis_suma' => 0,
                        'limite_nrd' => null,
                        'porcentaje_nrd' => null,
                        'estado' => 'SIN_DATOS',
                    ];
                }
            }

            $reg = $est->region_anatomica ?? 'GENERAL';
            if (isset($sedesAgrupadas[$sId]['regiones'][$reg])) {
                $cant = (int) $est->total_estudios;
                $prom = (float) $est->dosis_promedio;
                $lim = $est->limite_nrd ? (float) $est->limite_nrd : null;
                $pctFila = ($lim !== null && $lim > 0) ? round(($prom / $lim) * 100, 1) : null;

                $sedesAgrupadas[$sId]['regiones'][$reg]['total_estudios'] += $cant;
                if ($pctFila !== null) {
                    $sedesAgrupadas[$sId]['regiones'][$reg]['pct_suma'] += ($pctFila * $cant);
                    $sedesAgrupadas[$sId]['regiones'][$reg]['pct_estudios'] += $cant;
                    $sedesAgrupadas[$sId]['regiones'][$reg]['dosis_suma'] += ($prom * $cant);
                    $sedesAgrupadas[$sId]['regiones'][$reg]['limite_nrd'] = $lim;
                }
            }

            $sedesAgrupadas[$sId]['total_estudios'] += (int) $est->total_estudios;
            if ($est->estado_regulatorio === 'SUPERA NRD (CRÍTICO)') {
                $sedesAgrupadas[$sId]['superaciones']++;
            } elseif ($est->estado_regulatorio === 'ALERTA PREVENTIVA') {
                $sedesAgrupadas[$sId]['alertas']++;
            } else {
                $sedesAgrupadas[$sId]['conformes']++;
            }
        }

        $matrizRiesgo = [];
        foreach ($sedesAgrupadas as $sId => $sData) {
            $totalEvaluados = $sData['conformes'] + $sData['superaciones'] + $sData['alertas'];
            $tasaConf = $totalEvaluados > 0
                ? round(($sData['conformes'] / $totalEvaluados) * 100, 1)
                : 100.0;

            $filaMatriz = [
                'sede_id' => $sId,
                'nom_sede' => $sData['nom_sede'],
                'total_estudios' => $sData['total_estudios'],
                'tasa_conformidad' => $tasaConf,
                'regiones' => [],
            ];

            foreach ($regionesCatalogo as $rc) {
                $rData = $sData['regiones'][$rc];
                if ($rData['pct_estudios'] > 0 && $rData['limite_nrd'] !== null && $rData['limite_nrd'] > 0) {
                    $pctNrd = round($rData['pct_suma'] / $rData['pct_estudios'], 1);
                    $promDosis = round($rData['dosis_suma'] / $rData['pct_estudios'], 2);
                    $estado = ($pctNrd >= 100) ? 'CRÍTICO' : (($pctNrd >= 80) ? 'ALERTA' : 'SEGURO');

                    $filaMatriz['regiones'][$rc] = [
                        'pct_nrd' => $pctNrd,
                        'promedio_dosis' => $promDosis,
                        'limite_nrd' => $rData['limite_nrd'],
                        'total_estudios' => $rData['total_estudios'],
                        'estado' => $estado,
                    ];
                } else {
                    $filaMatriz['regiones'][$rc] = [
                        'pct_nrd' => null,
                        'promedio_dosis' => null,
                        'limite_nrd' => null,
                        'total_estudios' => 0,
                        'estado' => 'SIN_DATOS',
                    ];
                }
            }

            if ($sData['superaciones'] > 0) {
                $filaMatriz['nivel_riesgo'] = 'ALTO (Superó NRD)';
            } elseif ($sData['alertas'] > 0) {
                $filaMatriz['nivel_riesgo'] = 'MEDIO (En Alerta)';
            } else {
                $filaMatriz['nivel_riesgo'] = 'BAJO (Seguro)';
            }

            $matrizRiesgo[] = $filaMatriz;
        }

        return [
            'filtros' => [
                'nom_sede' => $nomSede,
                'modalidad' => $modalidad ?: 'Todas las Modalidades',
                'tipo_metrica' => $tipoMetrica ?: 'Todas las Métricas',
                'fecha_desde' => $fechaDesde->format('d/m/Y'),
                'fecha_hasta' => $fechaHasta->format('d/m/Y'),
                'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s'),
            ],
            'kpis' => [
                'total_estudios' => $totalEstudios,
                'total_equipos' => $totalEquipos,
                'total_pacientes' => $totalPacientes,
                'dosis_efectiva_media' => (float) $dosisEfectivaMedia,
                'total_grupos' => $totalGrupos,
                'total_conformes' => $totalConformes,
                'total_alertas' => $totalAlertas,
                'total_superaciones' => $totalSuperaciones,
                'cumplimiento_pct' => $cumplimientoPct,
            ],
            'estadisticas' => $estadisticasProcesadas,
            'matriz_riesgo' => $matrizRiesgo,
            'detalles' => $detallesProcesados,
        ];
    }

    /**
     * Registra el latido periódico (Heartbeat / Dead Man's Switch) emitido por el agente Edge.
     *
     * Flujo del proceso:
     * 1. Valida el identificador de la sede.
     * 2. Almacena en memoria de alta velocidad (Cache) el timestamp, versión del agente y estado de la cola local.
     * 3. Permite al Dashboard en Vue 3 mostrar un semáforo de disponibilidad por sede en tiempo real.
     *
     * @param  int  $sedeId  Identificador de la sede hospitalaria
     * @param  array  $datos  Metadatos del latido (versión, cola pendiente, etc.)
     * @return array Confirmación estructurada de recepción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function registrarHeartbeat(int $sedeId, array $datos): array
    {
        $cacheKey = "dosimetria_heartbeat_{$sedeId}";
        $payload = [
            'sede_id' => $sedeId,
            'timestamp' => Carbon::now()->toIso8601String(),
            'ultimo_contacto' => Carbon::now()->format('Y-m-d H:i:s'),
            'version' => $datos['version'] ?? '1.2.0',
            'pendientes' => (int) ($datos['pendientes'] ?? 0),
            'estado_cola' => $datos['estado_cola'] ?? 'OK',
            'detalles' => $datos['detalles'] ?? [],
        ];

        Cache::put($cacheKey, $payload, now()->addDays(7));
        Cache::put("telemetria_sede_{$sedeId}_dosimetria", [
            'servicio' => 'dosimetria',
            'estado' => strtoupper($datos['estado_cola'] ?? 'OK'),
            'mensaje' => 'Latido recibido. Pendientes: '.((int) ($datos['pendientes'] ?? 0)),
            'detalles' => $payload,
            'fecha' => now()->toIso8601String(),
        ], now()->addDays(30));

        return $this->success($payload, 'Heartbeat registrado exitosamente', 200);
    }

    /**
     * Obtiene el catálogo indexado de umbrales NRD activos por modalidad, métrica y región anatómica.
     * Incorpora auto-recuperación transparente (self-healing) que siembra los umbrales regulatorios
     * si la tabla se encuentra vacía o carece de registros por región anatómica.
     *
     * Flujo del proceso:
     * 1. Consulta todos los umbrales NRD con estado activo.
     * 2. Verifica si existen registros con región anatómica diferenciada.
     * 3. Si no existen, ejecuta de forma segura el UmbralesNrdSeeder defensivo y recarga el set.
     * 4. Indexa los umbrales por clave compuesta 'MODALIDAD_METRICA_REGION' y fallback 'MODALIDAD_METRICA'.
     *
     * @return array{0: array<string, UmbralNrd>, 1: Collection}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function obtenerCatalogoUmbrales(): array
    {
        $umbralesTodos = UmbralNrd::where('estado', true)->get();
        $tieneRegiones = $umbralesTodos->isNotEmpty() && $umbralesTodos->contains(fn ($u) => ! empty($u->region_anatomica) && $u->region_anatomica !== 'GENERAL');

        if (! $tieneRegiones) {
            try {
                (new UmbralesNrdSeeder)->run();
                $umbralesTodos = UmbralNrd::where('estado', true)->get();
            } catch (\Throwable $e) {
                Log::warning("DosimetriaService: No se pudo auto-sembrar umbrales NRD: {$e->getMessage()}");
            }
        }

        $mapaUmbrales = [];
        foreach ($umbralesTodos as $u) {
            $regKey = ! empty($u->region_anatomica) ? "_{$u->region_anatomica}" : '';
            $mapaUmbrales["{$u->modalidad}_{$u->tipo_metrica}{$regKey}"] = $u;
            if (! isset($mapaUmbrales["{$u->modalidad}_{$u->tipo_metrica}"]) || $u->region_anatomica === 'GENERAL') {
                $mapaUmbrales["{$u->modalidad}_{$u->tipo_metrica}"] = $u;
            }
        }

        return [$mapaUmbrales, $umbralesTodos];
    }

    /**
     * Asegura de forma transparente que las mamografías clínicas existentes en la base de datos
     * estén reflejadas en la telemetría dosimétrica sin requerir comandos de consola manuales.
     *
     * Flujo del proceso:
     * 1. Consulta en caché si ya existen registros de DGM en medicion_dosis_radiacion.
     * 2. Si no existen, verifica si la tabla principal de estudios contiene exámenes con modalidad MG.
     * 3. De ser así, ejecuta de forma desatendida el comando dosimetria:sync-mamografias.
     * 4. Almacena el estado en caché para no incurrir en sobrecarga en solicitudes concurrentes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-05
     */
    protected function asegurarTelemetriaMamografias(): void
    {
        $hayMg = Cache::remember('dosimetria_hay_mg', 1800, function () {
            return DB::table('medicion_dosis_radiacion')
                ->where('tipo_metrica', 'DGM')
                ->exists();
        });

        if (! $hayMg) {
            try {
                $hayEnEstudios = DB::table('estudios')
                    ->where('mods_in_study', 'like', '%MG%')
                    ->exists();

                if ($hayEnEstudios) {
                    Artisan::call('dosimetria:sync-mamografias');
                    Cache::put('dosimetria_hay_mg', true, 86400);
                }
            } catch (\Throwable $e) {
                Log::warning("DosimetriaService: No se pudo auto-sincronizar mamografías: {$e->getMessage()}");
            }
        }
    }

    /**
     * Asegura la sincronización transparente de estudios radiológicos integrales (CT, DX, CR)
     * para sedes asistenciales que cuenten con datos en la tabla principal de estudios pero
     * carezcan de telemetría dosimétrica en medicion_dosis_radiacion (Arquitectura Self-Healing).
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-05
     */
    protected function asegurarTelemetriaSedes(): void
    {
        $hondaOk = Cache::remember('dosimetria_honda_sincronizada', 1800, function () {
            return DB::table('medicion_dosis_radiacion')
                ->where('sede_id', 6)
                ->where('tipo_metrica', '!=', 'DGM')
                ->exists();
        });

        if (! $hondaOk) {
            try {
                $hayEstudios = DB::table('estudios')
                    ->where('sede_id', 6)
                    ->where(function ($q) {
                        $q->where('mods_in_study', 'like', '%CT%')
                            ->orWhere('mods_in_study', 'like', '%DX%')
                            ->orWhere('mods_in_study', 'like', '%CR%');
                    })
                    ->exists();

                if ($hayEstudios) {
                    Artisan::call('dosimetria:sync-estudios', ['--sede' => 6]);
                    Cache::put('dosimetria_honda_sincronizada', true, 86400);
                }
            } catch (\Throwable $e) {
                Log::warning("DosimetriaService: No se pudo auto-sincronizar estudios integrales de Honda: {$e->getMessage()}");
            }
        }
    }

    /**
     * Clasifica semánticamente la región anatómica del estudio a partir de su descripción DICOM.
     * Basado en la taxonomía de la Resolución 482 de 2018 y el RadLex Playbook.
     *
     * @param  string|null  $studyDesc  Descripción clínica del estudio DICOM
     * @param  string  $modalidad  Modalidad de imagen (CT, MG, DX, CR)
     * @return string Región anatómica normalizada (CRANEO, TORAX, ABDOMEN, PELVIS, COLUMNA, EXTREMIDADES, MAMA, GENERAL)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public static function clasificarRegionAnatomica(?string $studyDesc, string $modalidad = 'CT'): string
    {
        $desc = mb_strtoupper($studyDesc ?? '');
        $mod = strtoupper($modalidad);

        if ($mod === 'MG') {
            return 'MAMA';
        }

        // Cráneo y Macizo Facial (incluyendo variantes ortográficas de senos paranasales)
        if (preg_match('/CRANEO|CEREBRO|ENCEFALO|SILLA TURCA|SENO(S)?\s*PARANAS|FACIAL|MACIZO|OIDO|ORBITAS|CUELLO/', $desc)) {
            return 'CRANEO';
        }
        if (preg_match('/TORAX|PULMON|MEDIASTINO|REJA COSTAL|CARDIO|CORONARI|ANGIOTAC TORACICO/', $desc)) {
            return 'TORAX';
        }
        if (preg_match('/ABDOMEN|HIGADO|HEPATIC|PANCREAS|VIAS BILIARES|RENAL|URO|ENTEROTOMOGRAFIA|COLONOSCOPIA/', $desc)) {
            return 'ABDOMEN';
        }
        if (preg_match('/PELVIS|PELVIC|PROSTATA|VEJIGA|UTERO|CADERA|SACRO/', $desc)) {
            return 'PELVIS';
        }
        if (preg_match('/COLUMNA|CERVICAL|DORSAL|LUMBAR|SACROILIACA|LUMBOSACRA/', $desc)) {
            return 'COLUMNA';
        }
        if (preg_match('/RODILLA|HOMBRO|CODO|MU[NÑ]ECA|TOBILLO|PIE|MANO|FEMUR|TIBIA|HUMERO|RADIO|ANTEBRAZO|PIERNA|EXTREMID/', $desc)) {
            return 'EXTREMIDADES';
        }
        // Mama / Senos mamarios (excluyendo senos paranasales)
        if (preg_match('/MAMA|MAMOGRAF|SENO(S)?\s*(MAMARI|BILATERAL|IZQ|DER)/', $desc)) {
            return 'MAMA';
        }

        return 'GENERAL';
    }
}
