<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaProductoRequest;
use App\Models\Ecografias\EcografiaProducto;
use Illuminate\Http\Request;

class EcografiaProductoController extends Controller
{
    /**
     * Lista todos los productos de ecografías.
     *
     * Flujo del proceso:
     * 1. Consulta el modelo EcografiaProducto con sus relaciones (ecografia, medico, producto).
     * 2. Retorna la colección en formato JSON.
     *
     * @return \Illuminate\Http\JsonResponse Lista de productos de ecografías.
     */
    public function index()
    {
        $ecografiaProductos = EcografiaProducto::with(['ecografia.medico', 'productos'])->get();
        return response()->json($ecografiaProductos);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Crea un nuevo producto asociado a una ecografía.
     *
     * Flujo del proceso:
     * 1. Crea el registro usando los datos validados del request.
     * 2. Retorna confirmación y los datos creados.
     *
     * @param EcografiaProductoRequest $request Datos del producto a crear.
     * @return \Illuminate\Http\JsonResponse Datos del producto creado.
     */
    public function store(EcografiaProductoRequest $request)
    {
        $ecografiaProducto = EcografiaProducto::create($request->validated());
        return response()->json(['success' => true, 'data' => $ecografiaProducto], 201);
    }

    /**
     * Muestra un producto de ecografía específico.
     *
     * Flujo del proceso:
     * 1. Busca el producto por ID incluyendo relaciones.
     * 2. Retorna el objeto o error 404.
     *
     * @param int $id ID del producto de ecografía.
     * @return \Illuminate\Http\JsonResponse Datos del producto.
     */
    public function show($id)
    {
        $ecografiaProducto = EcografiaProducto::with(['ecografia.medico', 'productos'])->findOrFail($id);
        return response()->json($ecografiaProducto);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EcografiaProducto $ecografiaProducto)
    {
        //
    }

    /**
     * Actualiza un producto de ecografía.
     *
     * Flujo del proceso:
     * 1. Busca el producto por ID.
     * 2. Actualiza con los datos validados.
     * 3. Retorna confirmación.
     *
     * @param EcografiaProductoRequest $request Datos a actualizar.
     * @param int $id ID del producto de ecografía.
     * @return \Illuminate\Http\JsonResponse Datos actualizados.
     */
    public function update(EcografiaProductoRequest $request, $id)
    {
        $ecografiaProducto = EcografiaProducto::findOrFail($id);
        $ecografiaProducto->update($request->validated());
        return response()->json(['success' => true, 'data' => $ecografiaProducto]);
    }

    /**
     * Elimina un producto de ecografía.
     *
     * Flujo del proceso:
     * 1. Busca el producto por ID.
     * 2. Elimina el registro.
     * 3. Retorna mensaje de éxito.
     *
     * @param int $id ID del producto de ecografía.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function destroy($id)
    {
        $ecografiaProducto = EcografiaProducto::findOrFail($id);
        $ecografiaProducto->delete();
        return response()->json(['success' => true, 'message' => 'Producto de ecografía eliminado correctamente']);
    }
}
