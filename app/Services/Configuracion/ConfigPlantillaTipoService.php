<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPlantillaTipo;
use App\Services\BaseService;

class ConfigPlantillaTipoService extends BaseService
{
    /**
     * Obtiene todos los tipos de plantilla registrados.
     *
     * @returns array Arreglo con la lista de tipos de plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigPlantillaTipo::all());
    }

    /**
     * Busca un tipo de plantilla por su id.
     *
     * @param  int  $id  Identificador del tipo de plantilla a buscar.
     *
     * @returns array Arreglo con los datos del tipo de plantilla o un error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigPlantillaTipo::find($id);

        if (! $item) {
            return $this->notFound('Tipo de plantilla no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Obtiene los tipos de plantilla activos registrados.
     *
     * @returns array Arreglo con la lista de tipos de plantilla activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivos(): array
    {
        return $this->success(ConfigPlantillaTipo::where('estado', true)->get());
    }

    /**
     * Crea un nuevo tipo de plantilla y registra la auditoría correspondiente.
     *
     * @param  array  $data  Datos del tipo de plantilla a crear.
     *
     * @returns array Arreglo con el tipo de plantilla creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigPlantillaTipo::create($data);
        AuditLogger::crear($item, 'Tipo de plantilla creado: '.($data['nom_tipo'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza un tipo de plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del tipo de plantilla a actualizar.
     * @param  array  $data  Datos del tipo de plantilla a actualizar.
     *
     * @returns array Arreglo con el tipo de plantilla actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigPlantillaTipo::findOrFail($id);

        $fillable = [];
        if (isset($data['nom_tipo'])) {
            $fillable['nom_tipo'] = $data['nom_tipo'];
        }
        if (isset($data['producto_id'])) {
            $fillable['producto_id'] = $data['producto_id'];
        }
        if (isset($data['estado'])) {
            $fillable['estado'] = filter_var($data['estado'], FILTER_VALIDATE_BOOLEAN);
        }

        $item->update($fillable);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Tipo de plantilla actualizado: '.$item->nom_tipo);

        return $this->updated($item);
    }

    /**
     * Elimina un tipo de plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del tipo de plantilla a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigPlantillaTipo::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Tipo de plantilla eliminado: '.$item->nom_tipo);

        return $this->deleted();
    }
}
