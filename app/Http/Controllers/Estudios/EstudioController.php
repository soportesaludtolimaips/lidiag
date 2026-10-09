<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\GuardarTranscripcionRequest;
use App\Http\Requests\Estudios\LeerEstudioRequest;
use App\Http\Requests\Estudios\StoreEstudioRequest;
use App\Services\Estudios\EstudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EstudioController extends Controller
{
    /**
     * Inyecta el servicio de estudios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function __construct(
        private readonly EstudioService $estudioService,
    ) {}

    /**
     * Lista todos los estudios registrados en el sistema
     *
     * @returns JsonResponse Respuesta con la lista completa de estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function index(): JsonResponse
    {
        $result = $this->estudioService->all();

        return response()->json($result, $result['status']);
    }

    /**
     * Almacena un nuevo estudio en la base de datos.
     *
     * @param  StoreEstudioRequest  $request  - La solicitud HTTP que contiene los datos para crear el estudio.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
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
     * Lista los estudios pendientes de un médico específico en una sede determinada.
     *
     * @param  Request  $request  - La solicitud HTTP que contiene los parámetros necesarios para listar los estudios pendientes.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function listarPendientesMedico(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ]);

        $result = $this->estudioService->listarPendientesMedico(
            $request->integer('id'),
            $request->integer('sede_id'),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Marca un estudio como leído por un médico específico.
     *
     * @param  LeerEstudioRequest  $request  - La solicitud HTTP que contiene los parámetros necesarios para marcar el estudio como leído.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
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
     * Marca un estudio como visto.
     *
     * @param  int  $id  - El ID del estudio a marcar como visto.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
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
     * Lista los diagnósticos asociados a un estudio específico
     *
     * @param  Request  $request  Solicitud con el ID del estudio
     *
     * @returns JsonResponse Respuesta con la lista de diagnósticos del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarDiagnosticoPorEstudio(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:estudios,id',
        ]);

        $result = $this->estudioService->listarDiagnosticosPorEstudio(
            $request->integer('id')
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Lista los estudios pendientes de transcripción con filtros opcionales
     *
     * @param  Request  $request  Solicitud con filtros: sede_id, fechas, nombre/doc, quién asignó, médico
     *
     * @returns JsonResponse Respuesta con la lista de estudios pendientes de transcribir
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarPendientesTrascribir(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sede_id' => 'required|integer|exists:config_sedes,id',
                'bus_fech_ini' => 'nullable|date',
                'bus_fecha_fin' => 'nullable|date',
                'bus_nom_num_docu' => 'nullable|string|max:255',
                'quien_asigno_id' => 'nullable|integer|exists:users,id',
                'medico_id' => 'nullable|integer|exists:users,id',
                'destino_estudio' => 'nullable|string|max:255',
                'estudioParaTranscribir' => 'nullable|string',
            ], [
                'sede_id.required' => 'La sede es obligatoria.',
                'sede_id.exists' => 'La sede seleccionada no existe.',
            ]);

            $result = $this->estudioService->listarPendientesTranscribir($request->all());

            return response()->json($result, $result['status']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
            ], 500);
        }
    }

    /**
     * Lista los estudios asignados a médicos con filtros opcionales
     *
     * @param  Request  $request  Solicitud con filtros: sede_id, fechas, nombre/doc, quién asignó, médico
     *
     * @returns JsonResponse Respuesta con la lista de estudios asignados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function listarEstudiosAsignados(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'quien_asigno_id' => 'nullable|integer|exists:users,id',
            'medico_id' => 'nullable|integer|exists:users,id',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ]);

        $result = $this->estudioService->listarEstudiosAsignados($request->all());

        return response()->json($result, $result['status']);
    }

    /**
     * Guarda la transcripción de un estudio, actualiza paciente y genera reporte PDF
     *
     * @param  GuardarTranscripcionRequest  $request  Solicitud con datos completos: estudio_producto_id, lectura, datos paciente, sede, producto
     *
     * @returns JsonResponse Respuesta de confirmación de guardado
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
     * Lista los estudios pendientes por leer y transcribir sin filtros
     *
     * @returns JsonResponse Respuesta con la lista de estudios pendientes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function penditesPorLeerTranscribir(Request $request): JsonResponse
    {
        $result = $this->estudioService->pendientesPorLeerTranscribir();

        return response()->json($result, $result['status']);
    }

    /**
     * Busca si un paciente tiene estudios registrados por número de documento
     *
     * @param  Request  $request  Solicitud con el número de documento a buscar
     *
     * @returns JsonResponse Respuesta con indicador si tiene estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function buscarEstudioPorNumDocu(Request $request): JsonResponse
    {
        $request->validate([
            'numDocu' => 'required|string|max:50',
        ]);

        $result = $this->estudioService->buscarPorNumDocu($request->input('numDocu'));

        return response()->json($result, $result['status']);
    }

    /**
     * Anula la asignación de un producto de estudio (lo desactiva)
     *
     * @param  Request  $request  Solicitud con el ID del estudio producto a anular
     *
     * @returns JsonResponse Respuesta de confirmación de anulación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function anularAsignacion(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:estudios_productos,id',
        ], [
            'id.required' => 'El identificador del producto es obligatorio.',
            'id.exists' => 'El producto de estudio no existe.',
        ]);

        $result = $this->estudioService->anularAsignacion($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    /**
     * Descarga un archivo PDF de lectura de estudio
     *
     * @param  string  $nomArchivo  Nombre del archivo PDF a descargar
     *
     * @returns mixed Respuesta con el archivo o error si no existe
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function descargarPdfLectura(string $nomArchivo)
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
     * Envía notificación por email con los resultados del estudio
     *
     * @param  Request  $request  Solicitud con datos del estudio, paciente, sede y producto
     *
     * @returns JsonResponse Respuesta de confirmación del envío
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function notificarEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_estudio' => 'required|integer',
            'sede_id' => 'required|integer',
            'email' => 'required|email',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'lectura' => 'required|string',
            'estudio_producto_id' => 'required|integer',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ]);

        $result = $this->estudioService->notificarEmail($data);

        return response()->json($result, $result['status']);
    }

    /**
     * Envía los resultados de un estudio al portal de pacientes.
     *
     * @param  Request  $request  - La solicitud HTTP que contiene los datos necesarios para enviar los resultados del estudio.
     * @return JsonResponse - Una respuesta JSON que contiene el resultado de la operación, incluyendo el estado y cualquier mensaje relevante.
     *
     * @throws ValidationException - Si los datos proporcionados en la solicitud no cumplen con las reglas de validación definidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    public function enviarPortalPacientes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'lectura' => 'required|string',
            'estudio_producto_id' => 'required|integer',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ]);

        $result = $this->estudioService->enviarPortalPacientes($data);

        return response()->json($result, $result['status']);
    }
}
