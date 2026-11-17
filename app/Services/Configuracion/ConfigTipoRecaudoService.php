<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigTipoRecaudo;
use App\Services\BaseService;

class ConfigTipoRecaudoService extends BaseService
{
    /**
     * Retorna todos los tipos de recaudo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function all(): array
    {
        $tipos = ConfigTipoRecaudo::orderBy('id')->get();

        return $this->success($tipos);
    }

    /**
     * Retorna únicamente los tipos de recaudo activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function listarActivos(): array
    {
        $tipos = ConfigTipoRecaudo::activos()->orderBy('id')->get();

        return $this->success($tipos);
    }

    /**
     * Busca un tipo de recaudo por ID.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function find(int $id): array
    {
        $tipo = ConfigTipoRecaudo::find($id);

        if (! $tipo) {
            return $this->notFound('Tipo de recaudo no encontrado.');
        }

        return $this->success($tipo);
    }
}
