<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\ConsentimientoPlantillaProducto;
use App\Services\BaseService;

class ConsentimientoPlantillaProductoService extends BaseService
{
    public function index(int $plantillaId): array
    {
        ConsentimientoPlantilla::query()->findOrFail($plantillaId);

        $items = ConsentimientoPlantillaProducto::query()
            ->where('plantilla_id', $plantillaId)
            ->with(['producto:id,cod_cups,nom_produc', 'sede:id,nom_sede'])
            ->orderByDesc('id')
            ->get();

        return $this->success($items);
    }

    public function store(int $plantillaId, array $data): array
    {
        ConsentimientoPlantilla::query()->findOrFail($plantillaId);

        $productoId = (int) $data['producto_id'];
        $sedeId = ! empty($data['sede_id']) ? (int) $data['sede_id'] : null;

        $existe = ConsentimientoPlantillaProducto::query()
            ->where('plantilla_id', $plantillaId)
            ->where('producto_id', $productoId)
            ->where(fn ($q) => $sedeId ? $q->where('sede_id', $sedeId) : $q->whereNull('sede_id'))
            ->exists();

        if ($existe) {
            return $this->validationError(null, 'Ese producto/sede ya está asignado a la plantilla');
        }

        $asignacion = ConsentimientoPlantillaProducto::create([
            'plantilla_id' => $plantillaId,
            'producto_id' => $productoId,
            'sede_id' => $sedeId,
            'requiere_firma_medico' => (bool) ($data['requiere_firma_medico'] ?? true),
        ]);

        return $this->created($asignacion->load(['producto:id,cod_cups,nom_produc', 'sede:id,nom_sede']));
    }

    public function destroy(int $id): array
    {
        $asignacion = ConsentimientoPlantillaProducto::query()->findOrFail($id);
        $asignacion->delete();

        return $this->deleted();
    }
}
