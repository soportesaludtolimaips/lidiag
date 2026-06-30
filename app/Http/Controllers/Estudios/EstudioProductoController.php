<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EstudioProductoController extends Controller
{
    /**
     * Lista los productos asociados a un estudio.
     *
     * @param  Request  $request  ID del estudio (`id`).
     * @return JsonResponse Lista de productos.
     */
    public function index(Request $request)
    {
        $idEstudio = $request->id;
        $productos = EstudioProducto::where('lectua_id', $idEstudio);

        return response()->json(['productos' => $productos]);
    }

    /**
     * Crea un nuevo producto asociado a un estudio.
     *
     * @param  Request  $request  Datos del producto.
     * @param  EstudioProducto  $estudioProducto  Instancia del modelo.
     * @return JsonResponse Confirmación.
     */
    public function store(Request $request, EstudioProducto $estudioProducto)
    {
        $estudioProducto->create($request->only($estudioProducto->getFillable()));

        return response()->json(['message' => 'Registro creado satisfactoriamente.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return Response
     */
    public function update(Request $request, EstudioProducto $estudioProducto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Estudios\EstudioProducto  $estudioProducto
     * @return Response
     */
    public function destroy(EstudioProducto $estudioProducto)
    {
        //
    }
}
