<?php

namespace App\Services\Interfaces;

use App\Models\Ecografias\Ecografia;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\FacFactura;
use App\Models\Interface\Sahi\facFacturaDet;
use App\Models\Interface\Sahi\FacMovimiento;
use App\Models\Interface\Sahi\FacMovimientoDet;
use App\Models\Interface\Sahi\HceConsulta;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class SAHIService extends BaseService
{
    public function listarAtenciones(?string $numDocu): array
    {
        if (empty($numDocu)) {
            return $this->error('El número de documento es obligatorio.', 422);
        }

        $atenciones = Atencion::select('admAtencion.IdAtencion', 'admAtencion.FecIngreso')
            ->join('admCliente', 'admCliente.IdCliente', 'admAtencion.IdCliente')
            ->where('admCliente.NumDocumento', $numDocu)
            ->limit(20)
            ->orderBy('admAtencion.IdAtencion', 'DESC')
            ->get();

        return $this->success($atenciones);
    }

    public function listarProductosDiagnosticos(?int $idAtencion): array
    {
        if (! $idAtencion) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

        $productos = FacMovimiento::select('proProducto.IdProducto', 'proProducto.CodProducto as cod_cups', 'proProducto.NomProducto as nom_produc', 'facMovimientoDet.Cantidad')
            ->join('facMovimientoDet', 'facMovimientoDet.IdMovimiento', 'facMovimiento.IdMovimiento')
            ->join('proProducto', 'facMovimientoDet.IdProducto', 'proProducto.IdProducto')
            ->where('facMovimiento.IdDestino', $idAtencion)
            ->where('facMovimientoDet.Cantidad', '>', 0)
            ->whereIn('proProducto.IdGrupo', [44, 190, 225, 243])
            ->distinct()
            ->get();

        // Explicitly select columns with aliases matching frontend expectations
        $diagnosticos = HceConsulta::select(
            'dx.IdDiagnostico',
            'dx.CodCie9 as cod_diagnos',
            'dx.NomDiagnostico as nom_diagnos'
        )
            ->distinct('hceConsulta.IdConsulta')
            ->join('hceConsulDiagnostico AS cdx', 'hceConsulta.IdConsulta', 'cdx.IdConsulta')
            ->join('genDiagnostico AS dx', 'cdx.IdDiagnostico', 'dx.IdDiagnostico')
            ->where('hceConsulta.IdAtencion', $idAtencion)
            ->get();

        // Explicitly select the enfermedad actual column with the alias frontend expects
        $consultas = HceConsulta::select(
            'hceConsulta.IdConsulta',
            'hceConsulta.IdAtencion',
            'hceConsulta.DesEnfermedadActual',
            'hceConsulta.FecConsulta'
        )
            ->where('hceConsulta.IdAtencion', $idAtencion)
            ->limit(2)
            ->get();

        return $this->success(['productos' => $productos, 'diagnosticos' => $diagnosticos, 'consultas' => $consultas]);
    }

    public function buscarEpsPlanContrato(?int $idAtencion): array
    {
        if (! $idAtencion) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

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

        return $this->success($datos);
    }

    public function listarProductosAtencion($atencionId): array
    {
        $atencionId = (int) $atencionId;

        if ($atencionId <= 0) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

        $gruposPermitidos = [44, 195, 156, 190, 204];

        $consulta1 = facFacturaDet::select($this->buildAtencionSelect('facFacturaDet.ValTotal'))
            ->join('facFactura', 'facFacturaDet.IdFactura', '=', 'facFactura.IdFactura')
            ->join('proProducto', 'facFacturaDet.IdProducto', '=', 'proProducto.IdProducto')
            ->join('admAtencion', 'facFactura.IdDestino', '=', 'admAtencion.IdAtencion')
            ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
            ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
            ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
            ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
            ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
            ->where('admAtencion.IdAtencion', $atencionId)
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $consulta2 = FacMovimientoDet::select($this->buildAtencionSelect('facMovimientoDet.ValVenta'))
            ->join('facMovimiento', 'facMovimientoDet.IdMovimiento', '=', 'facMovimiento.IdMovimiento')
            ->join('proProducto', 'facMovimientoDet.IdProducto', '=', 'proProducto.IdProducto')
            ->join('admAtencion', 'facMovimiento.IdDestino', '=', 'admAtencion.IdAtencion')
            ->join('admCliente', 'admAtencion.IdCliente', '=', 'admCliente.IdCliente')
            ->join('AdmAtencionContrato', 'admAtencion.IdAtencion', '=', 'AdmAtencionContrato.IdAtencion')
            ->join('conContrato', 'AdmAtencionContrato.IdContrato', '=', 'conContrato.IdContrato')
            ->join('conPlan', 'AdmAtencionContrato.IdPlan', '=', 'conPlan.IdPlan')
            ->join('genTercero', 'conContrato.IdTercero', '=', 'genTercero.IdTercero')
            ->where('admAtencion.IdAtencion', $atencionId)
            ->whereIn('proProducto.IdGrupo', $gruposPermitidos);

        $atencionesSahi = $consulta1->union($consulta2)->get();

        $atencionesLidiag = $this->obtenerDatosLidiag($atencionId);

        $combinarAtenciones = [];
        foreach ($atencionesSahi as $atencion) {
            $matchAtencion = $atencionesLidiag->firstWhere('atencion_id', $atencion->IdAtencion);
            $atencion->estado_ecografia = $matchAtencion?->estado;

            $combinarAtenciones[] = $atencion;
        }

        return $this->success($combinarAtenciones);
    }

    private function buildAtencionSelect(string $valProducField): array
    {
        return [
            'admAtencion.IdAtencion',
            DB::raw("CONVERT(char(10), admAtencion.FecIngreso, 120) AS FecIngreso"),
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
            'admCliente.IdTipoDoc',
            DB::raw("{$valProducField} AS 'ValProduc'"),
            'conContrato.CodCon',
            'conContrato.DesContrato',
            'conPlan.DesPlan',
            'genTercero.NomTercero',
            DB::raw("(SELECT TOP(1) QuinOrdenoOrden.NomResponsable
                    FROM hceProceOrden QuinOrdenoOrden
                        INNER JOIN hceProceDetalle ON QuinOrdenoOrden.IdOrdenPro = hceProceDetalle.IdOrdenPro
                        INNER JOIN proProducto PoducOrden ON hceProceDetalle.IdProducto = PoducOrden.IdProducto
                    WHERE QuinOrdenoOrden.IdAtencion = admAtencion.IdAtencion AND PoducOrden.CodProducto = proProducto.CodProducto
                ) as quien_ordeno"),
        ];
    }

    private function obtenerDatosLidiag(int $atencionId)
    {
        return Ecografia::select('estado')
            ->where('atencion_id', $atencionId)->get();
    }
}
