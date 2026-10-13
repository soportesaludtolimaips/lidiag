<?php

namespace App\Services;

use App\Models\Agenda\AgendaCita;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    protected $token;

    protected $phoneNumberId;

    protected $businessAccountId;

    protected $apiVersion = 'v21.0';

    /**
     * Inicializa el servicio con las credenciales de la API de WhatsApp desde la configuración.
     */
    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->businessAccountId = config('services.whatsapp.business_account_id');
    }

    /**
     * Envía un mensaje de texto simple a un número de teléfono vía WhatsApp.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $mensaje  Contenido del mensaje de texto
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Envía una plantilla de mensaje de WhatsApp con parámetros opcionales al cuerpo.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $templateName  Nombre de la plantilla aprobada en WhatsApp
     * @param  array  $params  Parámetros a sustituir en el cuerpo de la plantilla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarPlantilla(string $telefono, string $templateName, array $params = []): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $components = [];
        if (! empty($params)) {
            $bodyParams = [];
            foreach ($params as $param) {
                $bodyParams[] = [
                    'type' => 'text',
                    'text' => $param['text'] ?? $param['value'] ?? $param,
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
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

    /**
     * Envía un mensaje de confirmación de cita con los datos y recomendaciones del paciente.
     *
     * @param  AgendaCita  $cita  Cita agendada cuyos datos se incluyen en el mensaje
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarConfirmacion(AgendaCita $cita): array
    {
        $fecha = $cita->fecha_hora_inicio->format('d/m/Y');
        $hora = $cita->fecha_hora_inicio->format('H:i');
        $sede = $cita->sede?->nom_sede ?? 'Sede principal';
        $producto = $cita->producto?->nom_produc ?? 'Estudio';

        $mensaje = "✅ *Confirmación de Cita*\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "🕐 *Hora:* {$hora}\n";
        $mensaje .= "🏥 *Sede:* {$sede}\n";
        $mensaje .= "🔬 *Estudio:* {$producto}\n\n";

        $recomendaciones = $cita->recomendaciones();
        if ($recomendaciones->isNotEmpty()) {
            $mensaje .= "📋 *Recomendaciones:*\n";
            foreach ($recomendaciones as $rec) {
                $texto = $rec->recomendacion;
                $texto = str_replace(
                    ['${num_docu_paciente}', '${nom_paciente}', '${cod_cups}', '${nom_produc}'],
                    [$cita->paciente?->num_docu ?? '', $cita->paciente?->nombres ?? '', $cita->producto?->cod_cups ?? '', $cita->producto?->nom_produc ?? ''],
                    $texto
                );
                $mensaje .= "• {$texto}\n";
            }
        }

        $mensaje .= "\n_recuerde llegar 15 minutos antes_";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    /**
     * Envía un recordatorio de cita programada para el día siguiente.
     *
     * @param  AgendaCita  $cita  Cita agendada a recordar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarRecordatorio(AgendaCita $cita): array
    {
        $fecha = $cita->fecha_hora_inicio->format('d/m/Y');
        $hora = $cita->fecha_hora_inicio->format('H:i');
        $sede = $cita->sede?->nom_sede ?? 'Sede principal';

        $mensaje = "⏰ *Recordatorio de Cita*\n\n";
        $mensaje .= "Su cita está programada para mañana.\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "🕐 *Hora:* {$hora}\n";
        $mensaje .= "🏥 *Sede:* {$sede}\n\n";
        $mensaje .= "Si necesita reprogramar, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    /**
     * Envía un recordatorio de cita aproximadamente dos horas antes de su horario.
     *
     * @param  AgendaCita  $cita  Cita agendada a recordar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarRecordatorio2h(AgendaCita $cita): array
    {
        $fecha = $cita->fecha_hora_inicio->format('d/m/Y');
        $hora = $cita->fecha_hora_inicio->format('H:i');
        $sede = $cita->sede?->nom_sede ?? 'Sede principal';

        $mensaje = "⏰ *Recordatorio de Cita*\n\n";
        $mensaje .= "Su cita está programada para dentro de aproximadamente 2 horas.\n\n";
        $mensaje .= "📅 *Fecha:* {$fecha}\n";
        $mensaje .= "🕐 *Hora:* {$hora}\n";
        $mensaje .= "🏥 *Sede:* {$sede}\n\n";
        $mensaje .= "Por favor, llegue con 15 minutos de anticipación.\n";
        $mensaje .= "Si necesita reprogramar, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    /**
     * Envía un aviso de cancelación de cita con el motivo, si se proporciona.
     *
     * @param  AgendaCita  $cita  Cita cancelada
     * @param  string  $motivo  Motivo de la cancelación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarCancelacion(AgendaCita $cita, string $motivo = ''): array
    {
        $mensaje = "❌ *Cita Cancelada*\n\n";
        $mensaje .= "Su cita ha sido cancelada.";

        if ($motivo) {
            $mensaje .= "\n\n*Motivo:* {$motivo}";
        }

        $mensaje .= "\n\nPara agendar una nueva cita, comuníquese con nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    /**
     * Envía un aviso de rechazo de solicitud de cita con motivo y observación.
     *
     * @param  AgendaCita  $cita  Solicitud de cita rechazada
     * @param  string  $motivo  Motivo del rechazo
     * @param  string  $observacion  Observación adicional sobre el rechazo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarRechazo(AgendaCita $cita, string $motivo, string $observacion = ''): array
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

    /**
     * Envía un mensaje confirmando la recepción de la solicitud de cita del paciente.
     *
     * @param  AgendaCita  $cita  Solicitud de cita registrada
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarSolicitudRecibida(AgendaCita $cita): array
    {
        $servicio = $cita->servicio?->nombre ?? 'Estudio';

        $mensaje = "✅ *Solicitud Recibida*\n\n";
        $mensaje .= "Su solicitud de cita para *{$servicio}* ha sido registrada.\n\n";
        $mensaje .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensaje .= "Gracias por confiar en nosotros.";

        return $this->enviarMensaje($cita->paciente?->cel ?? '', $mensaje);
    }

    /**
     * Envía una imagen por WhatsApp desde una URL pública con un pie de foto opcional.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $mediaUrl  URL pública de la imagen a enviar
     * @param  string|null  $caption  Pie de foto opcional
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Envía un documento por WhatsApp desde una URL pública con nombre y pie opcionales.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $mediaUrl  URL pública del documento a enviar
     * @param  string|null  $filename  Nombre del archivo mostrado al destinatario
     * @param  string|null  $caption  Pie de documento opcional
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Envía un mensaje interactivo de lista con secciones y botón de selección.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $body  Texto del cuerpo del mensaje interactivo
     * @param  string  $buttonText  Texto del botón que despliega la lista
     * @param  array  $sections  Secciones y opciones de la lista
     * @param  string|null  $headerText  Encabezado opcional del mensaje
     * @param  string|null  $footerText  Pie opcional del mensaje
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarLista(string $telefono, string $body, string $buttonText, array $sections, ?string $headerText = null, ?string $footerText = null): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $interactive = [
            'type' => 'list',
            'action' => [
                'button' => $buttonText,
                'sections' => $sections,
            ],
        ];

        if ($headerText) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => $headerText,
            ];
        }

        $interactive['body'] = ['text' => $body];

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->enviarRequest($payload);
    }

    /**
     * Envía un mensaje interactivo con botones de respuesta rápida.
     *
     * @param  string  $telefono  Número de teléfono del destinatario
     * @param  string  $body  Texto del cuerpo del mensaje interactivo
     * @param  array  $buttons  Botones de respuesta con id y título
     * @param  string|null  $headerText  Encabezado opcional del mensaje
     * @param  string|null  $footerText  Pie opcional del mensaje
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function enviarBotones(string $telefono, string $body, array $buttons, ?string $headerText = null, ?string $footerText = null): array
    {
        $telefono = $this->formatearTelefono($telefono);

        $actionButtons = [];
        foreach ($buttons as $btn) {
            $actionButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) $btn['id'],
                    'title' => $btn['title'],
                ],
            ];
        }

        $interactive = [
            'type' => 'button',
            'action' => [
                'buttons' => $actionButtons,
            ],
            'body' => ['text' => $body],
        ];

        if ($headerText) {
            $interactive['header'] = [
                'type' => 'text',
                'text' => $headerText,
            ];
        }

        if ($footerText) {
            $interactive['footer'] = ['text' => $footerText];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $telefono,
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        return $this->enviarRequest($payload);
    }

    /**
     * Marca un mensaje de WhatsApp como leído mediante la API de Graph.
     *
     * @param  string  $messageId  Identificador del mensaje a marcar como leído
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function marcarComoLeido(string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->enviarRequest($payload);
    }

    /**
     * Descarga un archivo multimedia de WhatsApp y lo almacena en el disco local.
     *
     * @param  string  $mediaId  Identificador del medio en WhatsApp
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
            $filename = 'cita_'.bin2hex(random_bytes(12)).'.'.$extension;

            $disk = 'local';
            $path = 'citas_soportes/'.$filename;

            Storage::disk($disk)->put($path, $response->body());

            return $path;
        } catch (\Exception $e) {
            Log::error('WhatsApp media download exception: '.$e->getMessage(), [
                'mediaId' => $mediaId,
            ]);

            return null;
        }
    }

    /**
     * Convierte un tipo MIME en su extensión de archivo correspondiente.
     *
     * @param  string  $mime  Tipo MIME a convertir
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Normaliza un número de teléfono agregando el prefijo del país de Colombia si falta.
     *
     * @param  string  $telefono  Número de teléfono a formatear
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Envía una petición HTTP a la API de WhatsApp y procesa la respuesta.
     *
     * @param  array  $payload  Cuerpo de la petición a enviar a la API
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Verifica la firma HMAC del payload recibido en el webhook de WhatsApp.
     *
     * @param  string  $payload  Cuerpo crudo de la petición del webhook
     * @param  string  $signature  Firma enviada por WhatsApp en el encabezado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function verificarWebhook(string $payload, string $signature): bool
    {
        $secret = config('services.whatsapp.app_secret');
        $expected = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Procesa la entrada del webhook de WhatsApp y extrae los datos del primer mensaje.
     *
     * @param  array  $entry  Entrada del webhook con los cambios y mensajes recibidos
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
        } elseif ($type === 'interactive') {
            $interactive = $message['interactive'] ?? [];
            $subtype = $interactive['type'] ?? null;
            $payload = $interactive[$subtype] ?? [];

            $result['interactive'] = [
                'type' => $subtype,
                'id' => $payload['id'] ?? null,
                'title' => $payload['title'] ?? null,
                'description' => $payload['description'] ?? null,
            ];
            $result['text'] = $payload['id'] ?? $payload['title'] ?? null;
        }

        return $result;
    }
}
