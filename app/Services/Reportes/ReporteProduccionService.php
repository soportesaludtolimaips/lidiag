<?php

namespace App\Services\Reportes;

use App\Exports\ProduccionDetalladaEcografiasExport;
use App\Exports\ProduccionDetalladaExport;
use App\Exports\ProduccionDetalladaOftalmologiaExport;
use App\Exports\ReporteAgentesTelemetriaExport;
use App\Exports\ReporteAlmacenamientoServidoresExport;
use App\Exports\ReporteAusentismoCitasExport;
use App\Exports\ReporteCitasWhatsAppExport;
use App\Exports\ReporteConsolidadoRedExport;
use App\Exports\ReporteRepresadosOportunidadExport;
use App\Exports\ReportesEstudioPAC;
use App\Exports\ReporteTATExport;
use App\Exports\ReporteTeleRadiologiaExport;
use App\Services\BaseService;
use App\Services\Seguridad\TokenSedeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteProduccionService extends BaseService
{
    /**
     * Genera el reporte detallado de producción en formato Excel.
     *
     * @param  array  $data  Datos filtrados: fechaIni, fechaFin, sedeId, quien_asigno, medico, tipo.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function generateReporteDetallado(array $data): BinaryFileResponse|array
    {
        $fechaInicio = $data['fechaIni'];
        $fechaFin = $data['fechaFin'];
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $quienAsignoId = $this->normalizeNullable($data['quien_asigno'] ?? null);
        $medicoId = $this->normalizeNullable($data['medico'] ?? null);
        $tipo = $data['tipo'];

        try {
            if ($tipo === 'Rx') {
                return Excel::download(
                    new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),
                    'produccion lecturas de imagenes diagnosticas.xlsx'
                );
            }

            if ($tipo === "Ecografia") {
                return Excel::download(
                    new ProduccionDetalladaEcografiasExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),
                    "produccion lecturas de ecografias.xlsx"
                );
            }

            if ($tipo === "Oftalmologia") {
                return Excel::download(
                    new ProduccionDetalladaOftalmologiaExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),
                    "produccion lecturas de oftalmologia.xlsx"
                );
            }

            return $this->error('Tipo de reporte inválido.', 422);
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteDetallado failed', [
                'tipo' => $tipo,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error al generar el reporte: '.$e->getMessage(), 500);
        }
    }

    /**
     * Normaliza un valor nullable convirtiendo cadenas vacías, '0' o 'null' a null.
     *
     * @param  mixed  $value  Valor a normalizar.
     *
     * @returns mixed|null Valor normalizado o null.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function normalizeNullable($value)
    {
        if ($value === null) {
            return null;
        }
        $v = is_string($value) ? trim($value) : $value;
        if ($v === '' || $v === '0' || $v === 0 || $v === 'null') {
            return null;
        }

        return $v;
    }

    /**
     * Genera el reporte de estudios tomados en PAC en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function generateReporteEstudiosPAC(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;

        if (! $sedeId) {
            return ['message' => 'Debes seleccionar una sede.', 'status' => 422];
        }

        try {
            return Excel::download(
                new ReportesEstudioPAC($fechaInicio, $fechaFin, $sedeId, "", ""),
                'estudios tomados en PAC.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteEstudiosPAC failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte PAC: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de estudios derivados a tele-radiología en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteTeleRadiologia(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;

        try {
            return Excel::download(
                new ReporteTeleRadiologiaExport($fechaInicio, $fechaFin, $sedeId),
                'reporte_tele_radiologia.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteTeleRadiologia failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de tele-radiología: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de ausentismo y cancelaciones de citas en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin, estado.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteAusentismoCitas(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;
        $estado = $data['estado'] ?? null;

        try {
            return Excel::download(
                new ReporteAusentismoCitasExport($fechaInicio, $fechaFin, $sedeId, $estado),
                'reporte_ausentismo_citas.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteAusentismoCitas failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de ausentismo: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de Calidad y Oportunidad (Turnaround Time - TAT) en formato Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId, fechaIni, fechaFin, modalidad.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteTAT(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);
        $fechaInicio = $data['fechaIni'] ?? null;
        $fechaFin = $data['fechaFin'] ?? null;
        $modalidad = $this->normalizeNullable($data['modalidad'] ?? null);

        try {
            return Excel::download(
                new ReporteTATExport($fechaInicio, $fechaFin, $sedeId, $modalidad),
                'reporte_oportunidad_tat.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteTAT failed', [
                'sede_id' => $sedeId,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'modalidad' => $modalidad,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de oportunidad TAT: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Genera el reporte de salud y telemetría de agentes hospitalarios en Excel.
     *
     * @param  array  $data  Datos filtrados: sedeId opcional.
     *
     * @returns BinaryFileResponse|array Archivo Excel o array con mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function generateReporteAgentes(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data['sedeId'] ?? null);

        try {
            return Excel::download(
                new ReporteAgentesTelemetriaExport($sedeId),
                'reporte_agentes_telemetria.xlsx'
            );
        } catch (\Throwable $e) {
            Log::error('ReporteProduccionService.generateReporteAgentes failed', [
                'sede_id' => $sedeId,
                'error' => $e->getMessage(),
            ]);

            return ['message' => 'Error al generar el reporte de agentes: '.$e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Resuelve los límites de fecha y etiqueta descriptiva según el período.
     *
     * @param  string  $periodo  Expresión de período (hoy, ayer, mes, semana o custom).
     * @param  string|null  $fechaIniCustom  Fecha inicial provista manualmente.
     * @param  string|null  $fechaFinCustom  Fecha final provista manualmente.
     * @return array{0: string, 1: string, 2: string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function resolverRangoFechas(string $periodo, ?string $fechaIniCustom = null, ?string $fechaFinCustom = null): array
    {
        $hoy = Carbon::today()->format("Y-m-d");

        if ($periodo === "ayer") {
            $ayer = Carbon::yesterday()->format("Y-m-d");

            return [$ayer, $ayer, "Ayer (".Carbon::yesterday()->format("d/m/Y").")"];
        }

        if ($periodo === "mes") {
            $inicioMes = Carbon::now()->startOfMonth()->format("Y-m-d");

            return [$inicioMes, $hoy, "Mes actual (".Carbon::now()->translatedFormat("F Y").")"];
        }

        if ($periodo === "semana" || $periodo === "7dias") {
            $inicioSemana = Carbon::now()->subDays(6)->format("Y-m-d");

            return [$inicioSemana, $hoy, "Últimos 7 días"];
        }

        if (! empty($fechaIniCustom)) {
            $ini = $fechaIniCustom;
            $fin = ! empty($fechaFinCustom) ? $fechaFinCustom : $ini;

            return [$ini, $fin, "Del {$ini} al {$fin}"];
        }

        return [$hoy, $hoy, "Hoy (".Carbon::today()->format("d/m/Y").")"];
    }

    /**
     * Obtiene el consolidado interactivo en vivo de la red hospitalaria.
     *
     * @param  array  $data  Filtros: periodo, fechaIni, fechaFin.
     * @return array Resumen analítico de la red.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function obtenerResumenConsolidadoRed(array $data): array
    {
        $periodo = $data["periodo"] ?? "hoy";
        $fechaIni = $data["fechaIni"] ?? null;
        $fechaFin = $data["fechaFin"] ?? null;

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIni, $fechaFin);

        $queryBase = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->whereBetween("estudios_productos.fechor_lectura", [
                "{$fechaInicio} 00:00:00",
                "{$fechaFin} 23:59:59",
            ]);

        $totalRedProcedimientos = (clone $queryBase)->count();
        $totalRedPacientes = (clone $queryBase)->distinct("estudios.id")->count("estudios.id");

        $sedesData = (clone $queryBase)
            ->selectRaw("config_sedes.id as sede_id, config_sedes.nom_sede, count(*) as cantidad")
            ->groupBy("config_sedes.id", "config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get()
            ->map(function ($s) use ($totalRedProcedimientos) {
                $nomCorto = trim(explode("-", $s->nom_sede)[0]);
                $pct = $totalRedProcedimientos > 0 ? round(($s->cantidad / $totalRedProcedimientos) * 100, 1) : 0;

                return [
                    "sede_id" => $s->sede_id,
                    "nom_sede" => $nomCorto,
                    "nom_completo" => $s->nom_sede,
                    "cantidad" => $s->cantidad,
                    "porcentaje" => $pct,
                ];
            });

        $modalidadesRaw = (clone $queryBase)
            ->selectRaw("COALESCE(NULLIF(estudios.mods_in_study, ''), 'OTRO') as mod_nombre, count(*) as cantidad")
            ->groupBy("mod_nombre")
            ->orderByDesc("cantidad")
            ->get();

        $modalidadesRed = $modalidadesRaw->map(function ($m) use ($totalRedProcedimientos) {
            $etiqueta = match ($m->mod_nombre) {
                "CR", "DX", "DR" => "Rayos X",
                "CT" => "Tomografía (TAC)",
                "MR" => "Resonancia Magnética",
                "MG" => "Mamografía",
                "US" => "Ecografía",
                default => $m->mod_nombre,
            };
            $icono = match ($m->mod_nombre) {
                "CR", "DX", "DR" => "fa fa-file-image-o",
                "CT" => "fa fa-circle-o-notch",
                "MR" => "fa fa-magnet",
                "MG" => "fa fa-female",
                "US" => "fa fa-podcast",
                default => "fa fa-heartbeat",
            };
            $pct = $totalRedProcedimientos > 0 ? round(($m->cantidad / $totalRedProcedimientos) * 100, 1) : 0;

            return [
                "mod_codigo" => $m->mod_nombre,
                "mod_nombre" => $etiqueta,
                "icono" => $icono,
                "cantidad" => $m->cantidad,
                "porcentaje" => $pct,
            ];
        });

        $pendientesRed = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->count();

        $avgMinutos = (clone $queryBase)
            ->whereRaw("COALESCE(estudios.fec_estudio, estudios.created_at) <= estudios_productos.fechor_lectura")
            ->whereRaw("TIMESTAMPDIFF(DAY, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura) <= 45")
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), estudios_productos.fechor_lectura)) as avg_min")
            ->value("avg_min");

        $tatTexto = "N/D";
        $tatClase = "secondary";
        $tatNivel = "Sin datos";
        if ($avgMinutos !== null && $avgMinutos > 0) {
            $horas = (int) floor($avgMinutos / 60);
            $mins = (int) round($avgMinutos % 60);
            $tatTexto = $horas > 0 ? "{$horas}h {$mins}m" : "{$mins}m";
            if ($avgMinutos <= 120) {
                $tatClase = "success";
                $tatNivel = "Excelente (<2h)";
            } elseif ($avgMinutos <= 720) {
                $tatClase = "success";
                $tatNivel = "Óptimo (<12h)";
            } elseif ($avgMinutos <= 1440) {
                $tatClase = "warning";
                $tatNivel = "Aceptable (<24h)";
            } else {
                $tatClase = "danger";
                $tatNivel = "Retrasado (>24h)";
            }
        }

        return [
            "fechaInicio" => $fechaInicio,
            "fechaFin" => $fechaFin,
            "etiquetaPeriodo" => $etiquetaPeriodo,
            "totalProcedimientos" => $totalRedProcedimientos,
            "totalPacientes" => $totalRedPacientes,
            "tatTexto" => $tatTexto,
            "tatClase" => $tatClase,
            "tatNivel" => $tatNivel,
            "pendientesRed" => $pendientesRed,
            "rankingSedes" => $sedesData,
            "modalidades" => $modalidadesRed,
        ];
    }

    /**
     * Obtiene el semáforo asistencial de estudios represados (>24h) y urgencias en tiempo real.
     *
     * @param  array  $data  Filtros: sedeId opcional.
     * @return array Semáforo analítico asistencial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function obtenerSemaforoRepresados(array $data): array
    {
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);

        $represadosPorSede = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->whereRaw("TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 24")
            ->when($sedeId, fn ($q) => $q->where("estudios.sede_id", $sedeId))
            ->selectRaw("
                config_sedes.id as sede_id,
                config_sedes.nom_sede,
                COUNT(*) as cantidad,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) BETWEEN 25 AND 48 THEN 1 ELSE 0 END) as rango_24_48,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) BETWEEN 49 AND 72 THEN 1 ELSE 0 END) as rango_48_72,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 72 THEN 1 ELSE 0 END) as rango_mas_72
            ")
            ->groupBy("config_sedes.id", "config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get();

        $urgentesPendientes = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->where("estudios.prioridad_id", 1)
            ->when($sedeId, fn ($q) => $q->where("estudios.sede_id", $sedeId))
            ->selectRaw("
                config_sedes.id as sede_id,
                config_sedes.nom_sede,
                COUNT(*) as cantidad,
                SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, COALESCE(estudios.fec_estudio, estudios.created_at), NOW()) > 30 THEN 1 ELSE 0 END) as mas_30m
            ")
            ->groupBy("config_sedes.id", "config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get();

        $totalRepresados = (int) $represadosPorSede->sum("cantidad");
        $tot24_48 = (int) $represadosPorSede->sum("rango_24_48");
        $tot48_72 = (int) $represadosPorSede->sum("rango_48_72");
        $totMas72 = (int) $represadosPorSede->sum("rango_mas_72");

        $totalUrgentes = (int) $urgentesPendientes->sum("cantidad");
        $totalUrgentes30m = (int) $urgentesPendientes->sum("mas_30m");

        return [
            "corte" => Carbon::now()->format("d/m/Y H:i"),
            "totalRepresados" => $totalRepresados,
            "tot24_48" => $tot24_48,
            "tot48_72" => $tot48_72,
            "totMas72" => $totMas72,
            "totalUrgentes" => $totalUrgentes,
            "totalUrgentes30m" => $totalUrgentes30m,
            "represadosPorSede" => $represadosPorSede->map(function ($r) use ($totalRepresados) {
                $nomCorto = trim(explode("-", $r->nom_sede)[0]);
                $pct = $totalRepresados > 0 ? round(($r->cantidad / $totalRepresados) * 100, 1) : 0;

                return [
                    "sede_id" => $r->sede_id,
                    "nom_sede" => $nomCorto,
                    "nom_completo" => $r->nom_sede,
                    "cantidad" => (int) $r->cantidad,
                    "porcentaje" => $pct,
                    "rango_24_48" => (int) $r->rango_24_48,
                    "rango_48_72" => (int) $r->rango_48_72,
                    "rango_mas_72" => (int) $r->rango_mas_72,
                ];
            }),
            "urgentesPorSede" => $urgentesPendientes->map(function ($u) {
                $nomCorto = trim(explode("-", $u->nom_sede)[0]);

                return [
                    "sede_id" => $u->sede_id,
                    "nom_sede" => $nomCorto,
                    "nom_completo" => $u->nom_sede,
                    "cantidad" => (int) $u->cantidad,
                    "mas_30m" => (int) $u->mas_30m,
                ];
            }),
        ];
    }

    /**
     * Obtiene estadísticas de citas y del tráfico del canal WhatsApp.
     *
     * @param  array  $data  Filtros: periodo, sedeId, fechaIni, fechaFin.
     * @return array Estadísticas de citas y WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function obtenerEstadisticasCitas(array $data): array
    {
        $periodo = $data["periodo"] ?? "hoy";
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);
        $fechaIni = $data["fechaIni"] ?? null;
        $fechaFin = $data["fechaFin"] ?? null;

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIni, $fechaFin);

        $tablaCitas = Schema::hasTable("citas") ? "citas" : "agenda_citas";
        $campoFechaCita = $tablaCitas === "citas" ? "fecha_hora_inicio" : "fecha_hora_ini";

        // 1. Citas programadas para HOY
        $hoy = Carbon::today()->format("Y-m-d");
        $queryCitasHoy = DB::table($tablaCitas)
            ->when($sedeId, fn ($q) => $q->where("sede_id", $sedeId))
            ->whereDate($campoFechaCita, $hoy);

        $totalCitasHoy = (clone $queryCitasHoy)->count();
        $estadosHoy = (clone $queryCitasHoy)
            ->selectRaw("estado, count(*) as cantidad")
            ->groupBy("estado")
            ->pluck("cantidad", "estado")
            ->toArray();

        $confirmadasHoy = $estadosHoy["confirmada"] ?? $estadosHoy["asignada"] ?? $estadosHoy["Atendido"] ?? 0;
        $canceladasHoy = $estadosHoy["cancelada"] ?? $estadosHoy["rechazada"] ?? $estadosHoy["Cancelado"] ?? 0;
        $pctAsistencia = $totalCitasHoy > 0 ? round(($confirmadasHoy / $totalCitasHoy) * 100, 1) : 0;

        // 2. Tráfico de solicitudes en el período seleccionado
        $querySolicitudes = DB::table($tablaCitas)
            ->when($sedeId, fn ($q) => $q->where("sede_id", $sedeId))
            ->whereBetween("created_at", ["{$fechaInicio} 00:00:00", "{$fechaFin} 23:59:59"]);

        $totalSolicitudes = (clone $querySolicitudes)->count();
        $estadosPeriodo = (clone $querySolicitudes)
            ->selectRaw("estado, count(*) as cantidad")
            ->groupBy("estado")
            ->pluck("cantidad", "estado")
            ->toArray();

        $aprobadasPeriodo = $estadosPeriodo["asignada"] ?? $estadosPeriodo["confirmada"] ?? $estadosPeriodo["Atendido"] ?? 0;
        $canceladasPeriodo = $estadosPeriodo["cancelada"] ?? $estadosPeriodo["rechazada"] ?? $estadosPeriodo["Cancelado"] ?? 0;
        $pendientesRevision = $estadosPeriodo["solicitada"] ?? $estadosPeriodo["Pendiente"] ?? 0;
        $tasaConversion = $totalSolicitudes > 0 ? round(($aprobadasPeriodo / $totalSolicitudes) * 100, 1) : 0;

        return [
            "fechaInicio" => $fechaInicio,
            "fechaFin" => $fechaFin,
            "etiquetaPeriodo" => $etiquetaPeriodo,
            "citasHoy" => [
                "total" => $totalCitasHoy,
                "confirmadas" => $confirmadasHoy,
                "canceladas" => $canceladasHoy,
                "pctAsistencia" => $pctAsistencia,
            ],
            "whatsappPeriodo" => [
                "totalSolicitudes" => $totalSolicitudes,
                "aprobadas" => $aprobadasPeriodo,
                "canceladas" => $canceladasPeriodo,
                "pendientesRevision" => $pendientesRevision,
                "tasaConversion" => $tasaConversion,
            ],
            "desgloseEstados" => collect($estadosPeriodo)->map(function ($cantidad, $estado) use ($totalSolicitudes) {
                $pct = $totalSolicitudes > 0 ? round(($cantidad / $totalSolicitudes) * 100, 1) : 0;

                return [
                    "estado" => ucfirst(str_replace("_", " ", (string) $estado)),
                    "cantidad" => $cantidad,
                    "porcentaje" => $pct,
                ];
            })->values(),
        ];
    }

    /**
     * Obtiene el estado del almacenamiento del Servidor Central y telemetría de sedes.
     *
     * @param  array  $data  Filtros: sedeId opcional.
     * @return array Telemetría de almacenamiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function obtenerMonitorAlmacenamiento(array $data): array
    {
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);

        $pathApp = base_path();
        $totalBytes = @disk_total_space($pathApp);
        $freeBytes = @disk_free_space($pathApp);

        $totalGb = $totalBytes ? round($totalBytes / (1024 ** 3), 1) : 0;
        $freeGb = $freeBytes ? round($freeBytes / (1024 ** 3), 1) : 0;
        $usedGb = ($totalBytes && $freeBytes) ? round(($totalBytes - $freeBytes) / (1024 ** 3), 1) : 0;
        $freePct = $totalBytes ? round(($freeBytes / $totalBytes) * 100, 1) : 0;
        $usedPct = $totalBytes ? round(($usedGb / max(1, $totalGb)) * 100, 1) : 0;

        $estadoCentral = "optimo";
        $iconoCentral = "success";
        if ($freePct <= 5.0 || $freeGb < 20.0) {
            $estadoCentral = "critico";
            $iconoCentral = "danger";
        } elseif ($freePct <= 15.0 || $freeGb < 50.0) {
            $estadoCentral = "advertencia";
            $iconoCentral = "warning";
        }

        /** @var TokenSedeService $tokenService */
        $tokenService = app(TokenSedeService::class);
        $resTokens = $tokenService->listarSedesConTokens();
        $sedesRaw = $resTokens["data"]["sedes"] ?? [];

        $sedes = collect($sedesRaw)->when($sedeId, fn ($c) => $c->where("id", $sedeId))->map(function ($s) {
            $nomCorto = trim(explode("-", $s["nom_sede"])[0]);
            $bk = $s["agente_backup_db"]["estado"] ?? "SIN_DATOS";
            $bkClase = match ($bk) {
                "OK" => "success",
                "ADVERTENCIA" => "warning",
                "ERROR" => "danger",
                default => "secondary",
            };
            $sync = $s["agente_sync_imagenes"]["estado"] ?? "SIN_DATOS";
            $syncClase = match ($sync) {
                "OK" => "success",
                "ERROR" => "danger",
                default => "secondary",
            };
            $dosi = $s["agente_dosimetria"]["estado"] ?? "SIN_DATOS";
            $dosiClase = match ($dosi) {
                "OK" => "success",
                "ERROR" => "danger",
                default => "secondary",
            };

            return [
                "id" => $s["id"],
                "nom_sede" => $nomCorto,
                "nom_completo" => $s["nom_sede"],
                "ip_dcm" => $s["ip_dcm"],
                "tiene_token" => $s["tiene_token"],
                "backup_estado" => $bk,
                "backup_clase" => $bkClase,
                "backup_tiempo" => $s["agente_backup_db"]["tiempo_humano"] ?? "Nunca",
                "sync_estado" => $sync,
                "sync_clase" => $syncClase,
                "dosimetria_estado" => $dosi,
                "dosimetria_clase" => $dosiClase,
            ];
        })->values();

        return [
            "corte" => Carbon::now()->format("d/m/Y H:i"),
            "servidorCentral" => [
                "totalGb" => $totalGb,
                "freeGb" => $freeGb,
                "usedGb" => $usedGb,
                "freePct" => $freePct,
                "usedPct" => $usedPct,
                "estado" => $estadoCentral,
                "iconoClase" => $iconoCentral,
            ],
            "sedes" => $sedes,
        ];
    }

    /**
     * Genera el archivo Excel de Consolidado de Toda la Red Hospitalaria.
     *
     * @param  array  $data  Filtros: periodo, fechaIni, fechaFin.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function generateReporteConsolidadoRed(array $data): BinaryFileResponse|array
    {
        $periodo = $data["periodo"] ?? "hoy";
        $fechaIni = $data["fechaIni"] ?? null;
        $fechaFin = $data["fechaFin"] ?? null;

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIni, $fechaFin);

        try {
            return Excel::download(
                new ReporteConsolidadoRedExport($fechaInicio, $fechaFin, $etiquetaPeriodo),
                "consolidado_red_produccion.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error("ReporteProduccionService.generateReporteConsolidadoRed failed", [
                "error" => $e->getMessage(),
            ]);

            return ["message" => "Error al generar el consolidado de red: ".$e->getMessage(), "status" => 500];
        }
    }

    /**
     * Genera el archivo Excel del Semáforo de Represamiento y Urgencias fuera de SLA.
     *
     * @param  array  $data  Filtros: sedeId opcional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function generateReporteRepresados(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);

        try {
            return Excel::download(
                new ReporteRepresadosOportunidadExport($sedeId),
                "reporte_represados_oportunidad.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error("ReporteProduccionService.generateReporteRepresados failed", [
                "sedeId" => $sedeId,
                "error" => $e->getMessage(),
            ]);

            return ["message" => "Error al generar el reporte de represados: ".$e->getMessage(), "status" => 500];
        }
    }

    /**
     * Genera el archivo Excel del reporte de Citas y Canal WhatsApp.
     *
     * @param  array  $data  Filtros: periodo, sedeId, fechaIni, fechaFin.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function generateReporteCitasWhatsApp(array $data): BinaryFileResponse|array
    {
        $periodo = $data["periodo"] ?? "hoy";
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);
        $fechaIni = $data["fechaIni"] ?? null;
        $fechaFin = $data["fechaFin"] ?? null;

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIni, $fechaFin);

        try {
            return Excel::download(
                new ReporteCitasWhatsAppExport($fechaInicio, $fechaFin, $sedeId, $etiquetaPeriodo),
                "reporte_citas_whatsapp.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error("ReporteProduccionService.generateReporteCitasWhatsApp failed", [
                "error" => $e->getMessage(),
            ]);

            return ["message" => "Error al generar el reporte de citas WhatsApp: ".$e->getMessage(), "status" => 500];
        }
    }

    /**
     * Genera el archivo Excel del Monitor de Almacenamiento y Servidores.
     *
     * @param  array  $data  Filtros: sedeId opcional.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function generateReporteAlmacenamiento(array $data): BinaryFileResponse|array
    {
        $sedeId = $this->normalizeNullable($data["sedeId"] ?? null);

        try {
            return Excel::download(
                new ReporteAlmacenamientoServidoresExport($sedeId),
                "reporte_almacenamiento_servidores.xlsx"
            );
        } catch (\Throwable $e) {
            Log::error("ReporteProduccionService.generateReporteAlmacenamiento failed", [
                "error" => $e->getMessage(),
            ]);

            return ["message" => "Error al generar el reporte de almacenamiento: ".$e->getMessage(), "status" => 500];
        }
    }
}
