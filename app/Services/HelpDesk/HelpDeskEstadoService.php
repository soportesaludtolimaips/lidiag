<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskEstado;
use App\Services\BaseService;

class HelpDeskEstadoService extends BaseService
{
    public function all(): array
    {
        return $this->success(HelpDeskEstado::query()->orderBy('orden')->orderBy('nombre')->get());
    }

    public function find(int $id): array
    {
        $item = HelpDeskEstado::find($id);
        if (! $item) {
            return $this->notFound('Estado no encontrado');
        }

        return $this->success($item);
    }

    public function create(): array
    {
        return $this->error('Estados fijos: no se permite crear nuevos estados.', 403);
    }

    public function update(int $id): array
    {
        return $this->error('Estados fijos: no se permite editar estados.', 403);
    }

    public function delete(int $id): array
    {
        return $this->error('Estados fijos: no se permite eliminar estados.', 403);
    }
}
