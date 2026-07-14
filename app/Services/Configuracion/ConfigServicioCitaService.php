<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigServicioCita;
use App\Services\BaseService;

class ConfigServicioCitaService extends BaseService
{
    public function all(?int $sedeId = null): array
    {
        $query = ConfigServicioCita::with('sede');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    public function find(int $id): array
    {
        $item = ConfigServicioCita::with('sede')->find($id);

        if (! $item) {
            return $this->notFound('Servicio de cita no encontrado');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = ConfigServicioCita::create($data);

        return $this->created($item->load('sede'));
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigServicioCita::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load('sede'));
    }

    public function delete(int $id): array
    {
        $item = ConfigServicioCita::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
