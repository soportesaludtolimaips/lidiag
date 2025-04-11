<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigModulo;
use Illuminate\Http\Request;

class ConfigModulosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modulos = ConfigModulo::all();
        return response()->json($modulos);
    }

    /**
     * Listo todos los modulos
     */
    public function listarModulos()
    {
        $modulos = ConfigModulo::all();
        return response()->json($modulos);
    }
}
