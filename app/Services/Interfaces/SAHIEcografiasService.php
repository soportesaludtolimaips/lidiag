<?php

namespace App\Services\Interfaces;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\EcografiaConsentimiento;
use App\Models\Interface\Sahi\facFacturaDet;
use App\Models\Interface\Sahi\FacMovimientoDet;
use App\Models\Worklist\WorklistOrden;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class SAHIEcografiasService extends BaseService
{
    /**
     * Lista y enriquece los productos de atención de SAHI para admisión de ecografías.
     * Soporta búsqueda por número de atención, número de documento, nombres de paciente
     * o rango de fechas (FecIngreso).
     *
     * Flujo del proceso:
     * 1. Resuelve los IDs de atención de SAHI según los criterios de búsqueda (atención, documento, nombres o fechas).
     * 2. Si no se encuentran atenciones o no hay criterios válidos, retorna una lista vacía exitosa.
     * 3. Consulta facturas (facFacturaDet) y órdenes/cargos (facMovimientoDet) de SAHI para las atenciones filtrando por grupos de ecografía/diagnóstico.
     * 4. Mapea el médico que ordenó el procedimiento desde hceProceOrden agrupado por atención.
     * 5. Cruza con los catálogos y admisiones existentes en LIDiag (ecografias, ecografias_productos, consentimientos y worklist).
     * 6. Retorna la colección estructurada y enriquecida para el módulo de admisión de ecografías.
     *
     * @param  int|string|null  $criterio  ID de atención, número de documento o nombre del paciente.
     * @param  string|null  $fechaIni  Fecha de inicio en formato YYYY-MM-DD.
     * @param  string|null  $fechaFin  Fecha de fin en formato YYYY-MM-DD.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarProductosAtencion(int|string|null $criterio = null, ?string $fechaIni = null, ?string $fechaFin = null): array
    {
        $atencionIds = $this->resolverAtencionesIds($criterio, $fechaIni, $fechaFin);

        if (empty($atencionIds)) {
            return $this->success([]);
        }

        $gruposPermitidos = [44, 195, 156, 190, 204, 191];

        $quienOrdenoMap = DB::connection('sqlsrv')
            ->table('hceProceOrden')
            ->whereIn('IdAtencion', $atencionIds)
            ->orderByDesc('IdOrdenPro')
            ->get(['IdAtencion', 'NomResponsable'])
            ->groupBy('IdAtencion')
            ->map(fn ($group) => $group->first()->NomResponsable)
            ->all();

        $consulta1 = facFacturaDet::select([
            'admAtencion.IdAtencion',
            DB::raw("CONVERT(char(10), admAtencion.FecIngreso, 120) AS FecIngreso"),
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
            DB::raw("facFacturaDet.ValTotal AS 'ValProduc'"),
            'facFacturaDet.Cantidad',
            'conContrato.CodCon',
            'conContrato.DesContrato',
            'conPlan.DesPlan',
            'genTercero.NomTercero',
        ])
            ->join('facFactura', 'facFacturaDet.IdFactura', '=', 'facFactura.IdFactura')
            ->join('proProducto', 'facFacturaDet.IdProducto', '=', 'proProducto.IdProducto')
            ->join('admAtencion', 'facFactura.IdDestino', '=', 'admAtencion.IdAtencion')
            ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
            ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
            ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
            ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
            ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
            ->join('genTipoDoc', 'admCliente.IdTipoDoc', '=', 'genTipoDoc.IdTipoDoc')
            ->whereIn('admAtencion.IdAtencion', $atencionIds)
            ->where('facFacturaDet.Cantidad', '>', 0)
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $consulta2 = FacMovimientoDet::select([
            'admAtencion.IdAtencion',
            DB::raw("CONVERT(char(10), admAtencion.FecIngreso, 120) AS FecIngreso"),
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
            DB::raw("facMovimientoDet.ValVenta AS 'ValProduc'"),
            'facMovimientoDet.Cantidad',
            'conContrato.CodCon',
            'conContrato.DesContrato',
            'conPlan.DesPlan',
            'genTercero.NomTercero',
        ])
            ->join('facMovimiento', 'facMovimientoDet.IdMovimiento', '=', 'facMovimiento.IdMovimiento')
            ->join('proProducto', 'facMovimientoDet.IdProducto', '=', 'proProducto.IdProducto')
            ->join('admAtencion', 'facMovimiento.IdDestino', '=', 'admAtencion.IdAtencion')
            ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
            ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
            ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
            ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
            ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
            ->join('genTipoDoc', 'admCliente.IdTipoDoc', '=', 'genTipoDoc.IdTipoDoc')
            ->whereIn('admAtencion.IdAtencion', $atencionIds)
            ->where('facMovimientoDet.Cantidad', '>', 0)
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $atencionesSahi = $consulta1->union($consulta2)->get();

        $atencionesSahi->transform(function ($row) use ($quienOrdenoMap) {
            $row->quien_ordeno = $quienOrdenoMap[$row->IdAtencion] ?? null;

            return $row;
        });

        $moduloEcografiasId = 2;
        $cods = $atencionesSahi
            ->map(fn ($r) => trim((string) ($r->CodProducto ?? '')))
            ->filter()
            ->unique()
            ->values();

        $configProductos = ConfigProducto::query()
            ->select(['id', 'cod_cups'])
            ->whereIn('cod_cups', $cods->all())
            ->get()
            ->keyBy('cod_cups');

        $productoIds = $configProductos->pluck('id')->values();

        $plantillasVigentes = ConsentimientoPlantilla::query()
            ->select(['id', 'producto_id'])
            ->where('config_modulo_id', $moduloEcografiasId)
            ->where('vigente', true)
            ->whereIn('producto_id', $productoIds->all())
            ->get()
            ->keyBy('producto_id');

        $local = DB::table('ecografias')
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->whereIn('ecografias.atencion_id', $atencionIds)
            ->select([
                'ecografias.id as ecografia_id',
                'ecografias.atencion_id',
                'ecografias.estado as estado_ecografia',
                'ep.producto_id',
                'ep.id as ecografia_producto_id',
                'ep.estado_envio_sahi',
                'ep.fechor_envio_sahi',
                'ep.error_envio_sahi',
                'ep.sahi_esquema_149',
                'ep.sahi_esquema_150',
            ])
            ->orderBy('ecografias.id')
            ->get()
            ->keyBy(fn ($item) => "{$item->atencion_id}_{$item->producto_id}");

        $ecografiaProductoIds = $local->pluck('ecografia_producto_id')->filter()->values();
        $consentimientos = EcografiaConsentimiento::query()
            ->select(['id', 'ecografia_producto_id', 'estado'])
            ->where('vigente', true)
            ->whereIn('ecografia_producto_id', $ecografiaProductoIds->all())
            ->get()
            ->keyBy('ecografia_producto_id');

        $ordenesWorklist = [];
        if ($ecografiaProductoIds->isNotEmpty()) {
            $ordenesWorklist = WorklistOrden::whereIn('ecografia_producto_id', $ecografiaProductoIds->all())
                ->whereIn('estado', ['PENDIENTE', 'ADQUIRIDO', 'COMPLETADO'])
                ->orderBy('id', 'desc')
                ->get()
                ->keyBy('ecografia_producto_id');
        }

        $atencionesSahi = $atencionesSahi->map(function ($row) use ($local, $configProductos, $plantillasVigentes, $consentimientos, $ordenesWorklist) {
            $productoId = (int) ($row->IdProducto ?? 0);
            $claveLocal = "{$row->IdAtencion}_{$productoId}";
            $match = $productoId && isset($local[$claveLocal]) ? $local[$claveLocal] : null;

            $row->id = $match?->ecografia_id ?? null;
            $row->estado_ecografia = $match?->estado_ecografia ?? null;

            $row->ecografia_producto_id = $match?->ecografia_producto_id ?? null;
            $row->estado_envio_sahi = $match?->estado_envio_sahi ?? null;
            $row->fechor_envio_sahi = $match?->fechor_envio_sahi ?? null;
            $row->error_envio_sahi = $match?->error_envio_sahi ?? null;
            $row->sahi_esquema_149 = $match?->sahi_esquema_149 ?? null;
            $row->sahi_esquema_150 = $match?->sahi_esquema_150 ?? null;

            $row->orden_worklist = ($row->ecografia_producto_id && isset($ordenesWorklist[$row->ecografia_producto_id]))
                ? $ordenesWorklist[$row->ecografia_producto_id]
                : null;

            $cod = trim((string) ($row->CodProducto ?? ''));
            $cfg = $cod && isset($configProductos[$cod]) ? $configProductos[$cod] : null;
            $cfgId = $cfg?->id ? (int) $cfg->id : null;

            $row->config_producto_id = $cfgId;
            $row->tiene_plantilla_consentimiento = $cfgId ? isset($plantillasVigentes[$cfgId]) : false;

            $row->consentimiento_id = null;
            $row->consentimiento_estado = null;
            $row->consentimiento_bloquea = false;

            $epId = $row->ecografia_producto_id ? (int) $row->ecografia_producto_id : null;
            if ($epId && isset($consentimientos[$epId])) {
                $c = $consentimientos[$epId];
                $row->consentimiento_id = (int) $c->id;
                $row->consentimiento_estado = (string) $c->estado;
                $row->consentimiento_bloquea = $row->tiene_plantilla_consentimiento && $c->estado === 'PENDIENTE_PACIENTE';
            }

            return $row;
        });

        return $this->success($atencionesSahi);
    }

    /**
     * Resuelve una lista de IDs de atención en SAHI según criterios de búsqueda.
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
                    $queryDoc->whereRaw('CONVERT(date, admAtencion.FecIngreso) >= ?', [$fechaIni]);
                }
                if ($fechaFin) {
                    $queryDoc->whereRaw('CONVERT(date, admAtencion.FecIngreso) <= ?', [$fechaFin]);
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
                    $queryNombre->whereRaw('CONVERT(date, admAtencion.FecIngreso) >= ?', [$fechaIni]);
                }
                if ($fechaFin) {
                    $queryNombre->whereRaw('CONVERT(date, admAtencion.FecIngreso) <= ?', [$fechaFin]);
                }

                $atencionIds = $queryNombre->orderByDesc('admAtencion.IdAtencion')
                    ->limit(20)
                    ->pluck('admAtencion.IdAtencion')
                    ->all();
            }
        } else {
            $queryFechas = DB::connection('sqlsrv')
                ->table('admAtencion')
                ->select('admAtencion.IdAtencion');

            if ($fechaIni) {
                $queryFechas->whereRaw('CONVERT(date, admAtencion.FecIngreso) >= ?', [$fechaIni]);
            }
            if ($fechaFin) {
                $queryFechas->whereRaw('CONVERT(date, admAtencion.FecIngreso) <= ?', [$fechaFin]);
            }

            $atencionIds = $queryFechas->orderByDesc('admAtencion.IdAtencion')
                ->limit(30)
                ->pluck('admAtencion.IdAtencion')
                ->all();
        }

        return $atencionIds;
    }
}
