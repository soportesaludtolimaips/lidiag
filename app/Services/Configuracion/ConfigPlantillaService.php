<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPlantilla;
use App\Services\BaseService;

class ConfigPlantillaService extends BaseService
{
    /**
     * Obtiene todas las plantillas con sus relaciones de tipo y médico.
     *
     * @returns array Arreglo con la lista de plantillas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])->get()
        );
    }

    /**
     * Busca una plantilla por su id incluyendo sus relaciones.
     *
     * @param  int  $id  Identificador de la plantilla a buscar.
     *
     * @returns array Arreglo con los datos de la plantilla o un error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigPlantilla::with(['plantillaTipo', 'medico'])->find($id);

        if (! $item) {
            return $this->notFound('Plantilla no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Obtiene las plantillas activas con sus relaciones de tipo y médico.
     *
     * @returns array Arreglo con la lista de plantillas activas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivos(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])
                ->where('estado', true)
                ->get()
        );
    }

    /**
     * Obtiene las plantillas activas filtradas por tipo y médico.
     *
     * @param  int  $tipoId  Identificador del tipo de plantilla.
     * @param  int  $medicoId  Identificador del médico.
     *
     * @returns array Arreglo con la lista de plantillas activas filtradas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivosPorTipo(int $tipoId, int $medicoId): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])
                ->where('estado', true)
                ->where('plantilla_tipo_id', $tipoId)
                ->where('medico_id', $medicoId)
                ->get()
        );
    }

    /**
     * Crea una nueva plantilla y registra la auditoría correspondiente.
     *
     * @param  array  $data  Datos de la plantilla a crear.
     *
     * @returns array Arreglo con la plantilla creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigPlantilla::create($data);
        AuditLogger::crear($item, 'Plantilla creada: '.($data['nom_plantilla'] ?? ''));

        return $this->created($item->load(['plantillaTipo', 'medico']));
    }

    /**
     * Actualiza una plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador de la plantilla a actualizar.
     * @param  array  $data  Datos de la plantilla a actualizar.
     *
     * @returns array Arreglo con la plantilla actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Plantilla actualizada: '.$item->nom_plantilla);

        return $this->updated($item->load(['plantillaTipo', 'medico']));
    }

    /**
     * Elimina una plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador de la plantilla a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Plantilla eliminada: '.$item->nom_plantilla);

        return $this->deleted();
    }
}
