<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaCita;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;
use App\Services\BaseService;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgendaCitaService extends BaseService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
        private readonly WhatsAppFlowService $whatsAppFlowService,
    ) {}

    public function all(Request $request): array
    {
        $query = AgendaCita::with(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra']);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha_hora_inicio', [
                $request->fecha_inicio.' 00:00:00',
                $request->fecha_fin.' 23:59:59',
            ]);
        }

        if ($request->has('servicio_cita_id')) {
            $query->where('servicio_cita_id', $request->servicio_cita_id);
        }

        if ($request->has('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }

        if ($request->has('busqueda')) {
            $busqueda = $request->busqueda;
            $query->whereHas('paciente', function ($q) use ($busqueda) {
                $q->where('num_docu', 'like', "%{$busqueda}%")
                    ->orWhere('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('pri_ape', 'like', "%{$busqueda}%");
            });
        }

        $citas = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return $this->success($citas);
    }

    public function find(AgendaCita $cita): array
    {
        $cita->load(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra', 'quienConfirma']);

        return $this->success($cita);
    }

    public function create(array $data): array
    {
        $paciente = $this->buscarOCrearPaciente($data);

        if (! $paciente) {
            return $this->error('No se pudo crear el paciente', 422);
        }

        $cita = AgendaCita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $data['servicio_cita_id'],
            'quien_registro_id' => Auth::id(),
            'numero_orden' => $data['numero_orden'] ?? null,
            'numero_autorizacion' => $data['numero_autorizacion'] ?? null,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        $this->whatsAppService->enviarSolicitudRecibida($cita);

        return $this->created([
            'message' => 'Solicitud de cita creada exitosamente',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ]);
    }

    public function update(AgendaCita $cita, array $data): array
    {
        $cita->update($data);

        return $this->success([
            'message' => 'Cita actualizada',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ]);
    }

    public function delete(AgendaCita $cita): array
    {
        if (! in_array($cita->estado, ['solicitada', 'cancelada'])) {
            return $this->error('No se puede eliminar una cita asignada o confirmada', 422);
        }

        $cita->delete();

        return $this->deleted('Cita eliminada');
    }

    public function asignar(AgendaCita $cita, array $data): array
    {
        $fechaInicio = Carbon::parse($data['fecha_hora_inicio']);
        $duracion = $data['duracion_minutos'] ?? 30;
        $fechaFin = $fechaInicio->copy()->addMinutes($duracion);

        DB::transaction(function () use ($cita, $data, $fechaInicio, $fechaFin, $duracion) {
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

        return $this->success([
            'message' => 'Cita asignada y notificación enviada',
            'cita' => $cita,
        ]);
    }

    public function confirmar(AgendaCita $cita): array
    {
        if (! in_array($cita->estado, ['asignada'])) {
            return $this->error('Solo se pueden confirmar citas asignadas', 422);
        }

        $cita->update([
            'estado' => 'confirmada',
            'quien_confirma_id' => Auth::id(),
        ]);

        return $this->success([
            'message' => 'Cita confirmada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'producto', 'sede']),
        ]);
    }

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

        return $this->success([
            'message' => 'Cita cancelada',
            'cita' => $cita->fresh(),
        ]);
    }

    public function cumplir(AgendaCita $cita): array
    {
        if ($cita->estado !== 'confirmada') {
            return $this->error('Solo se pueden marcar como cumplidas las citas confirmadas', 422);
        }

        $cita->update(['estado' => 'cumplida']);

        return $this->success([
            'message' => 'Cita marcada como cumplida',
            'cita' => $cita->fresh(),
        ]);
    }

    public function noAsistio(AgendaCita $cita): array
    {
        if ($cita->estado !== 'confirmada') {
            return $this->error('Solo se puede marcar inasistencia en citas confirmadas', 422);
        }

        $cita->update(['estado' => 'no_asistio']);

        return $this->success([
            'message' => 'Inasistencia registrada',
            'cita' => $cita->fresh(),
        ]);
    }

    public function pendientes(Request $request): array
    {
        $query = AgendaCita::with(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra', 'rechazoMotivo'])
            ->where('estado', 'solicitada');

        if ($request->servicio_cita_id) {
            $query->where('servicio_cita_id', $request->servicio_cita_id);
        }

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        if ($request->busqueda) {
            $busqueda = $request->busqueda;
            $query->whereHas('paciente', function ($q) use ($busqueda) {
                $q->where('num_docu', 'like', "%{$busqueda}%")
                    ->orWhere('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('pri_ape', 'like', "%{$busqueda}%");
            });
        }

        $citas = $query->orderBy('created_at', 'desc')->get();

        return $this->success($citas);
    }

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

        return $this->success([
            'message' => 'Cita rechazada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'rechazoMotivo']),
        ]);
    }

    public function disponibilidad(array $data): array
    {
        $fecha = Carbon::parse($data['fecha']);

        $detalles = AgendaDisponibilidaDetalle::query()
            ->whereHas('agendaDisponibilida', function ($q) use ($data) {
                $q->where('sede_id', $data['sede_id'])
                    ->where('servicio_cita_id', $data['servicio_cita_id'])
                    ->where('estado', 'Activo');
            })
            ->whereDate('fecha_hora_inicio', $fecha)
            ->where('estado', 'Disponible')
            ->orderBy('fecha_hora_inicio')
            ->get(['id', 'fecha_hora_inicio', 'fecha_hora_fin', 'estado']);

        return $this->success([
            'fecha' => $data['fecha'],
            'detalles' => $detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'inicio' => $detalle->fecha_hora_inicio->format('Y-m-d H:i'),
                    'fin' => $detalle->fecha_hora_fin->format('Y-m-d H:i'),
                    'disponible' => $detalle->estado === 'Disponible',
                ];
            }),
        ]);
    }

    public function servicios(): array
    {
        $servicios = ConfigServicioCita::where('estado', true)->get();

        return $this->success($servicios);
    }

    public function productosPorServicio(int $servicioCitaId, ?string $sedeId = null): array
    {
        $servicio = ConfigServicioCita::with(['productos' => function ($query) {
            $query->where('config_productos.estado', true);
        }])->findOrFail($servicioCitaId);

        if ($sedeId && $servicio->sede_id != $sedeId) {
            return $this->success([]);
        }

        return $this->success($servicio->productos);
    }

    public function webhookGet(string $mode, string $token, string $challenge): array
    {
        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return $this->success(['challenge' => $challenge]);
        }

        return $this->error('Token inválido', 403);
    }

    public function webhookPost(string $payload, string $signature, array $data): array
    {
        $secret = config('services.whatsapp.app_secret');

        if ($secret && (! $signature || ! $this->whatsAppService->verificarWebhook($payload, $signature))) {
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

    public function testEnviar(string $telefono, string $mensaje): array
    {
        $resultado = $this->whatsAppService->enviarMensaje($telefono, $mensaje);

        return $this->success([
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'resultado' => $resultado,
        ]);
    }

    public function notificacionesNoLeidas(): array
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()
            ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
            ->count();

        $notifications = $user->unreadNotifications()
            ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'mensaje' => $n->data['mensaje'] ?? '',
                'url' => $n->data['url'] ?? '/citas/revision',
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return $this->success([
            'count' => $count,
            'notifications' => $notifications,
        ]);
    }

    public function marcarNotificacionLeida(string $id): array
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return $this->success(['success' => true]);
    }

    public function marcarTodasLeidas(): array
    {
        Auth::user()->unreadNotifications()
            ->where('type', 'App\Notifications\Citas\CitaSolicitadaNotification')
            ->update(['read_at' => now()]);

        return $this->success(['success' => true]);
    }

    public function verConversacion(AgendaCita $cita): array
    {
        $conversacion = null;

        if ($cita->paciente && $cita->paciente->cel) {
            $cel = preg_replace('/[^0-9]/', '', $cita->paciente->cel);
            $conversacion = WhatsAppConversation::whereRaw(
                "REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '+', '') LIKE ?",
                ["%{$cel}"]
            )->first();
        }

        return $this->success([
            'cita' => $cita->load(['paciente', 'servicio', 'producto', 'sede']),
            'conversacion' => $conversacion,
        ]);
    }

    public function descargarMedia(string $filename): ?string
    {
        $filename = basename($filename);
        $path = 'citas_soportes/'.$filename;

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

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
