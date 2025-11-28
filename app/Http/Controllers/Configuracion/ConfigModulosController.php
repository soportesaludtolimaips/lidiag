<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigModulo;
use Illuminate\Http\Request;

class ConfigModulosController extends Controller
{
    /**
     * Lista todos los módulos configurados en el sistema.
     *
     * @return \Illuminate\Http\JsonResponse Lista de módulos.
     */
    public function index()
    {
        $modulos = ConfigModulo::all();
        return response()->json($modulos);
    }

    /**
     * Lista todos los módulos (Alias de index).
     *
     * @return \Illuminate\Http\JsonResponse Lista de módulos.
     */
    public function listarModulos()
    {
        $modulos = ConfigModulo::all();
        return response()->json($modulos);
    }
}
