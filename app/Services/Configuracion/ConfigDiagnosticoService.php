<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigDiagnostico;
use App\Services\BaseService;

class ConfigDiagnosticoService extends BaseService
{
    /**
     * Obtiene todos los diagnósticos registrados ordenados por nombre.
     *
     * @returns array Arreglo con la lista de diagnósticos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigDiagnostico::orderBy('nom_diagnos')->get());
    }

    /**
     * Crea un nuevo diagnóstico y registra la auditoría correspondiente.
     *
     * @param  array  $data  Datos del diagnóstico a crear.
     *
     * @returns array Arreglo con el diagnóstico creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigDiagnostico::create($data);
        AuditLogger::crear($item, 'Diagnóstico creado: '.($data['nom_diagnos'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza un diagnóstico existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del diagnóstico a actualizar.
     * @param  array  $data  Datos del diagnóstico a actualizar.
     *
     * @returns array Arreglo con el diagnóstico actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigDiagnostico::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Diagnóstico actualizado: '.$item->nom_diagnos);

        return $this->updated($item);
    }

    /**
     * Elimina un diagnóstico existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador del diagnóstico a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigDiagnostico::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Diagnóstico eliminado: '.$item->nom_diagnos);

        return $this->deleted();
    }
}
