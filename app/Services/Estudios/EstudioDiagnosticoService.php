<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioDiagnostico;
use App\Services\BaseService;

class EstudioDiagnosticoService extends BaseService
{
    public function all(): array
    {
        return $this->success(EstudioDiagnostico::all());
    }

    public function find(int $id): array
    {
        $item = EstudioDiagnostico::find($id);
        if (! $item) {
            return $this->notFound('Diagnóstico no encontrado');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $item = EstudioDiagnostico::create($data);

        return $this->created($item);
    }

    public function update(int $id, array $data): array
    {
        $item = EstudioDiagnostico::findOrFail($id);
        $item->update($data);

        return $this->updated($item);
    }

    public function delete(int $id): array
    {
        $item = EstudioDiagnostico::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
