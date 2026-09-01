<?php

namespace App\Services\Estudios;

use App\Models\Estudio\EstudioDiagnostico;
use App\Services\BaseService;

class EstudioDiagnosticoService extends BaseService
{
    /**
     * Obtiene todos los diagnósticos de estudios registrados
     *
     * @returns array Respuesta con la colección completa de diagnósticos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(EstudioDiagnostico::all());
    }

    /**
     * Busca un diagnóstico de estudio por su ID
     *
     * @param  int  $id  ID del diagnóstico a buscar
     *
     * @returns array Respuesta con el diagnóstico encontrado o error 404
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = EstudioDiagnostico::find($id);
        if (! $item) {
            return $this->notFound('Diagnóstico no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Crea un nuevo diagnóstico de estudio
     *
     * @param  array  $data  Datos del diagnóstico (estudio_id, cod_diagnos, nom_diagnos)
     *
     * @returns array Respuesta con el diagnóstico creado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = EstudioDiagnostico::create($data);

        return $this->created($item);
    }

    /**
     * Actualiza un diagnóstico de estudio existente
     *
     * @param  int  $id  ID del diagnóstico a actualizar
     * @param  array  $data  Datos a actualizar del diagnóstico
     *
     * @returns array Respuesta con el diagnóstico actualizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = EstudioDiagnostico::findOrFail($id);
        $item->update($data);

        return $this->updated($item);
    }

    /**
     * Elimina un diagnóstico de estudio existente
     *
     * @param  int  $id  ID del diagnóstico a eliminar
     *
     * @returns array Respuesta de confirmación de eliminación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = EstudioDiagnostico::findOrFail($id);
        $item->delete();

        return $this->deleted();
    }
}
