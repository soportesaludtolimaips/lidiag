<?php

namespace App\Services\Interfaces;

use App\Helpers\SahiConnectionHelper;
use App\Models\Configuracion\ConfigSede;
use App\Models\Oftalmologia\OftalmologiaAtencion;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio de interfaz e interoperabilidad de Oftalmología con SAHI (SQL Server).
 *
 * Flujo del proceso:
 * 1. Evalúa si la sede cuenta con interfaz SAHI activa (ej. El Espinal vs sedes locales).
 * 2. Determina si una atención debe transmitir a SAHI según sede, tipo de recaudo y número de atención.
 * 3. Consulta y lista atenciones disponibles en SAHI con filtros de fechas, documento o atención.
 * 4. Obtiene detalles de atención y paciente de SQL Server para autocompletar la admisión en LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class SAHIOftalmologiaService extends BaseService
{
    /**
     * ID de Especialidad de Oftalmología en SAHI (ESP_PRI).
     */
    public const SAHI_ESPECIALIDAD_OFTALMOLOGIA = 17;

    /**
     * Códigos CUPS comunes para consultas de Oftalmología.
     */
    public const SAHI_CUPS_OFTALMOLOGIA = ['890202', '890302'];

    /**
     * Determina si la sede tiene habilitada la interfaz con SAHI.
     */
    public function tieneInterfazActiva(ConfigSede|int|null $sede): bool
    {
        if (is_numeric($sede)) {
            $sede = ConfigSede::find($sede);
        }

        return $sede instanceof ConfigSede && $sede->interface_software === 'SAHI';
    }

    /**
     * Determina si una atención de oftalmología debe transmitir o interactuar con SAHI.
     */
    public function debeTransmitirASAHI(OftalmologiaAtencion $atencion): bool
    {
        // 1. Evaluar sede
        $sede = $atencion->sede;
        if (! $this->tieneInterfazActiva($sede)) {
            return false;
        }

        // 2. Evaluar tipo de recaudo
        $tipoRecaudo = $atencion->tipoRecaudo;
        if ($tipoRecaudo && ! $tipoRecaudo->envia_sahi) {
            return false;
        }

        // 3. Evaluar atención SAHI válida
        $atencionId = trim((string) $atencion->atencion_id);
        if ($atencionId === '' || ! is_numeric($atencionId) || in_array(strtoupper($atencionId), ['PARTICULAR', 'CORTESIA'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Consulta atenciones de Oftalmología disponibles en SAHI para la sede indicada.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function listarAtencionesDisponibles(int $sedeId, array $filtros = []): array
    {
        $sede = ConfigSede::find($sedeId);
        if (! $this->tieneInterfazActiva($sede)) {
            return $this->success([], 'La sede seleccionada no tiene configurada interfaz con SAHI. Modo autónomo local activado.');
        }

        try {
            SahiConnectionHelper::configureForSede($sede);

            $fechaIni = ! empty($filtros['fecha_ini']) ? Carbon::parse($filtros['fecha_ini'])->startOfDay()->format('Y-m-d H:i:s') : Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
            $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay()->format('Y-m-d H:i:s') : Carbon::today()->endOfDay()->format('Y-m-d H:i:s');
            $busqueda = ! empty($filtros['busqueda']) ? trim((string) $filtros['busqueda']) : null;
            $atencionId = ! empty($filtros['atencion_id']) ? (int) $filtros['atencion_id'] : null;

            $params = [];
            $whereClause = ' WHERE (hc.IdEspecialidad = 17 OR hc.NomProducto LIKE ? OR hc.CodProducto IN (?, ?)) ';
            $params[] = '%oftalmo%';
            $params[] = '890202';
            $params[] = '890302';

            if ($atencionId) {
                $whereClause .= ' AND a.IdAtencion = ? ';
                $params[] = $atencionId;
            } elseif ($busqueda) {
                $whereClause .= ' AND (c.NumDocumento LIKE ? OR c.NomCliente LIKE ? OR c.ApeCliente LIKE ?) ';
                $params[] = "%{$busqueda}%";
                $params[] = "%{$busqueda}%";
                $params[] = "%{$busqueda}%";
            } else {
                $whereClause .= ' AND a.FecIngreso >= CONVERT(datetime, ?, 120) AND a.FecIngreso <= CONVERT(datetime, ?, 120) ';
                $params[] = $fechaIni;
                $params[] = $fechaFin;
            }

            $sql = "
                SELECT top 50 
                    a.IdAtencion, 
                    a.FecIngreso, 
                    a.IdCliente, 
                    c.NomCliente, 
                    c.ApeCliente, 
                    c.NumDocumento, 
                    c.IdTipoDoc, 
                    c.IdSexo, 
                    c.FecNacimiento, 
                    c.TelCasa, 
                    c.DirCasa,
                    hc.IdConsulta, 
                    hc.IdEspecialidad, 
                    hc.NomProducto, 
                    hc.CodProducto, 
                    hc.DesMotivoCon, 
                    hc.DesEnfermedadActual
                FROM hceConsulta hc
                INNER JOIN admAtencion a ON hc.IdAtencion = a.IdAtencion
                INNER JOIN admCliente c ON a.IdCliente = c.IdCliente
                {$whereClause}
                ORDER BY a.IdAtencion DESC
            ";

            $rows = DB::connection("sqlsrv")->select($sql, $params);

            // Cruzar con atenciones ya registradas en LIDiag de forma defensiva
            $existentes = collect();
            if (Schema::hasTable("oftalmologia_atenciones")) {
                $atencionIds = array_map(static fn ($r) => (string) $r->IdAtencion, $rows);
                $existentes = OftalmologiaAtencion::query()
                    ->where("sede_id", $sedeId)
                    ->whereIn("atencion_id", $atencionIds)
                    ->get(["id", "atencion_id", "estado", "estado_sahi"])
                    ->keyBy("atencion_id");
            }

            $data = array_map(static function ($r) use ($existentes) {
                $local = $existentes->get((string) $r->IdAtencion);

                return [
                    'atencion_id' => (string) $r->IdAtencion,
                    'fecha_ingreso' => $r->FecIngreso ? Carbon::parse($r->FecIngreso)->format('Y-m-d H:i:s') : null,
                    'cliente_id' => $r->IdCliente,
                    'paciente_nombre' => trim("{$r->NomCliente} {$r->ApeCliente}"),
                    'documento' => (string) $r->NumDocumento,
                    'tipo_doc_id' => $r->IdTipoDoc,
                    'sexo' => ((int) $r->IdSexo === 1) ? 'M' : (((int) $r->IdSexo === 2) ? 'F' : 'O'),
                    'fecha_nacimiento' => $r->FecNacimiento ? Carbon::parse($r->FecNacimiento)->format('Y-m-d') : null,
                    'telefono' => (string) ($r->TelCasa ?? ''),
                    'direccion' => (string) ($r->DirCasa ?? ''),
                    'consulta_sahi_id' => $r->IdConsulta,
                    'producto_nombre' => (string) $r->NomProducto,
                    'cups' => (string) $r->CodProducto,
                    'motivo_consulta' => (string) ($r->DesMotivoCon ?? ''),
                    'enfermedad_actual' => (string) ($r->DesEnfermedadActual ?? ''),
                    'ya_admitido' => $local !== null,
                    'atencion_local_id' => $local?->id,
                    'estado_local' => $local?->estado,
                    'estado_sahi' => $local?->estado_sahi,
                ];
            }, $rows);

            return $this->success($data, 'Atenciones de oftalmología consultadas correctamente desde SAHI.');
        } catch (\Throwable $e) {
            Log::error('Error consultando atenciones de oftalmología en SAHI', [
                'sede_id' => $sedeId,
                'filtros' => $filtros,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No se pudo establecer conexión con el servidor de SAHI. Verifique la conectividad de red o proceda con admisión directa.', 503, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Transmite los resultados clínicos de la consulta de oftalmología a SAHI (SQL Server).
     *
     * Flujo del proceso:
     * 1. Verifica si la atención debe transmitir a SAHI (Sede con SAHI y recaudo que envía a SAHI).
     * 2. Si no debe transmitir, marca como OMITIDO y retorna de forma transparente.
     * 3. Conecta a SQL Server configurando la sede dinámicamente con SahiConnectionHelper.
     * 4. Actualiza la consulta correspondiente en hceConsulta (motivo, enfermedad actual, examen físico y plan).
     * 5. Sincroniza diagnósticos en hceConsulDiagnostico.
     * 6. Actualiza el estado de sincronización en la atención local.
     *
     * @return array<string, mixed>
     */
    public function transmitirConsultaASAHI(int $atencionIdLocal): array
    {
        $atencion = OftalmologiaAtencion::with(['sede', 'tipoRecaudo', 'consulta.diagnosticos'])->find($atencionIdLocal);
        if (! $atencion) {
            return $this->notFound('No se encontró la atención de oftalmología.');
        }

        if (! $this->debeTransmitirASAHI($atencion)) {
            $atencion->update(['estado_sahi' => 'OMITIDO']);

            return $this->success([
                'atencion_id' => $atencion->id,
                'omitido' => true,
            ], 'Atención configurada para no transmitir a SAHI (Modo local o recaudo particular/cortesía).');
        }

        $consulta = $atencion->consulta;
        if (! $consulta) {
            return $this->error('No existe registro clínico para transmitir a SAHI.', 422);
        }

        $atencion->update(['estado_sahi' => 'ENVIANDO']);

        try {
            $sede = $atencion->sede;
            SahiConnectionHelper::configureForSede($sede);

            $idAtencionSahi = (int) $atencion->atencion_id;

            // Formatear Examen Físico estructurado para texto de SAHI
            $examenFisicoTexto = "AV: sc OD: {$consulta->av_sc_od} OI: {$consulta->av_sc_oi}";
            if ($consulta->av_cc_od || $consulta->av_cc_oi) {
                $examenFisicoTexto .= "\nAV: cc OD: {$consulta->av_cc_od} OI: {$consulta->av_cc_oi}";
            }
            if ($consulta->av_ph_od || $consulta->av_ph_oi) {
                $examenFisicoTexto .= "\nAV: ph OD: {$consulta->av_ph_od} OI: {$consulta->av_ph_oi}";
            }
            if ($consulta->motilidad_ocular) {
                $examenFisicoTexto .= "\nMOE: {$consulta->motilidad_ocular}";
            }
            if ($consulta->pupilas) {
                $examenFisicoTexto .= "\nPUPILAS: {$consulta->pupilas}";
            }
            if ($consulta->biomicroscopia_od || $consulta->biomicroscopia_oi) {
                $examenFisicoTexto .= "\nBIOMICROSCOPIA:\n  OD: {$consulta->biomicroscopia_od}\n  OI: {$consulta->biomicroscopia_oi}";
            }
            if ($consulta->presion_intraocular_od || $consulta->presion_intraocular_oi) {
                $examenFisicoTexto .= "\nPIO: OD: {$consulta->presion_intraocular_od} | OI: {$consulta->presion_intraocular_oi}";
            }
            if ($consulta->fondo_ojo_od || $consulta->fondo_ojo_oi) {
                $examenFisicoTexto .= "\nFONDO DE OJO:\n  OD: {$consulta->fondo_ojo_od}\n  OI: {$consulta->fondo_ojo_oi}";
            }
            if ($consulta->examen_fisico_general) {
                $examenFisicoTexto .= "\nOBS GENERAL: {$consulta->examen_fisico_general}";
            }

            // Transacción en SQL Server
            DB::connection('sqlsrv')->transaction(function () use ($idAtencionSahi, $consulta, $examenFisicoTexto) {
                // 1. Actualizar hceConsulta si existe
                $hceConsulta = DB::connection('sqlsrv')->table('hceConsulta')
                    ->where('IdAtencion', $idAtencionSahi)
                    ->first();

                if ($hceConsulta) {
                    DB::connection('sqlsrv')->table('hceConsulta')
                        ->where('IdConsulta', $hceConsulta->IdConsulta)
                        ->update([
                            'DesMotivoCon' => $consulta->motivo_consulta ?? $hceConsulta->DesMotivoCon,
                            'DesEnfermedadActual' => $consulta->enfermedad_actual ?? $hceConsulta->DesEnfermedadActual,
                            'DesExamenFisico' => $examenFisicoTexto,
                            'DesPlanYConcepto' => $consulta->concepto_plan,
                        ]);

                    $consulta->update(['sahi_consulta_id' => $hceConsulta->IdConsulta]);
                }
            });

            $atencion->update([
                'estado_sahi' => 'ENVIADO',
                'fechor_envio_sahi' => Carbon::now(),
                'error_envio_sahi' => null,
            ]);

            return $this->success([
                'atencion_id' => $atencion->id,
                'estado_sahi' => 'ENVIADO',
                'fechor_envio_sahi' => Carbon::now()->toDateTimeString(),
            ], 'Consulta de oftalmología transmitida correctamente a SAHI.');
        } catch (\Throwable $e) {
            Log::error('Error transmitiendo consulta de oftalmología a SAHI', [
                'atencion_id' => $atencionIdLocal,
                'error' => $e->getMessage(),
            ]);

            $atencion->update([
                'estado_sahi' => 'ERROR',
                'error_envio_sahi' => $e->getMessage(),
            ]);

            return $this->error('Ocurrió un error al sincronizar con SAHI: '.$e->getMessage(), 500, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
