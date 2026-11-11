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
                            {--diagnose : Ejecuta diagnóstico de red, DNS y conectividad hacia Telegram}
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

        // Caso: Diagnóstico de conectividad
        if ($this->option("diagnose")) {
            $this->ejecutarDiagnostico($telegramService);

            return self::SUCCESS;
        }

        $botInfo = $telegramService->getMe();
        if (! ($botInfo["ok"] ?? false)) {
            $errorDesc = $botInfo["description"] ?? "Fallo en conexión";
            $this->error("❌ No fue posible conectar con Telegram Bot API: {$errorDesc}");

            $this->newLine();
            $this->ejecutarDiagnostico($telegramService, true);

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

        $this->info("Registrando menú de comandos nativos en Telegram...");
        $botHandlerService->registerBotCommands();

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

    /**
     * Ejecuta e imprime en consola el diagnóstico detallado de conectividad hacia Telegram.
     *
     * Flujo del proceso:
     * 1. Consulta el estado de red, DNS y socket TCP a través de TelegramService.
     * 2. Imprime resultados de host, IP, socket y respuesta HTTP.
     * 3. Si hay fallas, detalla las causas más probables en entornos de producción.
     *
     * @param  TelegramService  $telegramService  Instancia del servicio de Telegram.
     * @param  bool  $isFailureReport  Indica si se llama tras un fallo en handle().
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function ejecutarDiagnostico(TelegramService $telegramService, bool $isFailureReport = false): void
    {
        $this->info("==================================================================");
        $this->info("🔍 DIAGNÓSTICO DE CONECTIVIDAD TELEGRAM — LIDIAG");
        $this->info("==================================================================");

        $diag = $telegramService->testConnectivity();

        $this->line("🌐 Host destino:          {$diag['host']}:{$diag['port']}");
        $this->line("📍 Resolución DNS:        ".($diag['dns_ok'] ? "✅ OK (".implode(", ", $diag['dns_ips']).")" : "❌ FALLÓ (DNS no resuelve)"));
        $this->line("🔌 Socket TCP (Puerto {$diag['port']}): ".($diag['socket_ok'] ? "✅ Conectado ({$diag['socket_time_ms']}ms)" : "❌ BLOQUEADO ({$diag['socket_error']})"));
        $this->line("⚡ Forzar IPv4:           ".($diag['force_ipv4'] ? "✅ Activo (evita cuelgues IPv6)" : "⚪ Desactivado"));
        $this->line("🛡️ Proxy configurado:     ".($diag['proxy'] ?: "⚪ Ninguno (conexión directa)"));
        $this->line("🔑 Token configurado:     ".($diag['token_configured'] ? "✅ Presente en .env" : "❌ Ausente en .env"));
        $this->line("🤖 Respuesta Telegram:    ".($diag['api_ok'] ? "✅ OK (Bot autenticado)" : "❌ Falló respuesta HTTP"));

        if (! $diag['socket_ok'] || ! $diag['api_ok']) {
            $this->newLine();
            $this->warn("⚠️  CAUSAS FRECUENTES Y SOLUCIONES EN PRODUCCIÓN:");

            if (! $diag['socket_ok']) {
                $this->line("  1. 🔒 FIREWALL DEL HOSPITAL / SERVIDOR BLOQUEADO:");
                $this->line("     El cortafuegos perimetral (Fortinet, Sophos, pfSense) o Windows Defender");
                $this->line("     está bloqueando la salida al puerto 443 hacia servidores de Telegram.");
                $this->line("     👉 Solución A: Solicitar a infraestructura permitir salida TCP 443 hacia {$diag['host']}.");
                $this->line("     👉 Solución B: Si el hospital usa proxy corporativo, añadir a .env:");
                $this->line("        TELEGRAM_PROXY=http://ip-proxy:puerto");
            } else {
                $this->line("  1. ⏱️ TIMEOUT DE RED (cURL error 28):");
                $this->line("     La conexión inicial tardó más del tiempo límite configurado.");
                $this->line("     👉 Solución: Aumentar timeouts en .env agregando:");
                $this->line("        TELEGRAM_CONNECT_TIMEOUT=20");
                $this->line("        TELEGRAM_TIMEOUT=30");
            }

            $this->line("  2. 📝 ESPACIOS O SALTOS DE LÍNEA EN EL TOKEN:");
            $this->line("     Verifica en el archivo .env que TELEGRAM_BOT_TOKEN no tenga espacios");
            $this->line("     accidentales al inicio, en medio o al final.");
        } else {
            $this->newLine();
            $this->info("✅ Todas las pruebas de conectividad pasaron exitosamente.");
        }

        $this->info("==================================================================");
    }
}
