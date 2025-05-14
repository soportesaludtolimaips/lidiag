<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaRequest;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\General\Paciente;
use Illuminate\Http\Request;

class EcografiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ecografias = Ecografia::with(['paciente', 'usuarioRegistrador', 'adminSalud'])->get();
        return response()->json($ecografias);
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
    public function store(Request $request)
    {
        try {
            // Verificar si el paciente existe
            $paciente = Paciente::where('num_docu', $request->num_docu)->first();
            if (!$paciente) {
                // Crear el paciente si no existe
                $paciente = Paciente::create([
                    'num_docu' => $request->num_docu,
                    'nombres' => $request->nombres,
                    'seg_nom' => $request->seg_nom,
                    'pri_ape' => $request->pri_ape,
                    'seg_ape' => $request->seg_ape,
                    'sexo' => $request->sexo,
                    'fec_naci' => $request->fec_naci,
                    'direccion' => $request->direccion,
                    'tel' => $request->tel,
                    'cel' => $request->cel,
                    'sede_id' => $request->sede_id,
                    'quien_registro_id' => $request->quien_registro_id,
                ]);
            }

            // Verificar si la administradora de salud existe
            $adminSalud = ConfigAdminSalud::where('nom_admin_salud', $request->admin_salud)->first();
            if (!$adminSalud) {
                // Crear la administradora de salud si no existe
                $adminSalud = ConfigAdminSalud::create([
                    'nom_admin_salud' => $request->admin_salud,
                    'estado' => true,
                ]);
            }

            // Crear la ecografía
            $ecografia = Ecografia::create([
                'atencion_id' => $request->atencion_id,
                'paciente_id' => $paciente->id,
                'usua_regis_id' => $request->quien_registro_id,
                'admin_salud_id' => $adminSalud->id ?? null,
                'fec_cita_sahi' => $request->fec_cita_sahi ?? null,
                'fec_regis_cita_sahi' => $request->fec_regis_cita_sahi ?? null,
                'contrato' => $request->contrato ?? null,
                'plan' => $request->plan ?? null,
                'quien_ordena' => $request->quien_ordena ?? null,
                'estado' => true,
            ]);

            // Procesar los productos asociados
            foreach ($request->productosAgenda as $producto) {
                // Verificar si el producto existe
                $productoExistente = ConfigProducto::where('id', $producto['IdProducto'])->first();

                if (!$productoExistente) {
                    // Crear el producto si no existe
                    $productoExistente = ConfigProducto::create([
                        'id' => $producto['IdProducto'],
                        'nom_produc' => $producto['NomProducto'],
                        'cod_cups' => $producto['CodProducto'],
                        'val_produc' => $producto['ValProduc'],
                    ]);
                }

                // Asociar el producto a la ecografía
                EcografiaProducto::create([
                    'ecografia_id' => $ecografia->id,
                    'medico_id' => $request->medico_id ?? null,
                    'producto_id' => $productoExistente->id,
                    'lectura' => $producto['lectura'] ?? null,
                    'opinion' => $producto['opinion'] ?? null,
                    'estado' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ecografía y productos guardados correctamente.',
                'data' => $ecografia,
            ], 201);
        } catch (\Exception $e) {
            // Manejar errores
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al guardar la ecografía.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $ecografia = Ecografia::with(['paciente', 'usuarioRegistrador', 'adminSalud'])->findOrFail($id);
        return response()->json($ecografia);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ecografia $ecografia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EcografiaRequest $request, $id)
    {
        $ecografia = Ecografia::findOrFail($id);
        $ecografia->update($request->validated());
        return response()->json(['success' => true, 'data' => $ecografia]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ecografia = Ecografia::findOrFail($id);
        $ecografia->delete();
        return response()->json(['success' => true, 'message' => 'Ecografía eliminada correctamente']);
    }
}
