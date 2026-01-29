<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Models\Ecografias\Ecografia;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\FacFactura;
use App\Models\Interface\Sahi\facFacturaDet;
use App\Models\Interface\Sahi\FacMovimiento;
use App\Models\Interface\Sahi\FacMovimientoDet;
use App\Models\Interface\Sahi\HceConsulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Contracts\Service\Attribute\Required;

class SAHIEcografiasController extends Controller
{
    /**
     * Lista los productos de ecografía de una atención, combinando fuentes de facturación y movimientos.
     *
     * Similar a `SAHIController@listarProductosAtencion`, pero filtra específicamente por grupos
     * de productos relacionados con ecografías y devuelve la lista unificada.
     *
     * @param Request $request ID de la atención (`bus_nom_num_docu`).
     * @return \Illuminate\Http\JsonResponse Lista de productos de ecografía.
     */
    public function listarProductosAtencion(Request $request)
    {
        $atencionId = $request->bus_nom_num_docu;
        $gruposPermitidos = [44, 195, 156, 190, 204, 191];

        // `quien_ordeno` es solo texto informativo: lo resolvemos una vez por atención
        // (evita subconsulta correlacionada por fila).
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

        // Agregar texto de quien ordenó (si existe) para todos los ítems.
        if ($quienOrdeno) {
            $atencionesSahi->transform(function ($row) use ($quienOrdeno) {
                $row->quien_ordeno = $quienOrdeno;
                return $row;
            });
        }

        // Enriquecer con estado en LIDiag (si la atención/producto ya fue admitida)
        // Nota: ecografias.atencion_id (string) debe corresponder al IdAtencion SAHI.
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
            // determinístico: si existen múltiples ecografías por (atención, producto),
            // nos quedamos con la de mayor id (más reciente)
            ->orderBy('ecografias.id')
            ->get()
            ->keyBy('producto_id');

        $atencionesSahi = $atencionesSahi->map(function ($row) use ($local) {
            $productoId = (int) ($row->IdProducto ?? 0);
            $match = $productoId && isset($local[$productoId]) ? $local[$productoId] : null;

            // Campos que el frontend ya usa
            $row->id = $match?->ecografia_id ?? null; // usado por anulación
            $row->estado_ecografia = $match?->estado_ecografia ?? null;

            // Campos SAHI (estado de envío) para consistencia UI
            $row->ecografia_producto_id = $match?->ecografia_producto_id ?? null;
            $row->estado_envio_sahi = $match?->estado_envio_sahi ?? null;
            $row->fechor_envio_sahi = $match?->fechor_envio_sahi ?? null;
            $row->error_envio_sahi = $match?->error_envio_sahi ?? null;
            $row->sahi_esquema_149 = $match?->sahi_esquema_149 ?? null;
            $row->sahi_esquema_150 = $match?->sahi_esquema_150 ?? null;

            return $row;
        });

        return response()->json($atencionesSahi, 200);
    }
}
