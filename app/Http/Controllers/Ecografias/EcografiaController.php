<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Ecografias\PlantillaEcografiaController;
use App\Http\Requests\Ecografias\EcografiaRequest;
use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\General\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EcografiaController extends Controller
{
    /**
     * Listar ecografías con filtros básicos.
     *
     * Flujo del proceso:
     * 1. Consulta el modelo Ecografia con sus relaciones (paciente, usuario, adminSalud, medico).
     * 2. Aplica filtro por número de documento o nombre del paciente si se proporciona.
     * 3. Aplica filtro por rango de fechas de creación si se proporcionan.
     * 4. Retorna la colección de ecografías en formato JSON.
     *
     * @param Request $request Filtros de búsqueda (bus_nom_num_docu, bus_fehc_ini, bus_fehc_fin).
     * @return \Illuminate\Http\JsonResponse Lista de ecografías.
     */
    public function index(Request $request)
    {
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
     * Búsqueda avanzada de ecografías uniendo con productos.
     *
     * Flujo del proceso:
     * 1. Selecciona campos específicos de Ecografia, EcografiaProducto y ConfigProducto.
     * 2. Realiza JOINs con las tablas de productos y configuración.
     * 3. Aplica filtros de búsqueda por paciente (nombre/documento) y fechas.
     * 4. Retorna los resultados filtrados.
     *
     * @param Request $request Criterios de búsqueda (bus_nom_num_docu, bus_fehc_ini, bus_fehc_fin).
     * @return \Illuminate\Http\JsonResponse Lista detallada de ecografías con productos.
     */
    public function filtarEcografias(Request $request)
    {
        $ecografias = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.fechor_lectura',
            'ep.estado_envio_sahi',
            'ep.fechor_envio_sahi',
            'ep.sahi_esquema_149',
            'ep.sahi_esquema_150',
            'ep.error_envio_sahi',
            'p.nom_produc'
        )->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id');

        if ($request->filled('bus_nom_num_docu')) {
            $valor = $request->bus_nom_num_docu;
            $ecografias->where(function ($q) use ($valor) {
                $q->where('ecografias.atencion_id', 'like', "%{$valor}%")
                    ->orWhereHas('paciente', function ($p) use ($valor) {
                        $p->where('num_docu', 'like', "%{$valor}%")
                            ->orWhere('nombres', 'like', "%{$valor}%")
                            ->orWhere('pri_ape', 'like', "%{$valor}%")
                            ->orWhere('seg_ape', 'like', "%{$valor}%");
                    });
            });
        }

        if ($request->filled('bus_fehc_ini') && $request->filled('bus_fehc_fin')) {
            $ecografias->whereBetween('ecografias.created_at', [
                "{$request->bus_fehc_ini} 00:00:00",
                "{$request->bus_fehc_fin} 23:59:59"
            ]);
        }

        return response()->json($ecografias->get());
    }

    /**
     * Crea una nueva ecografía, paciente (si no existe) y productos asociados.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada (paciente, sede, médico, productos).
     * 2. Crea o actualiza el registro del Paciente.
     * 3. Busca o crea la Administradora de Salud (EPS).
     * 4. Crea el registro principal de Ecografia.
     * 5. Itera sobre los productos de la agenda:
     *    a. Busca o crea el ConfigProducto.
     *    b. Crea la relación EcografiaProducto.
     * 6. Retorna respuesta exitosa con los datos creados.
     *
     * @param Request $request Datos completos de la admisión (paciente, cita, productos).
     * @return \Illuminate\Http\JsonResponse Confirmación de creación o error.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'num_docu' => 'required|string',
                'nombres' => 'required|string',
                'pri_ape' => 'nullable|string',
                'sede_id' => 'required|integer',
                'atencion_id' => 'required|string|max:20',
                'medico_id' => 'required|integer',
                'productosAgenda' => 'required|array',
            ], [
                'num_docu.required' => 'El número de documento es obligatorio.',
                'nombres.required' => 'El nombre es obligatorio.',
                'pri_ape.required' => 'El primer apellido es obligatorio.',
                'sede_id.required' => 'La sede es obligatoria.',
                'atencion_id.required' => 'La atención es obligatoria.',
                'medico_id.required' => 'El médico es obligatorio.',
                'productosAgenda.required' => 'Debe agregar al menos un producto a la ecografía.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Crear el paciente si no existe
            $paciente = Paciente::updateOrCreate(
                ['num_docu' => $request->num_docu],
                [
                    'tipo_documento_id' => $request->tipo_documento_id,
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
                'medico_id' => $request->medico_id,
                'paciente_id' => $paciente->id,
                'usua_regis_id' => $request->quien_registro_id,
                'admin_salud_id' => $adminSalud->id ?? null,
                'atencion_id' => $request->atencion_id,
                'fec_cita_sahi' => $request->fec_cita_sahi ?? null,
                'fec_regis_cita_sahi' => $request->fec_regis_cita_sahi ?? null,
                'contrato' => $request->contrato ?? null,
                'plan' => $request->plan ?? null,
                'quien_ordena' => $request->quien_ordena ?? null,
                'estado' => 'EN PROCESO',
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
                    'usua_regis_id' => $request->quien_registro_id,
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
     * Muestra una ecografía específica.
     *
     * Flujo del proceso:
     * 1. Busca la ecografía por ID incluyendo relaciones.
     * 2. Retorna el objeto ecografía o error 404.
     *
     * @param int $id ID de la ecografía.
     * @return \Illuminate\Http\JsonResponse Datos de la ecografía.
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
     * Actualiza datos de una ecografía.
     *
     * Flujo del proceso:
     * 1. Busca la ecografía por ID.
     * 2. Actualiza con los datos validados del request.
     * 3. Retorna confirmación.
     *
     * @param EcografiaRequest $request Datos a actualizar.
     * @param int $id ID de la ecografía.
     * @return \Illuminate\Http\JsonResponse Datos actualizados.
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
     * Elimina una ecografía.
     *
     * Flujo del proceso:
     * 1. Busca la ecografía por ID.
     * 2. Elimina el registro.
     * 3. Retorna mensaje de éxito.
     *
     * @param int $id ID de la ecografía.
     * @return \Illuminate\Http\JsonResponse Confirmación.
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

    /**
     * Anula una ecografía lógicamente.
     *
     * Flujo del proceso:
     * 1. Busca la ecografía por ID.
     * 2. Cambia el estado a 'ANULADO'.
     * 3. Registra motivo, usuario y fecha de anulación.
     * 4. Guarda los cambios.
     *
     * @param int $id ID de la ecografía.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
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

    /**
     * Genera el reporte PDF de la ecografía.
     *
     * Flujo del proceso:
     * 1. Invoca al PlantillaEcografiaController para procesar la plantilla Word y generar el PDF.
     * 2. Retorna la respuesta del controlador de plantillas (URL del PDF).
     *
     * @param Request $request Datos para el reporte.
     * @param Ecografia $ecografia Instancia de la ecografía (no usada directamente en el método, pero inyectada).
     * @return mixed Respuesta de PlantillaEcografiaController::editWord.
     */
    public function generarReporte(Request $request, Ecografia $ecografia)
    {
        $genearPlantilla = (new PlantillaEcografiaController())->editWord($request);
        return $genearPlantilla;
    }
}
