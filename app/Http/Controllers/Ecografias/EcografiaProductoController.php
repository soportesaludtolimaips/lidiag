<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaProductoRequest;
use App\Models\Ecografias\EcografiaProducto;
use Illuminate\Http\Request;

class EcografiaProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ecografiaProductos = EcografiaProducto::with(['ecografia', 'medico', 'producto'])->get();
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
     * Store a newly created resource in storage.
     */
    public function store(EcografiaProductoRequest $request)
    {
        $ecografiaProducto = EcografiaProducto::create($request->validated());
        return response()->json(['success' => true, 'data' => $ecografiaProducto], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ecografiaProducto = EcografiaProducto::with(['ecografia', 'medico', 'producto'])->findOrFail($id);
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
     * Update the specified resource in storage.
     */
    public function update(EcografiaProductoRequest $request, $id)
    {
        $ecografiaProducto = EcografiaProducto::findOrFail($id);
        $ecografiaProducto->update($request->validated());
        return response()->json(['success' => true, 'data' => $ecografiaProducto]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ecografiaProducto = EcografiaProducto::findOrFail($id);
        $ecografiaProducto->delete();
        return response()->json(['success' => true, 'message' => 'Producto de ecografía eliminado correctamente']);
    }
}
