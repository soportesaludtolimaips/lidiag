<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dcm4chee\StudyListRequest;
use App\Http\Requests\Interfaces\EnviarOrdenTeleRadiologiaRequest;
use App\Services\Interfaces\TeleRadiologiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TeleRadiologiaController extends Controller
{
    /**
     * Constructor del controlador de tele-radiología.
     *
     * @param  TeleRadiologiaService  $teleRadiologiaService  Servicio de tele-radiología.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function __construct(
        private readonly TeleRadiologiaService $teleRadiologiaService,
    ) {}

    /**
     * Lista los estudios de tele-radiología disponibles consultando el PACS de la sede.
     *
     * Flujo del proceso:
     * 1. Valida los parámetros del filtro (sede_id, paciente/documento, rango de fechas).
     * 2. Delega la consulta a TeleRadiologiaService con los datos validados.
     * 3. Retorna la lista de estudios formateada en JSON con sustitución de caracteres UTF-8 inválidos.
     *
     * @param  StudyListRequest  $request  Solicitud HTTP con parámetros de filtrado validados.
     *
     * @returns JsonResponse Respuesta JSON con la lista de estudios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function listarEstudios(StudyListRequest $request): JsonResponse
    {
        try {
            $result = $this->teleRadiologiaService->listarEstudios($request->validated());

            return response()->json($result, $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envía un estudio de tele-radiología (endpoint no implementado).
     *
     * @param  Request  $request  Solicitud HTTP.
     *
     * @returns JsonResponse Respuesta JSON con error 501.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function enviarEstudio(Request $request): JsonResponse
    {
        $result = $this->teleRadiologiaService->enviarEstudio();

        return response()->json($result, $result['status']);
    }

    /**
     * Envía una orden de tele-radiología a la API externa.
     *
     * @param  EnviarOrdenTeleRadiologiaRequest  $request  Solicitud con datos de la orden.
     *
     * @returns JsonResponse Respuesta JSON con confirmación o error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function enviarOrdenTeleRadiologia(EnviarOrdenTeleRadiologiaRequest $request): JsonResponse
    {
        $result = $this->teleRadiologiaService->enviarOrdenTeleRadiologia($request->input('orden'));

        return response()->json($result, $result['status']);
    }

    /**
     * Recibe la lectura de tele-radiología desde la API externa y la persiste.
     *
     * Flujo del proceso:
     * 1. Decodifica el payload (probando primero UTF-8 nativo y fallback a ISO-8859-1).
     * 2. Registra en bitácora de Log::info el contenido recibido para auditoría y depuración.
     * 3. Valida la existencia de study_iuid, cod_cups y paciente/médico.
     * 4. Delega la persistencia a TeleRadiologiaService.
     *
     * @param  Request  $request  Solicitud HTTP con payload JSON de la lectura.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function recibirLectura(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();

        // 1. Intentar decodificar directamente en UTF-8 nativo
        $data = json_decode($rawContent, true);

        // 2. Si falla la decodificación por encoding, convertir desde ISO-8859-1
        if (json_last_error() !== JSON_ERROR_NONE) {
            $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
            $data = json_decode($utf8Content, true);
        }

        // 3. Fallback defensivo a request->all()
        if (! is_array($data)) {
            $data = $request->all();
        }

        Log::info('TeleRadiologiaController::recibirLectura - Payload recibido:', [
            'raw_length' => strlen($rawContent),
            'has_lectura' => isset($data['lectura']),
            'data_keys' => array_keys($data),
            'payload' => $data,
        ]);

        if (empty($data)) {
            return response()->json([
                'message' => 'Error al decodificar el payload JSON.',
                'error' => json_last_error_msg(),
            ], 400);
        }

        $validator = Validator::make($data, [
            'study_iuid' => 'required|string',
            'cod_cups' => 'required|string',
            'paciente.num_docu' => 'required|string',
            'medico.num_docu' => 'required_without:adendum.medico.num_docu|string',
        ]);

        if ($validator->fails()) {
            Log::warning('TeleRadiologiaController::recibirLectura - Validación fallida:', [
                'errors' => $validator->errors()->toArray(),
                'payload' => $data,
            ]);

            return response()->json(['errors' => $validator->errors()], 400);
        }

        $result = $this->teleRadiologiaService->recibirLectura($data);

        return response()->json($result, $result['status']);
    }
}
