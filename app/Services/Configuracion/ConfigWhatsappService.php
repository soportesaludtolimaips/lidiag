<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigWhatsapp;
use App\Services\BaseService;

class ConfigWhatsappService extends BaseService
{
    public function all(?int $sedeId = null): array
    {
        $query = ConfigWhatsapp::with(['sede', 'servicio']);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $this->success($query->get());
    }

    public function find(int $id): array
    {
        $item = ConfigWhatsapp::with(['sede', 'servicio'])->find($id);

        if (! $item) {
            return $this->notFound('Configuración de WhatsApp no encontrada');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = ConfigWhatsapp::create($data);

        return $this->created($item->load(['sede', 'servicio']));
    }

    public function update(int $id, array $data): array
    {
        $item = ConfigWhatsapp::findOrFail($id);
        $item->update($data);

        return $this->updated($item->load(['sede', 'servicio']));
    }

    public function delete(int $id): array
    {
        $item = ConfigWhatsapp::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }

    public function buscarPorSedeYServicio(int $sedeId, int $servicioCitaId): array
    {
        $config = ConfigWhatsapp::paraSedeYservicio($sedeId, $servicioCitaId)->first();

        if (! $config) {
            return $this->success([
                'mensaje_bienvenida' => 'Bienvenido a Lidiag, te notificaremos cuando tu resultado esté listo.',
            ]);
        }

        return $this->success($config);
    }
}
