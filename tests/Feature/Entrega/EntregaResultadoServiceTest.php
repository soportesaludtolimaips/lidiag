<?php

namespace Tests\Feature\Entrega;

use App\Mail\EnlaceResultadoPaciente;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EntregaResultado;
use App\Models\General\Paciente;
use App\Services\Entrega\EntregaResultadoService;
use App\Services\Entrega\ImagenesEstudioPacs;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Entrega del resultado al paciente: enlace del portal → imágenes → WhatsApp / correo → registro.
 * El portal, WhatsApp y el correo están simulados: no sale ninguna petición ni mensaje real.
 * Corre contra la BD de test aislada (`lidiag_test`).
 */
class EntregaResultadoServiceTest extends TestCase
{
    use DatabaseTransactions;

    private const TOKEN = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUV'; // 48

    private MockInterface $whatsapp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.URL_API_REPORTES' => 'https://reportes.test',
            'app.API_HMAC_SECRET' => 'secreto-de-prueba',
            'entrega_resultados.portal.url_publica' => 'https://lidiag-reportes.com',
            'entrega_resultados.activo' => true,
            'entrega_resultados.whatsapp.activo' => true,
            'entrega_resultados.whatsapp.plantilla' => 'plantilla_prueba',
            'entrega_resultados.correo.activo' => true,
            'entrega_resultados.imagenes.activo' => false,
            'entrega_resultados.imagenes.max_por_lote' => 20,
        ]);

        Mail::fake();
        $this->whatsapp = $this->mock(WhatsAppService::class);
    }

    // ------------------------------------------------------------ utilidades

    private function contexto(array $extra = []): array
    {
        return array_merge([
            'estudio_producto_id' => 987654,
            'lectura_id' => 77,
            'sede_id' => null,
            'study_iuid' => '1.2.3.4',
            'num_docu' => '1098765432',
            'nom_pacien' => 'RIOS PEREZ^MARIA FERNANDA^^^',
            'nom_sede' => 'Espinal',
        ], $extra);
    }

    private function paciente(array $datos = []): Paciente
    {
        return Paciente::create(array_merge([
            'num_docu' => '1098765432',
            'nombres' => 'RIOS PEREZ MARIA FERNANDA',
            'cel' => '3001234567',
            'email' => 'maria@example.com',
        ], $datos));
    }

    private function portal(string $imagenesEstado = 'sin_imagenes', ?array $enlace = null, array $extra = []): void
    {
        $enlace ??= [
            'id' => 5,
            'url' => 'https://lidiag-reportes.com/p/'.self::TOKEN,
            'expira_en' => now()->addDays(30)->toIso8601String(),
            'vistas' => 0,
            'ultima_vista_en' => null,
        ];

        Http::fake(array_merge([
            'reportes.test/api/lecturas/77/enlace' => Http::response([
                'lectura_id' => 77, 'imagenes_estado' => $imagenesEstado, 'enlace' => $enlace,
            ], 200),
        ], $extra));
    }

    private function entregar(array $ctx = [], bool $automatico = true): array
    {
        return app(EntregaResultadoService::class)->entregar($this->contexto($ctx), $automatico, 3);
    }

    // -------------------------------------------------------------- Canales

    public function test_envia_el_enlace_por_whatsapp_y_correo_y_registra_destinos_enmascarados(): void
    {
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()
            ->with('573001234567', 'plantilla_prueba', ['Espinal', '30'], self::TOKEN)
            ->andReturn(['success' => true, 'message_id' => 'wamid.ABC']);

        $resultado = $this->entregar();

        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
        $this->assertSame('enviado', $resultado['canales']['correo']);

        Mail::assertSent(EnlaceResultadoPaciente::class, function (EnlaceResultadoPaciente $correo) {
            return $correo->hasTo('maria@example.com')
                && $correo->url === 'https://lidiag-reportes.com/p/'.self::TOKEN
                && $correo->nombre === 'Maria'
                && $correo->sede === 'Espinal'
                && $correo->diasVigencia === 30
                && $correo->attachments === [] // solo el enlace: nada adjunto
                && str_contains($correo->render(), 'Ver mi resultado')
                && ! str_contains($correo->render(), 'Contraseña');
        });

        $wa = EntregaResultado::where('estudio_producto_id', 987654)->where('canal', 'whatsapp')->firstOrFail();
        $this->assertSame('enviado', $wa->estado);
        $this->assertSame('+57 3** *** 4567', $wa->destino);
        $this->assertSame('wamid.ABC', $wa->detalle);
        $this->assertTrue($wa->automatico);
        $this->assertSame(77, (int) $wa->lectura_portal_id);

        $correo = EntregaResultado::where('estudio_producto_id', 987654)->where('canal', 'correo')->firstOrFail();
        $this->assertSame('m***@example.com', $correo->destino);

        // Nunca se guarda el número, el correo ni el enlace completos.
        $guardado = json_encode(EntregaResultado::where('estudio_producto_id', 987654)->get()->toArray());
        $this->assertStringNotContainsString('3001234567', $guardado);
        $this->assertStringNotContainsString('maria@example.com', $guardado);
        $this->assertStringNotContainsString(self::TOKEN, $guardado);
    }

    // ------------------------------------------- Solo enlaces del dominio del portal

    private function afirmarQueNoSeEnvioNada(array $resultado): void
    {
        $this->assertSame(['imagenes' => 'enlace_invalido', 'canales' => []], $resultado);
        Mail::assertNothingSent();
        $this->assertSame(0, EntregaResultado::where('estudio_producto_id', 987654)->count());
    }

    public function test_descarta_un_enlace_de_otro_dominio_y_no_envia_nada(): void
    {
        $this->paciente();
        $this->portal('sin_imagenes', [
            'id' => 5,
            'url' => 'https://evil.example/p/'.self::TOKEN,
            'expira_en' => now()->addDays(30)->toIso8601String(),
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $this->afirmarQueNoSeEnvioNada($this->entregar());
    }

    public function test_sin_url_publica_configurada_no_envia_nada(): void
    {
        config(['entrega_resultados.portal.url_publica' => '']);
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $this->afirmarQueNoSeEnvioNada($this->entregar());
    }

    public function test_un_enlace_que_el_portal_creo_nuevo_tambien_se_valida(): void
    {
        // Sin enlace vigente el servicio pide uno nuevo: ese también debe ser del dominio del portal.
        $this->paciente();
        $this->portal('sin_imagenes', null, [
            'reportes.test/api/lecturas/77/enlace' => Http::sequence()
                ->push(['lectura_id' => 77, 'imagenes_estado' => 'sin_imagenes', 'enlace' => null], 200)
                ->push(['lectura_id' => 77, 'enlace' => [
                    'id' => 6, 'url' => 'https://evil.example/p/'.self::TOKEN, 'expira_en' => now()->addDays(30)->toIso8601String(),
                ]], 200),
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $this->afirmarQueNoSeEnvioNada($this->entregar());
    }

    public function test_el_enlace_enviado_se_arma_con_la_url_publica_configurada(): void
    {
        $this->paciente();
        // El portal escribe el dominio con otras mayúsculas: lo que sale es la URL configurada aquí.
        $this->portal('sin_imagenes', [
            'id' => 5,
            'url' => 'https://LIDIAG-Reportes.com/p/'.self::TOKEN,
            'expira_en' => now()->addDays(30)->toIso8601String(),
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()
            ->with('573001234567', 'plantilla_prueba', ['Espinal', '30'], self::TOKEN)
            ->andReturn(['success' => true, 'message_id' => 'wamid.ABC']);

        $this->entregar();

        Mail::assertSent(EnlaceResultadoPaciente::class, fn (EnlaceResultadoPaciente $correo) => $correo->url === 'https://lidiag-reportes.com/p/'.self::TOKEN);
    }

    public function test_un_canal_apagado_no_envia_ni_registra(): void
    {
        config(['entrega_resultados.whatsapp.activo' => false, 'entrega_resultados.correo.activo' => false]);
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $resultado = $this->entregar();

        $this->assertSame(['whatsapp' => 'desactivado', 'correo' => 'desactivado'], $resultado['canales']);
        Mail::assertNothingSent();
        $this->assertSame(0, EntregaResultado::where('estudio_producto_id', 987654)->count());
    }

    public function test_sin_celular_valido_omite_whatsapp_y_lo_deja_registrado(): void
    {
        // Solo un teléfono fijo: no es un celular.
        $this->paciente(['cel' => null, 'tel' => '6012345678']);
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $resultado = $this->entregar();

        $this->assertSame('omitido', $resultado['canales']['whatsapp']);
        $this->assertSame('enviado', $resultado['canales']['correo']);
        $this->assertSame('Sin celular válido', EntregaResultado::where('canal', 'whatsapp')->where('estudio_producto_id', 987654)->value('detalle'));
    }

    public function test_usa_el_telefono_si_es_un_celular_cuando_no_hay_campo_celular(): void
    {
        $this->paciente(['cel' => null, 'tel' => '310 987 6543']);
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()
            ->with('573109876543', 'plantilla_prueba', ['Espinal', '30'], self::TOKEN)
            ->andReturn(['success' => true, 'message_id' => 'wamid.X']);

        $this->assertSame('enviado', $this->entregar()['canales']['whatsapp']);
    }

    public function test_sin_correo_valido_omite_el_correo(): void
    {
        $this->paciente(['email' => 'no-es-un-correo']);
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar();

        $this->assertSame('omitido', $resultado['canales']['correo']);
        Mail::assertNothingSent();
    }

    public function test_un_fallo_de_whatsapp_se_registra_y_no_impide_el_correo(): void
    {
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()
            ->andReturn(['success' => false, 'error' => 'Business Account locked', 'error_code' => 131031]);

        $resultado = $this->entregar();

        $this->assertSame('fallido', $resultado['canales']['whatsapp']);
        $this->assertSame('enviado', $resultado['canales']['correo']);
        $registro = EntregaResultado::where('canal', 'whatsapp')->where('estudio_producto_id', 987654)->firstOrFail();
        $this->assertSame('fallido', $registro->estado);
        $this->assertSame('Business Account locked', $registro->detalle);
    }

    public function test_una_excepcion_al_enviar_whatsapp_no_rompe_la_entrega(): void
    {
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()->andThrow(new \RuntimeException('Conexión caída con el número 573001234567'));

        $resultado = $this->entregar();

        $this->assertSame('fallido', $resultado['canales']['whatsapp']);
        // El detalle guarda solo la clase del error: el mensaje podría traer el número del paciente.
        $detalle = EntregaResultado::where('canal', 'whatsapp')->where('estudio_producto_id', 987654)->value('detalle');
        $this->assertSame('RuntimeException', $detalle);
        $this->assertStringNotContainsString('3001234567', (string) $detalle);
    }

    // -------------------------------------------------------- Idempotencia

    public function test_el_modo_automatico_no_repite_un_canal_ya_enviado_pero_el_manual_si(): void
    {
        $this->paciente();
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->twice()->andReturn(['success' => true, 'message_id' => 'w']);

        $primero = $this->entregar();
        $repetido = $this->entregar(); // automático otra vez
        $manual = $this->entregar([], false); // reenvío manual de quien atiende

        $this->assertSame('enviado', $primero['canales']['whatsapp']);
        $this->assertSame('ya_enviado', $repetido['canales']['whatsapp']);
        $this->assertSame('ya_enviado', $repetido['canales']['correo']);
        $this->assertSame('enviado', $manual['canales']['whatsapp']);
        $this->assertSame(2, EntregaResultado::where('canal', 'whatsapp')->where('estudio_producto_id', 987654)->where('estado', 'enviado')->count());
        $this->assertFalse(EntregaResultado::where('estudio_producto_id', 987654)->latest('id')->first()->automatico);
    }

    // ------------------------------------------------------------ Portal

    public function test_si_el_enlace_vencio_pide_uno_nuevo_al_portal(): void
    {
        $this->paciente();
        $nuevo = [
            'id' => 6, 'url' => 'https://lidiag-reportes.com/p/'.self::TOKEN,
            'expira_en' => now()->addDays(30)->toIso8601String(), 'vistas' => 0, 'ultima_vista_en' => null,
        ];
        Http::fake(['reportes.test/api/lecturas/77/enlace' => Http::sequence()
            ->push(['lectura_id' => 77, 'imagenes_estado' => 'sin_imagenes', 'enlace' => null], 200) // GET: sin enlace vigente
            ->push(['lectura_id' => 77, 'enlace' => $nuevo], 200), // POST: enlace regenerado
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar();

        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $p) => $p->method() === 'POST' && str_ends_with($p->url(), '/api/lecturas/77/enlace'));
    }

    public function test_si_el_portal_no_responde_no_se_envia_nada(): void
    {
        $this->paciente();
        Http::fake(['reportes.test/*' => Http::response('error', 500)]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $resultado = $this->entregar();

        $this->assertSame(['imagenes' => 'sin_enlace', 'canales' => []], $resultado);
        Mail::assertNothingSent();
        $this->assertSame(0, EntregaResultado::where('estudio_producto_id', 987654)->count());
    }

    public function test_sin_portal_configurado_no_hace_nada(): void
    {
        config(['app.URL_API_REPORTES' => '']);
        $this->whatsapp->shouldReceive('enviarPlantilla')->never();

        $this->assertSame(['imagenes' => 'sin_portal', 'canales' => []], $this->entregar());
        Mail::assertNothingSent();
    }

    // ------------------------------------------------------------ Imágenes

    /** Sede con PACS y un PACS simulado que entrega N instancias JPEG. */
    private function pacsConImagenes(int $cantidad): ConfigSede
    {
        $sede = ConfigSede::create(['nom_sede' => 'Sede de prueba', 'ip_dcm' => '10.9.8.7']);

        $instancias = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $instancias[] = ['series_iuid' => 'S1', 'sop_iuid' => 'sop'.$i, 'serie_orden' => 1, 'orden' => $i];
        }

        $this->mock(ImagenesEstudioPacs::class, function (MockInterface $pacs) use ($instancias) {
            $pacs->shouldReceive('instancias')->andReturn($instancias);
            $pacs->shouldReceive('descargar')->andReturnUsing(fn () => "\xFF\xD8\xFF".random_bytes(64));
        });

        config(['entrega_resultados.imagenes.activo' => true]);

        return $sede;
    }

    public function test_sube_las_imagenes_por_lotes_y_cierra_la_subida(): void
    {
        $this->paciente();
        $sede = $this->pacsConImagenes(45);
        $this->portal('sin_imagenes', null, [
            'reportes.test/api/lecturas/77/imagenes' => Http::response(['recibidas' => 20, 'existentes' => 0, 'total' => 20], 200),
            'reportes.test/api/lecturas/77/imagenes/finalizar' => Http::response(['estado' => 'completo', 'total' => 45, 'esperadas' => 45], 200),
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar(['sede_id' => $sede->id]);

        $this->assertSame('completo', $resultado['imagenes']);
        // 45 imágenes con lotes de 20 → 20 + 20 + 5.
        $lotes = Http::recorded(fn (Request $p) => str_ends_with($p->url(), '/imagenes'));
        $this->assertCount(3, $lotes);
        Http::assertSent(fn (Request $p) => str_ends_with($p->url(), '/imagenes/finalizar') && ($p->data()['esperadas'] ?? null) === '45');
        // El enlace se entrega DESPUÉS de subir las imágenes.
        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
    }

    public function test_un_reenvio_no_vuelve_a_subir_imagenes_que_ya_estan_completas(): void
    {
        $this->paciente();
        $sede = $this->pacsConImagenes(5);
        $this->portal('completo');
        $this->whatsapp->shouldReceive('enviarPlantilla')->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar(['sede_id' => $sede->id], false);

        $this->assertSame('completo', $resultado['imagenes']);
        Http::assertNotSent(fn (Request $p) => str_contains($p->url(), '/imagenes'));
    }

    public function test_si_el_portal_rechaza_un_lote_cierra_como_parcial_y_aun_asi_entrega_el_enlace(): void
    {
        $this->paciente();
        $sede = $this->pacsConImagenes(45);
        $this->portal('sin_imagenes', null, [
            'reportes.test/api/lecturas/77/imagenes' => Http::sequence()
                ->push(['recibidas' => 20, 'existentes' => 0, 'total' => 20], 200)
                ->push(['message' => 'El estudio supera el máximo'], 422),
            'reportes.test/api/lecturas/77/imagenes/finalizar' => Http::response(['estado' => 'parcial', 'total' => 20, 'esperadas' => 45], 200),
        ]);
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar(['sede_id' => $sede->id]);

        $this->assertSame('parcial', $resultado['imagenes']);
        $this->assertCount(2, Http::recorded(fn (Request $p) => str_ends_with($p->url(), '/imagenes'))); // se detuvo en el 2.º
        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
    }

    public function test_sin_pacs_o_sin_estudio_las_imagenes_se_omiten_y_el_enlace_se_entrega(): void
    {
        $this->paciente();
        config(['entrega_resultados.imagenes.activo' => true]);
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar(['sede_id' => null]);

        $this->assertSame('sin_pacs', $resultado['imagenes']);
        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
    }

    public function test_un_error_consultando_el_pacs_no_impide_entregar_el_enlace(): void
    {
        $this->paciente();
        $sede = ConfigSede::create(['nom_sede' => 'Sede PACS caído', 'ip_dcm' => '10.9.8.7']);
        $this->mock(ImagenesEstudioPacs::class, fn (MockInterface $pacs) => $pacs->shouldReceive('instancias')->andThrow(new \RuntimeException('PACS sin conexión')));
        config(['entrega_resultados.imagenes.activo' => true]);
        $this->portal();
        $this->whatsapp->shouldReceive('enviarPlantilla')->once()->andReturn(['success' => true, 'message_id' => 'w']);

        $resultado = $this->entregar(['sede_id' => $sede->id]);

        $this->assertSame('error_pacs', $resultado['imagenes']);
        $this->assertSame('enviado', $resultado['canales']['whatsapp']);
    }
}
