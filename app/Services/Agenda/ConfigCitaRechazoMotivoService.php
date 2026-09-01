<?php

namespace App\Services\Agenda;

use App\Helpers\AuditLogger;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Services\BaseService;

class ConfigCitaRechazoMotivoService extends BaseService
{
    /**
     * Obtiene todos los motivos de rechazo de citas ordenados por motivo.
     *
     * @returns array resultado con la colección de motivos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigCitaRechazoMotivo::orderBy('motivo')->get());
    }

    /**
     * Crea un nuevo motivo de rechazo de cita con registro de auditoría.
     *
     * @param  array  $data  datos del motivo: motivo (texto),activo (booleano).
     *
     * @returns array resultado con el motivo creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $motivo = ConfigCitaRechazoMotivo::create([
            'motivo' => $data['motivo'],
            'activo' => $data['activo'] ?? true,
        ]);
        AuditLogger::crear($motivo, 'Motivo de rechazo creado: '.$motivo->motivo);

        return $this->created($motivo);
    }

    /**
     * Actualiza un motivo de rechazo existente con registro de auditoría.
     *
     * @param  int  $id  identificador del motivo a actualizar.
     * @param  array  $data  datos actualizados: motivo, activo.
     *
     * @returns array resultado con el motivo actualizado o error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $motivo = ConfigCitaRechazoMotivo::find($id);

        if (! $motivo) {
            return $this->notFound('Motivo de rechazo no encontrado');
        }

        $motivo->update([
            'motivo' => $data['motivo'],
            'activo' => $data['activo'] ?? $motivo->activo,
        ]);
        AuditLogger::actualizar($motivo, $motivo->getOriginal(), 'Motivo de rechazo actualizado: '.$motivo->motivo);

        return $this->updated($motivo);
    }

    /**
     * Elimina un motivo de rechazo de cita con registro de auditoría.
     *
     * @param  int  $id  identificador del motivo a eliminar.
     *
     * @returns array resultado de la eliminación o error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $motivo = ConfigCitaRechazoMotivo::find($id);

        if (! $motivo) {
            return $this->notFound('Motivo de rechazo no encontrado');
        }

        $motivo->delete();
        AuditLogger::eliminar($motivo, 'Motivo de rechazo eliminado: '.$motivo->motivo);

        return $this->deleted();
    }
}
