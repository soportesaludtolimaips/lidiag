<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranscripcionService
{
    /**
     * URL del servicio de transcripcion Python.
     */
    protected string $serviceUrl;

    /**
     * Timeout en segundos para la peticion.
     */
    protected int $timeout;

    public function __construct()
    {
        $this->serviceUrl = config('services.transcriptor.url', env('TRANSCRIPTOR_SERVICE_URL', 'http://localhost:8001'));
        $this->timeout = (int) config('services.transcriptor.timeout', env('TRANSCRIPTOR_TIMEOUT', 300));
    }

    /**
     * Transcribe un archivo de audio usando el servicio Whisper local.
     *
     * @param string $audioPath Ruta absoluta del archivo de audio
     * @return array ['texto' => string, 'duracion' => float, 'idioma' => string]
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
                ->attach('file', fopen($audioPath, 'r'), basename($audioPath))
                ->post("{$this->serviceUrl}/transcribir");

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Transcripcion completada', [
                    'duracion' => $data['duracion'] ?? 0,
                    'idioma' => $data['idioma'] ?? 'es',
                    'longitud_texto' => strlen($data['texto'] ?? ''),
                ]);

                return [
                    'texto' => $data['texto'] ?? '',
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

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('No se pudo conectar al servicio de transcripcion', [
                'url' => $this->serviceUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                'No se pudo conectar al servicio de transcripcion. Verifique que el servicio Python este corriendo en ' . $this->serviceUrl
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

            throw new \Exception('Error inesperado al transcribir audio: ' . $e->getMessage());
        }
    }

    /**
     * Verifica que el servicio de transcripcion este disponible.
     *
     * @return bool
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
}
