<?php

namespace App\Services\Agenda;

use App\Helpers\AuditLogger;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Services\BaseService;

class ConfigCitaRechazoMotivoService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigCitaRechazoMotivo::orderBy('motivo')->get());
    }

    public function create(array $data): array
    {
        $motivo = ConfigCitaRechazoMotivo::create([
            'motivo' => $data['motivo'],
            'activo' => $data['activo'] ?? true,
        ]);
        AuditLogger::crear($motivo, 'Motivo de rechazo creado: '.$motivo->motivo);

        return $this->created($motivo);
    }

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
