<?php

namespace App\Services\Agenda;

use App\Helpers\AuditLogger;
use App\Models\Agenda\AgendaCita;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;
use App\Services\BaseService;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgendaCitaService extends BaseService
{
    /**
     * Constructor del servicio de citas de agenda.
     *
     * @param  WhatsAppService  $whatsAppService  servicio de mensajería WhatsApp.
     * @param  WhatsAppFlowService  $whatsAppFlowService  servicio de flujos de WhatsApp.
     * @param  AgendaCitaQueryService  $queryService  servicio de consultas y lecturas de citas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly WhatsAppFlowService $whatsAppFlowService,
        private readonly AgendaCitaQueryService $queryService,
    ) {}

    /**
     * Obtiene todas las citas con filtros opcionales y paginación.
     *
     * @param  Request|array  $request  parámetros de búsqueda: estado, fechas, servicio, sede, búsqueda de paciente.
     *
     * @returns array resultado con las citas paginadas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function all(Request|array $request): array
    {
        $filters = $request instanceof Request ? $request->all() : $request;

        return $this->queryService->all($filters);
    }

    /**
     * Busca una cita por su modelo con relaciones cargadas.
     *
     * @param  AgendaCita  $cita  modelo de la cita a consultar.
     *
     * @returns array resultado con la cita y sus relaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function find(AgendaCita $cita): array
    {
        $cita->load(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra', 'quienConfirma']);

        return $this->success($cita);
    }

    /**
     * Crea una nueva solicitud de cita buscando o creando el paciente asociado.
     *
     * @param  array  $data  datos de la cita incluyendo información del paciente.
     *
     * @returns array resultado con la cita creada o error si no se pudo crear el paciente.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function create(array $data): array
    {
        $paciente = $this->buscarOCrearPaciente($data);

        if (! $paciente) {
            return $this->error('No se pudo crear el paciente', 422);
        }

        $cita = AgendaCita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $data['servicio_id'],
            'quien_registro_id' => Auth::id(),
            'numero_orden' => $data['numero_orden'] ?? null,
            'numero_autorizacion' => $data['numero_autorizacion'] ?? null,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        $this->whatsAppService->enviarSolicitudRecibida($cita);

        AuditLogger::crear(
            $cita,
            "Solicitud de cita #{$cita->id} registrada para paciente ID: {$paciente->id} por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->created([
            'message' => 'Solicitud de cita creada exitosamente',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ]);
    }

    /**
     * Actualiza los datos de una cita existente.
     *
     * @param  AgendaCita  $cita  modelo de la cita a actualizar.
     * @param  array  $data  datos a actualizar.
     *
     * @returns array resultado con la cita actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function update(AgendaCita $cita, array $data): array
    {
        $cita->update($data);

        return $this->success([
            'message' => 'Cita actualizada',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ]);
    }

    /**
     * Elimina una cita solo si está en estado solicitada o cancelada.
     *
     * @param  AgendaCita  $cita  modelo de la cita a eliminar.
     *
     * @returns array resultado de la eliminación o error si tiene estado asignada/confirmada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function delete(AgendaCita $cita): array
    {
        if (! in_array($cita->estado, ['solicitada', 'cancelada'])) {
            return $this->error('No se puede eliminar una cita asignada o confirmada', 422);
        }

        AuditLogger::eliminar(
            $cita,
            "Cita #{$cita->id} eliminada por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        $cita->delete();

        return $this->deleted('Cita eliminada');
    }

    /**
     * Asigna producto, sede, horario y duración a una cita solicitada, marcándola como asignada.
     *
     * @param  AgendaCita  $cita  modelo de la cita a asignar.
     * @param  array  $data  datos de asignación (producto_id, sede_id, fecha, duración).
     *
     * @returns array resultado con la cita asignada y notificación WhatsApp enviada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function asignar(AgendaCita $cita, array $data): array
    {
        $fechaInicio = Carbon::parse($data['fecha_hora_inicio']);
        $duracion = $data['duracion_minutos'] ?? 30;
        $fechaFin = $fechaInicio->copy()->addMinutes($duracion);

        $this->transaction(function () use ($cita, $data, $fechaInicio, $fechaFin, $duracion) {
            $cita->update([
                'producto_id' => $data['producto_id'],
                'sede_id' => $data['sede_id'],
                'agenda_disponibilidad_detalle_id' => $data['agenda_disponibilidad_detalle_id'] ?? null,
                'fecha_hora_inicio' => $fechaInicio,
                'fecha_hora_fin' => $fechaFin,
                'duracion_minutos' => $duracion,
                'estado' => 'asignada',
                'revisada' => true,
                'observaciones' => $data['observaciones'] ?? $cita->observaciones,
            ]);

            if (! empty($data['agenda_disponibilidad_detalle_id'])) {
                AgendaDisponibilidaDetalle::where('id', $data['agenda_disponibilidad_detalle_id'])
                    ->where('estado', 'Disponible')
                    ->update(['estado' => 'Reservado']);
            }
        });

        $cita->refresh()->load(['paciente', 'servicio', 'producto', 'sede']);

        if ($cita->paciente && empty($cita->paciente->cel)) {
            $conv = WhatsAppConversation::where('cita_id', $cita->id)->first();
            if ($conv && ! empty($conv->telefono)) {
                $cita->paciente->update(['cel' => $conv->telefono]);
                $cita->refresh()->load(['paciente', 'servicio', 'producto', 'sede']);
            }
        }

        $this->whatsAppService->enviarConfirmacion($cita);

        AuditLogger::log(
            'asignar_cita',
            $cita,
            "Cita #{$cita->id} asignada a sede ID: {$data['sede_id']}, fecha: ".($data['fecha_cita'] ?? 'N/A').' por usuario: '.(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Cita asignada y notificación enviada',
            'cita' => $cita,
        ]);
    }

    /**
     * Confirma una cita que está en estado asignada.
     *
     * @param  AgendaCita  $cita  modelo de la cita a confirmar.
     *
     * @returns array resultado con la cita confirmada o error si no está asignada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function confirmar(AgendaCita $cita): array
    {
        if (! in_array($cita->estado, ['asignada'])) {
            return $this->error('Solo se pueden confirmar citas asignadas', 422);
        }

        $cita->update([
            'estado' => 'confirmada',
            'quien_confirma_id' => Auth::id(),
        ]);

        AuditLogger::log(
            'confirmar_cita',
            $cita,
            "Cita #{$cita->id} confirmada por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Cita confirmada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'producto', 'sede']),
        ]);
    }

    /**
     * Cancela una cita con un motivo y envía notificación por WhatsApp.
     *
     * @param  AgendaCita  $cita  modelo de la cita a cancelar.
     * @param  string  $motivo  motivo de la cancelación.
     *
     * @returns array resultado con la cita cancelada o error si está cumplida/no_asistio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function cancelar(AgendaCita $cita, string $motivo = ''): array
    {
        if (in_array($cita->estado, ['cumplida', 'no_asistio'])) {
            return $this->error('No se puede cancelar una cita cumplida o con inasistencia', 422);
        }

        $cita->update([
            'estado' => 'cancelada',
            'observaciones' => $cita->observaciones."\nMotivo cancelación: ".$motivo,
        ]);

        $this->whatsAppService->enviarCancelacion($cita, $motivo);

        AuditLogger::log(
            'cancelar_cita',
            $cita,
            "Cita #{$cita->id} cancelada. Motivo: {$motivo} por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Cita cancelada',
            'cita' => $cita->fresh(),
        ]);
    }

    /**
     * Marca una cita confirmada como cumplida.
     *
     * @param  AgendaCita  $cita  modelo de la cita a marcar como cumplida.
     *
     * @returns array resultado con la cita cumplida o error si no está confirmada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function cumplir(AgendaCita $cita): array
    {
        if ($cita->estado !== 'confirmada') {
            return $this->error('Solo se pueden marcar como cumplidas las citas confirmadas', 422);
        }

        $cita->update(['estado' => 'cumplida']);

        AuditLogger::log(
            'cumplir_cita',
            $cita,
            "Cita #{$cita->id} marcada como cumplida por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Cita marcada como cumplida',
            'cita' => $cita->fresh(),
        ]);
    }

    /**
     * Registra inasistencia del paciente a una cita confirmada.
     *
     * @param  AgendaCita  $cita  modelo de la cita a marcar como inasistencia.
     *
     * @returns array resultado con la inasistencia registrada o error si no está confirmada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function noAsistio(AgendaCita $cita): array
    {
        if ($cita->estado !== 'confirmada') {
            return $this->error('Solo se puede marcar inasistencia en citas confirmadas', 422);
        }

        $cita->update(['estado' => 'no_asistio']);

        AuditLogger::log(
            'inasistencia_cita',
            $cita,
            "Inasistencia registrada para cita #{$cita->id} por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Inasistencia registrada',
            'cita' => $cita->fresh(),
        ]);
    }

    /**
     * Obtiene las citas solicitadas pendientes de revisión con filtros opcionales.
     *
     * @param  Request|array  $request  parámetros de filtrado: servicio, sede, búsqueda de paciente.
     *
     * @returns array resultado con las citas pendientes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function pendientes(Request|array $request): array
    {
        $filters = $request instanceof Request ? $request->all() : $request;

        return $this->queryService->pendientes($filters);
    }

    /**
     * Rechaza una cita solicitada con un motivo de rechazo configurado y envía notificación.
     *
     * @param  AgendaCita  $cita  modelo de la cita a rechazar.
     * @param  array  $data  datos del rechazo: rechazo_motivo_id, rechazo_observacion.
     *
     * @returns array resultado con la cita rechazada o error si no está solicitada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function rechazar(AgendaCita $cita, array $data): array
    {
        if ($cita->estado !== 'solicitada') {
            return $this->error('Solo se pueden rechazar citas solicitadas', 422);
        }

        $motivo = ConfigCitaRechazoMotivo::find($data['rechazo_motivo_id']);

        $cita->update([
            'estado' => 'rechazada',
            'revisada' => true,
            'rechazo_motivo_id' => $data['rechazo_motivo_id'],
            'rechazo_observacion' => $data['rechazo_observacion'] ?? null,
        ]);

        $this->whatsAppService->enviarRechazo(
            $cita,
            $motivo?->motivo ?? 'Motivo no especificado',
            $data['rechazo_observacion'] ?? ''
        );

        AuditLogger::log(
            'rechazar_cita',
            $cita,
            "Cita #{$cita->id} rechazada. Motivo: ".($motivo?->motivo ?? 'No especificado').' por usuario: '.(Auth::user()?->name ?? ('ID: '.Auth::id()))
        );

        return $this->success([
            'message' => 'Cita rechazada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'rechazoMotivo']),
        ]);
    }

    /**
     * Consulta los slots disponibles para una fecha, sede y servicio específicos.
     *
     * @param  array  $data  parámetros de consulta: fecha, sede_id, servicio_id.
     *
     * @returns array resultado con los horarios disponibles y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function disponibilidad(array $data): array
    {
        return $this->queryService->disponibilidad($data);
    }

    /**
     * Obtiene la lista de servicios de cita activos.
     *
     * @returns array resultado con los servicios activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function servicios(): array
    {
        return $this->queryService->servicios();
    }

    /**
     * Obtiene los productos activos asociados a un servicio de cita, con filtro opcional por sede.
     *
     * @param  int  $servicioCitaId  identificador del servicio de cita.
     * @param  string|null  $sedeId  identificador de la sede para filtrar (opcional).
     *
     * @returns array resultado con los productos del servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function productosPorServicio(int $servicioCitaId, ?string $sedeId = null): array
    {
        return $this->queryService->productosPorServicio($servicioCitaId, $sedeId);
    }

    /**
     * Maneja la verificación del webhook de WhatsApp (challenge response).
     *
     * @param  string  $mode  modo de suscripción.
     * @param  string  $token  token de verificación.
     * @param  string  $challenge  valor challenge a retornar.
     *
     * @returns array resultado con el challenge o error de token inválido.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function webhookGet(string $mode, string $token, string $challenge): array
    {
        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return $this->success(['challenge' => $challenge]);
        }

        return $this->error('Token inválido', 403);
    }

    /**
     * Procesa los mensajes entrantes del webhook de WhatsApp, descarga media y ejecuta el flujo conversacional.
     *
     * @param  string  $payload  cuerpo raw de la petición.
     * @param  string  $signature  firma HMAC del webhook.
     * @param  array  $data  datos parseados del webhook.
     *
     * @returns array resultado del procesamiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function webhookPost(string $payload, string $signature, array $data): array
    {
        $secret = config('services.whatsapp.app_secret');

        if (! $secret) {
            Log::critical('WhatsApp webhook: app_secret no configurado - rechazando solicitudes');

            return $this->error('Webhook no configurado', 500);
        }

        if (! $signature || ! $this->whatsAppService->verificarWebhook($payload, $signature)) {
            Log::warning('WhatsApp webhook: firma inválida', [
                'signature' => $signature,
            ]);

            return $this->error('Firma inválida', 401);
        }

        Log::info('WhatsApp webhook recibido', [
            'has_entries' => ! empty($data['entry']),
            'entry_count' => count($data['entry'] ?? []),
        ]);

        if (empty($data['entry'])) {
            return $this->success(['message' => 'OK']);
        }

        foreach ($data['entry'] as $entry) {
            $value = $entry['changes'][0]['value'] ?? [];
            Log::info('WhatsApp webhook entry', [
                'has_messages' => ! empty($value['messages']),
                'has_statuses' => ! empty($value['statuses']),
                'messages_count' => count($value['messages'] ?? []),
            ]);
            $mensaje = $this->whatsAppService->processarWebhook($entry);

            if (! $mensaje || ! $mensaje['from']) {
                continue;
            }

            try {
                $messageId = $mensaje['id'] ?? null;

                $mediaPath = null;
                $mediaDownloadError = false;
                if (in_array($mensaje['type'], ['image', 'document'])) {
                    $mediaId = $mensaje['media']['id'] ?? null;
                    if ($mediaId) {
                        $mediaPath = $this->whatsAppService->descargarMedia($mediaId);
                        if (! $mediaPath) {
                            $mediaDownloadError = true;
                        }
                    }
                }

                if ($mediaDownloadError) {
                    $this->whatsAppService->enviarMensaje(
                        $mensaje['from'],
                        "❌ No pudimos descargar su archivo. Intente enviarlo nuevamente.\n_(Formatos aceptados: JPG, PNG, PDF — máx 10MB)_"
                    );
                    if ($messageId) {
                        $this->whatsAppService->marcarComoLeido($messageId);
                    }

                    continue;
                }

                $respuesta = $this->whatsAppFlowService->procesarMensaje(
                    $mensaje['from'],
                    $mensaje['text'] ?? '',
                    $mensaje['type'] ?? 'text',
                    $mediaPath,
                    $mensaje['media'] ?? null,
                    $mensaje['phone_number_id'] ?? null
                );

                if ($respuesta) {
                    $this->enviarRespuesta($mensaje['from'], $respuesta);
                }

                if ($messageId) {
                    $this->whatsAppService->marcarComoLeido($messageId);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsApp webhook: error procesando mensaje (SAHI)', [
                    'telefono' => $mensaje['from'],
                    'tipo' => $mensaje['type'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return $this->success(['message' => 'OK']);
    }

    /**
     * Envía una respuesta por WhatsApp según el tipo (texto, lista o botones).
     *
     * @param  string  $telefono  número de teléfono del destinatario.
     * @param  array  $respuesta  datos de la respuesta a enviar (type, text/body, buttons/sections).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function enviarRespuesta(string $telefono, array $respuesta): void
    {
        $tipo = $respuesta['type'] ?? 'text';

        if ($tipo === 'list') {
            $this->whatsAppService->enviarLista(
                $telefono,
                $respuesta['body'],
                $respuesta['button'],
                $respuesta['sections'],
                $respuesta['header'] ?? null,
                $respuesta['footer'] ?? null
            );

            return;
        }

        if ($tipo === 'button') {
            $this->whatsAppService->enviarBotones(
                $telefono,
                $respuesta['body'],
                $respuesta['buttons'],
                $respuesta['header'] ?? null,
                $respuesta['footer'] ?? null
            );

            return;
        }

        $this->whatsAppService->enviarMensaje($telefono, $respuesta['text'] ?? '');
    }

    /**
     * Envía recomendaciones preoperatorias al paciente por WhatsApp según el producto de la cita.
     *
     * @param  AgendaCita  $cita  modelo de la cita con producto y paciente asociados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function enviarRecomendacionesWhatsApp(AgendaCita $cita): void
    {
        if (! $cita->producto_id) {
            return;
        }

        $recomendaciones = ConfigRecomendacionProducto::where('producto_id', $cita->producto_id)
            ->where('estado', true)
            ->get();

        if ($recomendaciones->isEmpty()) {
            return;
        }

        $paciente = $cita->paciente;
        $telefono = $paciente->cel ?? $paciente->tel ?? $paciente->telefono ?? null;

        if (! $telefono) {
            return;
        }

        $lineas = [
            "*📋 Recomendaciones para tu cita*",
            '',
            "📍 *Sede:* ".($cita->sede->nom_sede ?? 'N/A'),
            "🩺 *Servicio:* ".($cita->servicio->nombre ?? 'N/A'),
            "🗓️ *Fecha:* ".$cita->fecha_hora_inicio->format('d/m/Y'),
            "🕐 *Hora:* ".$cita->fecha_hora_inicio->format('H:i'),
            '',
            "📋 *Recomendaciones:*",
        ];

        foreach ($recomendaciones as $rec) {
            $texto = $rec->recomendacion;
            $texto = str_replace(
                ['${num_docu_paciente}', '${nom_paciente}', '${cod_cups}', '${nom_produc}'],
                [$paciente->num_docu ?? '', $paciente->nombres ?? '', $cita->producto?->cod_cups ?? '', $cita->producto?->nom_produc ?? ''],
                $texto
            );
            $lineas[] = '• '.$texto;
        }

        $mensaje = implode("\n", $lineas);

        $this->whatsAppService->enviarMensaje($telefono, $mensaje);
    }

    /**
     * Envía un mensaje de prueba por WhatsApp a un número específico.
     *
     * @param  string  $telefono  número de teléfono destino.
     * @param  string  $mensaje  contenido del mensaje a enviar.
     *
     * @returns array resultado con los datos del envío.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function testEnviar(string $telefono, string $mensaje): array
    {
        $resultado = $this->whatsAppService->enviarMensaje($telefono, $mensaje);

        return $this->success([
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'resultado' => $resultado,
        ]);
    }

    /**
     * Obtiene el conteo y las últimas notificaciones no leídas de citas del usuario autenticado.
     *
     * @returns array resultado con el conteo y la lista de notificaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function notificacionesNoLeidas(): array
    {
        $user = Auth::user();
        if (! $user) {
            return $this->success([
                'count' => 0,
                'notifications' => [],
            ]);
        }

        try {
            $count = $user->unreadNotifications()
                ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
                ->count();

            $notifications = $user->unreadNotifications()
                ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($n) {
                    $data = is_array($n->data) ? $n->data : (json_decode((string) ($n->data ?? ''), true) ?: []);

                    return [
                        'id' => (string) $n->id,
                        'mensaje' => $data['mensaje'] ?? '',
                        'url' => $data['url'] ?? '/citas/revision',
                        'created_at' => $n->created_at ? $n->created_at->diffForHumans() : '',
                    ];
                });

            return $this->success([
                'count' => $count,
                'notifications' => $notifications,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en AgendaCitaService::notificacionesNoLeidas: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->success([
                'count' => 0,
                'notifications' => [],
            ]);
        }
    }

    /**
     * Marca una notificación específica como leída para el usuario autenticado.
     *
     * @param  string  $id  identificador de la notificación.
     *
     * @returns array resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function marcarNotificacionLeida(string $id): array
    {
        $user = Auth::user();
        if (! $user) {
            return $this->success(['success' => true]);
        }

        try {
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
            }
        } catch (\Throwable $e) {
            Log::error('Error en AgendaCitaService::marcarNotificacionLeida: '.$e->getMessage());
        }

        return $this->success(['success' => true]);
    }

    /**
     * Marca todas las notificaciones de tipo CitaSolicitadaNotification como leídas.
     *
     * @returns array resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function marcarTodasLeidas(): array
    {
        $user = Auth::user();
        if (! $user) {
            return $this->success(['success' => true]);
        }

        try {
            $user->unreadNotifications()
                ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Error en AgendaCitaService::marcarTodasLeidas: '.$e->getMessage());
        }

        return $this->success(['success' => true]);
    }

    /**
     * Obtiene la cita con su conversación de WhatsApp asociada.
     *
     * @param  AgendaCita  $cita  modelo de la cuyos datos y conversación se desean consultar.
     *
     * @returns array resultado con la cita y la conversación encontrada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function verConversacion(AgendaCita $cita): array
    {
        return $this->queryService->verConversacion($cita);
    }

    /**
     * Descarga un archivo multimedia de soporte de citas desde el disco local.
     *
     * @param  string  $filename  nombre del archivo a descargar.
     *
     * @returns string|null ruta completa del archivo o null si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function descargarMedia(string $filename): ?string
    {
        $filename = basename($filename);
        $path = 'citas_soportes/'.$filename;

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    /**
     * Busca un paciente por documento o lo crea sincronizando desde SAHI o con los datos enviados.
     *
     * @param  array  $data  Datos del paciente: num_docu, telefono, nombres, apellidos, email
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function buscarOCrearPaciente(array $data): ?Paciente
    {
        if (! empty($data['num_docu'])) {
            $paciente = Paciente::where('num_docu', $data['num_docu'])->first();

            if ($paciente) {
                if (! empty($data['telefono']) && ! $paciente->cel) {
                    $paciente->update(['cel' => $data['telefono']]);
                }

                return $paciente;
            }

            $sahiPaciente = SahiPaciente::where('PA_NUMDOCU', $data['num_docu'])->first();

            if ($sahiPaciente) {
                return Paciente::create([
                    'tipo_documento_id' => 1,
                    'num_docu' => $sahiPaciente->PA_NUMDOCU,
                    'nombres' => $sahiPaciente->PA_NOMB1.' '.$sahiPaciente->PA_NOMB2,
                    'pri_ape' => $sahiPaciente->PA_APELL1,
                    'seg_ape' => $sahiPaciente->PA_APELL2,
                    'sexo' => $sahiPaciente->PA_SEXO,
                    'fec_naci' => $sahiPaciente->PA_FENAC,
                    'direccion' => $sahiPaciente->PA_DIRECC,
                    'tel' => $sahiPaciente->PA_TELEFO,
                    'cel' => $data['telefono'] ?? null,
                    'email' => $data['email'] ?? null,
                ]);
            }
        }

        if (! empty($data['nombres']) && ! empty($data['telefono'])) {
            return Paciente::create([
                'tipo_documento_id' => 1,
                'num_docu' => $data['num_docu'] ?? null,
                'nombres' => $data['nombres'],
                'direccion' => $data['direccion'] ?? null,
                'cel' => $data['telefono'],
                'email' => $data['email'] ?? null,
            ]);
        }

        return null;
    }

    /**
     * Genera una lista de horarios en intervalos de una duración dada para una fecha específica.
     *
     * @param  string  $fecha  fecha para la cual se generan los horarios (Y-m-d).
     * @param  int  $duracion  duración de cada slot en minutos.
     * @param  Collection  $citasExistentes  colección de citas existentes para verificar disponibilidad.
     *
     * @returns array lista de horarios con inicio, fin y estado de disponibilidad.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function generarHorarios(string $fecha, int $duracion, $citasExistentes): array
    {
        $inicioDia = Carbon::parse($fecha.' 07:00:00');
        $finDia = Carbon::parse($fecha.' 17:00:00');

        $horarios = [];
        $horaActual = $inicioDia->copy();

        while ($horaActual->lt($finDia)) {
            $horaFin = $horaActual->copy()->addMinutes($duracion);

            if ($horaFin->gt($finDia)) {
                break;
            }

            $ocupado = $citasExistentes->contains(function ($cita) use ($horaActual, $horaFin) {
                return $cita->fecha_hora_inicio < $horaFin && $cita->fecha_hora_fin > $horaActual;
            });

            $horarios[] = [
                'inicio' => $horaActual->format('Y-m-d H:i'),
                'fin' => $horaFin->format('Y-m-d H:i'),
                'disponible' => ! $ocupado,
            ];

            $horaActual = $horaFin;
        }

        return $horarios;
    }
}
