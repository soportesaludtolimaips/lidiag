<?php

namespace App\Http\Controllers\Admisiones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admisiones\AdminAdmisionAsignarLecturaRequest;
use App\Http\Requests\Admisiones\AdminAdmisionEstadoRequest;
use App\Http\Requests\Admisiones\AdminAdmisionIndexRequest;
use App\Http\Requests\Admisiones\AdminAdmisionStoreRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Admisiones\AdminAdmisionService;
use App\Services\Dcm4chee\WorklistService;
use App\Services\Estudios\EstudioService;

/**
 * Controlador para la trazabilidad de admisiones clínicas (admin_admisiones) y el módulo Pendientes de asignar.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionController extends Controller
{
    /**
     * Inyecta los servicios de admisiones, worklist y estudios y configura permisos Spatie por acción.
     *
     * Flujo del proceso:
     * 1. Asigna instancias inmutables de AdminAdmisionService, WorklistService y EstudioService.
     * 2. Restringe acciones de consulta, admisión y asignación según los permisos del sistema.
     *
     * @param  AdminAdmisionService  $admisionService  Servicio de dominio de admisiones.
     * @param  WorklistService  $worklistService  Servicio de Modality Worklist Dcm4chee.
     * @param  EstudioService  $estudioService  Servicio de asignación y lectura de estudios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function __construct(
        private readonly AdminAdmisionService $admisionService,
        private readonly WorklistService $worklistService,
        private readonly EstudioService $estudioService,
    ) {
        $this->middleware("permission:estudio.asignar|gestion.pacientes.index|estudio.index")->only([
            "listar",
            "show",
        ]);
        $this->middleware("permission:estudio.asignar")->only([
            "pendientesAsignar",
            "asignarLectura",
        ]);
        $this->middleware("permission:gestion.pacientes.create|gestion.pacientes.update|estudio.asignar")->only([
            "store",
            "actualizarEstado",
            "cancelar",
        ]);
    }

    /**
     * Lista registros de admin_admisiones aplicando filtros generales por sede, estado y fechas.
     *
     * Flujo del proceso:
     * 1. Valida los parámetros de búsqueda mediante AdminAdmisionIndexRequest.
     * 2. Invoca AdminAdmisionService::listar() y retorna ApiResponse estandarizada.
     *
     * @param  AdminAdmisionIndexRequest  $request  Solicitud con filtros validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function listar(AdminAdmisionIndexRequest $request): ApiResponse
    {
        $result = $this->admisionService->listar($request->validated());

        return ApiResponse::fromService($result);
    }

    /**
     * Lista las admisiones en estado PENDIENTE LECTURA para el módulo Pendientes de asignar.
     *
     * Flujo del proceso:
     * 1. Valida sede activa y filtros opcionales mediante AdminAdmisionIndexRequest.
     * 2. Consulta las admisiones listas para lectura sincronizadas con el workflow y PACS.
     * 3. Retorna ApiResponse con la colección de recursos AdminAdmisionResource.
     *
     * @param  AdminAdmisionIndexRequest  $request  Solicitud con criterios de búsqueda.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function pendientesAsignar(AdminAdmisionIndexRequest $request): ApiResponse
    {
        $result = $this->admisionService->listarPendientesAsignar(
            $request->validated(),
            $this->worklistService
        );

        return ApiResponse::fromService($result);
    }

    /**
     * Registra una nueva admisión y la envía al workflow de Modality Worklist.
     *
     * Flujo del proceso:
     * 1. Valida datos del paciente (local o interfaz), sede, examen y equipo mediante AdminAdmisionStoreRequest.
     * 2. Ejecuta el registro atómico de admisión y envío a Worklist.
     * 3. Retorna ApiResponse con código HTTP 201 o detalle de error.
     *
     * @param  AdminAdmisionStoreRequest  $request  Solicitud validada de admisión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function store(AdminAdmisionStoreRequest $request): ApiResponse
    {
        $usuarioId = (int) (auth()->id() ?: 1);
        $result = $this->admisionService->store(
            $request->validated(),
            $usuarioId,
            $this->worklistService
        );

        return ApiResponse::fromService($result);
    }

    /**
     * Retorna el detalle de una admisión específica.
     *
     * Flujo del proceso:
     * 1. Invoca AdminAdmisionService::show() con el ID recibido en ruta.
     * 2. Retorna ApiResponse con los datos serializados o HTTP 404.
     *
     * @param  int  $id  Identificador de admin_admisiones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function show(int $id): ApiResponse
    {
        $result = $this->admisionService->show($id);

        return ApiResponse::fromService($result);
    }

    /**
     * Actualiza el estado del flujo y los identificadores DICOM de una admisión.
     *
     * Flujo del proceso:
     * 1. Valida el estado y parámetros PACS con AdminAdmisionEstadoRequest.
     * 2. Ejecuta la transición auditada en AdminAdmisionService::actualizarEstado().
     * 3. Retorna ApiResponse con el recurso actualizado.
     *
     * @param  AdminAdmisionEstadoRequest  $request  Solicitud con el nuevo estado.
     * @param  int  $id  Identificador de la admisión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function actualizarEstado(AdminAdmisionEstadoRequest $request, int $id): ApiResponse
    {
        $result = $this->admisionService->actualizarEstado($id, $request->validated());

        return ApiResponse::fromService($result);
    }

    /**
     * Envía a lectura un estudio desde Pendientes de asignar conservando la trazabilidad del workflow.
     *
     * Flujo del proceso:
     * 1. Valida el JSON de asignación y los archivos adjuntos opcionales con AdminAdmisionAsignarLecturaRequest.
     * 2. Invoca AdminAdmisionService::asignarALectura() vinculando study_iuid, study_id y worklist_orden_id.
     * 3. Retorna ApiResponse confirmando la creación del estudio y el paso a estado ASIGNADO.
     *
     * @param  AdminAdmisionAsignarLecturaRequest  $request  Solicitud multipart de asignación.
     * @param  int  $id  Identificador del registro en admin_admisiones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function asignarLectura(AdminAdmisionAsignarLecturaRequest $request, int $id): ApiResponse
    {
        $result = $this->admisionService->asignarALectura(
            $id,
            $request->decoded(),
            $request->file("archivo1"),
            $request->file("archivo2"),
            $request->file("archivo3"),
            $this->estudioService,
        );

        return ApiResponse::fromService($result);
    }

    /**
     * Cancela una admisión activa y retira su orden de Worklist si aplica.
     *
     * Flujo del proceso:
     * 1. Obtiene el usuario autenticado que ejecuta la anulación.
     * 2. Invoca AdminAdmisionService::cancelar() y retorna ApiResponse.
     *
     * @param  int  $id  Identificador de la admisión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function cancelar(int $id): ApiResponse
    {
        $usuarioId = (int) (auth()->id() ?: 1);
        $result = $this->admisionService->cancelar($id, $usuarioId, $this->worklistService);

        return ApiResponse::fromService($result);
    }
}
