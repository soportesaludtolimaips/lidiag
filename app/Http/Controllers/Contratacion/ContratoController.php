<?php

namespace App\Http\Controllers\Contratacion;

use App\Exports\ContratoDetallePlantillaExport;
use App\Exports\ContratoEntidadPlantillaExport;
use App\Exports\ContratoMedicoPlantillaExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratacion\ContratoConfirmarEntidadRequest;
use App\Http\Requests\Contratacion\ContratoConfirmarMedicoRequest;
use App\Http\Requests\Contratacion\ContratoImportarMedicosRequest;
use App\Http\Requests\Contratacion\ContratoIndexRequest;
use App\Http\Requests\Contratacion\ContratoPreviewEntidadRequest;
use App\Http\Requests\Contratacion\ContratoPreviewMedicoRequest;
use App\Http\Requests\Contratacion\ContratoRequest;
use App\Http\Requests\Contratacion\ContratoSyncMedicosRequest;
use App\Models\Contratacion\Contrato;
use App\Models\User;
use App\Services\Contratacion\ContratoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller ContratoController: API REST del módulo de contratación.
 *
 * Gestiona el CRUD de contratos, la descarga de la plantilla de detalle,
 * la sincronización de médicos-producto y la importación Excel
 * de médicos-producto.
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
        $this->middleware('permission:contratos.show')->only(['show', 'listarMedicosProductos']);
        $this->middleware('permission:contratos.edit')->only(['update']);
        $this->middleware('permission:contratos.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los contratos con filtros opcionales (sede_id, estado, q).
     * Retorna paginación con sede, productos y médicos incluidos.
     *
     * @param  ContratoIndexRequest  $request  Petición validada con filtros opcionales.
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON con listado paginado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function index(ContratoIndexRequest $request): JsonResponse
    {
        $result = $this->contratoService->list($request->validated());

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
     * Aplica el diff del detalle de médicos del contrato.
     *
     * Flujo del proceso:
     * 1. Valida el payload con ContratoSyncMedicosRequest.
     * 2. Delega al servicio, que calcula y aplica el diff sin borrar.
     * 3. Devuelve el contrato actualizado o el error de validación.
     *
     * @param  ContratoSyncMedicosRequest  $request  Payload validado con medicos y retirar_faltantes opcional.
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato actualizado o error de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function syncMedicos(ContratoSyncMedicosRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->syncMedicos($contratoId, $request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Importa médicos-producto a un contrato desde un archivo Excel.
     *
     * Flujo del proceso:
     * 1. Valida archivo, médico y opción crear_faltantes con ContratoImportarMedicosRequest.
     * 2. Delega la validación y carga del archivo Excel a ContratoService::importarMedicos().
     * 3. Retorna la respuesta generada por el servicio.
     *
     * @param  ContratoImportarMedicosRequest  $request  Petición validada con archivo, medico_id y crear_faltantes.
     * @param  int  $contratoId  Identificador del contrato (de la ruta).
     *
     * @returns \Illuminate\Http\JsonResponse Respuesta JSON de éxito, confirmación requerida o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function importarMedicos(ContratoImportarMedicosRequest $request, int $contratoId): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->contratoService->importarMedicos(
            $contratoId,
            $request->file('archivo'),
            (int) $validated['medico_id'],
            $request->boolean('crear_faltantes')
        );

        return response()->json($result, (int) $result['status']);
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
     * 1. Valida el payload con ContratoSyncMedicosRequest.
     * 2. Pasa solo los items al servicio, que calcula el diff sin escribir.
     * 3. Devuelve el contrato más el diff para confirmación del usuario.
     *
     * @param  ContratoSyncMedicosRequest  $request  Payload con la lista medicos.
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @returns \Illuminate\Http\JsonResponse Contrato más el diff calculado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(ContratoSyncMedicosRequest $request, int $contratoId): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->contratoService->previewMedicos($contratoId, $validated['medicos']);

        return response()->json($result, $result['status']);
    }

    /**
     * Descarga la Plantilla 1: Tarifas de Entidad / Hospital en formato Excel.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @return BinaryFileResponse Descarga del archivo Excel.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function exportPlantillaEntidad(int $contratoId): BinaryFileResponse
    {
        $contrato = Contrato::with('sede')->find($contratoId);

        if (! $contrato) {
            abort(404, 'Contrato no encontrado');
        }

        $nombreSede = $contrato->sede->nom_sede ?? 'General';
        $nombreSedeLimpio = str_replace(' ', '_', $nombreSede);
        $nombreArchivo = "Tarifas_Entidad_Contrato_{$contratoId}_{$nombreSedeLimpio}.xlsx";

        return Excel::download(
            new ContratoEntidadPlantillaExport($contratoId),
            $nombreArchivo
        );
    }

    /**
     * Descarga la Plantilla 2: Tarifas de Médico Lector en formato Excel.
     *
     * @param  int  $contratoId  Identificador del contrato.
     * @param  int  $medicoId  Identificador del médico.
     * @return BinaryFileResponse Descarga del archivo Excel.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function exportPlantillaMedico(int $contratoId, int $medicoId): BinaryFileResponse
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            abort(404, 'Contrato no encontrado');
        }

        $medico = User::find($medicoId);

        if (! $medico) {
            abort(404, 'Médico no encontrado');
        }

        $nombreMedicoLimpio = str_replace(' ', '_', $medico->name);
        $nombreArchivo = "Tarifas_Medico_{$medicoId}_{$nombreMedicoLimpio}_Contrato_{$contratoId}.xlsx";

        return Excel::download(
            new ContratoMedicoPlantillaExport($contratoId, $medicoId),
            $nombreArchivo
        );
    }

    /**
     * Procesa la vista previa (diff no destructivo y cobertura) para la Plantilla 1 (Entidad).
     *
     * @param  ContratoPreviewEntidadRequest  $request  Petición validada con el archivo Excel.
     * @param  int  $contratoId  Identificador del contrato.
     * @return JsonResponse Resultado del diff y cobertura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function previewImportarEntidad(ContratoPreviewEntidadRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->previewImportarEntidad($contratoId, $request->file('archivo'));

        return response()->json($result, (int) ($result['status'] ?? 200));
    }

    /**
     * Confirma y persiste atómicamente el diff de tarifas de entidad (altas, cambios y bajas lógicas).
     *
     * @param  ContratoConfirmarEntidadRequest  $request  Datos validados para persistencia.
     * @param  int  $contratoId  Identificador del contrato.
     * @return JsonResponse Resultado de la persistencia.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function confirmarImportarEntidad(ContratoConfirmarEntidadRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->confirmarImportarEntidad($contratoId, $request->validated());

        return response()->json($result, (int) ($result['status'] ?? 200));
    }

    /**
     * Procesa la vista previa (diff no destructivo y cobertura) para la Plantilla 2 (Médicos Lectores)
     * con validación estricta de celda A4 (médico) y A2 (contrato).
     *
     * @param  ContratoPreviewMedicoRequest  $request  Petición validada con archivo y medico_id.
     * @param  int  $contratoId  Identificador del contrato.
     * @return JsonResponse Resultado del diff y cobertura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function previewImportarMedicos(ContratoPreviewMedicoRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->previewImportarMedicos(
            $contratoId,
            $request->file('archivo'),
            (int) $request->input('medico_id')
        );

        return response()->json($result, (int) ($result['status'] ?? 200));
    }

    /**
     * Confirma y persiste atómicamente el diff de tarifas de médicos lectores (altas, cambios y bajas lógicas).
     *
     * @param  ContratoConfirmarMedicoRequest  $request  Datos validados para persistencia.
     * @param  int  $contratoId  Identificador del contrato.
     * @return JsonResponse Resultado de la persistencia.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function confirmarImportarMedicos(ContratoConfirmarMedicoRequest $request, int $contratoId): JsonResponse
    {
        $result = $this->contratoService->confirmarImportarMedicos($contratoId, $request->validated());

        return response()->json($result, (int) ($result['status'] ?? 200));
    }

    /**
     * Retorna el listado de médicos vinculados y productos/tarifas del contrato para el drawer,
     * permitiendo filtrado condicional reactivo por médico lector.
     *
     * @param  Request  $request  Petición HTTP con parámetro opcional medico_id.
     * @param  int  $contratoId  Identificador del contrato.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function listarMedicosProductos(Request $request, int $contratoId): JsonResponse
    {
        $medicoId = $request->filled('medico_id') ? (int) $request->input('medico_id') : null;
        $result = $this->contratoService->listarMedicosProductos($contratoId, $medicoId);

        return response()->json($result, (int) ($result['status'] ?? 200));
    }
}
