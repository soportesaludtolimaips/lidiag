<?php

namespace App\Services;

use App\Models\Citas\Cita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function enviarSolicitudRecibida(Cita $cita): array
    {
        $servicio = $cita->servicio?->nombre ?? 'Estudio';

        $mensaje = "✅ *Solicitud Recibida*\n\n";
        $mensaje .= "Su solicitud de cita para *{$servicio}* ha sido registrada.\n\n";
        $mensaje .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensaje .= "Gracias por confiar en nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
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

        return [
            'from' => $message['from'] ?? null,
            'id' => $message['id'] ?? null,
            'timestamp' => $message['timestamp'] ?? null,
            'type' => $message['type'] ?? null,
            'text' => $message['text']['body'] ?? null,
            'metadata' => $metadata,
        ];
    }
}
