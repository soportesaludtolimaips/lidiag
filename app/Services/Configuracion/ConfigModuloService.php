<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigModulo;
use App\Services\BaseService;

class ConfigModuloService extends BaseService
{
    /**
     * Obtiene todos los módulos registrados en el sistema.
     *
     * @returns array Arreglo con la lista de módulos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(ConfigModulo::all());
    }
}
