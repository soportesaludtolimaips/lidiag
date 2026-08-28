<?php

namespace App\Services\Interfaces;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\EcografiaConsentimiento;
use App\Models\Interface\Sahi\facFacturaDet;
use App\Models\Interface\Sahi\FacMovimientoDet;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class SAHIEcografiasService extends BaseService
{
    /**
     * Lista y enriquece los productos de una atención de SAHI para admisión de ecografías.
     *
     * @param  int  $atencionId  Identificador de la atención en SAHI.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarProductosAtencion(int $atencionId): array
    {
        $gruposPermitidos = [44, 195, 156, 190, 204, 191];

        $quienOrdeno = DB::connection('sqlsrv')
            ->table('hceProceOrden')
            ->where('IdAtencion', $atencionId)
            ->orderByDesc('IdOrdenPro')
            ->value('NomResponsable');

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
            ->where('admAtencion.IdAtencion', $atencionId)
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
            ->where('admAtencion.IdAtencion', $atencionId)
            ->where('facMovimientoDet.Cantidad', '>', 0)
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $atencionesSahi = $consulta1->union($consulta2)->get();

        if ($quienOrdeno) {
            $atencionesSahi->transform(function ($row) use ($quienOrdeno) {
                $row->quien_ordeno = $quienOrdeno;

                return $row;
            });
        }

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
            ->where('ecografias.atencion_id', $atencionId)
            ->select([
                'ecografias.id as ecografia_id',
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
            ->keyBy('producto_id');

        $ecografiaProductoIds = $local->pluck('ecografia_producto_id')->filter()->values();
        $consentimientos = EcografiaConsentimiento::query()
            ->select(['id', 'ecografia_producto_id', 'estado'])
            ->where('vigente', true)
            ->whereIn('ecografia_producto_id', $ecografiaProductoIds->all())
            ->get()
            ->keyBy('ecografia_producto_id');

        $atencionesSahi = $atencionesSahi->map(function ($row) use ($local, $configProductos, $plantillasVigentes, $consentimientos) {
            $productoId = (int) ($row->IdProducto ?? 0);
            $match = $productoId && isset($local[$productoId]) ? $local[$productoId] : null;

            $row->id = $match?->ecografia_id ?? null;
            $row->estado_ecografia = $match?->estado_ecografia ?? null;

            $row->ecografia_producto_id = $match?->ecografia_producto_id ?? null;
            $row->estado_envio_sahi = $match?->estado_envio_sahi ?? null;
            $row->fechor_envio_sahi = $match?->fechor_envio_sahi ?? null;
            $row->error_envio_sahi = $match?->error_envio_sahi ?? null;
            $row->sahi_esquema_149 = $match?->sahi_esquema_149 ?? null;
            $row->sahi_esquema_150 = $match?->sahi_esquema_150 ?? null;

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

        $atencionesSahi = $atencionesSahi->map(function ($item) {
            $item->num_docu = maskData($item->num_docu ?? '', 'document');
            $item->direccion = maskData($item->direccion ?? '', 'address');
            $item->tel = maskData($item->tel ?? '', 'phone');
            $item->cel = maskData($item->cel ?? '', 'phone');
            $item->email = maskData($item->email ?? '', 'email');
            $item->fec_naci = maskData($item->fec_naci ?? '', 'dob');

            return $item;
        });

        return $this->success($atencionesSahi);
    }
}
