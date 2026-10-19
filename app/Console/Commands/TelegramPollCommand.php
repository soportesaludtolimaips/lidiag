<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotHandlerService;
use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = "telegram:poll 
                            {--once : Ejecuta una sola consulta de actualizaciones y finaliza}
                            {--delete-webhook : Elimina el webhook activo en Telegram para permitir polling}
                            {--set-webhook= : Registra una URL pública de webhook en Telegram}";

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = "Escucha y procesa comandos de Telegram mediante Long-Polling en desarrollo o gestiona el webhook.";

    /**
     * Ejecuta el comando de consola.
     *
     * Flujo del proceso:
     * 1. Valida si el bot cuenta con credenciales configuradas.
     * 2. Si se solicitó --set-webhook o --delete-webhook, ejecuta la acción y finaliza.
     * 3. Inicia bucle de Long-Polling consultando getUpdates cada 20 segundos.
     * 4. Despacha cada mensaje a TelegramBotHandlerService y avanza el offset.
     *
     * @param  TelegramService  $telegramService  Servicio de conexión a la API de Telegram.
     * @param  TelegramBotHandlerService  $botHandlerService  Servicio de procesamiento de comandos.
     * @return int Código de salida del comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function handle(TelegramService $telegramService, TelegramBotHandlerService $botHandlerService): int
    {
        if (! $telegramService->isConfigured()) {
            $this->error("❌ TELEGRAM_BOT_TOKEN no está configurado en el archivo .env.");

            return self::FAILURE;
        }

        // Caso: Configurar webhook
        $webhookUrl = $this->option("set-webhook");
        if ($webhookUrl) {
            $this->info("Registrando webhook en Telegram: {$webhookUrl} ...");
            $secret = config("telegram.secret_token");
            $res = $telegramService->setWebhook($webhookUrl, $secret);

            if ($res["ok"] ?? false) {
                $this->info("✅ Webhook configurado exitosamente.");

                return self::SUCCESS;
            }

            $this->error("❌ Error configurando webhook: ".($res["description"] ?? "Desconocido"));

            return self::FAILURE;
        }

        // Caso: Eliminar webhook
        if ($this->option("delete-webhook")) {
            $this->info("Eliminando webhook de Telegram ...");
            $res = $telegramService->deleteWebhook();
            $this->info("Resultado: ".json_encode($res));

            return self::SUCCESS;
        }

        $botInfo = $telegramService->getMe();
        if (! ($botInfo["ok"] ?? false)) {
            $this->error("❌ No fue posible conectar con Telegram Bot API: ".($botInfo["description"] ?? ""));

            return self::FAILURE;
        }

        $username = $botInfo["result"]["username"] ?? "Desconocido";
        $botName = $botInfo["result"]["first_name"] ?? "LIDiag Bot";

        $this->info("==================================================================");
        $this->info("🤖 BOT TELEGRAM LIDIAG INICIADO");
        $this->info("🏷️  Nombre:   {$botName}");
        $this->info("🔗 Username: @{$username}");
        $this->info("👥 Chat IDs autorizados: ".implode(", ", config("telegram.allowed_chat_ids", ["(todos)"])));
        $this->info("Presiona Ctrl+C para detener el servicio.");
        $this->info("==================================================================");

        $offset = null;
        $runOnce = (bool) $this->option("once");

        do {
            $response = $telegramService->getUpdates($offset, 20);

            if (! ($response["ok"] ?? false)) {
                $errorDesc = $response["description"] ?? $response["error"] ?? "Fallo en conexión";
                // Si hay un webhook activo en Telegram, advertir al desarrollador
                if (str_contains(strtolower($errorDesc), "webhook is active")) {
                    $this->warn("⚠️ Telegram indica que hay un Webhook activo. Usa: php artisan telegram:poll --delete-webhook");
                    sleep(5);

                    continue;
                }

                $this->warn("Aviso: {$errorDesc}. Reintentando en 3 segundos...");
                sleep(3);

                continue;
            }

            $updates = $response["result"] ?? [];

            foreach ($updates as $update) {
                $updateId = $update["update_id"] ?? 0;
                $offset = $updateId + 1;

                $from = $update["message"]["from"]["first_name"]
                    ?? $update["callback_query"]["from"]["first_name"]
                    ?? "Usuario";
                $text = $update["message"]["text"]
                    ?? $update["callback_query"]["data"]
                    ?? "(evento)";
                $chatId = $update["message"]["chat"]["id"]
                    ?? $update["callback_query"]["message"]["chat"]["id"]
                    ?? "N/A";

                $this->line("📩 [".now()->format("H:i:s")."] {$from} ({$chatId}): '{$text}'");

                try {
                    $botHandlerService->handleUpdate($update);
                } catch (\Throwable $e) {
                    $this->error("Error procesando actualización: ".$e->getMessage());
                }
            }

            if ($runOnce) {
                if ($offset !== null) {
                    $telegramService->getUpdates($offset, 0);
                }
                break;
            }
        } while (true);

        return self::SUCCESS;
    }
}
