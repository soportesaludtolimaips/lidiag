<?php

namespace Tests\Feature\Entrega;

use App\Models\Estudio\EntregaResultado;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Comandos de soporte: estado de los envíos y revocación del enlace (portal simulado).
 */
class EntregaComandosTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.URL_API_REPORTES' => 'https://reportes.test', 'app.API_HMAC_SECRET' => 'secreto-de-prueba']);
    }

    private function entrega(array $extra = []): EntregaResultado
    {
        return EntregaResultado::create(array_merge([
            'estudio_producto_id' => 555111,
            'sede_id' => 1,
            'lectura_portal_id' => 77,
            'canal' => 'whatsapp',
            'destino' => '+57 3** *** 4567',
            'estado' => 'enviado',
            'detalle' => 'wamid.X',
            'automatico' => true,
        ], $extra));
    }

    public function test_estado_muestra_los_envios_enmascarados_y_el_estado_del_enlace(): void
    {
        $this->entrega();
        $this->entrega(['canal' => 'correo', 'destino' => 'm***@example.com', 'detalle' => null, 'automatico' => false, 'usuario_id' => 4]);
        Http::fake(['reportes.test/api/lecturas/77/enlace' => Http::response([
            'lectura_id' => 77,
            'imagenes_estado' => 'completo',
            'enlace' => ['id' => 5, 'url' => 'https://lidiag-reportes.com/p/'.str_repeat('a', 48), 'expira_en' => '2026-12-04T10:00:00+00:00', 'vistas' => 2, 'ultima_vista_en' => '2026-11-05T09:00:00+00:00'],
        ], 200)]);

        $this->assertSame(0, Artisan::call('entrega:estado', ['estudio_producto_id' => 555111]));
        $salida = Artisan::output();

        $this->assertStringContainsString('+57 3** *** 4567', $salida);
        $this->assertStringContainsString('m***@example.com', $salida);
        $this->assertStringContainsString('manual (usuario 4)', $salida);
        $this->assertStringContainsString('imágenes completo', $salida);
        $this->assertStringContainsString('vistas: 2', $salida);
        $this->assertStringNotContainsString(str_repeat('a', 48), $salida); // el enlace nunca se imprime
    }

    public function test_estado_de_un_estudio_sin_envios(): void
    {
        Http::fake();

        $this->artisan('entrega:estado', ['estudio_producto_id' => 555999])
            ->expectsOutputToContain('no tiene envíos registrados')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_estado_avisa_si_el_portal_no_responde_sin_fallar(): void
    {
        $this->entrega();
        Http::fake(['reportes.test/*' => Http::response('x', 500)]);

        $this->artisan('entrega:estado', ['estudio_producto_id' => 555111])
            ->expectsOutputToContain('No se pudo consultar el portal (HTTP 500)')
            ->assertExitCode(0);
    }

    public function test_revocar_desactiva_el_enlace_vigente(): void
    {
        Http::fake([
            // El portal devuelve el vigente más reciente; tras revocarlo ya no queda ninguno.
            'reportes.test/api/lecturas/77/enlace' => Http::sequence()
                ->push(['enlace' => ['id' => 5]], 200)
                ->push(['enlace' => null], 200),
            'reportes.test/api/enlaces/5/revocar' => Http::response(['id' => 5, 'revocado' => true], 200),
        ]);

        $this->artisan('entrega:revocar', ['lectura_id' => 77])
            ->expectsOutputToContain('Enlace revocado')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $p) => $p->method() === 'POST' && str_ends_with($p->url(), '/api/enlaces/5/revocar'));
    }

    public function test_revocar_desactiva_todos_los_enlaces_vigentes_no_solo_el_ultimo(): void
    {
        Http::fake([
            // Un reenvío manual había creado un segundo enlace para la misma lectura.
            'reportes.test/api/lecturas/77/enlace' => Http::sequence()
                ->push(['enlace' => ['id' => 6]], 200)
                ->push(['enlace' => ['id' => 5]], 200)
                ->push(['enlace' => null], 200),
            'reportes.test/api/enlaces/*/revocar' => Http::response(['revocado' => true], 200),
        ]);

        $this->artisan('entrega:revocar', ['lectura_id' => 77])
            ->expectsOutputToContain('Se revocaron 2 enlaces')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $p) => $p->method() === 'POST' && str_ends_with($p->url(), '/api/enlaces/6/revocar'));
        Http::assertSent(fn (Request $p) => $p->method() === 'POST' && str_ends_with($p->url(), '/api/enlaces/5/revocar'));
    }

    public function test_revocar_falla_si_el_portal_rechaza_la_revocacion(): void
    {
        Http::fake([
            'reportes.test/api/lecturas/77/enlace' => Http::response(['enlace' => ['id' => 5]], 200),
            'reportes.test/api/enlaces/5/revocar' => Http::response('x', 500),
        ]);

        $this->artisan('entrega:revocar', ['lectura_id' => 77])
            ->expectsOutputToContain('HTTP 500')
            ->assertExitCode(1);
    }

    public function test_revocar_sin_enlace_vigente_no_hace_nada(): void
    {
        Http::fake(['reportes.test/api/lecturas/77/enlace' => Http::response(['enlace' => null], 200)]);

        $this->artisan('entrega:revocar', ['lectura_id' => 77])
            ->expectsOutputToContain('no tiene un enlace vigente')
            ->assertExitCode(0);

        Http::assertSentCount(1);
    }

    public function test_revocar_con_regenerar_pide_un_enlace_nuevo_y_revoca_los_anteriores(): void
    {
        Http::fake(['reportes.test/api/lecturas/77/enlace' => Http::response(['enlace' => ['id' => 6]], 200)]);

        $this->artisan('entrega:revocar', ['lectura_id' => 77, '--regenerar' => true])
            ->expectsOutputToContain('se creó uno nuevo')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $p) => $p->method() === 'POST'
            && str_ends_with($p->url(), '/api/lecturas/77/enlace')
            && ($p->data()['revocar_anteriores'] ?? null) === '1');
    }

    public function test_revocar_falla_con_mensaje_claro_si_el_portal_no_esta_configurado(): void
    {
        config(['app.URL_API_REPORTES' => '']);
        Http::fake();

        $this->artisan('entrega:revocar', ['lectura_id' => 77])
            ->expectsOutputToContain('no está configurado')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }
}
