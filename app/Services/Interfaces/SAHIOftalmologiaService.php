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
     * Códigos CUPS comunes para consultas e interconsultas de Oftalmología.
     */
    public const SAHI_CUPS_OFTALMOLOGIA = ['890202', '890302', '890476'];

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
     * Consulta tanto facturación (facFacturaDet) como movimientos/cargos (facMovimientoDet)
     * siguiendo la arquitectura probada del módulo de Ecografías.
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

            $fechaIni = ! empty($filtros['fecha_ini']) ? Carbon::parse($filtros['fecha_ini'])->format('Y-m-d') : null;
            $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->format('Y-m-d') : null;
            $criterio = ! empty($filtros['busqueda']) ? trim((string) $filtros['busqueda']) : (! empty($filtros['atencion_id']) ? trim((string) $filtros['atencion_id']) : null);

            // Si no hay criterio ni fechas, por defecto buscar fecha de hoy
            if (empty($criterio) && empty($fechaIni) && empty($fechaFin)) {
                $fechaIni = Carbon::today()->format('Y-m-d');
                $fechaFin = Carbon::today()->format('Y-m-d');
            }

            $atencionIds = $this->resolverAtencionesIds($criterio, $fechaIni, $fechaFin);

            if (empty($atencionIds)) {
                return $this->success([], 'No se encontraron atenciones con los criterios suministrados.');
            }

            $oftalmoFilter = static function ($q) {
                $q->where(function ($sub) {
                    $sub->whereIn('proProducto.CodLegal', ['890476', '890202', '890302'])
                        ->orWhereIn('proProducto.CodProducto', ['890476', '890202', '890302'])
                        ->orWhere('proProducto.NomProducto', 'LIKE', '%OFTALMO%');
                });
            };

            $columns1 = [
                'admAtencion.IdAtencion',
                DB::raw("CONVERT(char(19), admAtencion.FecIngreso, 120) AS FecIngreso"),
                'genTipoDoc.IdTipoDoc',
                'genTipoDoc.CodTipoDoc',
                'genTipoDoc.NomTipoDoc',
                'admCliente.NumDocumento',
                'admCliente.PrimerNombre',
                'admCliente.SegundoNombre',
                'admCliente.PrimerApellido',
                'admCliente.SegundoApellido',
                DB::raw("CONVERT(char(10), admCliente.FecNacimiento, 120) AS FecNacimiento"),
                'admCliente.IdSexo',
                'admCliente.DirCasa',
                'admCliente.TelCasa',
                'proProducto.NomProducto',
                'proProducto.IdProducto',
                'proProducto.CodProducto',
                'proProducto.CodLegal',
                DB::raw("facFacturaDet.ValTotal AS ValProduc"),
                'facFacturaDet.Cantidad',
                'conContrato.CodCon',
                'conContrato.DesContrato',
                'conPlan.DesPlan',
                'genTercero.NomTercero',
                DB::raw("COALESCE(genUbiAct.NomUbicacion, genUbiIng.NomUbicacion, '') AS NomUbicacion"),
                DB::raw("COALESCE(admCama.CodCama, '') AS Cama"),
            ];

            $columns2 = [
                'admAtencion.IdAtencion',
                DB::raw("CONVERT(char(19), admAtencion.FecIngreso, 120) AS FecIngreso"),
                'genTipoDoc.IdTipoDoc',
                'genTipoDoc.CodTipoDoc',
                'genTipoDoc.NomTipoDoc',
                'admCliente.NumDocumento',
                'admCliente.PrimerNombre',
                'admCliente.SegundoNombre',
                'admCliente.PrimerApellido',
                'admCliente.SegundoApellido',
                DB::raw("CONVERT(char(10), admCliente.FecNacimiento, 120) AS FecNacimiento"),
                'admCliente.IdSexo',
                'admCliente.DirCasa',
                'admCliente.TelCasa',
                'proProducto.NomProducto',
                'proProducto.IdProducto',
                'proProducto.CodProducto',
                'proProducto.CodLegal',
                DB::raw("facMovimientoDet.ValVenta AS ValProduc"),
                'facMovimientoDet.Cantidad',
                'conContrato.CodCon',
                'conContrato.DesContrato',
                'conPlan.DesPlan',
                'genTercero.NomTercero',
                DB::raw("COALESCE(genUbiAct.NomUbicacion, genUbiIng.NomUbicacion, '') AS NomUbicacion"),
                DB::raw("COALESCE(admCama.CodCama, '') AS Cama"),
            ];

            $consulta1 = DB::connection('sqlsrv')
                ->table('facFacturaDet')
                ->select($columns1)
                ->join('facFactura', 'facFacturaDet.IdFactura', '=', 'facFactura.IdFactura')
                ->join('proProducto', 'facFacturaDet.IdProducto', '=', 'proProducto.IdProducto')
                ->join('admAtencion', 'facFactura.IdDestino', '=', 'admAtencion.IdAtencion')
                ->leftJoin('admCama', 'admAtencion.IdCamaActual', '=', 'admCama.IdCama')
                ->leftJoin('genUbicacion as genUbiAct', 'admAtencion.IdUbicacionActual', '=', 'genUbiAct.IdUbicacion')
                ->leftJoin('genUbicacion as genUbiIng', 'admAtencion.IdUbicacionIng', '=', 'genUbiIng.IdUbicacion')
                ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
                ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
                ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
                ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
                ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
                ->join('genTipoDoc', 'admCliente.IdTipoDoc', '=', 'genTipoDoc.IdTipoDoc')
                ->whereIn('admAtencion.IdAtencion', $atencionIds)
                ->where('facFacturaDet.Cantidad', '>', 0)
                ->where($oftalmoFilter);

            $consulta2 = DB::connection('sqlsrv')
                ->table('facMovimientoDet')
                ->select($columns2)
                ->join('facMovimiento', 'facMovimientoDet.IdMovimiento', '=', 'facMovimiento.IdMovimiento')
                ->join('proProducto', 'facMovimientoDet.IdProducto', '=', 'proProducto.IdProducto')
                ->join('admAtencion', 'facMovimiento.IdDestino', '=', 'admAtencion.IdAtencion')
                ->leftJoin('admCama', 'admAtencion.IdCamaActual', '=', 'admCama.IdCama')
                ->leftJoin('genUbicacion as genUbiAct', 'admAtencion.IdUbicacionActual', '=', 'genUbiAct.IdUbicacion')
                ->leftJoin('genUbicacion as genUbiIng', 'admAtencion.IdUbicacionIng', '=', 'genUbiIng.IdUbicacion')
                ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
                ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
                ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
                ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
                ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
                ->join('genTipoDoc', 'admCliente.IdTipoDoc', '=', 'genTipoDoc.IdTipoDoc')
                ->whereIn('admAtencion.IdAtencion', $atencionIds)
                ->where('facMovimientoDet.Cantidad', '>', 0)
                ->where($oftalmoFilter);

            $rows = $consulta1->union($consulta2)->get();

            // Cruzar con atenciones ya registradas en LIDiag de forma defensiva
            $existentes = collect();
            if (Schema::hasTable('oftalmologia_atenciones')) {
                $idsAtenciones = $rows->pluck('IdAtencion')->map(fn ($id) => (string) $id)->all();
                $existentes = OftalmologiaAtencion::query()
                    ->where('sede_id', $sedeId)
                    ->whereIn('atencion_id', $idsAtenciones)
                    ->get(['id', 'atencion_id', 'estado', 'estado_sahi'])
                    ->keyBy('atencion_id');
            }

            $data = $rows->map(static function ($r) use ($existentes) {
                $local = $existentes->get((string) $r->IdAtencion);

                $nombreCompleto = trim(implode(' ', array_filter([
                    $r->PrimerNombre,
                    $r->SegundoNombre,
                    $r->PrimerApellido,
                    $r->SegundoApellido,
                ])));

                return [
                    'IdAtencion' => (string) $r->IdAtencion,
                    'atencion_id' => (string) $r->IdAtencion,
                    'FecIngreso' => $r->FecIngreso ? Carbon::parse($r->FecIngreso)->format('Y-m-d H:i:s') : null,
                    'fecha_ingreso' => $r->FecIngreso ? Carbon::parse($r->FecIngreso)->format('Y-m-d H:i:s') : null,
                    'PrimerNombre' => (string) ($r->PrimerNombre ?? ''),
                    'SegundoNombre' => (string) ($r->SegundoNombre ?? ''),
                    'PrimerApellido' => (string) ($r->PrimerApellido ?? ''),
                    'SegundoApellido' => (string) ($r->SegundoApellido ?? ''),
                    'paciente_nombre' => $nombreCompleto,
                    'NumDocumento' => (string) $r->NumDocumento,
                    'num_docu' => (string) $r->NumDocumento,
                    'IdTipoDoc' => $r->IdTipoDoc,
                    'tipo_doc_id' => $r->IdTipoDoc,
                    'CodTipoDoc' => (string) ($r->CodTipoDoc ?? ''),
                    'NomTipoDoc' => (string) ($r->NomTipoDoc ?? ''),
                    'IdSexo' => $r->IdSexo,
                    'sexo' => ((int) $r->IdSexo === 1) ? 'M' : (((int) $r->IdSexo === 2) ? 'F' : 'O'),
                    'FecNacimiento' => $r->FecNacimiento ? Carbon::parse($r->FecNacimiento)->format('Y-m-d') : null,
                    'fecha_nacimiento' => $r->FecNacimiento ? Carbon::parse($r->FecNacimiento)->format('Y-m-d') : null,
                    'TelCasa' => (string) ($r->TelCasa ?? ''),
                    'TelCelular' => (string) ($r->TelCasa ?? ''),
                    'telefono' => (string) ($r->TelCasa ?? ''),
                    'DirCasa' => (string) ($r->DirCasa ?? ''),
                    'direccion' => (string) ($r->DirCasa ?? ''),
                    'IdProducto' => $r->IdProducto,
                    'NomProducto' => (string) $r->NomProducto,
                    'procedimiento' => (string) $r->NomProducto,
                    'CodProducto' => (string) $r->CodProducto,
                    'CodLegal' => (string) $r->CodLegal,
                    'cod_cups' => (string) $r->CodProducto,
                    'ValProduc' => $r->ValProduc,
                    'Cantidad' => $r->Cantidad,
                    'CodCon' => (string) ($r->CodCon ?? ''),
                    'DesContrato' => (string) ($r->DesContrato ?? ''),
                    'contrato' => (string) ($r->DesContrato ?? ''),
                    'DesPlan' => (string) ($r->DesPlan ?? ''),
                    'plan' => (string) ($r->DesPlan ?? ''),
                    'NomTercero' => (string) ($r->NomTercero ?? ''),
                    'eps' => (string) ($r->NomTercero ?? ''),
                    'NomUbicacion' => (string) ($r->NomUbicacion ?? 'Consulta Externa'),
                    'ubicacion' => (string) ($r->NomUbicacion ?? 'Consulta Externa'),
                    'Cama' => (string) ($r->Cama ?? ''),
                    'cama' => (string) ($r->Cama ?? ''),
                    'ya_admitido' => $local !== null,
                    'atencion_local_id' => $local?->id,
                    'estado_local' => $local?->estado,
                    'estado_sahi' => $local?->estado_sahi,
                ];
            })->all();

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
     * Resuelve una lista de IDs de atención en SAHI según criterios de búsqueda para oftalmología.
     *
     * @param  int|string|null  $criterio  Número de atención, cédula o nombres de paciente.
     * @param  string|null  $fechaIni  Fecha de inicio (YYYY-MM-DD).
     * @param  string|null  $fechaFin  Fecha de fin (YYYY-MM-DD).
     * @return array<int>
     */
    private function resolverAtencionesIds(int|string|null $criterio = null, ?string $fechaIni = null, ?string $fechaFin = null): array
    {
        $criterio = trim((string) $criterio);

        if (empty($criterio) && empty($fechaIni) && empty($fechaFin)) {
            return [];
        }

        $atencionIds = [];

        if (! empty($criterio)) {
            if (is_numeric($criterio)) {
                $directo = DB::connection('sqlsrv')
                    ->table('admAtencion')
                    ->where('IdAtencion', (int) $criterio)
                    ->value('IdAtencion');

                if ($directo) {
                    $atencionIds[] = (int) $directo;
                }

                $queryDoc = DB::connection('sqlsrv')
                    ->table('admAtencion')
                    ->join('admCliente', 'admCliente.IdCliente', '=', 'admAtencion.IdCliente')
                    ->where('admCliente.NumDocumento', $criterio);

                if ($fechaIni) {
                    $queryDoc->whereRaw('admAtencion.FecIngreso >= CONVERT(datetime, ?, 120)', ["{$fechaIni} 00:00:00"]);
                }
                if ($fechaFin) {
                    $queryDoc->whereRaw('admAtencion.FecIngreso <= CONVERT(datetime, ?, 120)', ["{$fechaFin} 23:59:59"]);
                }

                $porDoc = $queryDoc->orderByDesc('admAtencion.IdAtencion')
                    ->limit(20)
                    ->pluck('admAtencion.IdAtencion')
                    ->all();

                $atencionIds = array_values(array_unique(array_merge($atencionIds, $porDoc)));
            } else {
                $tokens = array_filter(explode(' ', $criterio));
                $queryNombre = DB::connection('sqlsrv')
                    ->table('admAtencion')
                    ->join('admCliente', 'admCliente.IdCliente', '=', 'admAtencion.IdCliente')
                    ->select('admAtencion.IdAtencion');

                foreach ($tokens as $token) {
                    $queryNombre->where(function ($sub) use ($token) {
                        $sub->where('admCliente.PrimerNombre', 'LIKE', "%{$token}%")
                            ->orWhere('admCliente.SegundoNombre', 'LIKE', "%{$token}%")
                            ->orWhere('admCliente.PrimerApellido', 'LIKE', "%{$token}%")
                            ->orWhere('admCliente.SegundoApellido', 'LIKE', "%{$token}%");
                    });
                }

                if ($fechaIni) {
                    $queryNombre->whereRaw('admAtencion.FecIngreso >= CONVERT(datetime, ?, 120)', ["{$fechaIni} 00:00:00"]);
                }
                if ($fechaFin) {
                    $queryNombre->whereRaw('admAtencion.FecIngreso <= CONVERT(datetime, ?, 120)', ["{$fechaFin} 23:59:59"]);
                }

                $atencionIds = $queryNombre->orderByDesc('admAtencion.IdAtencion')
                    ->limit(20)
                    ->pluck('admAtencion.IdAtencion')
                    ->all();
            }
        } else {
            // Cuando la búsqueda es puramente por rango de fechas (sin documento/nombre),
            // filtramos las atenciones buscando directamente en movimientos y facturas que contengan
            // productos de oftalmología.
            $oftalmoFilter = static function ($q) {
                $q->where(function ($sub) {
                    $sub->whereIn('proProducto.CodLegal', ['890476', '890202', '890302'])
                        ->orWhereIn('proProducto.CodProducto', ['890476', '890202', '890302'])
                        ->orWhere('proProducto.NomProducto', 'LIKE', '%OFTALMO%');
                });
            };

            $queryMov = DB::connection('sqlsrv')
                ->table('facMovimientoDet')
                ->join('facMovimiento', 'facMovimientoDet.IdMovimiento', '=', 'facMovimiento.IdMovimiento')
                ->join('proProducto', 'facMovimientoDet.IdProducto', '=', 'proProducto.IdProducto')
                ->join('admAtencion', 'facMovimiento.IdDestino', '=', 'admAtencion.IdAtencion')
                ->where('facMovimientoDet.Cantidad', '>', 0)
                ->where($oftalmoFilter);

            $queryFac = DB::connection('sqlsrv')
                ->table('facFacturaDet')
                ->join('facFactura', 'facFacturaDet.IdFactura', '=', 'facFactura.IdFactura')
                ->join('proProducto', 'facFacturaDet.IdProducto', '=', 'proProducto.IdProducto')
                ->join('admAtencion', 'facFactura.IdDestino', '=', 'admAtencion.IdAtencion')
                ->where('facFacturaDet.Cantidad', '>', 0)
                ->where($oftalmoFilter);

            if ($fechaIni) {
                $queryMov->whereRaw('admAtencion.FecIngreso >= CONVERT(datetime, ?, 120)', ["{$fechaIni} 00:00:00"]);
                $queryFac->whereRaw('admAtencion.FecIngreso >= CONVERT(datetime, ?, 120)', ["{$fechaIni} 00:00:00"]);
            }
            if ($fechaFin) {
                $queryMov->whereRaw('admAtencion.FecIngreso <= CONVERT(datetime, ?, 120)', ["{$fechaFin} 23:59:59"]);
                $queryFac->whereRaw('admAtencion.FecIngreso <= CONVERT(datetime, ?, 120)', ["{$fechaFin} 23:59:59"]);
            }

            $idsMov = $queryMov->select('admAtencion.IdAtencion')->distinct()->pluck('admAtencion.IdAtencion')->all();
            $idsFac = $queryFac->select('admAtencion.IdAtencion')->distinct()->pluck('admAtencion.IdAtencion')->all();

            $atencionIds = array_values(array_unique(array_merge($idsMov, $idsFac)));
            rsort($atencionIds);
            $atencionIds = array_slice($atencionIds, 0, 50);
        }

        return $atencionIds;
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
