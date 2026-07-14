<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigModulo;
use App\Services\BaseService;

class ConfigModuloService extends BaseService
{
    public function all(): array
    {
        return $this->success(ConfigModulo::all());
    }
}
