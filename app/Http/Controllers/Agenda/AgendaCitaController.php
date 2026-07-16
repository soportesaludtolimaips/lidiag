<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\AgendaCitaAsignarRequest;
use App\Http\Requests\Agenda\AgendaCitaDisponibilidadRequest;
use App\Http\Requests\Agenda\AgendaCitaRechazarRequest;
use App\Http\Requests\Agenda\AgendaCitaStoreRequest;
use App\Http\Requests\Agenda\AgendaCitaUpdateRequest;
use App\Models\Agenda\AgendaCita;
use App\Services\Agenda\AgendaCitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaCitaController extends Controller
{
    public function __construct(
        private readonly AgendaCitaService $agendaCitaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->agendaCitaService->all($request);

        return response()->json($result, $result["status"]);
    }

    public function store(AgendaCitaStoreRequest $request): JsonResponse
    {
        $result = $this->agendaCitaService->create($request->validated());

        return response()->json($result, $result["status"]);
    }

    public function show(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->find($cita);

        return response()->json($result, $result["status"]);
    }

    public function update(AgendaCitaUpdateRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->update($cita, $request->validated());

        return response()->json($result, $result["status"]);
    }

    public function destroy(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->delete($cita);

        return response()->json($result, $result["status"]);
    }

    public function asignar(AgendaCitaAsignarRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->asignar($cita, $request->validated());

        return response()->json($result, $result["status"]);
    }

    public function confirmar(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->confirmar($cita);

        return response()->json($result, $result["status"]);
    }

    public function cancelar(Request $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->cancelar($cita, $request->input('motivo', ''));

        return response()->json($result, $result["status"]);
    }

    public function cumplir(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->cumplir($cita);

        return response()->json($result, $result["status"]);
    }

    public function noAsistio(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->noAsistio($cita);

        return response()->json($result, $result["status"]);
    }

    public function pendientes(Request $request): JsonResponse
    {
        $result = $this->agendaCitaService->pendientes($request);

        return response()->json($result, $result["status"]);
    }

    public function rechazar(AgendaCitaRechazarRequest $request, AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->rechazar($cita, $request->validated());

        return response()->json($result, $result["status"]);
    }

    public function disponibilidad(AgendaCitaDisponibilidadRequest $request): JsonResponse
    {
        $result = $this->agendaCitaService->disponibilidad($request->validated());

        return response()->json($result, $result["status"]);
    }

    public function servicios(): JsonResponse
    {
        $result = $this->agendaCitaService->servicios();

        return response()->json($result, $result["status"]);
    }

    public function productosPorServicio(Request $request, int $servicioCitaId): JsonResponse
    {
        $result = $this->agendaCitaService->productosPorServicio($servicioCitaId, $request->query('sede_id'));

        return response()->json($result, $result["status"]);
    }

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

        return response()->json($result, $result["status"]);
    }

    public function descargarMedia(string $filename)
    {
        $path = $this->agendaCitaService->descargarMedia($filename);

        if (! $path) {
            abort(404, 'Archivo no encontrado');
        }

        return response()->file($path);
    }

    public function testEnviar(Request $request): JsonResponse
    {
        $telefono = $request->input('telefono', $request->input('phone', '15556370875'));
        $mensaje = $request->input('mensaje', 'Hola! Este es un mensaje de prueba desde LIDiag.');

        $result = $this->agendaCitaService->testEnviar($telefono, $mensaje);

        return response()->json($result, $result["status"]);
    }

    public function notificacionesNoLeidas(): JsonResponse
    {
        $result = $this->agendaCitaService->notificacionesNoLeidas();

        return response()->json($result, $result["status"]);
    }

    public function marcarNotificacionLeida(string $id): JsonResponse
    {
        $result = $this->agendaCitaService->marcarNotificacionLeida($id);

        return response()->json($result, $result["status"]);
    }

    public function marcarTodasLeidas(): JsonResponse
    {
        $result = $this->agendaCitaService->marcarTodasLeidas();

        return response()->json($result, $result["status"]);
    }

    public function verConversacion(AgendaCita $cita): JsonResponse
    {
        $result = $this->agendaCitaService->verConversacion($cita);

        return response()->json($result, $result["status"]);
    }
}
