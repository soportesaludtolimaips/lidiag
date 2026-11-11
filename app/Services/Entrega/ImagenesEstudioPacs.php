<?php

namespace App\Services\Entrega;

use App\Helpers\DicomHelper;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Lista y descarga (JPEG por WADO) las imágenes de un estudio desde el PACS de la sede.
 *
 * Reutiliza la conexión y la descarga de DicomHelper (lo que ya usa el correo actual), pero
 * ordena las instancias por serie e imagen para que el visor del paciente las muestre bien.
 */
class ImagenesEstudioPacs
{
    /**
     * Instancias del estudio ordenadas por serie e imagen.
     *
     * @return list<array{series_iuid: string, sop_iuid: string, serie_orden: int, orden: int}>
     */
    public function instancias(ConfigSede $sede, string $studyUid): array
    {
        try {
            $filas = $this->consultarOrdenado($sede, $studyUid);
        } catch (QueryException) {
            // Esquema del PACS sin series_no / inst_no: se usa la consulta sin orden de DicomHelper.
            $filas = array_map(
                fn (array $i) => ['series_iuid' => $i['series_iuid'], 'sop_iuid' => $i['sop_iuid']],
                DicomHelper::getStudyInstances($sede, $studyUid)
            );
        }

        return $this->numerar($filas);
    }

    /**
     * Asigna número de serie (1, 2, …) y de imagen dentro de la serie, en el orden recibido.
     *
     * @param  list<array{series_iuid: string, sop_iuid: string}>  $filas
     * @return list<array{series_iuid: string, sop_iuid: string, serie_orden: int, orden: int}>
     */
    public function numerar(array $filas): array
    {
        $resultado = [];
        $series = [];
        $contadores = [];

        foreach ($filas as $fila) {
            $serie = $fila['series_iuid'];
            if (! isset($series[$serie])) {
                $series[$serie] = count($series) + 1;
                $contadores[$serie] = 0;
            }

            $resultado[] = [
                'series_iuid' => $serie,
                'sop_iuid' => $fila['sop_iuid'],
                'serie_orden' => $series[$serie],
                'orden' => ++$contadores[$serie],
            ];
        }

        return $resultado;
    }

    /**
     * JPEG de una instancia, o null si el PACS no lo entrega o lo que llega no es un JPEG
     * (algunos PACS responden con una página de error y código 200).
     *
     * @param  array{series_iuid: string, sop_iuid: string}  $instancia
     */
    public function descargar(ConfigSede $sede, string $studyUid, array $instancia): ?string
    {
        $bytes = DicomHelper::downloadViaWado(
            (string) $sede->ip_dcm,
            $studyUid,
            $instancia['series_iuid'],
            $instancia['sop_iuid'],
            'image/jpeg'
        );

        return is_string($bytes) && str_starts_with($bytes, "\xFF\xD8\xFF") ? $bytes : null;
    }

    /** @return list<array{series_iuid: string, sop_iuid: string}> */
    private function consultarOrdenado(ConfigSede $sede, string $studyUid): array
    {
        DicomHelper::connectPacs($sede);

        try {
            DB::purge('mysql_sucursal');

            return DB::connection('mysql_sucursal')
                ->table('instance')
                ->join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $studyUid)
                ->orderByRaw('CAST(series.series_no AS UNSIGNED), series.pk, CAST(instance.inst_no AS UNSIGNED), instance.pk')
                ->get(['series.series_iuid', 'instance.sop_iuid'])
                ->map(fn ($fila) => ['series_iuid' => $fila->series_iuid, 'sop_iuid' => $fila->sop_iuid])
                ->all();
        } finally {
            DicomHelper::disconnectPacs();
        }
    }
}
