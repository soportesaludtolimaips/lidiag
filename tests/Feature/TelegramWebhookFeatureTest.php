<?php

namespace Tests\Feature;

use App\Services\Telegram\TelegramBotHandlerService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * Pruebas de integración para TelegramWebhookController.
 *
 * Flujo del proceso:
 * 1. Verifica rechazo con HTTP 403 cuando el secret token de la cabecera no coincide.
 * 2. Verifica procesamiento exitoso cuando el secret token es válido o no está configurado.
 * 3. Verifica invocación del despachador TelegramBotHandlerService al recibir actualizaciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
class TelegramWebhookFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('telegram.bot_token', '123456:FAKE_TOKEN');
        Config::set('telegram.allowed_chat_ids', ['777888999']);
    }

    public function test_webhook_rechaza_peticion_con_secret_token_invalido(): void
    {
        Config::set('telegram.secret_token', 'secreto_super_seguro_lidiag');

        $response = $this->postJson('/api/telegram/webhook', [
            'update_id' => 12345,
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'token_falso_atacante',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Token inválido']);
    }

    public function test_webhook_acepta_y_procesa_actualizacion_valida(): void
    {
        Config::set('telegram.secret_token', 'secreto_valido');

        $payload = [
            'update_id' => 99999,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 777888999, 'first_name' => 'Dr. Lozano'],
                'chat' => ['id' => 777888999, 'type' => 'private'],
                'text' => '/ayuda',
            ],
        ];

        $mockHandler = Mockery::mock(TelegramBotHandlerService::class);
        $mockHandler->shouldReceive('handleUpdate')
            ->once()
            ->with($payload);

        $this->app->instance(TelegramBotHandlerService::class, $mockHandler);

        $response = $this->postJson('/api/telegram/webhook', $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'secreto_valido',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_webhook_procesa_sin_secret_token_cuando_no_esta_configurado(): void
    {
        Config::set('telegram.secret_token', null);

        $payload = [
            'update_id' => 88888,
            'message' => [
                'message_id' => 2,
                'from' => ['id' => 123000, 'first_name' => 'Dr. Gomez'],
                'chat' => ['id' => 123000, 'type' => 'private'],
                'text' => '/sedes',
            ],
        ];

        $mockHandler = Mockery::mock(TelegramBotHandlerService::class);
        $mockHandler->shouldReceive('handleUpdate')
            ->once()
            ->with($payload);

        $this->app->instance(TelegramBotHandlerService::class, $mockHandler);

        $response = $this->postJson('/api/telegram/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }
}
