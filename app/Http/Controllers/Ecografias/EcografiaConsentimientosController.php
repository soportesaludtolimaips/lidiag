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

    /**
     * Lista los consentimientos vigentes aplicando los filtros recibidos.
     *
     * @param  Request  $request  Solicitud HTTP con los filtros de búsqueda.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Retorna la información de un consentimiento a partir de su token público.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  string  $token  Token del consentimiento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function infoPorToken(Request $request, string $token): JsonResponse
    {
        $result = $this->consentimientosService->infoPorToken($token);

        return response()->json($result, $result['status']);
    }

    /**
     * Crea un token de firma de consentimiento válido por 10 minutos.
     *
     * @param  EcografiaConsentimientoCrearTokenRequest  $request  Solicitud validada con los datos del token.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Registra la firma del paciente/acompañante a partir de un token.
     *
     * @param  Request  $request  Solicitud HTTP con la firma en PNG.
     * @param  string  $token  Token del consentimiento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
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

    /**
     * Registra la firma del médico sobre un consentimiento.
     *
     * @param  EcografiaConsentimientoFirmarMedicoRequest  $request  Solicitud validada con los datos de firma.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Anula un consentimiento de ecografía registrando la observación.
     *
     * @param  EcografiaConsentimientoAnularRequest  $request  Solicitud validada con la observación.
     * @param  int  $id  Identificador del consentimiento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Retorna el consentimiento vigente asociado a un producto de ecografía.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  int  $ecografia_producto_id  Identificador del producto de ecografía.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function vigentePorEcografiaProducto(Request $request, int $ecografia_producto_id): JsonResponse
    {
        $result = $this->consentimientosService->vigentePorEcografiaProducto($ecografia_producto_id);

        return response()->json($result, $result['status']);
    }

    /**
     * Genera el PDF cifrado del consentimiento de ecografía.
     *
     * @param  Request  $request  Solicitud HTTP.
     * @param  int  $id  Identificador del consentimiento.
     *
     * @returns {JsonResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * @returns {JsonResponse|BinaryFileResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
     * Genera un archivo ZIP con los PDF de los consentimientos seleccionados.
     *
     * @param  EcografiaConsentimientoGenerarZipRequest  $request  Solicitud validada con los ids.
     *
     * @returns {JsonResponse|BinaryFileResponse}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
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
