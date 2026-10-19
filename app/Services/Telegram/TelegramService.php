<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $token;

    protected string $baseUrl;

    protected bool $sslVerify;

    /**
     * Inicializa el servicio con el token de Telegram desde la configuración.
     *
     * Flujo del proceso:
     * 1. Lee el token configurado en config('telegram.bot_token').
     * 2. Configura la URL base de la API de Telegram Bot.
     * 3. Establece la política de verificación SSL.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct()
    {
        $this->token = config("telegram.bot_token");
        $this->baseUrl = "https://api.telegram.org/bot{$this->token}";
        $this->sslVerify = (bool) config("telegram.ssl_verify", false);
    }

    /**
     * Crea una instancia de cliente HTTP configurada con timeouts y SSL según el entorno.
     *
     * Flujo del proceso:
     * 1. Inicializa PendingRequest con timeout específico.
     * 2. Deshabilita verificación SSL si sslVerify es false (útil en Laragon/Windows).
     *
     * @param  int  $timeout  Segundos de espera antes de timeout.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function client(int $timeout = 15): PendingRequest
    {
        $client = Http::timeout($timeout);

        if (! $this->sslVerify) {
            $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * Determina si el servicio cuenta con un token de bot configurado.
     *
     * Flujo del proceso:
     * 1. Verifica si la propiedad token contiene un valor no vacío.
     *
     * @return bool Verdadero si está configurado el token.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function isConfigured(): bool
    {
        return ! empty($this->token);
    }

    /**
     * Envía un mensaje de texto con formato HTML o Markdown a un chat de Telegram.
     *
     * Flujo del proceso:
     * 1. Valida la presencia del token del bot.
     * 2. Estructura el payload con chat_id, texto, parse_mode y teclado opcional.
     * 3. Ejecuta la petición POST al endpoint sendMessage.
     * 4. Registra en log cualquier error de red o respuesta HTTP inválida.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  string  $text  Contenido del mensaje a enviar.
     * @param  string  $parseMode  Modo de formateo (HTML o MarkdownV2).
     * @param  array|null  $replyMarkup  Teclado interactivo opcional (InlineKeyboardMarkup o ReplyKeyboardMarkup).
     * @return array Respuesta decodificada de Telegram con estado 'ok'.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function sendMessage(string $chatId, string $text, string $parseMode = "HTML", ?array $replyMarkup = null): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "description" => "TELEGRAM_BOT_TOKEN no configurado."];
        }

        $payload = [
            "chat_id" => $chatId,
            "text" => $text,
            "parse_mode" => $parseMode,
            "disable_web_page_preview" => true,
        ];

        if ($replyMarkup) {
            $payload["reply_markup"] = $replyMarkup;
        }

        try {
            $response = $this->client(15)->post("{$this->baseUrl}/sendMessage", $payload);

            return $response->json() ?? ["ok" => false, "description" => "Respuesta vacía de Telegram."];
        } catch (\Throwable $e) {
            Log::error("TelegramService::sendMessage error: ".$e->getMessage(), [
                "chat_id" => $chatId,
            ]);

            return ["ok" => false, "description" => $e->getMessage()];
        }
    }

    /**
     * Envía un archivo adjunto (documento Excel, PDF, etc.) a un chat de Telegram.
     *
     * Flujo del proceso:
     * 1. Valida la existencia física del archivo local.
     * 2. Adjunta el archivo binario mediante multipart form data al endpoint sendDocument.
     * 3. Añade pie de foto (caption) y modo de parseo si fueron suministrados.
     * 4. Retorna la respuesta de la API de Telegram.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  string  $filePath  Ruta absoluta del archivo a enviar en el servidor.
     * @param  string|null  $caption  Texto explicativo opcional como pie del documento.
     * @param  string  $parseMode  Modo de formateo del pie de foto (HTML por defecto).
     * @return array Respuesta decodificada de Telegram con estado 'ok'.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function sendDocument(string $chatId, string $filePath, ?string $caption = null, string $parseMode = "HTML"): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "description" => "TELEGRAM_BOT_TOKEN no configurado."];
        }

        if (! file_exists($filePath)) {
            Log::error("TelegramService::sendDocument archivo no encontrado: {$filePath}");

            return ["ok" => false, "description" => "Archivo local no encontrado."];
        }

        try {
            $request = $this->client(60)
                ->attach("document", file_get_contents($filePath), basename($filePath));

            $data = [
                "chat_id" => $chatId,
            ];

            if ($caption) {
                $data["caption"] = $caption;
                $data["parse_mode"] = $parseMode;
            }

            $response = $request->post("{$this->baseUrl}/sendDocument", $data);

            return $response->json() ?? ["ok" => false, "description" => "Respuesta vacía de Telegram."];
        } catch (\Throwable $e) {
            Log::error("TelegramService::sendDocument error: ".$e->getMessage(), [
                "chat_id" => $chatId,
                "file" => $filePath,
            ]);

            return ["ok" => false, "description" => $e->getMessage()];
        }
    }

    /**
     * Notifica el estado de acción del bot (ej. 'typing', 'upload_document').
     *
     * Flujo del proceso:
     * 1. Ejecuta POST al endpoint sendChatAction para mostrar retroalimentación en la app de Telegram.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  string  $action  Acción a reflejar ('typing', 'upload_document', etc.).
     * @return array Respuesta decodificada de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function sendChatAction(string $chatId, string $action = "typing"): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false];
        }

        try {
            $response = $this->client(5)->post("{$this->baseUrl}/sendChatAction", [
                "chat_id" => $chatId,
                "action" => $action,
            ]);

            return $response->json() ?? ["ok" => false];
        } catch (\Throwable $e) {
            return ["ok" => false, "description" => $e->getMessage()];
        }
    }

    /**
     * Consulta actualizaciones pendientes mediante Long-Polling (usado en comandos de consola/desarrollo).
     *
     * Flujo del proceso:
     * 1. Envía petición al endpoint getUpdates con offset y tiempo de espera timeout.
     * 2. Retorna el listado de updates pendientes enviados por los usuarios.
     *
     * @param  int|null  $offset  Identificador del último update procesado + 1.
     * @param  int  $timeout  Segundos de espera en el servidor de Telegram (Long-Polling).
     * @return array Lista de actualizaciones entregadas por Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function getUpdates(?int $offset = null, int $timeout = 20): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "result" => []];
        }

        $params = ["timeout" => $timeout];
        if ($offset !== null) {
            $params["offset"] = $offset;
        }

        try {
            $response = $this->client($timeout + 5)->get("{$this->baseUrl}/getUpdates", $params);

            return $response->json() ?? ["ok" => false, "result" => []];
        } catch (\Throwable $e) {
            return ["ok" => false, "result" => [], "error" => $e->getMessage()];
        }
    }

    /**
     * Registra la URL del webhook en los servidores de Telegram.
     *
     * Flujo del proceso:
     * 1. Envía la URL y el token secreto de verificación al endpoint setWebhook.
     *
     * @param  string  $url  URL pública HTTPS donde Telegram despachará los eventos.
     * @param  string|null  $secretToken  Token secreto opcional para validación de cabecera.
     * @return array Respuesta de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "description" => "TELEGRAM_BOT_TOKEN no configurado."];
        }

        $data = ["url" => $url];
        if ($secretToken) {
            $data["secret_token"] = $secretToken;
        }

        try {
            $response = $this->client(10)->post("{$this->baseUrl}/setWebhook", $data);

            return $response->json() ?? ["ok" => false];
        } catch (\Throwable $e) {
            return ["ok" => false, "description" => $e->getMessage()];
        }
    }

    /**
     * Elimina el webhook registrado para permitir Long-Polling.
     *
     * Flujo del proceso:
     * 1. Envía petición al endpoint deleteWebhook.
     *
     * @return array Respuesta de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function deleteWebhook(): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "description" => "TELEGRAM_BOT_TOKEN no configurado."];
        }

        try {
            $response = $this->client(10)->post("{$this->baseUrl}/deleteWebhook");

            return $response->json() ?? ["ok" => false];
        } catch (\Throwable $e) {
            return ["ok" => false, "description" => $e->getMessage()];
        }
    }

    /**
     * Consulta información general del bot autenticado.
     *
     * Flujo del proceso:
     * 1. Envía petición al endpoint getMe para validar conectividad y credenciales.
     *
     * @return array Respuesta con datos del bot (id, username, first_name).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function getMe(): array
    {
        if (! $this->isConfigured()) {
            return ["ok" => false, "description" => "TELEGRAM_BOT_TOKEN no configurado."];
        }

        try {
            $response = $this->client(10)->get("{$this->baseUrl}/getMe");

            return $response->json() ?? ["ok" => false];
        } catch (\Throwable $e) {
            return ["ok" => false, "description" => $e->getMessage()];
        }
    }
}
