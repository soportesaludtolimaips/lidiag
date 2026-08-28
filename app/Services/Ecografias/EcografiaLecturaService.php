<?php

namespace App\Services\Ecografias;

use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class EcografiaLecturaService extends BaseService
{
    /**
     * Procesa la acción de lectura de una ecografía (imprimir lectura o terminar lectura), actualizando la lectura, opinión y estado del producto y de la ecografía dentro de una transacción.
     *
     * @param  array  $data  Datos validados de la solicitud que incluyen la acción a realizar y el registro de lectura y opinión.
     * @param  int  $id  Identificador de la ecografía sobre la cual se ejecuta la acción de lectura.
     *
     * @returns array Resultado de la operación con el estado, mensajes y datos relacionados a la lectura de la ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function leer(array $data, int $id): array
    {
        $accion = (string) ($data['accion'] ?? '');

        return DB::transaction(function () use ($data, $id, $accion) {
            $query = EcografiaProducto::where('ecografia_id', $id);

            if (! $query->exists()) {
                return $this->notFound('El producto de la ecografía no se encontró');
            }

            if ($accion === 'IMPRIMIR_LECTURA') {
                $query->update([
                    'lectura' => $data['registro']['lectura'] ?? null,
                    'opinion' => $data['registro']['opinion'] ?? null,
                    'usua_regis_id' => auth()->id(),
                    'fechor_lectura' => now(),
                ]);

                return $this->created(['message' => 'Lectura guardada exitosamente']);
            }

            if ($accion === 'TERMINAR_LECTURA') {
                $query->update([
                    'lectura' => $data['registro']['lectura'] ?? null,
                    'opinion' => $data['registro']['opinion'] ?? null,
                    'usua_regis_id' => auth()->id(),
                    'fechor_lectura' => now(),
                    'estado' => 'LEIDO',
                ]);

                Ecografia::where('id', $id)->update([
                    'estado' => 'LEIDO',
                ]);

                return $this->created(['message' => 'Lectura guardada exitosamente']);
            }

            return $this->validationError(null, 'Acción no soportada.');
        });
    }

    /**
     * Lista las ecografías en estado "EN PROCESO" asociadas a un médico, incluyendo la información del producto, consentimientos y datos enmascarados del paciente.
     *
     * @param  int  $idMedico  Identificador del médico cuyas ecografías pendientes de lectura se desean consultar.
     *
     * @returns array Resultado con la lista de ecografías pendientes de lectura y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function listarPendientesPorLectura(int $idMedico): array
    {
        $medico = User::find($idMedico);
        if (! $medico) {
            return $this->notFound('El medico no se encontro');
        }

        $moduloEcografiasId = 2;

        $ecografiasPendientes = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.opinion',
            'ep.fechor_lectura',
            'ep.estado_envio_sahi',
            'ep.fechor_envio_sahi',
            'ep.sahi_esquema_149',
            'ep.sahi_esquema_150',
            'ep.error_envio_sahi',
            'p.id as producto_id',
            'p.nom_produc',
            'p.cod_cups',
            'c.id as consentimiento_id',
            'c.estado as consentimiento_estado',
            'c.firma_paciente_at as consentimiento_firma_paciente_at',
            'c.firma_medico_at as consentimiento_firma_medico_at',
            DB::raw('CASE WHEN cp.id IS NULL THEN 0 ELSE 1 END as tiene_plantilla_consentimiento')
        )
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id')
            ->leftJoin('consentimiento_plantillas as cp', function ($join) use ($moduloEcografiasId) {
                $join->on('cp.producto_id', '=', 'p.id')
                    ->where('cp.config_modulo_id', '=', $moduloEcografiasId)
                    ->where('cp.vigente', '=', 1);
            })
            ->leftJoin('ecografias_consentimientos as c', function ($join) {
                $join->on('c.ecografia_producto_id', '=', 'ep.id')
                    ->where('c.vigente', '=', 1);
            })
            ->where('ecografias.medico_id', $idMedico)
            ->where('ep.estado', '=', 'EN PROCESO')
            ->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->get();

        $ecografiasPendientes->transform(function ($eco) {
            if (isset($eco->num_docu)) {
                $eco->num_docu = maskData($eco->num_docu, 'document');
            }
            if (isset($eco->direccion)) {
                $eco->direccion = maskData($eco->direccion, 'address');
            }

            return $eco;
        });

        return $this->success(['ecografias' => $ecografiasPendientes]);
    }
}
