<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecografias\EcografiaConsentimientoAnularRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoCrearTokenRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoFirmarMedicoRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoFirmarPacienteRequest;
use App\Http\Requests\Ecografias\EcografiaConsentimientoGenerarZipRequest;
use App\Services\Ecografias\EcografiaConsentimientosService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EcografiaConsentimientosController extends Controller
{
    /**
     * Inyecta el servicio de consentimientos de ecografías y configura los middlewares.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Lista los consentimientos de ecografías paginados según los filtros recibidos.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros q, desde, hasta y sede_id.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Devuelve la información del consentimiento asociado a un token de firma.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  string  $token  Token único del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function infoPorToken(Request $request, string $token): JsonResponse
    {
        $result = $this->consentimientosService->infoPorToken($token);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un token de firma para un consentimiento de ecografía.
     *
     * @param  EcografiaConsentimientoCrearTokenRequest  $request  Solicitud validada con los datos del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function crearToken(EcografiaConsentimientoCrearTokenRequest $request): JsonResponse
    {
        $result = $this->consentimientosService->crearToken(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Registra la firma del paciente sobre un consentimiento usando su token.
     *
     * @param  EcografiaConsentimientoFirmarPacienteRequest  $request  Solicitud validada con la firma del paciente.
     * @param  string  $token  Token único del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function firmarPacientePorToken(EcografiaConsentimientoFirmarPacienteRequest $request, string $token): JsonResponse
    {
        $result = $this->consentimientosService->firmarPacientePorToken(
            $token,
            $request->validated(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Registra la firma del médico sobre un consentimiento.
     *
     * @param  EcografiaConsentimientoFirmarMedicoRequest  $request  Solicitud validada con la firma del médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function firmarMedico(EcografiaConsentimientoFirmarMedicoRequest $request): JsonResponse
    {
        $result = $this->consentimientosService->firmarMedico(
            $request->validated(),
            $request->user(),
            $request->ip(),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Anula un consentimiento de ecografía registrado.
     *
     * @param  EcografiaConsentimientoAnularRequest  $request  Solicitud validada con el motivo de anulación.
     * @param  int  $id  Identificador del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function anular(EcografiaConsentimientoAnularRequest $request, int $id): JsonResponse
    {
        $result = $this->consentimientosService->anular(
            $id,
            $request->validated(),
            $request->user(),
        );

        return response()->json($result, $result['status']);
    }

    /**
     * Devuelve el consentimiento vigente de un producto de ecografía.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  int  $ecografia_producto_id  Identificador del producto de ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function vigentePorEcografiaProducto(Request $request, int $ecografia_producto_id): JsonResponse
    {
        $result = $this->consentimientosService->vigentePorEcografiaProducto($ecografia_producto_id);

        return response()->json($result, $result['status']);
    }

    /**
     * Genera el PDF de un consentimiento de ecografía.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  int  $id  Identificador del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function generarPdf(Request $request, int $id): JsonResponse
    {
        $result = $this->consentimientosService->generarPdf($id);

        return response()->json($result, $result['status']);
    }

    /**
     * Descarga el PDF previamente generado de un consentimiento.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  int  $id  Identificador del consentimiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Genera y descarga un ZIP con los consentimientos solicitados.
     *
     * @param  EcografiaConsentimientoGenerarZipRequest  $request  Solicitud validada con los IDs de consentimientos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function generarZip(EcografiaConsentimientoGenerarZipRequest $request)
    {
        $result = $this->consentimientosService->generarZip($request->validated());

        if ($result['status'] !== 200) {
            return response()->json($result, $result['status']);
        }

        return response()->download($result['data']['path'])->deleteFileAfterSend(true);
    }
}
