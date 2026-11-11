<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\Backup\BackupMonitorService;
use App\Services\Backup\BackupService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupMonitorController extends Controller
{
    /**
     * Constructor del controlador de monitoreo de backups.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        protected BackupMonitorService $monitorService,
        protected BackupService $backupService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:seguridad.backups.index')->only(['estado', 'descargarAgentesDcm']);
        $this->middleware('permission:seguridad.backups.ejecutar')->only(['ejecutarBackupDb']);
    }

    /**
     * Retorna el estado consolidado de los backups de base de datos, storage y sedes hospitalarias.
     *
     * Flujo del proceso:
     * 1. Solicita a BackupMonitorService el estado general de salud y métricas de almacenamiento.
     * 2. Retorna respuesta JSON estructurada con código HTTP 200.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function estado(): JsonResponse
    {
        $result = $this->monitorService->obtenerEstadoGeneral();

        return response()->json($result, (int) $result['status']);
    }

    /**
     * Dispara la ejecución inmediata de un backup de la base de datos principal MySQL.
     *
     * Flujo del proceso:
     * 1. Invoca BackupService::ejecutar() con la opción only_db y retención automática.
     * 2. Retorna respuesta JSON con el resultado de la operación y el nombre del archivo generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function ejecutarBackupDb(): JsonResponse
    {
        $res = $this->backupService->ejecutar(["only_db" => true]);

        if (! ($res["success"] ?? false)) {
            return response()->json([
                "success" => false,
                "message" => $res["error"] ?? $res["message"] ?? "Fallo al generar el backup de base de datos.",
            ], 500);
        }

        return response()->json([
            "success" => true,
            "message" => "Backup de base de datos generado exitosamente.",
            "data" => $res["data"] ?? $res,
        ], 200);
    }

    /**
     * Empaqueta y descarga el paquete completo de agentes de respaldo y telemetría DCM para sedes hospitalarias.
     *
     * Flujo del proceso:
     * 1. Invoca BackupMonitorService::generarZipAgentesDcm() para construir el archivo ZIP temporal.
     * 2. Si ocurre un error, retorna respuesta JSON con código HTTP 500.
     * 3. Retorna la respuesta de descarga binaria con cabeceras para forzar la descarga en el navegador y eliminación posterior.
     *
     * @return BinaryFileResponse|JsonResponse Respuesta de descarga o error en formato JSON.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function descargarAgentesDcm(): BinaryFileResponse|JsonResponse
    {
        $res = $this->monitorService->generarZipAgentesDcm();

        if (! ($res['success'] ?? false) || empty($res['data']['path'])) {
            return response()->json([
                'success' => false,
                'message' => $res['message'] ?? 'No se pudo generar el paquete de agentes.',
            ], 500);
        }

        return response()->download(
            $res['data']['path'],
            $res['data']['nombre'] ?? 'agente_backups_dcm.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }
}
