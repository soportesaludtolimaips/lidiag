<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interfaces\EnviarOrdenTeleRadiologiaRequest;
use App\Services\Interfaces\TeleRadiologiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
     * Lista los estudios de tele-radiología disponibles.
     *
     * @param  Request  $request  Solicitud HTTP con parámetros de filtrado.
     *
     * @returns JsonResponse Respuesta JSON con la lista de estudios.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function listarEstudios(Request $request): JsonResponse
    {
        try {
            $result = $this->teleRadiologiaService->listarEstudios($request);

            return response()->json($result, $result['status']);
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
     * @param  Request  $request  Solicitud HTTP con payload JSON de la lectura.
     *
     * @returns JsonResponse Respuesta JSON con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2025-11-24
     */
    public function recibirLectura(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $data = json_decode($utf8Content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
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
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $result = $this->teleRadiologiaService->recibirLectura($data);

        return response()->json($result, $result['status']);
    }
}
