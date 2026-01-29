<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Dcm4chee\StudyController as Dcm4cheeStudyController;
use App\Models\Dcm4chee\Study;

class TeleRadiologiaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:estudio.asignar|estudio.transcribir');
    }

    /**
     * Lista estudios desde PACS (proxy a DCM4CHEE).
     *
     * Nota: este endpoint existe para el módulo de Tele-radiología.
     */
    public function listarEstudios(Request $request)
    {
        // Reutilizar el controlador de DCM4CHEE para evitar duplicar lógica
        return app(Dcm4cheeStudyController::class)->listarEstudios($request, new Study());
    }

    /**
     * Endpoint reservado para envío de estudios a teleradiología.
     *
     * Actualmente el flujo principal usa enviarOrdenTeleRadiologia + creación de estudio local.
     */
    public function enviarEstudio(Request $request)
    {
        return response()->json([
            'message' => 'Endpoint no implementado. Use /tele-radiologia-enviar-orden y /estudios.',
        ], 501);
    }

    /**
     * Envía una orden a la API externa de Teleradiología.
     *
     * @param Request $request Datos de la orden.
     * @return \Illuminate\Http\JsonResponse Respuesta de la API externa o error.
     */
    public function enviarOrdenTeleRadiologia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'orden' => 'required|array',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $orden = $request->input('orden');

            // Validar que la orden exista
            if (!$orden) {
                return response()->json(['message' => 'No se recibieron datos de la orden.'], 400);
            }

            $url = env('TELERADIOLOGIA_ORDEN_URL', 'https://ris.tlimagen.com/RISServices/integracion/salud_tolima/ordenes');

            // Realizar la petición a la API externa
            $response = Http::timeout((int) env('TELERADIOLOGIA_TIMEOUT', 30))
                ->retry((int) env('TELERADIOLOGIA_RETRIES', 1), 200)
                ->post($url, $orden);

            // Retornar la respuesta de la API externa al frontend
            if ($response->successful()) {
                $json = $response->json();

                // Normalizar "confirmation" para el frontend
                $confirmation = null;
                if (is_array($json)) {
                    $confirmation = $json['confirmation'] ?? $json['confirmation_id'] ?? $json['id'] ?? null;
                    if ($confirmation === null && isset($json['data']) && is_array($json['data'])) {
                        $confirmation = $json['data']['confirmation'] ?? $json['data']['id'] ?? null;
                    }
                }

                return response()->json([
                    'message' => $json['message'] ?? 'Orden enviada correctamente.',
                    'confirmation' => $confirmation,
                    'data' => $json,
                ], $response->status() === 201 ? 201 : 200);
            } else {
                return response()->json([
                    'message' => 'Error en la API externa',
                    'details' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno en el servidor proxy',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
