<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscripcionService
{
    /**
     * Marcas temporales (área de uso privado de Unicode) que dejan señalado dónde había una orden hablada
     * mientras se resuelve la palabra siguiente.
     */
    private const MARCA_INICIO = "\u{E000}";

    private const MARCA_FIN = "\u{E001}";

    /**
     * URL del servicio de transcripcion Python.
     */
    protected string $serviceUrl;

    /**
     * Timeout en segundos para la peticion.
     */
    protected int $timeout;

    /**
     * Inicializa el servicio de transcripción con la URL y el timeout configurados.
     */
    public function __construct()
    {
        $this->serviceUrl = config('services.transcriptor.url', env('TRANSCRIPTOR_SERVICE_URL', 'http://localhost:8001'));
        $this->timeout = (int) config('services.transcriptor.timeout', env('TRANSCRIPTOR_TIMEOUT', 300));
    }

    /**
     * Transcribe un archivo de audio usando el servicio Whisper local.
     *
     * @param  string  $audioPath  Ruta absoluta del archivo de audio
     * @return array ['texto' => string, 'duracion' => float, 'idioma' => string]
     *
     * @throws \Exception
     */
    public function transcribir(string $audioPath): array
    {
        if (! file_exists($audioPath)) {
            throw new \Exception("El archivo de audio no existe: {$audioPath}");
        }

        Log::info('Iniciando transcripcion de audio', ['path' => $audioPath]);

        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', file_get_contents($audioPath), basename($audioPath))
                ->post("{$this->serviceUrl}/transcribir");

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Transcripcion completada', [
                    'duracion' => $data['duracion'] ?? 0,
                    'idioma' => $data['idioma'] ?? 'es',
                    'longitud_texto' => mb_strlen($data['texto'] ?? '', 'UTF-8'),
                    'muestra_texto' => mb_substr($data['texto'] ?? '', 0, 100, 'UTF-8'),
                ]);

                return [
                    'texto' => self::convertirOrdenesHabladas((string) ($data['texto'] ?? '')),
                    'duracion' => (float) ($data['duracion'] ?? 0),
                    'idioma' => $data['idioma'] ?? 'es',
                ];
            }

            $statusCode = $response->status();
            $errorData = $response->json();
            $errorMessage = $errorData['detail'] ?? 'Error desconocido del servicio de transcripcion';

            Log::error('Error en servicio de transcripcion', [
                'status' => $statusCode,
                'error' => $errorMessage,
            ]);

            throw new \Exception("Error transcribiendo audio: {$errorMessage} (HTTP {$statusCode})");
        } catch (ConnectionException $e) {
            Log::error('No se pudo conectar al servicio de transcripcion', [
                'url' => $this->serviceUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'No se pudo conectar al servicio de transcripcion. Verifique que el servicio Python este corriendo en '.$this->serviceUrl
            );
        } catch (\Exception $e) {
            if (str_starts_with($e->getMessage(), 'Error transcribiendo audio') ||
                str_starts_with($e->getMessage(), 'No se pudo conectar')) {
                throw $e;
            }

            Log::error('Error inesperado en transcripcion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('Error inesperado al transcribir audio: '.$e->getMessage());
        }
    }

    /**
     * Verifica que el servicio de transcripcion este disponible.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(10)->get("{$this->serviceUrl}/health");

            if ($response->successful()) {
                $data = $response->json();

                return ($data['status'] ?? '') === 'ok' && ($data['model_loaded'] ?? false);
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('Servicio de transcripcion no disponible', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Convierte en signos las únicas tres órdenes habladas que se entienden en el texto que devuelve Whisper.
     *
     * Flujo del proceso:
     * 1. «punto y coma» pasa a «;» pegado a la palabra anterior.
     * 2. «punto y aparte» (y «punto aparte», «punto a parte», «punto y a parte») pasa a «.» más un párrafo nuevo,
     *    y la palabra siguiente empieza con mayúscula.
     * 3. «espacio» se entiende como orden solo en «dar espacio» o aislado por la puntuación que Whisper pone en
     *    las pausas («..., espacio, ...»). Suelto dentro de una frase se respeta: «espacio pleural» o «espacio
     *    discal» son términos clínicos.
     *
     * Whisper suele rodear la orden con comas o puntos y poner mayúscula a la palabra siguiente: se absorben. Si el
     * texto no trae ninguna orden se devuelve idéntico; aplicarla dos veces da el mismo resultado.
     *
     * @param  string  $texto  Texto devuelto por el servicio de transcripción.
     * @return string Texto con las órdenes convertidas.
     */
    public static function convertirOrdenesHabladas(string $texto): string
    {
        if ($texto === '' || str_contains($texto, self::MARCA_INICIO) || str_contains($texto, self::MARCA_FIN)) {
            return $texto;
        }

        $convertido = $texto;
        $cambios = 0;
        foreach (self::patronesOrdenes() as [$tipo, $patron]) {
            $resultado = preg_replace_callback(
                $patron,
                static fn (array $m): string => self::MARCA_INICIO.$tipo
                    .((($m[1] ?? '') === '.' || ($m[2] ?? '') === '.') ? '1' : '0').self::MARCA_FIN,
                $convertido,
                -1,
                $encontradas
            );
            if ($resultado === null) {
                return $texto;
            }
            $convertido = $resultado;
            $cambios += $encontradas;
        }

        if ($cambios === 0) {
            return $texto;
        }

        $resuelto = preg_replace_callback(
            '/\x{E000}([SAE])([01])\x{E001}[ \t]*(?:(\p{Lu})(?=\p{Ll})|(\p{L}))?/u',
            static function (array $m): string {
                $capitalizada = $m[3] ?? '';
                $letra = $capitalizada.($m[4] ?? '');

                if ($m[1] === 'A') {
                    return ".\n\n".mb_strtoupper($letra);
                }

                // Si Whisper puso un punto junto a la orden, la palabra siguiente quedó con mayúscula de inicio de frase.
                if ($m[2] === '1' && $capitalizada !== '') {
                    $letra = mb_strtolower($capitalizada);
                }

                return ($m[1] === 'S' ? '; ' : ' ').$letra;
            },
            $convertido
        );

        return $resuelto === null ? $texto : trim($resuelto, " \t\n");
    }

    /**
     * Expresiones que localizan cada orden hablada junto con la puntuación que Whisper suele poner a su alrededor.
     *
     * @return list<array{0: string, 1: string}> Pares [tipo de orden, expresión regular]: S = punto y coma,
     *                                           A = punto y aparte, E = espacio.
     */
    private static function patronesOrdenes(): array
    {
        $izquierda = '(?<![\p{L}\p{N}_])';
        $derecha = '(?![\p{L}\p{N}_])';
        $antes = '[ \t]*(?:([,.;:])[ \t]*)?';
        $despues = '(?:[ \t]*([,.]))?[ \t]*';

        return [
            ['S', '/'.$antes.$izquierda.'punto[ \t]+y[ \t]+coma'.$derecha.$despues.'/iu'],
            ['A', '/'.$antes.$izquierda.'punto[ \t]+(?:y[ \t]+)?a[ \t]?parte'.$derecha.$despues.'/iu'],
            ['E', '/'.$antes.$izquierda.'dar[ \t]+espacio'.$derecha.$despues.'/iu'],
            ['E', '/[ \t]*([,.;:])[ \t]*'.$izquierda.'espacio'.$derecha.'[ \t]*([,.;:])[ \t]*/iu'],
        ];
    }
}
