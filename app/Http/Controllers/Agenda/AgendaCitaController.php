<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaCitaAsignarRequest;
use App\Http\Requests\Agenda\AgendaCitaCancelarRequest;
use App\Http\Requests\Agenda\AgendaCitaDisponibilidadRequest;
use App\Http\Requests\Agenda\AgendaCitaIndexRequest;
use App\Http\Requests\Agenda\AgendaCitaPendientesRequest;
use App\Http\Requests\Agenda\AgendaCitaRechazarRequest;
use App\Http\Requests\Agenda\AgendaCitaStoreRequest;
use App\Http\Requests\Agenda\AgendaCitaTestEnviarRequest;
use App\Http\Requests\Agenda\AgendaCitaUpdateRequest;
use App\Models\Agenda\AgendaCita;
use App\Services\Agenda\AgendaCitaQueryService;
use App\Services\Agenda\AgendaCitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgendaCitaController extends Controller
{
    /**
     * Constructor del controlador de citas de agenda.
     *
     * @param  AgendaCitaService  $agendaCitaService  servicio de comandos y mutaciones de citas.
     * @param  AgendaCitaQueryService  $agendaCitaQueryService  servicio de consultas y lecturas de citas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function __construct(
        private readonly AgendaCitaService $agendaCitaService,
        private readonly AgendaCitaQueryService $agendaCitaQueryService,
    ) {}

    /**
     * Lista todas las citas con filtros opcionales y paginación.
     *
     * @param  AgendaCitaIndexRequest  $request  parámetros validados de búsqueda y filtrado.
     *
     * @returns JsonResponse respuesta con las citas encontradas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function index(AgendaCitaIndexRequest $request): JsonResponse
    {
        $result = $this->agendaCitaQueryService->all($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Crea una nueva solicitud de cita.
     *
     * @param  AgendaCitaStoreRequest  $request  datos validados de la nueva cita.
     *
     * @returns JsonResponse respuesta con la cita creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function store(AgendaCitaStoreRequest $request): JsonResponse
    {
        $result = $this->agendaCitaService->create($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Muestra los detalles de una cita específica.
     *
     * @param  AgendaCita  $cita  modelo de la cita a consultar.
     *
     * @returns JsonResponse respuesta con los datos de la cita.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function show(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->find($cita);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Actualiza los datos de una cita existente.
     *
     * @param  AgendaCitaUpdateRequest  $request  datos actualizados de la cita.
     * @param  AgendaCita  $cita  modelo de la cita a actualizar.
     *
     * @returns JsonResponse respuesta con la cita actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function update(AgendaCitaUpdateRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->update($cita, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Elimina una cita del sistema.
     *
     * @param  AgendaCita  $cita  modelo de la cita a eliminar.
     *
     * @returns JsonResponse respuesta de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function destroy(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->delete($cita);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Asigna un horario, producto y sede a una cita solicitada.
     *
     * @param  AgendaCitaAsignarRequest  $request  datos de asignación (producto, sede, fecha).
     * @param  AgendaCita  $cita  modelo de la cita a asignar.
     *
     * @returns JsonResponse respuesta con la cita asignada y notificación enviada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function asignar(AgendaCitaAsignarRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->asignar($cita, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Confirma una cita que fue asignada previamente.
     *
     * @param  AgendaCita  $cita  modelo de la cita a confirmar.
     *
     * @returns JsonResponse respuesta con la cita confirmada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function confirmar(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->confirmar($cita);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Cancela una cita existente con un motivo opcional.
     *
     * @param  AgendaCitaCancelarRequest  $request  petición validada con el motivo de cancelación.
     * @param  AgendaCita  $cita  modelo de la cita a cancelar.
     *
     * @returns JsonResponse respuesta con la cita cancelada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function cancelar(AgendaCitaCancelarRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->cancelar($cita, (string) $request->input('motivo', ''));

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Marca una cita confirmada como cumplida.
     *
     * @param  AgendaCita  $cita  modelo de la cita a marcar como cumplida.
     *
     * @returns JsonResponse respuesta con la cita cumplida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function cumplir(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->cumplir($cita);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Registra inasistencia del paciente a una cita confirmada.
     *
     * @param  AgendaCita  $cita  modelo de la cita a marcar como inasistencia.
     *
     * @returns JsonResponse respuesta con la inasistencia registrada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function noAsistio(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->noAsistio($cita);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Lista las citas pendientes de revisión (solicitadas).
     *
     * @param  AgendaCitaPendientesRequest  $request  parámetros validados de filtrado.
     *
     * @returns JsonResponse respuesta con las citas pendientes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function pendientes(AgendaCitaPendientesRequest $request): JsonResponse
    {
        $result = $this->agendaCitaQueryService->pendientes($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Rechaza una cita solicitada con un motivo y observación.
     *
     * @param  AgendaCitaRechazarRequest  $request  datos del rechazo (motivo, observación).
     * @param  AgendaCita  $cita  modelo de la cita a rechazar.
     *
     * @returns JsonResponse respuesta con la cita rechazada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function rechazar(AgendaCitaRechazarRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->rechazar($cita, $request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Consulta la disponibilidad de horarios para una fecha y servicio específicos.
     *
     * @param  AgendaCitaDisponibilidadRequest  $request  parámetros de consulta (sede, servicio, fecha).
     *
     * @returns JsonResponse respuesta con los horarios disponibles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function disponibilidad(AgendaCitaDisponibilidadRequest $request): JsonResponse
    {
        $result = $this->agendaCitaQueryService->disponibilidad($request->validated());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Obtiene la lista de servicios de cita disponibles.
     *
     * @returns JsonResponse respuesta con los servicios activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function servicios(): JsonResponse
    {
        $result = $this->agendaCitaQueryService->servicios();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Obtiene los productos asociados a un servicio de cita específico.
     *
     * @param  Request  $request  petición con filtro opcional de sede.
     * @param  int  $servicioCitaId  identificador del servicio de cita.
     *
     * @returns JsonResponse respuesta con los productos del servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function productosPorServicio(Request $request, int $servicioCitaId): JsonResponse
    {
        $result = $this->agendaCitaQueryService->productosPorServicio($servicioCitaId, $request->query('sede_id'));

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Maneja el webhook de WhatsApp para verificación y recepción de mensajes.
     *
     * @param  Request  $request  petición entrante del webhook de WhatsApp.
     *
     * @returns mixed respuesta según el método (GET: verificación, POST: procesamiento).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function webhook(Request $request)
    {
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode', $request->query('hub.mode'));
            $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
            $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

            $result = $this->agendaCitaService->webhookGet($mode, $token, $challenge);

            if ($result['status'] !== 200) {
                return response()->json(['error' => $result['message']], $result['status']);
            }

            return response($result['data']['challenge'], 200)->header('Content-Type', 'text/plain');
        }

        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256', '');

        $result = $this->agendaCitaService->webhookPost($payload, $signature, $request->all());

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Descarga un archivo multimedia asociado a una cita.
     *
     * @param  string  $filename  nombre del archivo a descargar.
     *
     * @returns mixed respuesta con el archivo o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function descargarMedia(string $filename)
    {
        $path = $this->agendaCitaService->descargarMedia($filename);

        if (! $path) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->file($path);
    }

    /**
     * Envía un mensaje de prueba por WhatsApp a un número específico.
     *
     * @param  AgendaCitaTestEnviarRequest  $request  petición validada con el teléfono y mensaje de prueba.
     *
     * @returns JsonResponse respuesta con el resultado del envío.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function testEnviar(AgendaCitaTestEnviarRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->agendaCitaService->testEnviar($validated['telefono'], $validated['mensaje']);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Obtiene el conteo y lista de notificaciones no leídas del usuario.
     *
     * @returns JsonResponse respuesta con las notificaciones pendientes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function notificacionesNoLeidas(): JsonResponse
    {
        try {
            $result = $this->agendaCitaService->notificacionesNoLeidas();

            return response()->json($result, (int) ($result["status"] ?? 200));
        } catch (\Throwable $e) {
            Log::error("Error en AgendaCitaController::notificacionesNoLeidas: ".$e->getMessage(), [
                "exception" => $e,
            ]);

            return response()->json([
                "success" => true,
                "data" => [
                    "count" => 0,
                    "notifications" => [],
                ],
                "message" => "Sin notificaciones pendientes",
                "status" => 200,
            ], 200);
        }
    }

    /**
     * Marca una notificación específica como leída.
     *
     * @param  string  $id  identificador de la notificación.
     *
     * @returns JsonResponse respuesta de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function marcarNotificacionLeida(string $id): JsonResponse
    {
        $result = $this->agendaCitaService->marcarNotificacionLeida($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Marca todas las notificaciones de citas como leídas.
     *
     * @returns JsonResponse respuesta de confirmación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function marcarTodasLeidas(): JsonResponse
    {
        $result = $this->agendaCitaService->marcarTodasLeidas();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Obtiene la conversación de WhatsApp asociada a una cita.
     *
     * @param  AgendaCita  $cita  modelo de la cita cuya conversación se desea consultar.
     *
     * @returns JsonResponse respuesta con la cita y su conversación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function verConversacion(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaQueryService->verConversacion($cita);

        return response()->json($result, (int) $result["status"]);
    }
}
