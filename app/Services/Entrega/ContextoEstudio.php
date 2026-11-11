<?php

namespace App\Services\Entrega;

use Illuminate\Support\Facades\DB;

/**
 * Datos del estudio tomados de la BASE DE DATOS, no de lo que envía la pantalla.
 *
 * La pantalla de transcripción manda el documento, la sede y el UID del estudio. Si ese dato llega
 * equivocado (p. ej. dos pestañas abiertas) o alterado, la entrega iría a otra persona. Por eso, para
 * decidir A QUIÉN se entrega y de DÓNDE salen las imágenes, se resuelve el estudio por su id.
 */
class ContextoEstudio
{
    /**
     * @return array{num_docu: string, nom_pacien: ?string, sede_id: ?int, nom_sede: ?string, study_iuid: ?string}|null
     *                                                                                                                  null si el estudio no existe
     */
    public function resolver(int $estudioProductoId): ?array
    {
        $fila = DB::table('estudios_productos')
            ->join('estudios', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->leftJoin('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->where('estudios_productos.id', $estudioProductoId)
            ->first(['pacientes.num_docu', 'pacientes.nombres', 'estudios.sede_id', 'config_sedes.nom_sede', 'estudios.study_iuid']);

        if ($fila === null) {
            return null;
        }

        return [
            'num_docu' => trim((string) $fila->num_docu),
            'nom_pacien' => $fila->nombres,
            'sede_id' => $fila->sede_id !== null ? (int) $fila->sede_id : null,
            'nom_sede' => $fila->nom_sede,
            'study_iuid' => $fila->study_iuid,
        ];
    }
}
