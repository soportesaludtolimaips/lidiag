<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\FacFactura;
use App\Models\Interface\Sahi\FacMovimiento;
use App\Models\Interface\Sahi\HceConsulta;
use Illuminate\Http\Request;

class SAHIController extends Controller
{
    /**
     * Listas las atenciones de un paciente
     */
    public function listarAtenciones(Request $request)
    {
        $atenciones = Atencion::select('admAtencion.IdAtencion', 'admAtencion.FecIngreso')
            ->join('admCliente', 'admCliente.IdCliente', 'admAtencion.IdCliente')
            ->where('admCliente.NumDocumento', $request->numDocu)
            ->orderByDesc('admAtencion.IdAtencion')
            ->limit(20)
            ->orderBy('admAtencion.IdAtencion', 'DESC')
            ->get();

        return response()->json($atenciones);
    }

    public function listarProductosDiagnosticos(Request $request)
    {
        $productos = FacMovimiento::select('proProducto.IdProducto', 'proProducto.CodProducto as cod_cups', 'proProducto.NomProducto as nom_produc', 'facMovimientoDet.Cantidad')
            ->join('facMovimientoDet', 'facMovimientoDet.IdMovimiento', 'facMovimiento.IdMovimiento')
            ->join('proProducto', 'facMovimientoDet.IdProducto', 'proProducto.IdProducto')
            ->where('facMovimiento.IdDestino', $request->idAtencion)
            ->whereIn('proProducto.IdGrupo', [44, 190, 225, 243])
            ->get();

        $diagnosticos = HceConsulta::select('dx.IdDiagnostico', 'dx.CodCie9 as cod_diagnos', 'dx.NomDiagnostico as nom_diagnos')
            ->distinct('hceConsulta.IdConsulta')
            ->join('hceConsulDiagnostico AS cdx', 'hceConsulta.IdConsulta', 'cdx.IdConsulta')
            ->join('genDiagnostico AS dx', 'cdx.IdDiagnostico', 'dx.IdDiagnostico')
            ->where('hceConsulta.IdAtencion', $request->idAtencion)
            ->get();

        return response()->json(['productos' => $productos, 'diagnosticos' => $diagnosticos]);
    }

    public function buscarEpsPlanContrato($idAtencion)
    {
        $datos = FacFactura::select(
            'facFactura.IdFactura',
            'genTercero.NomTercero',
            'conContrato.CodCon',
            'conPlan.IdPlan',
            'conPlan.DesPlan',
            'conPlan.CodLegal',
            'conContrato.DesContrato',
            'genUbicacion.NomUbicacion'
        )
            ->join('admAtencion', 'facFactura.IdDestino', 'admAtencion.IdAtencion')
            ->join('genUbicacion', 'admAtencion.IdUbicacionIng', 'genUbicacion.IdUbicacion')
            ->join('conPlan', 'facFactura.IdPlan', 'conPlan.IdPlan')
            ->join('conContrato', 'facFactura.IdContrato', 'conContrato.IdContrato')
            ->join('genTercero', 'conPlan.IdTercero', 'genTercero.IdTercero')
            ->where('facFactura.IdDestino', $idAtencion)
            ->orderByDesc('facFactura.IdFactura')
            ->limit(1)
            ->get();

        return $datos;
    }
}
