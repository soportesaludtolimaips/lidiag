<?php

namespace Tests\Unit\Entrega;

use App\Services\Entrega\ClientePortalReportes;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La firma HMAC que genera principal debe coincidir con la que verifica el portal
 * (VerifyHmacSignature en lidiag-reportes). Aquí se recalcula con el algoritmo documentado:
 *
 *   stringToSign = METODO \n RUTA \n TIMESTAMP \n SHA256(campos) \n SHA256(archivos)
 */
class ClientePortalReportesTest extends TestCase
{
    private const SECRETO = 'secreto-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            // Con prefijo de ruta: debe formar parte de lo que se firma.
            'app.URL_API_REPORTES' => 'https://reportes.test/portal',
            'app.API_HMAC_SECRET' => self::SECRETO,
            'entrega_resultados.portal.verificar_tls' => true,
            'entrega_resultados.portal.url_publica' => 'https://lidiag-reportes.com',
        ]);
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
    }

    /** Sustituye las respuestas simuladas de setUp por las de la prueba. */
    private function simular(array $respuestas): void
    {
        Http::swap(new HttpFactory);
        Http::fake($respuestas);
    }

    private function esperada(string $metodo, string $ruta, string $ts, array $campos, string $huellaArchivos): string
    {
        ksort($campos);
        $payloadSha = hash('sha256', http_build_query($campos, '', '&', PHP_QUERY_RFC3986));

        return base64_encode(hash_hmac(
            'sha256',
            implode("\n", [$metodo, $ruta, $ts, $payloadSha, $huellaArchivos]),
            self::SECRETO,
            true
        ));
    }

    private function firmaValida(Request $peticion, string $metodo, string $ruta, array $campos, string $huellaArchivos): bool
    {
        $ts = $peticion->header('X-Timestamp')[0] ?? '';

        return $peticion->method() === $metodo
            && ctype_digit($ts)
            && abs(time() - (int) $ts) <= 5
            && ($peticion->header('X-Signature')[0] ?? '') === $this->esperada($metodo, $ruta, $ts, $campos, $huellaArchivos);
    }

    public function test_configurado_exige_url_y_secreto(): void
    {
        $this->assertTrue((new ClientePortalReportes)->configurado());

        config(['app.URL_API_REPORTES' => '']);
        $this->assertFalse((new ClientePortalReportes)->configurado());

        config(['app.URL_API_REPORTES' => 'https://reportes.test', 'app.API_HMAC_SECRET' => '']);
        $this->assertFalse((new ClientePortalReportes)->configurado());
    }

    public function test_firma_la_lectura_con_el_hash_del_pdf_y_la_ruta_completa(): void
    {
        $pdf = '%PDF-1.4 informe de prueba';
        $campos = ['num_docu' => '123', 'name' => 'JUAN LOPEZ', 'nom_produc' => 'RX'];

        (new ClientePortalReportes)->almacenarLectura($campos, $pdf, 'informe.pdf');

        Http::assertSent(fn (Request $p) => $p->url() === 'https://reportes.test/portal/api/almacenarLectura'
            && $this->firmaValida($p, 'POST', '/portal/api/almacenarLectura', $campos, hash('sha256', $pdf)));
    }

    public function test_recorta_los_textos_y_omite_los_campos_vacios_como_hace_el_portal(): void
    {
        // El portal recorta los textos y convierte los vacíos en null ANTES de verificar la firma.
        (new ClientePortalReportes)->almacenarLectura(
            ['num_docu' => '123', 'name' => '  JUAN LOPEZ  ', 'fec_naci' => '', 'nom_produc' => null, 'nom_sede' => 'Espinal'],
            'pdf',
            'a.pdf'
        );

        $normalizados = ['num_docu' => '123', 'name' => 'JUAN LOPEZ', 'nom_sede' => 'Espinal'];
        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'POST', '/portal/api/almacenarLectura', $normalizados, hash('sha256', 'pdf')));
    }

    public function test_el_lote_de_imagenes_firma_el_manifiesto_y_los_hash_ordenados(): void
    {
        $a = "\xFF\xD8\xFFaaaa";
        $b = "\xFF\xD8\xFFbbbb";
        $imagenes = [
            ['bytes' => $a, 'sop_uid' => '1.2.1', 'serie_uid' => '1.2', 'serie_orden' => 1, 'orden' => 1],
            ['bytes' => $b, 'sop_uid' => '1.2.2', 'serie_uid' => '1.2', 'serie_orden' => 1, 'orden' => 2],
        ];

        (new ClientePortalReportes)->subirLote(55, $imagenes);

        $manifiesto = json_encode([
            ['sha256' => hash('sha256', $a), 'sop_uid' => '1.2.1', 'serie_uid' => '1.2', 'serie_orden' => 1, 'orden' => 1],
            ['sha256' => hash('sha256', $b), 'sop_uid' => '1.2.2', 'serie_uid' => '1.2', 'serie_orden' => 1, 'orden' => 2],
        ]);
        $huellas = [hash('sha256', $a), hash('sha256', $b)];
        sort($huellas);

        Http::assertSent(fn (Request $p) => $p->url() === 'https://reportes.test/portal/api/lecturas/55/imagenes'
            && $p->isMultipart()
            && $this->firmaValida($p, 'POST', '/portal/api/lecturas/55/imagenes', ['manifiesto' => $manifiesto], hash('sha256', implode("\n", $huellas))));
    }

    public function test_finalizar_y_gestion_del_enlace_se_firman_sin_archivos(): void
    {
        $cliente = new ClientePortalReportes;
        $vacio = hash('sha256', '');

        $cliente->finalizarImagenes(55, 13);
        $cliente->enlace(55);
        $cliente->regenerarEnlace(55, true);
        $cliente->revocarEnlace(9);

        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'POST', '/portal/api/lecturas/55/imagenes/finalizar', ['esperadas' => '13'], $vacio));
        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'GET', '/portal/api/lecturas/55/enlace', [], $vacio));
        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'POST', '/portal/api/lecturas/55/enlace', ['revocar_anteriores' => '1'], $vacio));
        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'POST', '/portal/api/enlaces/9/revocar', [], $vacio));
    }

    public function test_una_firma_con_otro_secreto_no_coincide(): void
    {
        (new ClientePortalReportes)->enlace(1);

        Http::assertSent(function (Request $p) {
            $ts = $p->header('X-Timestamp')[0];
            $conOtroSecreto = base64_encode(hash_hmac(
                'sha256',
                implode("\n", ['GET', '/portal/api/lecturas/1/enlace', $ts, hash('sha256', ''), hash('sha256', '')]),
                'otro-secreto',
                true
            ));

            return $p->header('X-Signature')[0] !== $conOtroSecreto;
        });
    }

    // ------------------------------------------------------------ Reintentos

    public function test_reintenta_si_el_portal_esta_saturado_y_firma_de_nuevo_cada_intento(): void
    {
        config(['entrega_resultados.portal.pausa_reintento_ms' => 0]);
        $this->simular(['*' => Http::sequence()
            ->push('demasiadas peticiones', 429)
            ->push('mal gateway', 502)
            ->push(['ok' => true], 200),
        ]);

        $respuesta = (new ClientePortalReportes)->enlace(1);

        $this->assertSame(200, $respuesta->status());
        Http::assertSentCount(3);
        // Cada intento lleva su propia firma válida (no se reutiliza la del primero).
        Http::assertSent(fn (Request $p) => $this->firmaValida($p, 'GET', '/portal/api/lecturas/1/enlace', [], hash('sha256', '')));
    }

    public function test_deja_de_reintentar_tras_tres_intentos_y_devuelve_la_ultima_respuesta(): void
    {
        config(['entrega_resultados.portal.pausa_reintento_ms' => 0]);
        $this->simular(['*' => Http::response('saturado', 429)]);

        $respuesta = (new ClientePortalReportes)->finalizarImagenes(1, 3);

        $this->assertSame(429, $respuesta->status());
        Http::assertSentCount(3);
    }

    public function test_un_error_del_cliente_no_se_reintenta(): void
    {
        $this->simular(['*' => Http::response(['message' => 'inválido'], 422)]);

        $this->assertSame(422, (new ClientePortalReportes)->subirLote(1, [
            ['bytes' => "\xFF\xD8\xFFx", 'sop_uid' => '1', 'serie_uid' => '1', 'serie_orden' => 1, 'orden' => 1],
        ])->status());

        Http::assertSentCount(1);
    }

    public function test_reintenta_si_no_hay_conexion_y_al_agotar_los_intentos_lanza_la_excepcion(): void
    {
        config(['entrega_resultados.portal.pausa_reintento_ms' => 0]);
        $intentos = 0;
        $this->simular(['*' => function () use (&$intentos) {
            $intentos++;

            throw new ConnectionException('sin conexión');
        }]);

        try {
            (new ClientePortalReportes)->enlace(1);
            $this->fail('Debía lanzar ConnectionException');
        } catch (ConnectionException) {
            $this->assertSame(3, $intentos);
        }
    }

    public function test_regenerar_el_enlace_no_se_reintenta_para_no_crear_enlaces_duplicados(): void
    {
        config(['entrega_resultados.portal.pausa_reintento_ms' => 0]);
        $this->simular(['*' => Http::response('mal gateway', 502)]);

        $this->assertSame(502, (new ClientePortalReportes)->regenerarEnlace(1)->status());

        Http::assertSentCount(1);
    }

    // ------------------------------------------------- Verificación del certificado

    /** Opciones de Guzzle con las que el cliente habla con el portal. */
    private function opcionesHttp(): array
    {
        $metodo = new \ReflectionMethod(ClientePortalReportes::class, 'http');

        return $metodo->invoke(new ClientePortalReportes)->getOptions();
    }

    public function test_verifica_el_certificado_del_portal_cuando_se_activa(): void
    {
        config(['entrega_resultados.portal.verificar_tls' => true, 'entrega_resultados.portal.ca_bundle' => '']);

        // Guzzle verifica por defecto: basta con que no se haya desactivado.
        $this->assertNotFalse($this->opcionesHttp()['verify'] ?? true);
    }

    public function test_sin_verificar_solo_si_se_pide_expresamente(): void
    {
        config(['entrega_resultados.portal.verificar_tls' => false, 'entrega_resultados.portal.ca_bundle' => '']);

        $this->assertFalse($this->opcionesHttp()['verify']);
    }

    public function test_con_un_certificado_propio_se_verifica_contra_ese_certificado_aunque_verificar_tls_este_apagado(): void
    {
        config(['entrega_resultados.portal.verificar_tls' => false, 'entrega_resultados.portal.ca_bundle' => 'C:/certs/portal.pem']);

        $this->assertSame('C:/certs/portal.pem', $this->opcionesHttp()['verify']);
    }

    // ------------------------------------------------- URL pública del enlace

    private const TOKEN = 'aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3aB3'; // 48 caracteres

    public function test_acepta_un_enlace_del_dominio_publico_y_devuelve_su_token(): void
    {
        $portal = new ClientePortalReportes;

        $this->assertSame(self::TOKEN, $portal->tokenDeEnlace('https://lidiag-reportes.com/p/'.self::TOKEN));

        // La URL configurada puede llevar «/» final y otro uso de mayúsculas en el dominio.
        config(['entrega_resultados.portal.url_publica' => 'https://LIDIAG-Reportes.com/']);
        $this->assertSame(self::TOKEN, $portal->tokenDeEnlace('https://lidiag-reportes.com/p/'.self::TOKEN));
    }

    /** @return array<string, array{string}> */
    public static function enlacesRechazados(): array
    {
        $t = self::TOKEN;

        return [
            'otro dominio' => ['https://evil.example/p/'.$t],
            'dominio del portal como prefijo del de otro' => ['https://lidiag-reportes.com.evil.example/p/'.$t],
            'credenciales que esconden otro dominio' => ['https://lidiag-reportes.com@evil.example/p/'.$t],
            'http en vez de https' => ['http://lidiag-reportes.com/p/'.$t],
            'sin la ruta /p/' => ['https://lidiag-reportes.com/'.$t],
            'otra ruta' => ['https://lidiag-reportes.com/admin/p/'.$t],
            'token de 47 caracteres' => ['https://lidiag-reportes.com/p/'.substr($t, 0, 47)],
            'token de 49 caracteres' => ['https://lidiag-reportes.com/p/'.$t.'a'],
            'token con un símbolo' => ['https://lidiag-reportes.com/p/'.substr($t, 0, 47).'-'],
            'con parámetros' => ['https://lidiag-reportes.com/p/'.$t.'?redirigir=https://evil.example'],
            'con fragmento' => ['https://lidiag-reportes.com/p/'.$t.'#x'],
            'con barra final' => ['https://lidiag-reportes.com/p/'.$t.'/'],
            'con salto de línea final' => ['https://lidiag-reportes.com/p/'.$t."\n"],
            'esquema relativo' => ['//lidiag-reportes.com/p/'.$t],
            'vacía' => [''],
        ];
    }

    #[DataProvider('enlacesRechazados')]
    public function test_rechaza_un_enlace_que_no_es_exactamente_url_publica_p_token(string $url): void
    {
        $this->assertNull((new ClientePortalReportes)->tokenDeEnlace($url));
    }

    public function test_sin_una_url_publica_utilizable_no_se_acepta_ningun_enlace(): void
    {
        $portal = new ClientePortalReportes;

        foreach (['', 'lidiag-reportes.com', 'ftp://lidiag-reportes.com', 'javascript:alert(1)'] as $invalida) {
            config(['entrega_resultados.portal.url_publica' => $invalida]);

            $this->assertSame('', $portal->urlPublica(), $invalida);
            $this->assertNull($portal->tokenDeEnlace('https://lidiag-reportes.com/p/'.self::TOKEN), $invalida);
        }
    }

    public function test_en_produccion_la_url_publica_debe_ser_https(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $portal = new ClientePortalReportes;

        config(['entrega_resultados.portal.url_publica' => 'http://lidiag-reportes.com']);
        $this->assertSame('', $portal->urlPublica());
        $this->assertNull($portal->tokenDeEnlace('http://lidiag-reportes.com/p/'.self::TOKEN));

        config(['entrega_resultados.portal.url_publica' => 'https://lidiag-reportes.com']);
        $this->assertSame(self::TOKEN, $portal->tokenDeEnlace('https://lidiag-reportes.com/p/'.self::TOKEN));
    }

    public function test_fuera_de_produccion_se_acepta_http_para_probar_en_local(): void
    {
        config(['entrega_resultados.portal.url_publica' => 'http://127.0.0.1:8002']);

        $this->assertSame(self::TOKEN, (new ClientePortalReportes)->tokenDeEnlace('http://127.0.0.1:8002/p/'.self::TOKEN));
    }
}
