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
     * Listar los productos de una atencion junto con el estado de ecografia en Lidiag
     */
    public function listarProductosAtencion(Request $request)
    {
        $atencionId = $request->bus_nom_num_docu;
        $gruposPermitidos = [44, 195, 156, 190, 204];

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
            'conContrato.CodCon',
            'conContrato.DesContrato',
            'conPlan.DesPlan',
            'genTercero.NomTercero',
            DB::raw("(SELECT TOP(1) QuinOrdenoOrden.NomResponsable
                    FROM hceProceOrden QuinOrdenoOrden
                        INNER JOIN hceProceDetalle ON QuinOrdenoOrden.IdOrdenPro = hceProceDetalle.IdOrdenPro
                        INNER JOIN proProducto PoducOrden ON hceProceDetalle.IdProducto = PoducOrden.IdProducto
                    WHERE QuinOrdenoOrden.IdAtencion = admAtencion.IdAtencion AND PoducOrden.CodProducto = proProducto.CodProducto
                ) as quien_ordeno")
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
            'conContrato.CodCon',
            'conContrato.DesContrato',
            'conPlan.DesPlan',
            'genTercero.NomTercero',
            DB::raw("(SELECT TOP(1) QuinOrdenoOrden.NomResponsable
                    FROM hceProceOrden QuinOrdenoOrden
                        INNER JOIN hceProceDetalle ON QuinOrdenoOrden.IdOrdenPro = hceProceDetalle.IdOrdenPro
                        INNER JOIN proProducto PoducOrden ON hceProceDetalle.IdProducto = PoducOrden.IdProducto
                    WHERE QuinOrdenoOrden.IdAtencion = admAtencion.IdAtencion AND PoducOrden.CodProducto = proProducto.CodProducto
                ) as quien_ordeno")
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
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $atencionesSahi = $consulta1->union($consulta2)->get();

        return response()->json($atencionesSahi, 200);
    }
}
