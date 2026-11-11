<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Services\Backup\BackupDcmService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class BackupUploadController extends Controller
{
    /**
     * Servicio de backup DCM.
     */
    protected BackupDcmService $backupDcmService;

    /**
     * Constructor del controlador.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct(BackupDcmService $backupDcmService)
    {
        $this->backupDcmService = $backupDcmService;
    }

    /**
     * Recibe y almacena el archivo comprimido de backup enviado remotamente por el agente Python de una sede.
     *
     * Flujo del proceso:
     * 1. Desactiva límites de tiempo de ejecución para recepción de archivos médicos de gran tamaño.
     * 2. Valida la cabecera Authorization (Bearer Token) contra BACKUP_API_TOKEN del .env con hash_equals.
     * 3. Valida los parámetros del formulario: 'sede_id' (existente en config_sedes) y 'backup_file' (archivo adjunto).
     * 4. Resuelve el nombre canónico y slug de la sede.
     * 5. Traslada el archivo recibido hacia C:\Backups_Lidiag\dcm_sedes\ con nomenclatura estándar.
     * 6. Ejecuta la política de retención automática eliminando backups con más de 30 días de antigüedad.
     * 7. Registra el evento en storage/logs/backups.log y retorna respuesta JSON de éxito.
     *
     * @param  Request  $request  Petición HTTP con el archivo multipart y token
     * @return JsonResponse Resultado de la operación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function uploadDcm(Request $request): JsonResponse
    {
        @ini_set("upload_max_filesize", "2048M");
        @ini_set("post_max_size", "2048M");
        @ini_set("memory_limit", "1024M");
        @set_time_limit(1800);

        // Detectar si el tamaño superó post_max_size del webserver antes de llegar al controlador
        if (empty($_FILES) && empty($_POST) && isset($_SERVER["CONTENT_LENGTH"]) && (int) $_SERVER["CONTENT_LENGTH"] > 0) {
            $maxPost = ini_get("post_max_size");
            Log::channel("backups")->warning("Subida rechazada: payload supera post_max_size ({$maxPost})", [
                "content_length" => $_SERVER["CONTENT_LENGTH"],
            ]);

            return response()->json([
                "success" => false,
                "message" => "El archivo enviado ({$_SERVER['CONTENT_LENGTH']} bytes) superó el límite 'post_max_size' ({$maxPost}) configurado en el servidor web.",
            ], 413);
        }

        // 1. Validar autenticación (compatible con Sanctum por sede multi-canal y BACKUP_API_TOKEN estático)
        if (! $this->resolverTokenValido($request)) {
            Log::channel("backups")->warning("Intento no autorizado de subida de backup DCM", [
                "ip" => $request->ip(),
                "sede_id" => $request->input("sede_id"),
            ]);

            return response()->json([
                "success" => false,
                "message" => "No autorizado. Token de backup inválido o ausente.",
            ], 401);
        }

        // 2. Validar parámetros requeridos
        $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
            "backup_file" => "required|file|mimes:zip,sql,gz,7z,tar|max:2097152",
        ]);

        try {
            $sedeId = (int) $request->input("sede_id");
            $sede = ConfigSede::findOrFail($sedeId);
            $slug = Str::slug($sede->nom_sede ?? "sede_{$sedeId}", "_");
            $timestamp = date("Y-m-d_His");

            $archivoSubido = $request->file("backup_file");
            $allowedExtensions = ["zip", "sql", "gz", "7z", "tar"];
            $rawExtension = strtolower($archivoSubido->getClientOriginalExtension() ?: "zip");
            $extension = in_array($rawExtension, $allowedExtensions, true) ? $rawExtension : "zip";
            $nombreFinal = "backup_dcm_sede_{$sedeId}_{$slug}_{$timestamp}.{$extension}";

            $directorioDestino = config("backup.dcm_destination_path", "C:\\Backups_Lidiag\\dcm_sedes");

            if (! File::exists($directorioDestino)) {
                File::makeDirectory($directorioDestino, 0755, true, true);
            }

            // Trasladar archivo a la carpeta destino
            $archivoSubido->move($directorioDestino, $nombreFinal);
            $rutaCompleta = rtrim($directorioDestino, "\\/").DIRECTORY_SEPARATOR.$nombreFinal;

            if (! file_exists($rutaCompleta) || filesize($rutaCompleta) === 0) {
                throw new Exception("El archivo de backup no se guardó correctamente en disco o está vacío.");
            }

            $tamanoBytes = filesize($rutaCompleta);
            $tamanoHumano = $this->backupDcmService->formatearBytes($tamanoBytes);

            // 3. Evaluar retención
            $retencionDias = (int) config("backup.dcm_retention_days", 30);
            $purgados = [];
            if ($retencionDias > 0) {
                $purgados = $this->backupDcmService->aplicarPoliticaRetencion($directorioDestino, $retencionDias);
            }

            $datosRespuesta = [
                "sede_id" => $sedeId,
                "nom_sede" => $sede->nom_sede,
                "archivo" => $nombreFinal,
                "ruta_completa" => $rutaCompleta,
                "tamano_bytes" => $tamanoBytes,
                "tamano_humano" => $tamanoHumano,
                "purgados_retencion" => $purgados,
            ];

            Cache::put("telemetria_sede_{$sedeId}_backup_db", [
                "servicio" => "backup_db",
                "estado" => "OK",
                "mensaje" => "Archivo {$nombreFinal} recibido ({$tamanoHumano})",
                "detalles" => [
                    "archivo" => $nombreFinal,
                    "tamano" => $tamanoHumano,
                ],
                "fecha" => now()->toIso8601String(),
            ], now()->addDays(30));

            Log::channel("backups")->info("Backup DCM recibido y almacenado con éxito vía Webhook", $datosRespuesta);

            return response()->json([
                "success" => true,
                "message" => "Backup de la sede #{$sedeId} ({$sede->nom_sede}) recibido y almacenado exitosamente.",
                "data" => $datosRespuesta,
            ], 200);
        } catch (Throwable $e) {
            Log::channel("backups")->error("Error al procesar subida de backup DCM: ".$e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json([
                "success" => false,
                "message" => "Error interno al almacenar el backup: ".$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registra el latido o telemetría periódica de los servicios de backup e imágenes de una sede.
     *
     * Flujo del proceso:
     * 1. Valida autenticación por Bearer Token (Sanctum o estático).
     * 2. Valida los parámetros: sede_id, servicio, estado y métricas operativas.
     * 3. Registra el evento en storage/logs/backups.log para visualización en el dashboard central.
     * 4. Retorna respuesta HTTP 200 con confirmación de recepción.
     *
     * @param  Request  $request  Petición HTTP con telemetría del agente
     * @return JsonResponse Confirmación de recepción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function telemetria(Request $request): JsonResponse
    {
        if (! $this->resolverTokenValido($request)) {
            return response()->json([
                "success" => false,
                "message" => "No autorizado. Token de acceso inválido o ausente.",
            ], 401);
        }

        $validated = $request->validate([
            "sede_id" => "required|integer|exists:config_sedes,id",
            "servicio" => "required|string|max:50",
            "estado" => "required|string|max:50",
            "mensaje" => "sometimes|nullable|string|max:500",
            "detalles" => "sometimes|nullable|array",
        ]);

        $sede = ConfigSede::find($validated["sede_id"]);
        $sedeNombre = $sede->nom_sede ?? "Sede #{$validated['sede_id']}";

        $datosTelemetria = [
            "servicio" => $validated["servicio"],
            "estado" => strtoupper($validated["estado"]),
            "mensaje" => $validated["mensaje"] ?? "OK",
            "detalles" => $validated["detalles"] ?? [],
            "fecha" => now()->toIso8601String(),
        ];

        // 1. Guardar en caché rápida
        Cache::put("telemetria_sede_{$validated['sede_id']}_{$validated['servicio']}", $datosTelemetria, now()->addDays(30));

        // 2. Persistir en archivo JSON dedicado para inmunidad ante caídas de caché o reinicios de FPM
        try {
            $dirTelemetria = storage_path("app/telemetria");
            if (! File::isDirectory($dirTelemetria)) {
                File::makeDirectory($dirTelemetria, 0755, true, true);
            }
            $archivoPersistencia = "{$dirTelemetria}/telemetria_sede_{$validated['sede_id']}_{$validated['servicio']}.json";
            File::put($archivoPersistencia, json_encode($datosTelemetria, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $e) {
            Log::channel("backups")->warning("No se pudo escribir archivo de persistencia de telemetría: ".$e->getMessage());
        }

        Log::channel("backups")->info(
            "[Telemetría {$validated['servicio']}] {$sedeNombre}: Estado {$validated['estado']} - ".($validated["mensaje"] ?? "OK"),
            [
                "sede_id" => $validated["sede_id"],
                "servicio" => $validated["servicio"],
                "estado" => $validated["estado"],
                "detalles" => $validated["detalles"] ?? [],
            ]
        );

        return response()->json([
            "success" => true,
            "message" => "Telemetría registrada correctamente para {$sedeNombre}.",
            "timestamp" => now()->format("Y-m-d H:i:s"),
        ]);
    }

    /**
     * Resuelve y valida el token de autenticación multicanal (Bearer, headers alternativos o payload).
     *
     * @param  Request  $request  Petición HTTP entrante
     * @return bool Verdadero si el token es válido
     */
    protected function resolverTokenValido(Request $request): bool
    {
        $tokenRecibido = $request->bearerToken()
            ?: $request->header("X-API-TOKEN")
            ?: $request->header("x-api-token")
            ?: $request->header("X-Backup-Token")
            ?: $request->input("api_token")
            ?: $request->input("token");

        if (empty($tokenRecibido)) {
            return false;
        }

        // 1. Limpiar prefijo Bearer si fue enviado dentro del header o payload
        $tokenRecibido = trim((string) $tokenRecibido);
        if (str_starts_with(strtolower($tokenRecibido), "bearer ")) {
            $tokenRecibido = trim(substr($tokenRecibido, 7));
        }

        // 2. Token estático del .env (compatibilidad institucional)
        $tokenEsperado = (string) config("backup.api_token");
        if (! empty($tokenEsperado) && hash_equals($tokenEsperado, $tokenRecibido)) {
            return true;
        }

        // 3. Token Sanctum emitido por sede hospitalaria
        $personalToken = PersonalAccessToken::findToken($tokenRecibido);
        if ($personalToken && $personalToken->tokenable) {
            $personalToken->forceFill(["last_used_at" => now()])->save();

            return true;
        }

        return false;
    }
}
