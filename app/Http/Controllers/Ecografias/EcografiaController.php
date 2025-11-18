<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use App\Http\Requests\Ecografias\EcografiaRequest;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Http\Request;

class EcografiaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request;
        $ecografias = Ecografia::with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->when($request->bus_nom_num_docu, function ($query) use ($request) {
                if ($request->bus_nom_num_docu && $request->bus_nom_num_docu != '') {
                    $query->whereHas('paciente', function ($q) use ($request) {
                        $q->where('num_docu', 'like', '%' . $request->bus_nom_num_docu . '%')
                            ->orWhere('nombres', 'like', '%' . $request->bus_nom_num_docu . '%')
                            ->orWhere('pri_ape', 'like', '%' . $request->bus_nom_num_docu . '%');
                    });
                }
            })->when($request->bus_fehc_ini, function ($query) use ($request) {
                if ($request->bus_fehc_ini && $request->bus_fehc_fin) {
                    $query->whereBetween('created_at', [$request->bus_fehc_ini . ' 00:00:00', $request->bus_fehc_fin . ' 23:59:59']);
                }
            })
            ->get();

        return response()->json($ecografias);
    }

    /**
     * Filtar ecografías según criterios de búsqueda.
     */
    public function filtarEcografias(Request $request)
    {
        $ecografias = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.fechor_lectura',
            'p.nom_produc'
        )->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id');

        $query = $ecografias->newQuery();

        if ($request->filled('bus_nom_num_docu')) {
            $valor = $request->bus_nom_num_docu;
            $query->whereHas('paciente', function ($q) use ($valor) {
                $q->where('atencion_id', 'like', "%{$valor}%")
                    ->orWhere('num_docu', 'like', "%{$valor}%")
                    ->orWhere('nombres', 'like', "%{$valor}%")
                    ->orWhere('pri_ape', 'like', "%{$valor}%");
            });
        }

        if ($request->filled('bus_fehc_ini') && $request->filled('bus_fehc_fin')) {
            $query->whereBetween('created_at', [
                "{$request->bus_fehc_ini} 00:00:00",
                "{$request->bus_fehc_fin} 23:59:59"
            ]);
        }

        $filterEcografias = $ecografias->get();

        return response()->json($filterEcografias);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $validateData = $request->validate(
                [
                    'num_docu' => 'required|string',
                    'nombres' => 'required|string',
                    'pri_ape' => 'nullable|string',
                    'sede_id' => 'required|integer',
                    'atencion_id' => 'required|integer',
                    'medico_id' => 'required|integer',
                    'productosAgenda' => 'required|array',
                ],
                [
                    'num_docu.required' => 'El número de documento es obligatorio.',
                    'nombres.required' => 'El nombre es obligatorio.',
                    'pri_ape.required' => 'El primer apellido es obligatorio.',
                    'sede_id.required' => 'La sede es obligatoria.',
                    'atencion_id.required' => 'La atención es obligatoria.',
                    'medico_id.required' => 'El médico es obligatorio.',
                    'productosAgenda.required' => 'Debe agregar al menos un producto a la ecografía.',
                ]
            );

            // Crear el paciente si no existe
            $paciente = Paciente::updateOrCreate(
                ['num_docu' => $request->num_docu],
                [
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
                    'email' => $request->email,
                ]
            );

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
                'medico_id' => $request->medico_id,
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
        if (!$ecografia) {
            return response()->json(['message' => 'Ecografía no encontrada'], 404);
        }

        return response()->json($ecografia);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EcografiaRequest $request, $id)
    {
        $ecografia = Ecografia::findOrFail($id);
        if (!$ecografia) {
            return response()->json(['message' => 'Ecografía no encontrada'], 404);
        }

        $ecografia->update($request->validated());
        return response()->json(['success' => true, 'data' => $ecografia]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $ecografia = Ecografia::findOrFail($id);
        if (!$ecografia) {
            return response()->json(['message' => 'Ecografía no encontrada'], 404);
        }

        $ecografia->delete();
        return response()->json(['success' => true, 'message' => 'Ecografía eliminada correctamente']);
    }

    public function anularEcografia($id)
    {
        $ecografia = Ecografia::findOrFail($id);
        if (!$ecografia) {
            return response()->json(['message' => 'Ecografía no encontrada'], 404);
        }

        $ecografia->estado = 'ANULADO';
        $ecografia->motivo_anula = request()->input('motivo_anula');
        $ecografia->usua_anula_id = Auth()->id();
        $ecografia->fechor_anula = now();
        $ecografia->save();

        return response()->json(['success' => true, 'message' => 'Ecografía anulada correctamente']);
    }

    public function generarReporte(Request $request, Ecografia $ecografia)
    {
        return $request;

        $request->merge(['TIPO_REPORTE' => 'ECOGRAFIA']);
        $request->merge(['sede_id' => $request->sede_id]);

        $genearPlantilla = (new PlantillaEstudioController())->editWord($request);
        return $genearPlantilla;
    }
}
