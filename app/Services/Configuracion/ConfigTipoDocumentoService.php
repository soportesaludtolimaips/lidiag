<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigTipoDocumento;
use App\Services\BaseService;

class ConfigTipoDocumentoService extends BaseService
{
    /**
     * Retorna todos los tipos de documento.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigTipoDocumento::all());
    }

    /**
     * Busca un tipo de documento por su identificador.
     *
     * @param  int  $id  Identificador del tipo de documento.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigTipoDocumento::find($id);

        if (! $item) {
            return $this->notFound('Tipo de documento no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Retorna los tipos de documento activos.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivos(): array
    {
        return $this->success(ConfigTipoDocumento::where('estado', true)->get());
    }

    /**
     * Crea un nuevo tipo de documento.
     *
     * @param  array  $data  Datos del tipo de documento.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigTipoDocumento::create($data);
        AuditLogger::crear($item, 'Tipo de documento creado: '.($data['tipo_documento'] ?? ''));

        return $this->created($item);
    }

    /**
     * Actualiza un tipo de documento existente.
     *
     * @param  int  $id  Identificador del tipo de documento.
     * @param  array  $data  Datos a actualizar.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigTipoDocumento::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Tipo de documento actualizado: '.$item->tipo_documento);

        return $this->updated($item);
    }

    /**
     * Elimina un tipo de documento existente.
     *
     * @param  int  $id  Identificador del tipo de documento.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigTipoDocumento::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Tipo de documento eliminado: '.$item->tipo_documento);

        return $this->deleted();
    }
}
