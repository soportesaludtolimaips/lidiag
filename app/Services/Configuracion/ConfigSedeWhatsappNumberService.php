<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigSedeWhatsappNumber;
use App\Services\BaseService;

class ConfigSedeWhatsappNumberService extends BaseService
{
    public function all(?int $sedeId = null): array
    {
        $query = ConfigSedeWhatsappNumber::with('sede');

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    public function create(array $data): array
    {
        $item = ConfigSedeWhatsappNumber::create($data);

        return $this->created($item->load('sede'));
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigSedeWhatsappNumber::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load('sede'));
    }

    public function delete(int $id): array
    {
        $item = ConfigSedeWhatsappNumber::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }

    public function find(int $id): array
    {
        $item = ConfigSedeWhatsappNumber::with('sede')->find($id);

        if (! $item) {
            return $this->notFound('Número de WhatsApp no encontrado');
        }

        return $this->success($item);
    }
}
