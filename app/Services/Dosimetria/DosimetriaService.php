<?php

namespace App\Services\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dosimetria\EquipoEmisor;
use App\Models\Dosimetria\RegistroDosisRadiacion;
use App\Models\Dosimetria\UmbralNrd;
use App\Services\BaseService;
use Carbon\Carbon;
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
                    'study_desc' => $reg['study_desc'] ?? null,
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
            $fechaDesde = ! empty($filtros['fecha_desde']) ? Carbon::parse($filtros['fecha_desde'])->startOfDay() : Carbon::now()->subMonths(6)->startOfMonth();
            $fechaHasta = ! empty($filtros['fecha_hasta']) ? Carbon::parse($filtros['fecha_hasta'])->endOfDay() : Carbon::now()->endOfDay();

            // 1. Cargar umbrales NRD regulatorios
            $umbrales = UmbralNrd::where('estado', true)->get()->keyBy(function ($item) {
                return "{$item->modalidad}_{$item->tipo_metrica}";
            });

            // 2. Consulta agrupada por Sede, Modalidad y Mes
            $query = DB::table('medicion_dosis_radiacion as r')
                ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
                ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
                ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
                ->select(
                    'r.sede_id',
                    's.nom_sede',
                    DB::raw("COALESCE(e.modalidad, 'OTRA') as modalidad"),
                    'r.tipo_metrica',
                    DB::raw("DATE_FORMAT(r.fecha_estudio, '%Y-%m') as mes_periodo"),
                    DB::raw('COUNT(*) as total_estudios'),
                    DB::raw('ROUND(AVG(r.valor_dosis), 2) as dosis_promedio'),
                    DB::raw('ROUND(MAX(r.valor_dosis), 2) as dosis_maxima'),
                    DB::raw('ROUND(MIN(r.valor_dosis), 2) as dosis_minima'),
                    'r.unidad_medida'
                )
                ->groupBy('r.sede_id', 's.nom_sede', 'modalidad', 'r.tipo_metrica', 'mes_periodo', 'r.unidad_medida')
                ->orderBy('mes_periodo', 'ASC');

            if ($sedeId) {
                $query->where('r.sede_id', $sedeId);
            }
            if ($modalidad) {
                $query->where('e.modalidad', $modalidad);
            }
            if ($tipoMetrica) {
                $query->where('r.tipo_metrica', $tipoMetrica);
            }

            $filas = $query->get();

            // 3. Evaluar alertas de superación de NRD
            $alertas = [];
            $seriesChart = [];
            $mesesSet = [];
            $sedesSet = [];

            foreach ($filas as $fila) {
                $mesesSet[$fila->mes_periodo] = true;
                $sedesSet[$fila->sede_id] = $fila->nom_sede;

                $nrdKey = "{$fila->modalidad}_{$fila->tipo_metrica}";
                $umbral = $umbrales->get($nrdKey);

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
                    $alertas[] = [
                        'sede_id' => $fila->sede_id,
                        'nom_sede' => $fila->nom_sede,
                        'modalidad' => $fila->modalidad,
                        'tipo_metrica' => $fila->tipo_metrica,
                        'mes_periodo' => $fila->mes_periodo,
                        'dosis_promedio' => $fila->dosis_promedio,
                        'limite_nrd' => $limiteNrd,
                        'unidad_medida' => $fila->unidad_medida,
                        'gravedad' => $superaNrd ? 'PELIGRO_NRD' : 'ALERTA_PREVENTIVA',
                        'mensaje' => $superaNrd
                            ? "Sede {$fila->nom_sede} superó el Nivel de Referencia ({$fila->dosis_promedio} >= {$limiteNrd} {$fila->unidad_medida}) en {$fila->modalidad} ({$fila->tipo_metrica})."
                            : "Sede {$fila->nom_sede} en nivel de advertencia ({$fila->dosis_promedio} {$fila->unidad_medida}) para {$fila->modalidad}.",
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
                ->orderBy('r.fecha_estudio', 'DESC')
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

            // 5. Sedes disponibles enriquecidas con su monitor de latidos (Heartbeat / Dead Man's Switch)
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

            // 6. Resumen de KPIs
            $totalEstudiosPeriodo = DB::table('medicion_dosis_radiacion')
                ->whereBetween('fecha_estudio', [$fechaDesde, $fechaHasta])
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            $totalEquiposActivos = EquipoEmisor::where('estado', true)
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            $sedesOnline = $sedesConEstado->where('estado_conexion', 'ONLINE')->count();

            return $this->success([
                'consolidado' => $filas,
                'alertas' => $alertas,
                'ultimos_registros' => $ultimosRegistros,
                'umbrales_nrd' => $umbrales->values(),
                'sedes' => $sedesConEstado,
                'periodo' => [
                    'desde' => $fechaDesde->format('Y-m-d'),
                    'hasta' => $fechaHasta->format('Y-m-d'),
                    'meses' => array_keys($mesesSet),
                ],
                'kpis' => [
                    'total_estudios' => $totalEstudiosPeriodo,
                    'total_equipos' => $totalEquiposActivos,
                    'total_alertas' => count($alertas),
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

        // 2. Cargar catálogo de umbrales NRD activos
        $umbrales = UmbralNrd::where('estado', true)->get()->keyBy(function ($item) {
            return "{$item->modalidad}_{$item->tipo_metrica}";
        });

        // 3. Consulta de Estadísticas Agrupadas por Sede, Mes, Modalidad y Métrica
        $queryEstadisticas = DB::table('medicion_dosis_radiacion as r')
            ->join('config_sedes as s', 's.id', '=', 'r.sede_id')
            ->leftJoin('medicion_dosis_equipos_emisores as e', 'e.id', '=', 'r.equipo_emisor_id')
            ->whereBetween('r.fecha_estudio', [$fechaDesde, $fechaHasta])
            ->select(
                'r.sede_id',
                's.nom_sede',
                DB::raw("COALESCE(e.modalidad, 'OTRA') as modalidad"),
                'r.tipo_metrica',
                DB::raw("DATE_FORMAT(r.fecha_estudio, '%Y-%m') as mes_periodo"),
                DB::raw('COUNT(*) as total_estudios'),
                DB::raw('ROUND(AVG(r.valor_dosis), 2) as dosis_promedio'),
                DB::raw('ROUND(MIN(r.valor_dosis), 2) as dosis_minima'),
                DB::raw('ROUND(MAX(r.valor_dosis), 2) as dosis_maxima'),
                'r.unidad_medida'
            )
            ->groupBy('r.sede_id', 's.nom_sede', 'modalidad', 'r.tipo_metrica', 'mes_periodo', 'r.unidad_medida')
            ->orderBy('s.nom_sede', 'ASC')
            ->orderBy('mes_periodo', 'DESC')
            ->orderBy('modalidad', 'ASC');

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

        $estadisticasProcesadas = $filasEstadisticas->map(function ($fila) use ($umbrales, &$totalSuperaciones, &$totalAlertas, &$totalConformes) {
            $nrdKey = "{$fila->modalidad}_{$fila->tipo_metrica}";
            $umbral = $umbrales->get($nrdKey);

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

            $fila->limite_nrd = $limiteNrd;
            $fila->valor_alerta = $valorAlerta;
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
            ->orderBy('r.fecha_estudio', 'DESC');

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
        $detallesProcesados = $filasDetalles->map(function ($reg) use ($umbrales) {
            $nrdKey = "{$reg->modalidad}_{$reg->tipo_metrica}";
            $umbral = $umbrales->get($nrdKey);

            $limiteNrd = $umbral ? (float) $umbral->valor_limite_nrd : null;
            $valorAlerta = $umbral ? (float) $umbral->valor_alerta : null;

            $estadoIndividual = 'CONFORME';
            if ($limiteNrd !== null && (float) $reg->valor_dosis >= $limiteNrd) {
                $estadoIndividual = 'SUPERA NRD';
            } elseif ($valorAlerta !== null && (float) $reg->valor_dosis >= $valorAlerta) {
                $estadoIndividual = 'ALERTA';
            }

            $reg->limite_nrd = $limiteNrd;
            $reg->valor_alerta = $valorAlerta;
            $reg->estado_evaluacion = $estadoIndividual;

            return $reg;
        });

        // 7. KPIs consolidados
        $totalGrupos = count($estadisticasProcesadas);
        $totalEstudios = count($detallesProcesados);
        $totalEquipos = EquipoEmisor::where('estado', true)
            ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
            ->count();

        $cumplimientoPct = $totalGrupos > 0
            ? round(($totalConformes / $totalGrupos) * 100, 1)
            : 100.0;

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
                'total_grupos' => $totalGrupos,
                'total_conformes' => $totalConformes,
                'total_alertas' => $totalAlertas,
                'total_superaciones' => $totalSuperaciones,
                'cumplimiento_pct' => $cumplimientoPct,
            ],
            'estadisticas' => $estadisticasProcesadas,
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

        return $this->success($payload, 'Heartbeat registrado exitosamente', 200);
    }
}
