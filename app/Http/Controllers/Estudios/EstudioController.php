<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\AnularAsignacionEstudioRequest;
use App\Http\Requests\Estudios\BuscarEstudioNumDocuRequest;
use App\Http\Requests\Estudios\EnviarPortalPacientesRequest;
use App\Http\Requests\Estudios\GuardarTranscripcionRequest;
use App\Http\Requests\Estudios\LeerEstudioRequest;
use App\Http\Requests\Estudios\ListarDiagnosticoEstudioRequest;
use App\Http\Requests\Estudios\ListarEstudiosAsignadosRequest;
use App\Http\Requests\Estudios\ListarPendientesMedicoRequest;
use App\Http\Requests\Estudios\ListarPendientesTranscribirRequest;
use App\Http\Requests\Estudios\NotificarEmailEstudioRequest;
use App\Http\Requests\Estudios\StoreEstudioRequest;
use App\Services\Estudios\EstudioQueryService;
use App\Services\Estudios\EstudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EstudioController extends Controller
{
    /**
     * Inyecta los servicios de dominio y consultas de estudios.
     *
     * @param  EstudioService  $estudioService  Servicio de mutación y ciclo de vida de estudios
     * @param  EstudioQueryService  $queryService  Servicio de consultas especializadas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        private readonly EstudioService $estudioService,
        private readonly EstudioQueryService $queryService,
    ) {}

    /**
     * Lista todos los estudios registrados en el sistema.
     *
     * @return JsonResponse Respuesta con la colección de estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->queryService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Almacena un nuevo estudio con paciente, productos, diagnósticos y soportes.
     *
     * @param  StoreEstudioRequest  $request  Solicitud validada de creación de estudio
     * @return JsonResponse Resultado de la asignación del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function store(StoreEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioService->create(
            $request->decoded(),
            $request->file('archivo1'),
            $request->file('archivo2'),
            $request->file('archivo3'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los estudios pendientes de lectura asignados a un médico en una sede.
     *
     * @param  ListarPendientesMedicoRequest  $request  Solicitud validada con médico y sede
     * @return JsonResponse Lista de estudios pendientes priorizados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function listarPendientesMedico(ListarPendientesMedicoRequest $request): JsonResponse
    {
        $result = $this->queryService->listarPendientesMedico(
            $request->integer('id'),
            $request->integer('sede_id'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Registra la lectura interpretativa de un estudio por el médico asignado.
     *
     * @param  LeerEstudioRequest  $request  Solicitud validada con producto y lectura
     * @return JsonResponse Confirmación del registro de lectura
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function leerEstudio(LeerEstudioRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->estudioService->leerEstudio(
            (int) $data['estudio_producto_id'],
            $data['lectura'],
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Marca un producto de estudio como visto por el médico.
     *
     * @param  int  $id  ID del producto de estudio a marcar
     * @return JsonResponse Confirmación de marcado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function marcarEstudioVisto(int $id): JsonResponse
    {
        $result = $this->estudioService->marcarVisto($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los diagnósticos CIE-10 asociados a un estudio.
     *
     * @param  ListarDiagnosticoEstudioRequest  $request  Solicitud validada con ID de estudio
     * @return JsonResponse Colección de diagnósticos asociados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarDiagnosticoPorEstudio(ListarDiagnosticoEstudioRequest $request): JsonResponse
    {
        $result = $this->queryService->listarDiagnosticosPorEstudio(
            $request->integer('id')
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los estudios pendientes de transcripción aplicando filtros de negocio.
     *
     * @param  ListarPendientesTranscribirRequest  $request  Solicitud validada con filtros
     * @return JsonResponse Lista de estudios pendientes para transcribir
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarPendientesTrascribir(ListarPendientesTranscribirRequest $request): JsonResponse
    {
        $result = $this->queryService->listarPendientesTranscribir($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los estudios asignados a médicos con filtros de búsqueda y paginación.
     *
     * @param  ListarEstudiosAsignadosRequest  $request  Solicitud validada con filtros
     * @return JsonResponse Lista de estudios asignados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarEstudiosAsignados(ListarEstudiosAsignadosRequest $request): JsonResponse
    {
        $result = $this->queryService->listarEstudiosAsignados($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Guarda la transcripción final de un estudio y genera el informe clínico oficial.
     *
     * @param  GuardarTranscripcionRequest  $request  Solicitud validada con datos de transcripción
     * @return JsonResponse Confirmación del guardado de transcripción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function guardarTranscripcion(GuardarTranscripcionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['fechor_lectura'] = $request->input('fechor_lectura', $request->input('fec_lectura'));
        $data['fechor_trascrito'] = $request->input('fechor_trascrito', Carbon::now()->format('Y-m-d H:i:s'));

        $result = $this->estudioService->guardarTranscripcion($data);

        return response()->json($result, $result['status']);
    }

    /**
     * Lista estudios pendientes por leer y transcribir globalmente.
     *
     * @return JsonResponse Lista global de pendientes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function penditesPorLeerTranscribir(): JsonResponse
    {
        $result = $this->queryService->pendientesPorLeerTranscribir();

        return response()->json($result, $result['status']);
    }

    /**
     * Verifica si un paciente registra historial de estudios según su documento.
     *
     * @param  BuscarEstudioNumDocuRequest  $request  Solicitud validada con número de documento
     * @return JsonResponse Indicador booleano de existencia de estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function buscarEstudioPorNumDocu(BuscarEstudioNumDocuRequest $request): JsonResponse
    {
        $result = $this->queryService->buscarPorNumDocu($request->validated('numDocu'));

        return response()->json($result, $result['status']);
    }

    /**
     * Anula la asignación activa de un producto de estudio.
     *
     * @param  AnularAsignacionEstudioRequest  $request  Solicitud validada con ID de producto
     * @return JsonResponse Confirmación de anulación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function anularAsignacion(AnularAsignacionEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioService->anularAsignacion($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Transmite el archivo binario PDF de lectura oficial para descarga.
     *
     * @param  string  $nomArchivo  Nombre seguro del archivo PDF solicitado
     * @return BinaryFileResponse|JsonResponse Flujo de descarga o error JSON
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function descargarPdfLectura(string $nomArchivo): BinaryFileResponse|JsonResponse
    {
        $basename = basename($nomArchivo);
        if ($basename !== $nomArchivo) {
            abort(400, 'Nombre de archivo inválido');
        }

        $result = $this->estudioService->descargarPdfLectura($basename);

        if (! $result['success']) {
            return response()->json($result, $result['status']);
        }

        return response()->file($result['data']['path']);
    }

    /**
     * Despacha notificación por email con el informe radiológico al paciente o entidad.
     *
     * @param  NotificarEmailEstudioRequest  $request  Solicitud validada con datos de notificación
     * @return JsonResponse Confirmación del despacho
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function notificarEmail(NotificarEmailEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioService->notificarEmail($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Sincroniza y envía los resultados del estudio al portal web de pacientes.
     *
     * @param  EnviarPortalPacientesRequest  $request  Solicitud validada con datos del informe
     * @return JsonResponse Confirmación del envío al portal
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function enviarPortalPacientes(EnviarPortalPacientesRequest $request): JsonResponse
    {
        $result = $this->estudioService->enviarPortalPacientes($request->validated());

        return response()->json($result, $result['status']);
    }

    /**
     * Envía manualmente un producto de estudio a SAHI (SQL Server).
     *
     * @param  Request  $request  Solicitud HTTP con estudio_producto_id
     * @return JsonResponse Confirmación y resultado del despacho a SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function enviarSahiEstudioProducto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
        ]);

        $result = $this->estudioService->enviarSahiEstudioProducto((int) $validated['estudio_producto_id']);

        return response()->json($result, $result['status'] ?? 200);
    }
}
