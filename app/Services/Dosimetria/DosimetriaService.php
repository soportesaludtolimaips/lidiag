<?php

namespace App\Services\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use App\Models\Dosimetria\EquipoEmisor;
use App\Models\Dosimetria\RegistroDosisRadiacion;
use App\Models\Dosimetria\UmbralNrd;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DosimetriaService extends BaseService
{
    /**
     * Procesa y persiste un lote masivo de mediciones dosimétricas proveniente del Agente Edge.
     *
     * Flujo del proceso:
     * 1. Inicia transacción de base de datos con reintentos defensivos.
     * 2. Resuelve o auto-registra los equipos emisores asociados según AET Title y modalidad.
     * 3. Ejecuta un upsert eficiente para evitar duplicados en study_uid y tipo_metrica.
     * 4. Registra auditoría clínica del volumen de dosis recibido.
     *
     * @param  int  $sedeId  Identificador de la sede hospitalaria
     * @param  array  $registros  Arreglo de mediciones dosimétricas anonimizadas
     * @return array Respuesta estructurada con resultado del guardado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function guardarLote(int $sedeId, array $registros): array
    {
        return $this->transaction(function () use ($sedeId, $registros) {
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

            return $this->success(
                [
                    'sede_id' => $sedeId,
                    'guardados' => $totalProcesados,
                    'equipos_identificados' => count($equiposCache),
                ],
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

            // 5. Sedes disponibles para el filtro
            $sedesDisponibles = ConfigSede::select('id', 'nom_sede')->where('estado', true)->orderBy('nom_sede')->get();

            // 6. Resumen de KPIs
            $totalEstudiosPeriodo = DB::table('medicion_dosis_radiacion')
                ->whereBetween('fecha_estudio', [$fechaDesde, $fechaHasta])
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            $totalEquiposActivos = EquipoEmisor::where('estado', true)
                ->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
                ->count();

            return $this->success([
                'consolidado' => $filas,
                'alertas' => $alertas,
                'ultimos_registros' => $ultimosRegistros,
                'umbrales_nrd' => $umbrales->values(),
                'sedes' => $sedesDisponibles,
                'periodo' => [
                    'desde' => $fechaDesde->format('Y-m-d'),
                    'hasta' => $fechaHasta->format('Y-m-d'),
                    'meses' => array_keys($mesesSet),
                ],
                'kpis' => [
                    'total_estudios' => $totalEstudiosPeriodo,
                    'total_equipos' => $totalEquiposActivos,
                    'total_alertas' => count($alertas),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en DosimetriaService::obtenerDashboard: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('No se pudo generar el consolidado de dosimetría: '.$e->getMessage(), 500);
        }
    }
}
