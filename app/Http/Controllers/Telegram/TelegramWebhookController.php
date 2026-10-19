<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotHandlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    /**
     * Inicializa el controlador inyectando el servicio de procesamiento de comandos.
     *
     * Flujo del proceso:
     * 1. Almacena la instancia de TelegramBotHandlerService.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct(
        protected TelegramBotHandlerService $botHandlerService
    ) {}

    /**
     * Recibe y procesa los eventos entrantes enviados por Telegram mediante Webhook.
     *
     * Flujo del proceso:
     * 1. Valida el token secreto de la cabecera X-Telegram-Bot-Api-Secret-Token si está configurado.
     * 2. Despacha el payload JSON al procesador de mensajes TelegramBotHandlerService.
     * 3. Retorna respuesta HTTP 200 con estado 'ok' para confirmarle a Telegram la entrega exitosa.
     *
     * @param  Request  $request  Petición HTTP entrante con el update de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function webhook(Request $request): JsonResponse
    {
        $secretToken = config("telegram.secret_token");
        $headerToken = $request->header("X-Telegram-Bot-Api-Secret-Token");

        if ($secretToken && $headerToken && ! hash_equals($secretToken, $headerToken)) {
            Log::warning("Telegram webhook: token secreto inválido", [
                "ip" => $request->ip(),
            ]);

            return response()->json(["error" => "Token inválido"], 403);
        }

        try {
            $update = $request->all();
            $this->botHandlerService->handleUpdate($update);
        } catch (\Throwable $e) {
            Log::error("Telegram webhook error: ".$e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);
        }

        return response()->json(["ok" => true]);
    }
}
