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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la interoperabilidad y consulta de datos en el sistema HIS / SAHI (SQL Server).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class SAHIService extends BaseService
{
    /**
     * Verifica la conectividad directa y tiempo de respuesta con la base de datos de SAHI.
     *
     * Flujo del proceso:
     * 1. Registra marca de tiempo inicial.
     * 2. Ejecuta una consulta ligera SELECT 1 sobre la conexión sqlsrv.
     * 3. Calcula la latencia en milisegundos y retorna estado exitoso.
     * 4. En caso de fallo de red o autenticación, captura la excepción y retorna error 503 estructurado.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function verificarConexion(): array
    {
        try {
            $inicio = microtime(true);
            DB::connection('sqlsrv')->selectOne('SELECT 1 as test');
            $latencia = round((microtime(true) - $inicio) * 1000, 2);

            return $this->success([
                'conectado' => true,
                'latencia_ms' => $latencia,
                'host' => config('database.connections.sqlsrv.host'),
                'database' => config('database.connections.sqlsrv.database'),
            ], 'Conexión con el sistema HIS / SAHI establecida correctamente.');
        } catch (\Throwable $e) {
            Log::error('Fallo en verificación de conexión con SAHI: '.$e->getMessage(), [
                'host' => config('database.connections.sqlsrv.host'),
                'database' => config('database.connections.sqlsrv.database'),
            ]);

            return $this->error(
                'No fue posible establecer conexión con el servidor SAHI (SQL Server): '.$e->getMessage(),
                503,
                [
                    'conectado' => false,
                    'host' => config('database.connections.sqlsrv.host'),
                    'database' => config('database.connections.sqlsrv.database'),
                ]
            );
        }
    }

    /**
     * Lista las atenciones de un paciente en SAHI por su número de documento.
     *
     * Flujo del proceso:
     * 1. Valida que el número de documento no sea nulo o vacío.
     * 2. Consulta en SQL Server las últimas 20 atenciones asociadas al cliente mediante join con admCliente.
     * 3. Ordena descendentemente por identificador de atención.
     * 4. Captura fallos de conectividad con SQL Server retornando HTTP 503 sin romper la aplicación.
     *
     * @param  string|null  $numDocu  Número de documento del paciente
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function listarAtenciones(?string $numDocu): array
    {
        if (empty($numDocu)) {
            return $this->error('El número de documento es obligatorio.', 422);
        }

        try {
            $atenciones = Atencion::select('admAtencion.IdAtencion', 'admAtencion.FecIngreso')
                ->join('admCliente', 'admCliente.IdCliente', 'admAtencion.IdCliente')
                ->where('admCliente.NumDocumento', $numDocu)
                ->limit(20)
                ->orderBy('admAtencion.IdAtencion', 'desc')
                ->get();

            return $this->success($atenciones);
        } catch (\Throwable $e) {
            Log::error('Error al listar atenciones en SAHI: '.$e->getMessage(), ['numDocu' => $numDocu]);

            return $this->error('No fue posible comunicarse con el sistema SAHI: '.$e->getMessage(), 503);
        }
    }

    /**
     * Lista los productos, diagnósticos y consultas de una atención específica en SAHI.
     *
     * Flujo del proceso:
     * 1. Valida la presencia obligatoria del ID de atención.
     * 2. Consulta productos asignados a la atención filtrando por grupos de diagnóstico permitidos (44, 190, 225, 243).
     * 3. Consulta diagnósticos CIE asociados a la consulta médica en genDiagnostico.
     * 4. Consulta las notas de enfermedad actual y fecha de consulta en hceConsulta.
     * 5. Captura fallos de conectividad con SQL Server retornando HTTP 503 defensivo.
     *
     * @param  int|null  $idAtencion  Identificador de la atención en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function listarProductosDiagnosticos(?int $idAtencion): array
    {
        if (! $idAtencion) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

        try {
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
        } catch (\Throwable $e) {
            Log::error('Error al listar productos/diagnósticos en SAHI: '.$e->getMessage(), ['idAtencion' => $idAtencion]);

            return $this->error('No fue posible comunicarse con el sistema SAHI: '.$e->getMessage(), 503);
        }
    }

    /**
     * Busca la EPS, plan y contrato asociados a una atención en SAHI.
     *
     * Flujo del proceso:
     * 1. Valida la presencia obligatoria del ID de atención.
     * 2. Consulta en facFactura y tablas maestras (genTercero, conContrato, conPlan, genUbicacion) la última factura.
     * 3. Retorna los datos contractuales y de ubicación del paciente.
     * 4. Captura fallos de conectividad con SQL Server retornando HTTP 503 defensivo.
     *
     * @param  int|null  $idAtencion  Identificador de la atención en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function buscarEpsPlanContrato(?int $idAtencion): array
    {
        if (! $idAtencion) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

        try {
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
        } catch (\Throwable $e) {
            Log::error('Error al buscar EPS/Plan en SAHI: '.$e->getMessage(), ['idAtencion' => $idAtencion]);

            return $this->error('No fue posible comunicarse con el sistema SAHI: '.$e->getMessage(), 503);
        }
    }

    /**
     * Lista los productos facturados y movidos de una atención en SAHI, enriquecidos con el estado de ecografía en LIDiag.
     *
     * Flujo del proceso:
     * 1. Valida y castea a entero el ID de atención.
     * 2. Construye y ejecuta la unión de productos facturados (facFacturaDet) y por movimiento (facMovimientoDet).
     * 3. Consulta en la base de datos de LIDiag el estado de ecografías vinculadas a dicha atención.
     * 4. Enlaza el estado de ecografía en cada registro obtenido de SAHI.
     * 5. Captura fallos de conectividad con SQL Server retornando HTTP 503 defensivo.
     *
     * @param  mixed  $atencionId  Identificador de la atención en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function listarProductosAtencion($atencionId): array
    {
        $atencionId = (int) $atencionId;

        if ($atencionId <= 0) {
            return $this->error('El ID de atención es obligatorio.', 422);
        }

        try {
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
        } catch (\Throwable $e) {
            Log::error('Error al listar productos de atención en SAHI: '.$e->getMessage(), ['atencionId' => $atencionId]);

            return $this->error('No fue posible comunicarse con el sistema SAHI: '.$e->getMessage(), 503);
        }
    }

    /**
     * Construye la lista de columnas SELECT comunes para las consultas de atención en SAHI.
     *
     * Flujo del proceso:
     * 1. Configura proyección de datos demográficos y de ingreso convirtiendo fechas con formato SQL Server (120).
     * 2. Incluye datos de producto, contrato, plan y tercero responsable.
     * 3. Integra subconsulta correlacionada para determinar el médico que ordenó el procedimiento.
     *
     * @param  string  $valProducField  Campo que aporta el valor del producto (ValTotal o ValVenta)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function buildAtencionSelect(string $valProducField): array
    {
        return [
            'admAtencion.IdAtencion',
            DB::raw('CONVERT(char(10), admAtencion.FecIngreso, 120) AS FecIngreso'),
            'admCliente.NumDocumento',
            'admCliente.PrimerNombre',
            'admCliente.SegundoNombre',
            'admCliente.PrimerApellido',
            'admCliente.SegundoApellido',
            DB::raw('CONVERT(char(10), admCliente.FecNacimiento, 120) AS FecNacimiento'),
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
            DB::raw('(SELECT TOP(1) QuinOrdenoOrden.NomResponsable
                    FROM hceProceOrden QuinOrdenoOrden
                        INNER JOIN hceProceDetalle ON QuinOrdenoOrden.IdOrdenPro = hceProceDetalle.IdOrdenPro
                        INNER JOIN proProducto PoducOrden ON hceProceDetalle.IdProducto = PoducOrden.IdProducto
                    WHERE QuinOrdenoOrden.IdAtencion = admAtencion.IdAtencion AND PoducOrden.CodProducto = proProducto.CodProducto
                ) as quien_ordeno'),
        ];
    }

    /**
     * Obtiene el estado de las ecografías de Lidiag asociadas a una atención de SAHI.
     *
     * Flujo del proceso:
     * 1. Consulta en la base de datos local MySQL los registros de Ecografia asociados al atencion_id.
     * 2. Retorna la colección con el atributo de estado.
     *
     * @param  int  $atencionId  Identificador de la atención en SAHI
     * @return Collection<int, Ecografia>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function obtenerDatosLidiag(int $atencionId)
    {
        return Ecografia::select('estado', 'atencion_id')
            ->where('atencion_id', $atencionId)->get();
    }
}
