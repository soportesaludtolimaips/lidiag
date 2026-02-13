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

class SAHIController extends Controller
{
    public function __construct()
    {
        // Estas rutas se exponen por /api (ver routes/api_interfaces.php)
        $this->middleware('auth:sanctum');
        $this->middleware('permission:estudio.asignar,web')->only(['listarAtenciones', 'listarProductosDiagnosticos']);
    }

    /**
     * Lista las atenciones (ingresos) de un paciente.
     *
     * @param  Request  $request  Número de documento del paciente (`numDocu`).
     * @return \Illuminate\Http\JsonResponse Lista de atenciones.
     */
    public function listarAtenciones(Request $request)
    {
        $atenciones = Atencion::select('admAtencion.IdAtencion', 'admAtencion.FecIngreso')
            ->join('admCliente', 'admCliente.IdCliente', 'admAtencion.IdCliente')
            ->where('admCliente.NumDocumento', $request->numDocu)
            ->limit(20)
            ->orderBy('admAtencion.IdAtencion', 'DESC')
            ->get();

        return response()->json($atenciones);
    }

    /**
     * Lista productos facturados y diagnósticos asociados a una atención.
     *
     * @param  Request  $request  ID de la atención (`idAtencion`).
     * @return \Illuminate\Http\JsonResponse Objeto con listas de `productos` y `diagnosticos`.
     */
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

        $consultas = HceConsulta::where('IdAtencion', $request->idAtencion)->limit(2)->get();

        return response()->json(['productos' => $productos, 'diagnosticos' => $diagnosticos, 'consultas' => $consultas]);
    }

    /**
     * Busca los datos de la EPS, Plan y Contrato de una atención.
     *
     * @param  int  $idAtencion  ID de la atención.
     * @return \Illuminate\Support\Collection Datos de facturación y contrato.
     */
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

    /**
     * Lista detallada de productos de una atención, combinando fuentes de facturación y movimientos.
     *
     * Combina resultados de `facFacturaDet` y `FacMovimientoDet` para obtener todos los productos
     * cargados a la cuenta del paciente. Luego cruza esta información con la base de datos local
     * de Lidiag para determinar el estado de la ecografía si existe.
     *
     * @param  Request  $request  ID de la atención (`bus_nom_num_docu`).
     * @return \Illuminate\Http\JsonResponse Lista combinada de productos con estado.
     */
    public function listarProductosAtencion(Request $request)
    {
        $atencionId = $request->bus_nom_num_docu;
        $gruposPermitidos = [44, 195, 156, 190, 204];

        $consulta1 = facFacturaDet::select([
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
                ) as quien_ordeno"),
        ])
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

        $consulta2 = FacMovimientoDet::select([
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
                ) as quien_ordeno"),
        ])
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

        // Arreglo para almacenar las atenciones combinadas de SAHi y Lidiag
        $combinarAtenciones[] = [];
        foreach ($atencionesSahi as $atencionLidiag) {

            $matchAtencion = $atencionesLidiag->firstWhere('atencion_id', true);
            if ($matchAtencion) {
                $atencionLidiag->estado_ecografia = $matchAtencion->estado;
            } else {
                $atencionLidiag->estado_ecografia = null;
            }

            $combinarAtenciones[] = $atencionLidiag;
        }

        return response()->json($combinarAtenciones, 200);
    }

    /**
     * Obtiene el estado de las ecografías en Lidiag para una atención.
     *
     * @param  int  $atencionId  ID de la atención.
     * @return \Illuminate\Support\Collection Colección de estados.
     */
    private function obtenerDatosLidiag($atencionId)
    {
        $atenciones = Ecografia::select('estado')
            ->where('atencion_id', $atencionId)->get();

        return $atenciones;
    }
}
