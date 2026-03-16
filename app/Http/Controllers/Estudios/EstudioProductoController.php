<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;

class EstudioProductoController extends Controller
{
    /**
     * Lista los productos asociados a un estudio.
     *
     * @param Request $request ID del estudio (`id`).
     * @return \Illuminate\Http\JsonResponse Lista de productos.
     */
    public function index(Request $request)
    {
        $idEstudio = $request->id;
        $productos = EstudioProducto::where('lectua_id', $idEstudio);
        return response()->json(array('productos' => $productos));
    }

    /**
     * Crea un nuevo producto asociado a un estudio.
     *
     * @param Request $request Datos del producto.
     * @param EstudioProducto $estudioProducto Instancia del modelo.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function store(Request $request, EstudioProducto $estudioProducto)
    {
        $estudioProducto->create($request->all());
        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EstudioProducto $estudioProducto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return \Illuminate\Http\Response
     */
    public function destroy(EstudioProducto $estudioProducto)
    {
        //
    }
}
