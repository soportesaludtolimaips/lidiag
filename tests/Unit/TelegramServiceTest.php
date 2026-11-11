<?php

namespace Tests\Unit;

use App\Services\Telegram\TelegramBotHandlerService;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pruebas unitarias para TelegramService y TelegramBotHandlerService.
 *
 * Flujo del proceso:
 * 1. Verifica detección de configuración (token existente o ausente).
 * 2. Verifica despacho de mensajes simples y con botones mediante mock HTTP.
 * 3. Verifica autorización de usuarios por lista blanca de chat IDs.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
class TelegramServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set("telegram.bot_token", "123456:TEST_TOKEN");
        Config::set("telegram.allowed_chat_ids", ["111222333", "444555666"]);
    }

    public function test_telegram_service_is_configured_when_token_is_present(): void
    {
        $service = new TelegramService;
        $this->assertTrue($service->isConfigured());

        Config::set("telegram.bot_token", null);
        $serviceNoToken = new TelegramService;
        $this->assertFalse($serviceNoToken->isConfigured());
    }

    public function test_send_message_dispatches_http_request(): void
    {
        Http::fake([
            "https://api.telegram.org/bot123456:TEST_TOKEN/sendMessage" => Http::response([
                "ok" => true,
                "result" => ["message_id" => 999],
            ], 200),
        ]);

        $service = new TelegramService;
        $result = $service->sendMessage("111222333", "Hola Mundo Clínico");

        $this->assertTrue($result["ok"]);
        $this->assertSame(999, $result["result"]["message_id"]);

        Http::assertSent(function ($request) {
            return $request->url() === "https://api.telegram.org/bot123456:TEST_TOKEN/sendMessage" &&
                $request["chat_id"] === "111222333" &&
                $request["text"] === "Hola Mundo Clínico";
        });
    }

    public function test_bot_handler_authorizes_whitelisted_chat_id(): void
    {
        $telegramService = new TelegramService;
        $handler = new TelegramBotHandlerService($telegramService);

        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod("isChatAuthorized");
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($handler, "111222333"));
        $this->assertTrue($method->invoke($handler, 444555666));
        $this->assertFalse($method->invoke($handler, "999999999"));
    }

    public function test_get_me_fetches_bot_info(): void
    {
        Http::fake([
            "https://api.telegram.org/bot123456:TEST_TOKEN/getMe" => Http::response([
                "ok" => true,
                "result" => [
                    "id" => 123456,
                    "is_bot" => true,
                    "first_name" => "LIDiag Bot",
                    "username" => "lidiag_bot",
                ],
            ], 200),
        ]);

        $service = new TelegramService;
        $info = $service->getMe();

        $this->assertTrue($info["ok"]);
        $this->assertSame("lidiag_bot", $info["result"]["username"]);
    }
}
