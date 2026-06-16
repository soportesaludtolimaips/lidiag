<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Agenda\AgendaCita;
use App\Models\Agenda\AgendaDisponibilida;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AgendaCitaController extends Controller
{
    protected $whatsAppService;

    protected $whatsAppFlowService;

    public function __construct(WhatsAppService $whatsAppService, WhatsAppFlowService $whatsAppFlowService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->whatsAppFlowService = $whatsAppFlowService;
    }

    public function index(Request $request)
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

        return response()->json($citas, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'numero_orden' => 'nullable|string|max:50',
            'numero_autorizacion' => 'nullable|string|max:50',
            'telefono' => 'required|string',
            'num_docu' => 'nullable|string',
            'nombres' => 'nullable|string',
            'direccion' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente = $this->buscarOCrearPaciente($request);

        if (! $paciente) {
            return response()->json(['error' => 'No se pudo crear el paciente'], 422);
        }

        $cita = AgendaCita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $request->servicio_cita_id,
            'quien_registro_id' => Auth::id(),
            'numero_orden' => $request->numero_orden,
            'numero_autorizacion' => $request->numero_autorizacion,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
            'observaciones' => $request->observaciones,
        ]);

        $this->whatsAppService->enviarSolicitudRecibida($cita);

        return response()->json([
            'message' => 'Solicitud de cita creada exitosamente',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ], 201);
    }

    public function show(AgendaCita $cita)
    {
        $cita->load(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra', 'quienConfirma']);

        return response()->json($cita, 200);
    }

    public function update(Request $request, AgendaCita $cita)
    {
        $validator = Validator::make($request->all(), [
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $cita->update($request->only(['observaciones']));

        return response()->json([
            'message' => 'Cita actualizada',
            'cita' => $cita->fresh(['paciente', 'servicio']),
        ], 200);
    }

    public function destroy(AgendaCita $cita)
    {
        if (! in_array($cita->estado, ['solicitada', 'cancelada'])) {
            return response()->json([
                'error' => 'No se puede eliminar una cita asignada o confirmada',
            ], 422);
        }

        $cita->delete();

        return response()->json(['message' => 'Cita eliminada'], 200);
    }

    public function asignar(Request $request, AgendaCita $cita)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'agenda_disponibilidad_detalle_id' => 'nullable|exists:agenda_disponibilidad_detalle,id',
            'fecha_hora_inicio' => 'required|date',
            'duracion_minutos' => 'nullable|integer|min:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
        $duracion = $request->duracion_minutos ?? 30;
        $fechaFin = $fechaInicio->copy()->addMinutes($duracion);

        DB::transaction(function () use ($request, $cita, $fechaInicio, $fechaFin, $duracion) {
            $cita->update([
                'producto_id' => $request->producto_id,
                'sede_id' => $request->sede_id,
                'agenda_disponibilidad_detalle_id' => $request->agenda_disponibilidad_detalle_id,
                'fecha_hora_inicio' => $fechaInicio,
                'fecha_hora_fin' => $fechaFin,
                'duracion_minutos' => $duracion,
                'estado' => 'asignada',
                'revisada' => true,
                'observaciones' => $request->observaciones ?? $cita->observaciones,
            ]);

            // Si se seleccionó un detalle de disponibilidad, marcarlo como Reservado
            if ($request->agenda_disponibilidad_detalle_id) {
                AgendaDisponibilidaDetalle::where('id', $request->agenda_disponibilidad_detalle_id)
                    ->where('estado', 'Disponible')
                    ->update(['estado' => 'Reservado']);
            }
        });

        $cita->refresh()->load(['paciente', 'servicio', 'producto', 'sede']);

        if ($cita->paciente && empty($cita->paciente->cel)) {
            $conv = \App\Models\Citas\WhatsAppConversation::where('cita_id', $cita->id)->first();
            if ($conv && ! empty($conv->telefono)) {
                $cita->paciente->update(['cel' => $conv->telefono]);
                $cita->refresh()->load(['paciente', 'servicio', 'producto', 'sede']);
            }
        }

        $this->whatsAppService->enviarConfirmacion($cita);
        $this->enviarRecomendacionesWhatsApp($cita);

        return response()->json([
            'message' => 'Cita asignada y notificación enviada',
            'cita' => $cita,
        ], 200);
    }

    /**
     * Envía al paciente las recomendaciones del producto asignado vía WhatsApp.
     */
    protected function enviarRecomendacionesWhatsApp(AgendaCita $cita): void
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
            "📍 *Sede:* " . ($cita->sede->nom_sede ?? 'N/A'),
            "🩺 *Servicio:* " . ($cita->servicio->nombre ?? 'N/A'),
            "🗓️ *Fecha:* " . $cita->fecha_hora_inicio->format('d/m/Y'),
            "🕐 *Hora:* " . $cita->fecha_hora_inicio->format('H:i'),
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
            $lineas[] = '• ' . $texto;
        }

        $mensaje = implode("\n", $lineas);

        $this->whatsAppService->enviarMensaje($telefono, $mensaje);
    }

    public function confirmar(AgendaCita $cita)
    {
        if (! in_array($cita->estado, ['asignada'])) {
            return response()->json([
                'error' => 'Solo se pueden confirmar citas asignadas',
            ], 422);
        }

        $cita->update([
            'estado' => 'confirmada',
            'quien_confirma_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Cita confirmada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'producto', 'sede']),
        ], 200);
    }

    public function cancelar(Request $request, AgendaCita $cita)
    {
        if (in_array($cita->estado, ['cumplida', 'no_asistio'])) {
            return response()->json([
                'error' => 'No se puede cancelar una cita cumplida o con inasistencia',
            ], 422);
        }

        $motivo = $request->input('motivo', '');

        $cita->update([
            'estado' => 'cancelada',
            'observaciones' => $cita->observaciones."\nMotivo cancelación: ".$motivo,
        ]);

        $this->whatsAppService->enviarCancelacion($cita, $motivo);

        return response()->json([
            'message' => 'Cita cancelada',
            'cita' => $cita->fresh(),
        ], 200);
    }

    public function cumplir(AgendaCita $cita)
    {
        if ($cita->estado !== 'confirmada') {
            return response()->json([
                'error' => 'Solo se pueden marcar como cumplidas las citas confirmadas',
            ], 422);
        }

        $cita->update(['estado' => 'cumplida']);

        return response()->json([
            'message' => 'Cita marcada como cumplida',
            'cita' => $cita->fresh(),
        ], 200);
    }

    public function noAsistio(AgendaCita $cita)
    {
        if ($cita->estado !== 'confirmada') {
            return response()->json([
                'error' => 'Solo se puede marcar inasistencia en citas confirmadas',
            ], 422);
        }

        $cita->update(['estado' => 'no_asistio']);

        return response()->json([
            'message' => 'Inasistencia registrada',
            'cita' => $cita->fresh(),
        ], 200);
    }

    public function pendientes(Request $request)
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

        return response()->json($citas, 200);
    }

    public function rechazar(Request $request, AgendaCita $cita)
    {
        if ($cita->estado !== 'solicitada') {
            return response()->json(['error' => 'Solo se pueden rechazar citas solicitadas'], 422);
        }

        $validator = Validator::make($request->all(), [
            'rechazo_motivo_id' => 'required|exists:config_cita_rechazo_motivos,id',
            'rechazo_observacion' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $motivo = ConfigCitaRechazoMotivo::find($request->rechazo_motivo_id);

        $cita->update([
            'estado' => 'rechazada',
            'revisada' => true,
            'rechazo_motivo_id' => $request->rechazo_motivo_id,
            'rechazo_observacion' => $request->rechazo_observacion,
        ]);

        $this->whatsAppService->enviarRechazo(
            $cita,
            $motivo?->motivo ?? 'Motivo no especificado',
            $request->rechazo_observacion ?? ''
        );

        return response()->json([
            'message' => 'Cita rechazada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'rechazoMotivo']),
        ], 200);
    }

    public function disponibilidad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'nullable|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'fecha' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fecha = Carbon::parse($request->fecha);

        $detalles = AgendaDisponibilidaDetalle::query()
            ->whereHas('agendaDisponibilida', function ($q) use ($request) {
                $q->where('sede_id', $request->sede_id)
                    ->where('servicio_cita_id', $request->servicio_cita_id)
                    ->where('estado', 'Activo');
            })
            ->whereDate('fecha_hora_inicio', $fecha)
            ->where('estado', 'Disponible')
            ->orderBy('fecha_hora_inicio')
            ->get(['id', 'fecha_hora_inicio', 'fecha_hora_fin', 'estado']);

        return response()->json([
            'fecha' => $request->fecha,
            'detalles' => $detalles->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'inicio' => $detalle->fecha_hora_inicio->format('Y-m-d H:i'),
                    'fin' => $detalle->fecha_hora_fin->format('Y-m-d H:i'),
                    'disponible' => $detalle->estado === 'Disponible',
                ];
            }),
        ], 200);
    }

    public function servicios()
    {
        $servicios = ConfigServicioCita::where('estado', true)->get();

        return response()->json($servicios, 200);
    }

    public function productosPorServicio(Request $request, $servicioCitaId)
    {
        $servicio = ConfigServicioCita::with(['productos' => function ($query) {
            $query->where('config_productos.estado', true);
        }])->findOrFail($servicioCitaId);

        $sedeId = $request->query('sede_id');

        if ($sedeId && $servicio->sede_id != $sedeId) {
            return response()->json([], 200);
        }

        return response()->json($servicio->productos, 200);
    }

    public function webhook(Request $request)
    {
        if ($request->isMethod('get')) {
            // PHP parse_str convierte puntos a guiones bajos: hub.mode → hub_mode
            $mode = $request->query('hub_mode', $request->query('hub.mode'));
            $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
            $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Token inválido', 403);
        }

        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256', '');
        $secret = config('services.whatsapp.app_secret');

        if ($secret && $signature && ! $this->whatsAppService->verificarWebhook($payload, $signature)) {
            \Illuminate\Support\Facades\Log::warning('WhatsApp webhook: firma inválida', [
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'Firma inválida'], 401);
        }

        $data = $request->all();

        \Illuminate\Support\Facades\Log::info('WhatsApp webhook recibido', [
            'has_entries' => ! empty($data['entry']),
            'entry_count' => count($data['entry'] ?? []),
        ]);

        if (empty($data['entry'])) {
            return response()->json(['message' => 'OK'], 200);
        }

        foreach ($data['entry'] as $entry) {
            $value = $entry['changes'][0]['value'] ?? [];
            \Illuminate\Support\Facades\Log::info('WhatsApp webhook entry', [
                'has_messages' => ! empty($value['messages']),
                'has_statuses' => ! empty($value['statuses']),
                'messages_count' => count($value['messages'] ?? []),
            ]);
            $mensaje = $this->whatsAppService->processarWebhook($entry);

            if (! $mensaje || ! $mensaje['from']) {
                continue;
            }

            $messageId = $mensaje['id'] ?? null;

            $mediaPath = null;
            if (in_array($mensaje['type'], ['image', 'document'])) {
                $mediaId = $mensaje['media']['id'] ?? null;
                if ($mediaId) {
                    $mediaPath = $this->whatsAppService->descargarMedia($mediaId);
                }
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
        }

        return response()->json(['message' => 'OK'], 200);
    }

    public function descargarMedia($filename)
    {
        $path = 'citas_soportes/'.$filename;

        if (! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($path));
    }

    protected function enviarRespuesta(string $telefono, array $respuesta): void
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

    public function testEnviar(Request $request)
    {
        $telefono = $request->input('telefono', '15556370875');
        $mensaje = $request->input('mensaje', 'Hola! Este es un mensaje de prueba desde LIDiag.');

        $resultado = $this->whatsAppService->enviarMensaje($telefono, $mensaje);

        return response()->json([
            'telefono' => $telefono,
            'mensaje' => $mensaje,
            'resultado' => $resultado,
        ]);
    }

    public function verConversacion(AgendaCita $cita)
    {
        $conversacion = null;

        if ($cita->paciente && $cita->paciente->cel) {
            $conversacion = WhatsAppConversation::where('telefono', 'like', '%'.$cita->paciente->cel)
                ->first();
        }

        return response()->json([
            'cita' => $cita->load(['paciente', 'servicio', 'producto', 'sede']),
            'conversacion' => $conversacion,
        ], 200);
    }

    protected function buscarOCrearPaciente(Request $request): ?Paciente
    {
        if ($request->num_docu) {
            $paciente = Paciente::where('num_docu', $request->num_docu)->first();

            if ($paciente) {
                if ($request->telefono && ! $paciente->cel) {
                    $paciente->update(['cel' => $request->telefono]);
                }

                return $paciente;
            }

            $sahiPaciente = SahiPaciente::where('PA_NUMDOCU', $request->num_docu)->first();

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
                    'cel' => $request->telefono,
                    'email' => $request->email,
                ]);
            }
        }

        if ($request->nombres && $request->telefono) {
            return Paciente::create([
                'tipo_documento_id' => 1,
                'num_docu' => $request->num_docu ?? null,
                'nombres' => $request->nombres,
                'direccion' => $request->direccion,
                'cel' => $request->telefono,
                'email' => $request->email,
            ]);
        }

        return null;
    }

    protected function generarHorarios(string $fecha, int $duracion, $citasExistentes): array
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

    protected function procesarRespuestaWhatsApp(array $mensaje): void
    {
        $telefono = $mensaje['from'];
        $texto = strtolower(trim($mensaje['text']));

        $paciente = Paciente::where('cel', 'like', "%{$telefono}")->first();

        if (! $paciente) {
            return;
        }

        $cita = AgendaCita::where('paciente_id', $paciente->id)
            ->whereIn('estado', ['asignada', 'confirmada'])
            ->latest()
            ->first();

        if (! $cita) {
            return;
        }

        if (in_array($texto, ['si', 'confirmar', 'si, confirmar', '1'])) {
            $cita->update([
                'estado' => 'confirmada',
                'quien_confirma_id' => Auth::id(),
            ]);
        } elseif (in_array($texto, ['no', 'cancelar', '2'])) {
            $cita->update(['estado' => 'cancelada']);
            $this->whatsAppService->enviarCancelacion($cita, 'Cancelado por el paciente');
        } elseif (in_array($texto, ['reprogramar', '3'])) {
            $this->whatsAppService->enviarMensaje(
                $telefono,
                "Para reprogramar su cita, por favor comuníquese con nosotros."
            );
        }
    }
}
