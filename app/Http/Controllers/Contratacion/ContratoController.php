<?php

namespace App\Http\Controllers\Contratacion;

use App\Exports\ContratoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratacion\ContratoDetalleRequest;
use App\Http\Requests\Contratacion\ContratoRequest;
use App\Imports\ContratoDetalleImport;
use App\Models\Contratacion\Contrato;
use App\Services\Contratacion\ContratoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContratoController extends Controller
{
    /**
     * Constructor del controller. Inyecta el servicio de contratos
     * y configura los middlewares de autenticación y permisos.
     *
     * @param  ContratoService  $contratoService  Servicio de lógica de negocio para contratos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function __construct(
        private readonly ContratoService $contratoService,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:contratos.index')->only(['index']);
        $this->middleware('permission:contratos.create')->only(['store']);
        $this->middleware('permission:contratos.show')->only(['show']);
        $this->middleware('permission:contratos.edit')->only(['update']);
        $this->middleware('permission:contratos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los contratos con filtros opcionales (sede_id, estado, q).
     * Retorna paginación con sede y detalles incluidos.
     *
     * @param  Request  $request  Petición HTTP con filtros opcionales.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con listado paginado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->contratoService->list($request->only([
            'sede_id', 'estado', 'q', 'per_page', 'page',
        ]));

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un nuevo contrato con los datos del formulario.
     *
     * @param  ContratoRequest  $request  Petición validada con datos del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function store(ContratoRequest $request): JsonResponse
    {
        $result = $this->contratoService->create($request->decoded());

        return response()->json($result, $result['status']);
    }

    /**
     * Muestra un contrato específico por su ID, con sede y detalles.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->contratoService->find($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un contrato existente con los nuevos datos.
     *
     * @param  ContratoRequest  $request  Petición validada con datos actualizados.
     * @param  int  $id  Identificador del contrato a actualizar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function update(ContratoRequest $request, int $id): JsonResponse
    {
        $result = $this->contratoService->update($id, $request->decoded());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un contrato por su ID.
     *
     * @param  int  $id  Identificador del contrato a eliminar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->contratoService->delete($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Cambia el estado (activo/inactivo) de un contrato.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el estado actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function toggleEstado(int $id): JsonResponse
    {
        $result = $this->contratoService->toggleEstado($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Agrega un detalle individual (médico + producto) a un contrato.
     *
     * @param  ContratoDetalleRequest  $request  Petición validada con datos del detalle.
     * @param  int  $contratoId  Identificador del contrato padre.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el detalle creado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function storeDetalle(ContratoDetalleRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->addDetalle($contratoId, $request->decoded());

        return response()->json($result, $result['status']);
    }

    /**
     * Actualiza un detalle existente de un contrato.
     *
     * @param  ContratoDetalleRequest  $request  Petición validada con datos actualizados.
     * @param  int  $detalleId  Identificador del detalle a actualizar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el detalle actualizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function updateDetalle(ContratoDetalleRequest $request, int $detalleId): JsonResponse
    {
        $result = $this->contratoService->updateDetalle($detalleId, $request->decoded());

        return response()->json($result, $result['status']);
    }

    /**
     * Elimina un detalle individual de un contrato.
     *
     * @param  int  $detalleId  Identificador del detalle a eliminar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function destroyDetalle(int $detalleId): JsonResponse
    {
        $result = $this->contratoService->deleteDetalle($detalleId);

        return response()->json($result, $result['status']);
    }

    /**
     * Sincroniza los detalles de un contrato: reemplaza todos los detalles existentes
     * por una nueva lista enviada desde el frontend.
     *
     * @param  Request  $request  Petición HTTP con los datos validados.
     *                            - detalles (array): Lista de productos con valores.
     * @param  int  $contratoId  Identificador del contrato a actualizar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato actualizado o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncDetalles(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.medico_id' => 'required|integer|exists:users,id',
            'detalles.*.producto_id' => 'required|integer|exists:config_productos,id',
            'detalles.*.valor_contrato' => 'required|numeric|min:0',
            'detalles.*.valor_consulta' => 'required|numeric|min:0',
            'detalles.*.valor_urgencias' => 'required|numeric|min:0',
        ]);

        $result = $this->contratoService->syncDetalles($contratoId, $validated);

        return response()->json($result, $result['status']);
    }

    /**
     * Descarga la plantilla Excel para importar detalles de contratos.
     * El Excel incluye: info del contrato (fila 2), headers (fila 3),
     * productos CUPS con requi_lectura=1 (fila 4+), y desplegable de sedes.
     *
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns BinaryFileResponse Respuesta con el archivo Excel para descargar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function exportPlantilla(int $contratoId): BinaryFileResponse
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            abort(404, 'Contrato no encontrado');
        }

        $nombreSede = $contrato->sede->nom_sede ?? 'General';
        $nombreArchivo = "Plantilla_Contrato_{$contratoId}_{$nombreSede}.xlsx";

        return Excel::download(
            new ContratoPlantillaExport($contratoId),
            $nombreArchivo
        );
    }

    /**
     * Importa detalles de contratos desde un archivo Excel.
     * Lee el contrato_id del Excel, valida CUPS por fila,
     * y crea registros en contrato_detalle dentro de una transacción atómica.
     *
     * @param  Request  $request  Petición con archivo Excel y medico_id.
     * @param  int  $contratoId  Identificador del contrato (de la ruta).
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de éxito o error detallado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function importDetalles(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'medico_id' => 'required|integer|exists:users,id',
        ]);

        try {
            Excel::import(
                new ContratoDetalleImport($validated['medico_id']),
                $request->file('archivo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Detalles importados correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
