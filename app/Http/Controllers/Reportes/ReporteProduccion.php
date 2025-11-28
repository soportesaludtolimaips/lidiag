<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\ProduccionDetalladaExport;
use App\Exports\ProduccionDetalladaEcografiasExport;
use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class ReporteProduccion extends Controller
{
    /**
     * Genera y descarga un reporte detallado de producción en Excel.
     *
     * Permite generar reportes para 'Rx' o 'Ecografia', filtrando por fechas,
     * sede, usuario que asignó y médico.
     *
     * @param Request $resquest Filtros: `fechaIni`, `fechaFin`, `sedeId`, `quien_asigno`, `medico`, `tipo`.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo Excel descargable.
     */
    public function reporteProduccionDetallado(Request $resquest)
    {
        $fechaInicio = $resquest->fechaIni;
        $fechaFin    = $resquest->fechaFin;

        $sedeId = $resquest->sedeId !== '0' ? $resquest->sedeId : null;
        $quienAsignoId = $resquest->quien_asigno !== 'null' ? $resquest->quien_asigno : null;
        $medicoId = $resquest->medico !== 'null' ? $resquest->medico : null;

        /* $ecografias = DB::table('view_reporte_ecografias')
            ->when($fechaInicio, fn ($q) => $q->whereBetween(DB::raw('DATE(fechor_lectura)'), [$fechaInicio, $fechaFin]))
            //->when($sedeId, fn ($q) => $q->where('sede_id', $sedeId))
            ->when($quienAsignoId, fn ($q) => $q->where('usua_regis_id', $quienAsignoId))
            ->when($medicoId, fn ($q) => $q->where('medico_id', $medicoId))
            ->select('id', 'fec_cita_sahi', 'fec_regis_cita_sahi', 'fechor_lectura', 'atencion_id', 'quien_registro', 'medico', 'documento_paciente', 
                    'nombre_paciente', 'cod_cups', 'nom_produc', 'eps', 'contrato', 'plan', 'quien_ordena', 'lectura', 'opinion')
            ->get();

        return $ecografias; */

        // Define el nombre y la ruta del archivo
        $fileName = 'estudios_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = $resquest->tipo === 'Rx' ? 'reporte_lecturas/' . $fileName : 'reporte_lecturas_ecografias/' . $fileName;
        
        if ($resquest->tipo === 'Rx') {
            return Excel::download(new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),  'produccion lecturas de imagenes diagnosticas.xlsx');
        }
        if ($resquest->tipo === 'Ecografia') {
            return Excel::download(new ProduccionDetalladaEcografiasExport($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId),  'produccion lecturas de ecografias.xlsx');
        }
    }
}
