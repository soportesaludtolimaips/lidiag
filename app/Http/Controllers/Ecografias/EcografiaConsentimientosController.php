<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaConsentimientoAnularRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoCrearTokenRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoFirmarMedicoRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoGenerarZipRequest;
use App\Services\Ecografias\EcografiaConsentimientosService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EcografiaConsentimientosController extends Controller
{
    public function __construct(
        private readonly EcografiaConsentimientosService $consentimientosService,
    ) {
        $this->middleware('auth')->only(['index', 'crearToken', 'firmarMedico', 'vigentePorEcografiaProducto', 'generarPdf', 'descargarPdf', 'generarZip']);

        $this->middleware('permission:ecografias.consentimientos.capturar_firma,web')->only(['crearToken']);
        $this->middleware('permission:ecografias.consentimientos.firmar_medico,web')->only(['firmarMedico']);
        $this->middleware('permission:ecografias.consentimientos.show|ecografias.consentimientos.index,web')->only(['vigentePorEcografiaProducto']);
        $this->middleware('permission:ecografias.consentimientos.index,web')->only(['index']);
        $this->middleware('permission:ecografias.consentimientos.pdf.generar,web')->only(['generarPdf']);
        $this->middleware('permission:ecografias.consentimientos.pdf.descargar,web')->only(['descargarPdf']);
        $this->middleware('permission:ecografias.consentimientos.zip.generar,web')->only(['generarZip']);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->consentimientosService->index([
            'q' => $request->query('q', ''),
            'desde' => $request->query('desde'),
            'hasta' => $request->query('hasta'),
            'sede_id' => $request->query('sede_id'),
        ]);

        return response()->json($result, $result['status']);
    }

    public function infoPorToken(Request $request, string $token): JsonResponse
    {
        $result = $this->consentimientosService->infoPorToken($token);

        return response()->json($result, $result['status']);
    }

    public function crearToken(EcografiaConsentimientoCrearTokenRequest $request): JsonResponse
    {
        $result = $this->consentimientosService->crearToken(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    public function firmarPacientePorToken(Request $request, string $token): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'firma_png' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $result = $this->consentimientosService->firmarPacientePorToken(
            $token,
            $request->only('firma_png'),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    public function firmarMedico(EcografiaConsentimientoFirmarMedicoRequest $request): JsonResponse
    {
        $result = $this->consentimientosService->firmarMedico(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    public function anular(EcografiaConsentimientoAnularRequest $request, int $id): JsonResponse
    {
        $result = $this->consentimientosService->anular(
            $id,
            $request->validated(),
            $request->user(),
        );

        return response()->json($result, $result['status']);
    }

    public function vigentePorEcografiaProducto(Request $request, int $ecografia_producto_id): JsonResponse
    {
        $result = $this->consentimientosService->vigentePorEcografiaProducto($ecografia_producto_id);

        return response()->json($result, $result['status']);
    }

    public function generarPdf(Request $request, int $id): JsonResponse
    {
        $result = $this->consentimientosService->generarPdf($id);

        return response()->json($result, $result['status']);
    }

    public function descargarPdf(Request $request, int $id)
    {
        $result = $this->consentimientosService->descargarPdf($id);

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($result['data']['disk']);

        return $disk->download($result['data']['path']);
    }

    public function generarZip(EcografiaConsentimientoGenerarZipRequest $request)
    {
        $result = $this->consentimientosService->generarZip($request->validated());

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        return response()->download($result['data']['path'])->deleteFileAfterSend(true);
    }
}
