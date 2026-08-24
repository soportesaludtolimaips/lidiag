<?php

namespace App\Http\Controllers\Contratacion;

use App\Exports\ContratoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratacion\ContratoRequest;
use App\Imports\ContratoMedicoImport;
use App\Imports\ContratoProductoImport;
use App\Services\Contratacion\ContratoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller ContratoController: API REST del módulo de contratación.
 *
 * Gestiona el CRUD de contratos, la descarga de la plantilla general,
 * la sincronización de médicos-producto y las importaciones Excel
 * (productos al contrato y médicos-producto).
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
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
     * Retorna paginación con sede, productos y médicos incluidos.
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
     * Muestra un contrato específico por su ID, con sede, productos y médicos.
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
     * Elimina un contrato por su ID junto con sus médicos y productos vinculados.
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
     * Descarga la plantilla general de productos desde config_productos.
     * Formato: CODIGO | ACTIVIDAD | Tarifa Actividad (todos los productos activos).
     * Si se envía ?contrato_id=, precarga el valor contratado existente en contratos_productos.
     *
     * @param  Request  $request  Petición HTTP con el parámetro opcional contrato_id.
     *
     * @returns BinaryFileResponse Respuesta con el archivo Excel para descargar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function exportPlantillaGeneral(Request $request): BinaryFileResponse
    {
        $contratoId = $request->query('contrato_id') !== null
            ? (int) $request->query('contrato_id')
            : null;

        $nombreArchivo = $contratoId
            ? "Plantilla_Contrato_{$contratoId}.xlsx"
            : 'Plantilla_General_Productos.xlsx';

        return Excel::download(
            new ContratoPlantillaExport($contratoId),
            $nombreArchivo
        );
    }

    /**
     * Importa productos a un contrato desde un archivo Excel.
     * Cada fila contiene CODIGO | ACTIVIDAD | Tarifa Actividad; se hace upsert
     * en contratos_productos por (contrato_id, producto_id).
     *
     * @param  Request  $request  Petición con el archivo Excel.
     * @param  int  $contratoId  Identificador del contrato destino.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de éxito o error detallado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function importarProductos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new ContratoProductoImport($contratoId);
            Excel::import($import, $request->file('archivo'));

            return response()->json([
                'success' => true,
                'message' => "Productos importados correctamente ({$import->importados} creados/actualizados).",
                'data' => ['importados' => $import->importados],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Sincroniza los médicos-producto de un contrato: reemplaza todos los registros
     * existentes por la lista enviada desde el frontend (delete all + insert).
     *
     * @param  Request  $request  Petición HTTP con los datos validados.
     *                            - medicos (array): Lista de vinculaciones médico-producto.
     * @param  int  $contratoId  Identificador del contrato a actualizar.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con el contrato actualizado o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'medicos' => 'required|array',
            'medicos.*.medico_id' => 'required|integer|exists:users,id',
            'medicos.*.producto_id' => 'required|integer|exists:config_productos,id',
            'medicos.*.valor_contrato' => 'required|numeric|min:0',
            'medicos.*.valor_consulta' => 'required|numeric|min:0',
            'medicos.*.valor_urgencias' => 'required|numeric|min:0',
        ]);

        $result = $this->contratoService->syncMedicos($contratoId, $validated);

        return response()->json($result, $result['status']);
    }

    /**
     * Importa médicos-producto a un contrato desde un archivo Excel.
     * El médico se selecciona previamente en el modal; cada fila del Excel contiene
     * CODIGO | ACTIVIDAD | Valor Contrato | Valor Consulta | Valor Urgencias.
     * Valida que el CUPS esté configurado en contratos_productos del contrato.
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
    public function importarMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'medico_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $import = new ContratoMedicoImport($contratoId, (int) $validated['medico_id']);
            Excel::import($import, $request->file('archivo'));

            return response()->json([
                'success' => true,
                'message' => "Detalle importado correctamente ({$import->importados} registros).",
                'data' => ['importados' => $import->importados],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
