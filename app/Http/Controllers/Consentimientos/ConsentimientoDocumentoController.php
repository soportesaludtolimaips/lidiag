<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consentimientos\ConsentimientoAnularRequest;
use App\Http\Requests\Consentimientos\ConsentimientoCrearTokenRequest;
use App\Http\Requests\Consentimientos\ConsentimientoFirmarMedicoRequest;
use App\Http\Requests\Consentimientos\ConsentimientoGenerarRequest;
use App\Services\Consentimientos\ConsentimientoDocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsentimientoDocumentoController extends Controller
{
    public function __construct(
        private readonly ConsentimientoDocumentoService $service,
    ) {
        $this->middleware('auth:sanctum')->only([
            'index', 'show', 'generar', 'crearToken', 'firmarMedico', 'anular', 'generarPdf', 'descargarPdf',
        ]);

        $this->middleware('permission:consentimientos.index,web')->only(['index']);
        $this->middleware('permission:consentimientos.show,web')->only(['show']);
        $this->middleware('permission:consentimientos.generar,web')->only(['generar', 'buscarPaciente', 'medicos']);
        $this->middleware('permission:consentimientos.firmar_paciente,web')->only(['crearToken']);
        $this->middleware('permission:consentimientos.firmar_medico,web')->only(['firmarMedico']);
        $this->middleware('permission:consentimientos.anular,web')->only(['anular']);
        $this->middleware('permission:consentimientos.restaurar,web')->only(['restaurar']);
        $this->middleware('permission:consentimientos.pdf.generar,web')->only(['generarPdf']);
        $this->middleware('permission:consentimientos.pdf.descargar,web')->only(['descargarPdf']);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->service->index([
            'q' => $request->query('q', ''),
            'estado' => $request->query('estado'),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
            'sede_id' => $request->query('sede_id'),
        ]);

        return response()->json($result, $result['status']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->service->show($id);

        return response()->json($result, $result['status']);
    }

    public function generar(ConsentimientoGenerarRequest $request): JsonResponse
    {
        $result = $this->service->generar($request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function buscarPaciente(Request $request): JsonResponse
    {
        $numDocu = trim((string) $request->query('num_docu', ''));
        if ($numDocu === '') {
            return response()->json(['message' => 'num_docu es obligatorio'], 422);
        }

        $result = $this->service->buscarPaciente($numDocu);

        return response()->json($result, $result['status']);
    }

    public function medicos(): JsonResponse
    {
        $result = $this->service->medicos();

        return response()->json($result, $result['status']);
    }

    public function crearToken(ConsentimientoCrearTokenRequest $request): JsonResponse
    {
        $result = $this->service->crearToken($request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function infoPorToken(Request $request, string $token): JsonResponse
    {
        $result = $this->service->infoPorToken($token);

        return response()->json($result, $result['status']);
    }

    public function firmarPacientePorToken(Request $request, string $token): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'firma_png' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $result = $this->service->firmarPacientePorToken($token, $request->only('firma_png'), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function firmarMedico(ConsentimientoFirmarMedicoRequest $request): JsonResponse
    {
        $result = $this->service->firmarMedico($request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function anular(ConsentimientoAnularRequest $request, int $id): JsonResponse
    {
        $result = $this->service->anular($id, $request->validated(), $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    /**
     * Restaura un consentimiento expirado (permiso consentimientos.restaurar).
     *
     * @param  Request  $request  Petición HTTP.
     * @param  int  $id  ID del consentimiento.
     *
     * @author Davide Rivera (Luis Castaño)
     *
     * @date 2026-08-29
     */
    public function restaurar(Request $request, int $id): JsonResponse
    {
        $result = $this->service->restaurar($id, $request->user(), $request->ip());

        return response()->json($result, $result['status']);
    }

    public function generarPdf(Request $request, int $id): JsonResponse
    {
        $result = $this->service->generarPdf($id);

        return response()->json($result, $result['status']);
    }

    public function descargarPdf(Request $request, int $id)
    {
        $result = $this->service->descargarPdf($id);

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        return response()
            ->download($result['data']['tmp_path'], $result['data']['download_name'])
            ->deleteFileAfterSend(true);
    }
}
