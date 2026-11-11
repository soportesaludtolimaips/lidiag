<?php

namespace App\Services\Entrega;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de la API servidor-a-servidor del portal de pacientes (lidiag-reportes).
 *
 * Cada petición va firmada con HMAC-SHA256 (el portal la verifica en VerifyHmacSignature):
 *
 *   stringToSign = METODO \n RUTA \n TIMESTAMP \n SHA256(campos) \n SHA256(archivos)
 *   - campos: sin archivos, claves ordenadas, http_build_query RFC3986.
 *   - archivos: un PDF (file_reporte) → su SHA-256; un lote de imágenes (archivos[]) → SHA-256 de
 *     los SHA-256 de cada archivo, ordenados y unidos con "\n"; sin archivos → SHA-256 de ''.
 *
 * El portal recorta los textos y convierte los vacíos en null ANTES de verificar la firma; por eso
 * aquí los campos se normalizan igual (recortados y sin vacíos). Si no, una firma con un campo vacío
 * nunca coincidiría.
 */
class ClientePortalReportes
{
    private const INTENTOS = 3;

    public function configurado(): bool
    {
        return $this->base() !== '' && $this->secreto() !== '';
    }

    /**
     * URL pública del portal (la que ven los pacientes), o '' si no está configurada o no es utilizable:
     * debe ser http(s) y, en producción, https.
     */
    public function urlPublica(): string
    {
        $url = rtrim((string) config('entrega_resultados.portal.url_publica'), '/');
        $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($url === '' || ! in_array($esquema, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return ($esquema === 'https' || ! app()->environment('production')) ? $url : '';
    }

    /**
     * Token (48 caracteres) de un enlace del portal, o null si la URL no es exactamente
     * «<URL pública>/p/<token>». Solo se reenvían al paciente enlaces del dominio del portal: aunque
     * la respuesta de la API llegara alterada (el TLS no se verifica mientras se use la IP interna),
     * un mensaje oficial de la institución nunca llevará a un sitio ajeno.
     */
    public function tokenDeEnlace(string $url): ?string
    {
        $base = $this->urlPublica();
        if ($base === '') {
            return null;
        }

        return preg_match('#^'.preg_quote($base, '#').'/p/([A-Za-z0-9]{48})$#Di', $url, $coincidencia) === 1
            ? $coincidencia[1]
            : null;
    }

    /** Envía la lectura (PDF) y devuelve el enlace del paciente. */
    public function almacenarLectura(array $campos, string $pdf, string $nombreArchivo): Response
    {
        return $this->enviar('POST', '/api/almacenarLectura', $campos, [['file_reporte', $pdf, $nombreArchivo]], hash('sha256', $pdf));
    }

    /**
     * Sube un lote de imágenes JPEG. Cada elemento: bytes, sop_uid, serie_uid, serie_orden, orden.
     *
     * @param  list<array{bytes: string, sop_uid: string, serie_uid: ?string, serie_orden: int, orden: int}>  $imagenes
     */
    public function subirLote(int $lecturaId, array $imagenes): Response
    {
        $manifiesto = [];
        $archivos = [];
        $huellas = [];

        foreach ($imagenes as $indice => $imagen) {
            $sha = hash('sha256', $imagen['bytes']);
            $huellas[] = $sha;
            $manifiesto[] = [
                'sha256' => $sha,
                'sop_uid' => $imagen['sop_uid'],
                'serie_uid' => $imagen['serie_uid'],
                'serie_orden' => $imagen['serie_orden'],
                'orden' => $imagen['orden'],
            ];
            $archivos[] = ['archivos[]', $imagen['bytes'], 'imagen'.$indice.'.jpg'];
        }

        sort($huellas);

        return $this->enviar(
            'POST',
            "/api/lecturas/{$lecturaId}/imagenes",
            ['manifiesto' => json_encode($manifiesto)],
            $archivos,
            hash('sha256', implode("\n", $huellas))
        );
    }

    public function finalizarImagenes(int $lecturaId, int $esperadas): Response
    {
        return $this->enviar('POST', "/api/lecturas/{$lecturaId}/imagenes/finalizar", ['esperadas' => (string) $esperadas]);
    }

    /** Enlace vigente de la lectura, vistas y estado de las imágenes. */
    public function enlace(int $lecturaId): Response
    {
        return $this->enviar('GET', "/api/lecturas/{$lecturaId}/enlace");
    }

    public function regenerarEnlace(int $lecturaId, bool $revocarAnteriores = false): Response
    {
        // No se reintenta: un reintento tras una respuesta perdida crearía otro enlace.
        return $this->enviar('POST', "/api/lecturas/{$lecturaId}/enlace", ['revocar_anteriores' => $revocarAnteriores ? '1' : '0'], [], null, false);
    }

    public function revocarEnlace(int $enlaceId): Response
    {
        return $this->enviar('POST', "/api/enlaces/{$enlaceId}/revocar");
    }

    /**
     * Envía la petición y, si el portal está saturado (429) o no responde (502/503/504, sin conexión),
     * la repite hasta 3 veces. Cada intento se FIRMA DE NUEVO: el portal rechaza una firma ya usada, y
     * con una pausa de al menos 1 s la marca de tiempo cambia.
     *
     * @param  array<string, mixed>  $campos
     * @param  list<array{0: string, 1: string, 2: string}>  $archivos  [campo, bytes, nombre]
     */
    private function enviar(string $metodo, string $rutaApi, array $campos = [], array $archivos = [], ?string $huellaArchivos = null, bool $reintentar = true): Response
    {
        $intentos = $reintentar ? self::INTENTOS : 1;

        for ($intento = 1; ; $intento++) {
            try {
                $respuesta = $this->enviarUnaVez($metodo, $rutaApi, $campos, $archivos, $huellaArchivos);
            } catch (ConnectionException $e) {
                if ($intento >= $intentos) {
                    throw $e;
                }
                $this->pausar($intento);

                continue;
            }

            if ($intento < $intentos && in_array($respuesta->status(), [429, 502, 503, 504], true)) {
                $this->pausar($intento);

                continue;
            }

            return $respuesta;
        }
    }

    private function pausar(int $intento): void
    {
        usleep(max(0, (int) config('entrega_resultados.portal.pausa_reintento_ms', 1000)) * 1000 * $intento);
    }

    /**
     * @param  array<string, mixed>  $campos
     * @param  list<array{0: string, 1: string, 2: string}>  $archivos
     */
    private function enviarUnaVez(string $metodo, string $rutaApi, array $campos, array $archivos, ?string $huellaArchivos): Response
    {
        $campos = $this->normalizar($campos);
        $ruta = $this->prefijoRuta().$rutaApi;
        $ts = time();

        ksort($campos);
        $payloadSha = hash('sha256', http_build_query($campos, '', '&', PHP_QUERY_RFC3986));
        $firma = base64_encode(hash_hmac(
            'sha256',
            implode("\n", [$metodo, $ruta, (string) $ts, $payloadSha, $huellaArchivos ?? hash('sha256', '')]),
            $this->secreto(),
            true
        ));

        $peticion = $this->http()->withHeaders(['X-Timestamp' => (string) $ts, 'X-Signature' => $firma]);
        foreach ($archivos as [$campo, $bytes, $nombre]) {
            $peticion = $peticion->attach($campo, $bytes, $nombre);
        }

        $url = $this->base().$rutaApi;

        return $metodo === 'GET' ? $peticion->get($url) : $peticion->post($url, $campos);
    }

    /** Igual que el portal antes de firmar: textos recortados y sin campos vacíos. */
    private function normalizar(array $campos): array
    {
        $limpios = [];
        foreach ($campos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            if ($valor === null || $valor === '') {
                continue;
            }
            $limpios[$clave] = $valor;
        }

        return $limpios;
    }

    private function http(): PendingRequest
    {
        $peticion = Http::acceptJson()->timeout((int) config('entrega_resultados.portal.timeout', 60));

        $ca = (string) config('entrega_resultados.portal.ca_bundle');
        if ($ca !== '') {
            // Confía solo en ese certificado/CA: la conexión se verifica sin depender de una CA pública.
            return $peticion->withOptions(['verify' => $ca]);
        }

        return config('entrega_resultados.portal.verificar_tls') ? $peticion : $peticion->withoutVerifying();
    }

    private function base(): string
    {
        return rtrim((string) config('app.URL_API_REPORTES'), '/');
    }

    /** Ruta de la URL base (si el portal está en un subdirectorio), parte de lo que se firma. */
    private function prefijoRuta(): string
    {
        return rtrim((string) (parse_url($this->base(), PHP_URL_PATH) ?: ''), '/');
    }

    private function secreto(): string
    {
        return (string) config('app.API_HMAC_SECRET', '');
    }
}
