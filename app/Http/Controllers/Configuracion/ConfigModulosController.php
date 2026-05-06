<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigModulo;
use Illuminate\Http\JsonResponse;

class ConfigModulosController extends Controller
{
    /**
     * Lista todos los módulos configurados en el sistema.
     *
     * @return JsonResponse Lista de módulos.
     */
    public function index()
    {
        $modulos = ConfigModulo::all();

        return response()->json($modulos);
    }

    /**
     * Lista todos los módulos (Alias de index).
     *
     * @return JsonResponse Lista de módulos.
     */
    public function listarModulos()
    {
        $modulos = ConfigModulo::all();

        return response()->json($modulos);
    }
}
