<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Services\Seguridad\TokenSedeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenSedeController extends Controller
{
    /**
     * Constructor con inyección de dependencias y políticas Spatie.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function __construct(
        protected TokenSedeService $tokenService
    ) {
        $this->middleware("auth");
        $this->middleware("permission:seguridad.tokens.index")->only(["index", "descargarConfigJson"]);
        $this->middleware("permission:seguridad.tokens.create")->only(["store"]);
        $this->middleware("permission:seguridad.tokens.revoke")->only(["destroy"]);
    }

    /**
     * Retorna el listado consolidado de sedes con los metadatos de sus tokens de acceso Edge.
     *
     * Flujo del proceso:
     * 1. Consulta al servicio el consolidado de sedes y tokens asociados.
     * 2. Retorna la respuesta en formato JSON estandarizado.
     *
     * @return JsonResponse Listado de sedes y tokens
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function index(): JsonResponse
    {
        $result = $this->tokenService->listarSedesConTokens();

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Emite un nuevo token de acceso para la sede indicada realizando rotación automática.
     *
     * Flujo del proceso:
     * 1. Valida el identificador de sede enviado en la petición.
     * 2. Ejecuta la generación y rotación segura mediante TokenSedeService.
     * 3. Devuelve el token en texto plano (única visualización) y el objeto de configuración.
     *
     * @param  Request  $request  Petición con el campo sede_id
     * @return JsonResponse Token emitido y bloque config.json
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
        ]);

        $result = $this->tokenService->generarTokenSede((int) $validated["sede_id"]);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Revoca definitivamente un token de sede impidiendo su uso futuro.
     *
     * Flujo del proceso:
     * 1. Valida la existencia del token por su ID.
     * 2. Solicita la eliminación del token a TokenSedeService.
     * 3. Retorna la confirmación en formato JSON.
     *
     * @param  int  $id  Identificador del token en personal_access_tokens
     * @return JsonResponse Confirmación de revocación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->tokenService->revocarToken($id);

        return response()->json($result, (int) $result["status"]);
    }

    /**
     * Descarga el archivo config.json institucional preconfigurado para una sede.
     *
     * Flujo del proceso:
     * 1. Localiza la sede por su ID.
     * 2. Construye la estructura JSON con los parámetros de la sede.
     * 3. Envía la respuesta con cabeceras de descarga de archivo adjunto `.json`.
     *
     * @param  int  $sedeId  Identificador de la sede
     * @return Response Descarga de archivo config.json
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function descargarConfigJson(int $sedeId): Response
    {
        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            return response()->json(["message" => "Sede no encontrada"], 404);
        }

        $configData = $this->tokenService->construirConfigJson($sede, "[REEMPLAZAR_POR_TOKEN_ACTUAL]");
        $jsonContent = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $fileName = "config_sede_{$sede->id}.json";

        return response($jsonContent, 200, [
            "Content-Type" => "application/json",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
