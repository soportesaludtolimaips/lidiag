<?php

namespace App\Http\Controllers\Citas;

use App\Http\Controllers\Controller;
use App\Models\Citas\Cita;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;
use App\Services\WhatsAppFlowService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CitaController extends Controller
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
        $query = Cita::with(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra']);

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

        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $request->servicio_cita_id,
            'quien_registra_id' => Auth::id(),
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

    public function show(Cita $cita)
    {
        $cita->load(['paciente', 'servicio', 'producto', 'sede', 'quienRegistra', 'quienConfirma']);

        return response()->json($cita, 200);
    }

    public function update(Request $request, Cita $cita)
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

    public function destroy(Cita $cita)
    {
        if (! in_array($cita->estado, ['solicitada', 'cancelada'])) {
            return response()->json([
                'error' => 'No se puede eliminar una cita asignada o confirmada',
            ], 422);
        }

        $cita->delete();

        return response()->json(['message' => 'Cita eliminada'], 200);
    }

    public function asignar(Request $request, Cita $cita)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:config_productos,id',
            'sede_id' => 'required|exists:config_sedes,id',
            'fecha_hora_inicio' => 'required|date',
            'duracion_minutos' => 'nullable|integer|min:15',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fechaInicio = Carbon::parse($request->fecha_hora_inicio);
        $duracion = $request->duracion_minutos ?? 30;
        $fechaFin = $fechaInicio->copy()->addMinutes($duracion);

        $cita->update([
            'producto_id' => $request->producto_id,
            'sede_id' => $request->sede_id,
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin' => $fechaFin,
            'duracion_minutos' => $duracion,
            'estado' => 'asignada',
            'observaciones' => $request->observaciones ?? $cita->observaciones,
        ]);

        $this->whatsAppService->enviarConfirmacion($cita);

        return response()->json([
            'message' => 'Cita asignada y notificación enviada',
            'cita' => $cita->fresh(['paciente', 'servicio', 'producto', 'sede']),
        ], 200);
    }

    public function confirmar(Cita $cita)
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

    public function cancelar(Request $request, Cita $cita)
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

    public function cumplir(Cita $cita)
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

    public function noAsistio(Cita $cita)
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

    public function disponibilidad(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:config_productos,id',
            'sede_id' => 'nullable|exists:config_sedes,id',
            'fecha' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $producto = ConfigProducto::findOrFail($request->producto_id);
        $duracion = $producto->tiempoatencion ?? 30;

        $citasExistentes = Cita::where('producto_id', $request->producto_id)
            ->whereDate('fecha_hora_inicio', $request->fecha)
            ->whereIn('estado', ['asignada', 'confirmada'])
            ->orderBy('fecha_hora_inicio')
            ->get();

        $horarios = $this->generarHorarios($request->fecha, $duracion, $citasExistentes);

        return response()->json([
            'fecha' => $request->fecha,
            'duracion_minutos' => $duracion,
            'horarios' => $horarios,
        ], 200);
    }

    public function servicios()
    {
        $servicios = ConfigServicioCita::where('estado', true)->get();

        return response()->json($servicios, 200);
    }

    public function productosPorServicio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $servicio = ConfigServicioCita::with(['productos' => function ($query) {
            $query->where('estado', true);
        }])->findOrFail($request->servicio_cita_id);

        return response()->json($servicio->productos, 200);
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256', '');

        if ($signature && ! $this->whatsAppService->verificarWebhook($payload, $signature)) {
            return response()->json(['error' => 'Firma inválida'], 401);
        }

        $data = $request->all();

        if (empty($data['entry'])) {
            return response()->json(['message' => 'OK'], 200);
        }

        foreach ($data['entry'] as $entry) {
            $mensaje = $this->whatsAppService->processarWebhook($entry);

            if ($mensaje && $mensaje['text']) {
                $respuesta = $this->whatsAppFlowService->procesarMensaje(
                    $mensaje['from'],
                    $mensaje['text']
                );

                if ($respuesta) {
                    $this->whatsAppService->enviarMensaje($mensaje['from'], $respuesta);
                }
            }
        }

        return response()->json(['message' => 'OK'], 200);
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

    public function verConversacion(Cita $cita)
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

        $cita = Cita::where('paciente_id', $paciente->id)
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
