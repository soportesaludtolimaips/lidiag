<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigProductoRequest;
use App\Models\Configuracion\ConfigProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConfigProductoController extends Controller
{
    /**
     * Lista todos los productos (CUPS) ordenados alfabéticamente.
     *
     * @return \Illuminate\Http\JsonResponse Lista de productos.
     */
    public function index()
    {
        $producto = ConfigProducto::orderBy('nom_produc')->get();
        return response()->json($producto);
    }

    /**
     * Muestra un producto específico.
     *
     * @param  ConfigProducto  $configProducto Instancia del producto.
     * @return \Illuminate\Http\JsonResponse Datos del producto.
     */
    public function show(ConfigProducto $configProducto)
    {
        return response()->json($configProducto);
    }

    /**
     * Crea un nuevo producto.
     *
     * @param  ConfigProductoRequest  $request Datos del producto.
     * @param  ConfigProducto  $configProducto Instancia del modelo.
     * @return \Illuminate\Http\JsonResponse Confirmación de creación.
     */
    public function store(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $configProducto->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza un producto existente.
     *
     * @param  ConfigProductoRequest  $request Datos actualizados.
     * @param  ConfigProducto  $configProducto Instancia a actualizar.
     * @return \Illuminate\Http\JsonResponse Confirmación de actualización.
     */
    public function update(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $configProducto->update($request->all());
        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    /**
     * Elimina un producto.
     *
     * @param  ConfigProducto  $configProducto Instancia a eliminar.
     * @return \Illuminate\Http\JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigProducto $configProducto)
    {
        $configProducto->delete();
        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }
}
