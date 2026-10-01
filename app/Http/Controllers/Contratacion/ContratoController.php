<?php

namespace App\Http\Controllers\Contratacion;

use App\Exceptions\CupsFaltantesException;
use App\Exports\ContratoDetallePlantillaExport;
use App\Exports\ContratoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratacion\ContratoRequest;
use App\Imports\ContratoMedicoImport;
use App\Imports\ContratoProductoImport;
use App\Models\Contratacion\Contrato;
use App\Services\Contratacion\ContratoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
     * Flujo de confirmación: si el Excel trae CUPS inexistentes en config_productos
     * y no se envió crear_faltantes=1, responde 200 con requiere_confirmacion=true
     * y la lista de faltantes (sin importar nada). El frontend pregunta al usuario;
     * si acepta, reenvía el archivo con crear_faltantes=1.
     *
     * @param  Request  $request  Petición con el archivo Excel y el flag opcional crear_faltantes.
     * @param  int  $contratoId  Identificador del contrato destino.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de éxito, confirmación requerida o error detallado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function importarProductos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'crear_faltantes' => 'nullable|boolean',
        ]);

        try {
            $import = new ContratoProductoImport(
                $contratoId,
                $request->boolean('crear_faltantes')
            );
            Excel::import($import, $request->file('archivo'));

            return response()->json([
                'success' => true,
                'message' => "Productos importados correctamente ({$import->importados} creados/actualizados).",
                'data' => ['importados' => $import->importados],
            ], 200);
        } catch (CupsFaltantesException $e) {
            // Pre-validación: devolver faltantes sin importar nada (status 200 para no disparar toasts del interceptor)
            return response()->json([
                'success' => false,
                'requiere_confirmacion' => true,
                'message' => $e->getMessage(),
                'data' => ['faltantes' => $e->faltantes],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Aplica el diff del detalle de médicos del contrato.
     *
     * Flujo del proceso:
     * 1. Valida el payload con las reglas compartidas.
     * 2. Delega al servicio, que calcula y aplica el diff sin borrar.
     * 3. Devuelve el contrato actualizado o el error de validación.
     *
     * @param  Request  $request  Payload con medicos y retirar_faltantes opcional.
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato actualizado o error de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function syncMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $this->validarDetalleMedicos($request);

        $result = $this->contratoService->syncMedicos($contratoId, $validated);

        return response()->json($result, $result['status']);
    }

    /**
     * Reglas de validación del detalle de médicos-producto.
     *
     * Flujo del proceso:
     * 1. Exige la lista medicos con valores por ítem.
     * 2. Acepta retirar_faltantes opcional (sin él, el servicio usa false).
     * 3. Devuelve solo los campos con regla (medicos + retirar_faltantes si vino).
     *
     * @param  Request  $request  Petición con la lista medicos.
     * @return array<string, mixed> Datos ya validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function validarDetalleMedicos(Request $request): array
    {
        return $request->validate([
            'medicos' => 'required|array',
            'medicos.*.medico_id' => 'required|integer|exists:users,id',
            'medicos.*.producto_id' => 'required|integer|exists:config_productos,id',
            'medicos.*.valor_consulta' => 'required|numeric|min:0',
            'medicos.*.valor_urgencias' => 'required|numeric|min:0',
            'medicos.*.fecha_ingreso' => 'nullable|date',
            'retirar_faltantes' => 'sometimes|boolean',
        ]);
    }

    /**
     * Importa médicos-producto a un contrato desde un archivo Excel.
     * El médico se selecciona previamente en el modal; cada fila del Excel contiene
     * Código CUPS | Producto | Valor Consulta | Valor Urgencias (encabezados fila 3).
     * Valida que el Contrato ID en la celda C2 coincida con el contrato de la ruta
     * y que el CUPS esté configurado en contratos_productos del contrato.
     *
     * Flujo de confirmación: si el Excel trae CUPS inexistentes en config_productos
     * y no se envió crear_faltantes=1, responde 200 con requiere_confirmacion=true
     * y la lista de faltantes (sin importar nada). El frontend pregunta al usuario;
     * si acepta, reenvía el archivo con crear_faltantes=1.
     *
     * @param  Request  $request  Petición con archivo Excel, medico_id y flag opcional crear_faltantes.
     * @param  int  $contratoId  Identificador del contrato (de la ruta).
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de éxito, confirmación requerida o error detallado.
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
            'crear_faltantes' => 'nullable|boolean',
        ]);

        try {
            // Validar Contrato ID de la celda C2 contra el contrato de la ruta
            $excel = IOFactory::load($request->file('archivo')->getPathname());
            $c2Value = $excel->getActiveSheet()->getCell('C2')->getValue();

            if (! $c2Value || ! preg_match('/(\d+)/', (string) $c2Value, $matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el ID del contrato en el archivo Excel (celda C2).',
                ], 422);
            }

            $excelContratoId = (int) $matches[1];

            if ($excelContratoId !== $contratoId) {
                return response()->json([
                    'success' => false,
                    'message' => "El archivo no corresponde a este contrato. Se esperaba ID {$contratoId}, el archivo tiene ID {$excelContratoId}.",
                ], 422);
            }

            $import = new ContratoMedicoImport(
                $contratoId,
                (int) $validated['medico_id'],
                $request->boolean('crear_faltantes')
            );
            Excel::import($import, $request->file('archivo'));

            return response()->json([
                'success' => true,
                'message' => "Detalle importado correctamente ({$import->importados} registros).",
                'data' => ['importados' => $import->importados],
            ], 200);
        } catch (CupsFaltantesException $e) {
            // Pre-validación: devolver faltantes sin importar nada (status 200 para no disparar toasts del interceptor)
            return response()->json([
                'success' => false,
                'requiere_confirmacion' => true,
                'message' => $e->getMessage(),
                'data' => ['faltantes' => $e->faltantes],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Descarga la plantilla de detalle de un contrato para cargar médicos-producto.
     * Estructura: título (fila 1), Sede/Inicio/Contrato ID en C2 (fila 2),
     * encabezados (fila 3) y productos configurados en el contrato (filas 4+)
     * con las columnas de valores vacías para diligenciar por médico.
     *
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns BinaryFileResponse Respuesta con el archivo Excel para descargar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function exportPlantillaDetalle(int $contratoId): BinaryFileResponse
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            abort(404, 'Contrato no encontrado');
        }

        $nombreSede = $contrato->sede->nom_sede ?? 'General';
        $nombreArchivo = "Detalle_Contrato_{$contratoId}_{$nombreSede}.xlsx";

        return Excel::download(
            new ContratoDetallePlantillaExport($contratoId),
            $nombreArchivo
        );
    }

    /**
     * Informa qué productos del catálogo exigibles no tienen tarifa cargada.
     *
     * Flujo del proceso:
     * 1. Delega al servicio el cálculo de cobertura del contrato.
     * 2. Devuelve totales y lista de faltantes con su código.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Totales de cobertura y lista de faltantes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function cobertura(int $id): JsonResponse
    {
        $result = $this->contratoService->cobertura($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Previsualiza el cambio del detalle de médicos sin aplicarlo.
     *
     * Flujo del proceso:
     * 1. Valida el payload con las reglas compartidas.
     * 2. Pasa solo los items al servicio, que calcula el diff sin escribir.
     * 3. Devuelve el contrato más el diff para confirmación del usuario.
     *
     * @param  Request  $request  Payload con la lista medicos.
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato más el diff calculado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(Request $request, int $contratoId): JsonResponse
    {
        $validated = $this->validarDetalleMedicos($request);

        $result = $this->contratoService->previewMedicos($contratoId, $validated['medicos']);

        return response()->json($result, $result['status']);
    }
}
