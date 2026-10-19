<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Services\Backup\BackupDcmService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        set_time_limit(0);

        // 1. Validar autenticación por Bearer Token
        $tokenRecibido = $request->bearerToken() ?: $request->header("X-Backup-Token") ?: $request->input("token");
        $tokenEsperado = (string) config("backup.api_token", env("BACKUP_API_TOKEN", "lidiag_backup_secure_token_2026"));

        if (empty($tokenRecibido) || ! hash_equals($tokenEsperado, (string) $tokenRecibido)) {
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
            "backup_file" => "required|file",
        ]);

        try {
            $sedeId = (int) $request->input("sede_id");
            $sede = ConfigSede::findOrFail($sedeId);
            $slug = Str::slug($sede->nom_sede ?? "sede_{$sedeId}", "_");
            $timestamp = date("Y-m-d_His");

            $archivoSubido = $request->file("backup_file");
            $extension = strtolower($archivoSubido->getClientOriginalExtension() ?: "zip");
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
}
