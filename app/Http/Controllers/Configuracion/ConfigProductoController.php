<?php

namespace App\Http\Controllers\Configuracion;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ConfigProductoRequest;
use App\Models\Configuracion\ConfigProducto;
use Illuminate\Http\JsonResponse;

class ConfigProductoController extends Controller
{
    public function __construct()
    {
        // Catálogo público: solo proteger escrituras
        $this->middleware('auth')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:config.productos.create')->only(['store']);
        $this->middleware('permission:config.productos.edit')->only(['update']);
        $this->middleware('permission:config.productos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los productos (CUPS) ordenados alfabéticamente.
     *
     * @return JsonResponse Lista de productos.
     */
    public function index()
    {
        $producto = ConfigProducto::orderBy('nom_produc')->get();

        return response()->json($producto);
    }

    /**
     * Muestra un producto específico.
     *
     * @param  ConfigProducto  $configProducto  Instancia del producto.
     * @return JsonResponse Datos del producto.
     */
    public function show(ConfigProducto $configProducto)
    {
        return response()->json($configProducto);
    }

    /**
     * Crea un nuevo producto.
     *
     * @param  ConfigProductoRequest  $request  Datos del producto.
     * @param  ConfigProducto  $configProducto  Instancia del modelo.
     * @return JsonResponse Confirmación de creación.
     */
    public function store(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $configProducto->create($request->only(['cod_cups', 'nom_produc', 'porcen', 'val_produc', 'requi_lectura', 'tipo_oral', 'tipo_venoso', 'factura', 'estado']));

        AuditLogger::crear($configProducto, 'Producto creado');

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Actualiza un producto existente.
     *
     * @param  ConfigProductoRequest  $request  Datos actualizados.
     * @param  ConfigProducto  $configProducto  Instancia a actualizar.
     * @return JsonResponse Confirmación de actualización.
     */
    public function update(ConfigProductoRequest $request, ConfigProducto $configProducto)
    {
        $datosViejos = $configProducto->toArray();
        $configProducto->update($request->only(['cod_cups', 'nom_produc', 'porcen', 'val_produc', 'requi_lectura', 'tipo_oral', 'tipo_venoso', 'factura', 'estado']));

        AuditLogger::actualizar($configProducto, $datosViejos, 'Producto actualizado');

        return response()->json(['message' => 'Registro actualizado satisfactoriamente.']);
    }

    /**
     * Elimina un producto.
     *
     * @param  ConfigProducto  $configProducto  Instancia a eliminar.
     * @return JsonResponse Confirmación de eliminación.
     */
    public function destroy(ConfigProducto $configProducto)
    {
        $configProducto->delete();

        AuditLogger::eliminar($configProducto, 'Producto eliminado');

        return response()->json(['message' => 'Registro eliminado satisfactoriamente.']);
    }
}
