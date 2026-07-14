<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioProducto;
use App\Services\BaseService;

class EstudioProductoService extends BaseService
{
    public function listarPorEstudio(int $estudioId): array
    {
        $productos = EstudioProducto::where('estudio_id', $estudioId)->get();

        return $this->success(['productos' => $productos]);
    }

    public function create(array $data): array
    {
        $item = EstudioProducto::create($data);

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->update($data);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
