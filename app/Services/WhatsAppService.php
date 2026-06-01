<?php

namespace App\Services;

use App\Models\Citas\Cita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    protected $token;

    protected $phoneNumberId;

    protected $businessAccountId;

    protected $apiVersion = 'v21.0';

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->businessAccountId = config('services.whatsapp.business_account_id');
    }

    public function enviarMensaje(string $telefono, string $mensaje): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'text',
            'text' => [
                'body' => $mensaje,
            ],
        ];

        return $this->enviarRequest($payload);
    }

    public function enviarPlantilla(string $telefono, string $templateName, array $params = []): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $components = [];
        foreach ($params as $param) {
            $components[] = [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'parameter_name' => $param['name'],
                        'text' => $param['value'],
                    ],
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'es_CO',
                ],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->enviarRequest($payload);
    }

    public function enviarConfirmacion(Cita $cita): array
    {
        $fecha = $cita->fecha_hora_inicio->format('d/m/Y');
        $hora = $cita->fecha_hora_inicio->format('H:i');
        $sede = $cita->sede?->nombre ?? 'Sede principal';
        $producto = $cita->producto?->nom_producto ?? 'Estudio';

        $mensaje = "✅ *Confirmación de Cita*\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "🕐 *Hora:* {$hora}\n";
        $mensaje .= "🏥 *Sede:* {$sede}\n";
        $mensaje .= "🔬 *Estudio:* {$producto}\n\n";

        $recomendaciones = $cita->recomendaciones();
        if ($recomendaciones->isNotEmpty()) {
            $mensaje .= "📋 *Recomendaciones:*\n";
            foreach ($recomendaciones as $rec) {
                $mensaje .= "• {$rec->recomendacion}\n";
            }
        }

        $mensaje .= "\n_recuerde llegar 15 minutos antes_";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    public function enviarRecordatorio(Cita $cita): array
    {
        $fecha = $cita->fecha_hora_inicio->format('d/m/Y');
        $hora = $cita->fecha_hora_inicio->format('H:i');
        $sede = $cita->sede?->nombre ?? 'Sede principal';

        $mensaje = "⏰ *Recordatorio de Cita*\n\n";
        $mensaje .= "Su cita está programada para mañana.\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "🕐 *Hora:* {$hora}\n";
        $mensaje .= "🏥 *Sede:* {$sede}\n\n";
        $mensaje .= "Si necesita reprogramar, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    public function enviarCancelacion(Cita $cita, string $motivo = ''): array
    {
        $mensaje = "❌ *Cita Cancelada*\n\n";
        $mensaje .= "Su cita ha sido cancelada.";

        if ($motivo) {
            $mensaje .= "\n\n*Motivo:* {$motivo}";
        }

        $mensaje .= "\n\nPara agendar una nueva cita, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    public function enviarRechazo(Cita $cita, string $motivo, string $observacion = ''): array
    {
        $mensaje = "❌ *Solicitud de Cita Rechazada*\n\n";
        $mensaje .= "Su solicitud de cita ha sido revisada.\n\n";
        $mensaje .= "*Motivo:* {$motivo}\n";

        if ($observacion) {
            $mensaje .= "*Observación:* {$observacion}\n";
        }

        $mensaje .= "\nPara más información, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    public function enviarSolicitudRecibida(Cita $cita): array
    {
        $servicio = $cita->servicio?->nombre ?? 'Estudio';

        $mensaje = "✅ *Solicitud Recibida*\n\n";
        $mensaje .= "Su solicitud de cita para *{$servicio}* ha sido registrada.\n\n";
        $mensaje .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensaje .= "Gracias por confiar en nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    public function enviarImagen(string $telefono, string $mediaUrl, ?string $caption = null): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'image',
            'image' => [
                'link' => $mediaUrl,
            ],
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->enviarRequest($payload);
    }

    public function enviarDocumento(string $telefono, string $mediaUrl, ?string $filename = null, ?string $caption = null): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'document',
            'document' => [
                'link' => $mediaUrl,
            ],
        ];

        if ($filename) {
            $payload['document']['filename'] = $filename;
        }

        if ($caption) {
            $payload['document']['caption'] = $caption;
        }

        return $this->enviarRequest($payload);
    }

    public function marcarComoLeido(string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->enviarRequest($payload);
    }

    public function descargarMedia(string $mediaId): ?string
    {
        try {
            $mediaUrl = "https://graph.facebook.com/{$this->apiVersion}/{$mediaId}";

            $mediaInfo = Http::withToken($this->token)
                ->timeout(15)
                ->get($mediaUrl)
                ->json();

            if (empty($mediaInfo['url'])) {
                Log::error('WhatsApp media info missing URL', ['mediaId' => $mediaId, 'response' => $mediaInfo]);
                return null;
            }

            $response = Http::withToken($this->token)
                ->timeout(60)
                ->get($mediaInfo['url']);

            if ($response->failed()) {
                Log::error('WhatsApp media download failed', [
                    'mediaId' => $mediaId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $mimeType = $mediaInfo['mime_type'] ?? 'application/octet-stream';
            $extension = $this->mimeToExtension($mimeType);
            $filename = 'cita_' . bin2hex(random_bytes(12)) . '.' . $extension;

            $disk = 'local';
            $path = 'citas_soportes/' . $filename;

            Storage::disk($disk)->put($path, $response->body());

            return $path;
        } catch (\Exception $e) {
            Log::error('WhatsApp media download exception: '.$e->getMessage(), [
                'mediaId' => $mediaId,
            ]);
            return null;
        }
    }

    protected function mimeToExtension(string $mime): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'video/mp4' => 'mp4',
        ];

        return $map[$mime] ?? 'bin';
    }

    protected function formatearTelefono(string $telefono): string
    {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        if (str_starts_with($telefono, '57')) {
            return $telefono;
        }

        if (str_starts_with($telefono, '0')) {
            $telefono = '57'.substr($telefono, 1);
        } elseif (str_starts_with($telefono, '3')) {
            $telefono = '57'.$telefono;
        }

        return $telefono;
    }

    protected function enviarRequest(array $payload): array
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->token)
                ->timeout(30)
                ->post($url, $payload);

            $data = $response->json();

            if ($response->failed()) {
                Log::error('WhatsApp API Error', [
                    'status' => $response->status(),
                    'response' => $data,
                ]);

                return [
                    'success' => false,
                    'error' => $data['error']['message'] ?? 'Error desconocido',
                    'error_code' => $data['error']['code'] ?? null,
                ];
            }

            return [
                'success' => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp API Exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verificarWebhook(string $payload, string $signature): bool
    {
        $secret = config('services.whatsapp.app_secret');
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function processarWebhook(array $entry): ?array
    {
        if (empty($entry['changes'])) {
            return null;
        }

        $changes = $entry['changes'][0] ?? [];
        $value = $changes['value'] ?? [];

        if (empty($value['messages'])) {
            return null;
        }

        $message = $value['messages'][0];
        $metadata = $value['metadata'] ?? [];

        $type = $message['type'] ?? null;
        $result = [
            'from' => $message['from'] ?? null,
            'id' => $message['id'] ?? null,
            'timestamp' => $message['timestamp'] ?? null,
            'type' => $type,
            'text' => null,
            'media' => null,
            'metadata' => $metadata,
            'phone_number_id' => $metadata['phone_number_id'] ?? null,
        ];

        if ($type === 'text') {
            $result['text'] = $message['text']['body'] ?? null;
        } elseif (in_array($type, ['image', 'document', 'audio', 'video'])) {
            $media = $message[$type] ?? [];
            $result['media'] = [
                'id' => $media['id'] ?? null,
                'mime_type' => $media['mime_type'] ?? null,
                'sha256' => $media['sha256'] ?? null,
                'caption' => $media['caption'] ?? null,
                'filename' => $media['filename'] ?? null,
            ];
            $result['text'] = $media['caption'] ?? null;
        }

        return $result;
    }
}
