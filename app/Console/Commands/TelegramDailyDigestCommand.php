<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotHandlerService;
use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;

class TelegramDailyDigestCommand extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = "telegram:daily-digest {--chat-id= : Chat ID específico de destino}";

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = "Envía el resumen diario matutino consolidado de producción y represamientos a los administradores autorizados.";

    /**
     * Ejecuta el comando de consola.
     *
     * Flujo del proceso:
     * 1. Verifica si el servicio de Telegram se encuentra configurado.
     * 2. Obtiene los destinatarios (opción --chat-id o lista blanca configurada).
     * 3. Despacha el reporte consolidado de ayer y el semáforo asistencial actual.
     *
     * @param  TelegramService  $telegramService  Cliente de Telegram.
     * @param  TelegramBotHandlerService  $botHandlerService  Generador de reportes.
     * @return int Código de salida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function handle(TelegramService $telegramService, TelegramBotHandlerService $botHandlerService): int
    {
        if (! $telegramService->isConfigured()) {
            $this->error("TELEGRAM_BOT_TOKEN no configurado.");

            return self::FAILURE;
        }

        $chatIdOption = $this->option("chat-id");
        $destinatarios = $chatIdOption
            ? [$chatIdOption]
            : config("telegram.allowed_chat_ids", []);

        if (empty($destinatarios)) {
            $this->warn("No hay destinatarios autorizados configurados en TELEGRAM_ALLOWED_CHAT_IDS.");

            return self::SUCCESS;
        }

        $this->info("Despachando Resumen Diario Matutino a ".count($destinatarios)." destinatarios...");

        foreach ($destinatarios as $chatId) {
            $chatIdStr = (string) $chatId;
            $this->line("-> Enviando a Chat ID {$chatIdStr}...");

            try {
                // 1. Encabezado de saludo matutino
                $telegramService->sendMessage(
                    $chatIdStr,
                    "☀️ <b>BUENOS DÍAS — RESUMEN MATUTINO SALUD TOLIMA</b>\n\n"
                    ."A continuación se presenta el balance de cierre del día de ayer y el estado asistencial de inicio de jornada:"
                );

                // 2. Consolidado de producción de toda la red del día anterior
                $botHandlerService->generarYEnviarReporteRed($chatIdStr, "ayer");

                // 3. Semáforo asistencial de estudios represados (>24h) y urgencias
                $botHandlerService->generarYEnviarReporteRetrasados($chatIdStr);

                $this->info("✅ Entregado exitosamente a {$chatIdStr}.");
            } catch (\Throwable $e) {
                $this->error("❌ Error enviando a {$chatIdStr}: ".$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
