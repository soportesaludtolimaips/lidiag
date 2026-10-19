<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupDcmService;
use Illuminate\Console\Command;

class LidiagBackupDcmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "lidiag:backup-dcm
        {--sede= : ID de una sede especifica a respaldar}
        {--destination= : Ruta personalizada de destino para los backups DCM}
        {--retention= : Dias de retencion para purga automatica}
        {--timeout= : Timeout en segundos para la prueba de conexion por sede}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Genera el backup de las bases de datos DCM/PACS de cada sede en archivos .zip independientes";

    /**
     * Instancia del servicio de backup DCM.
     */
    protected BackupDcmService $backupDcmService;

    /**
     * Constructor del comando.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct(BackupDcmService $backupDcmService)
    {
        parent::__construct();
        $this->backupDcmService = $backupDcmService;
    }

    /**
     * Ejecuta el comando de backup de bases de datos DCM por sedes.
     *
     * @return int Código de salida del comando (0: éxito, 1: fallo)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function handle(): int
    {
        $this->info("=========================================================");
        $this->info("    SISTEMA DE BACKUP DCM / PACS POR SEDES - LIDiag      ");
        $this->info("=========================================================");

        $opciones = [];

        if ($this->option("sede")) {
            $opciones["sede_id"] = (int) $this->option("sede");
        }

        if ($this->option("destination")) {
            $opciones["destination_path"] = $this->option("destination");
        }

        if ($this->option("retention") !== null) {
            $opciones["retention_days"] = (int) $this->option("retention");
        }

        if ($this->option("timeout") !== null) {
            $opciones["timeout"] = (int) $this->option("timeout");
        }

        $callback = function (string $tipo, string $mensaje) {
            match ($tipo) {
                "success" => $this->info("  [OK] ".$mensaje),
                "error" => $this->error("  [ERROR] ".$mensaje),
                "comment" => $this->comment("  [INFO] ".$mensaje),
                default => $this->line("  [RUN] ".$mensaje),
            };
        };

        $resultado = $this->backupDcmService->ejecutar($opciones, $callback);

        if (! $resultado["success"]) {
            $this->newLine();
            $this->error("Fallo durante el proceso de backup DCM: ".($resultado["message"] ?? "Error desconocido"));

            return 1;
        }

        $datos = $resultado["data"] ?? [];
        $detalles = $datos["detalles"] ?? [];

        $this->newLine();
        $this->info("---------------------------------------------------------");
        $this->info("               DETALLE DE SEDES PROCESADAS               ");
        $this->info("---------------------------------------------------------");

        $filasTabla = [];
        foreach ($detalles as $d) {
            $estadoFormateado = $d["estado"] === "OK" ? "<info>EXITOSO</info>" : "<error>FALLIDO</error>";
            $filasTabla[] = [
                $d["sede_id"],
                $d["nom_sede"],
                $d["host"],
                $d["bd"],
                $estadoFormateado,
                $d["archivo"] !== "N/A" ? $d["archivo"] : ($d["error"] ?? "N/A"),
                $d["tamano_humano"],
                $d["duracion_segundos"]."s",
            ];
        }

        $this->table(
            ["ID", "Sede", "Host", "Base de Datos", "Estado", "Archivo / Detalle", "Tamaño", "Tiempo"],
            $filasTabla
        );

        $this->info("Resumen: ".($datos["exitosas"] ?? 0)." sedes respaldadas, ".($datos["fallidas"] ?? 0)." con error o fuera de linea.");
        $this->info("Directorio de destino: ".($datos["destino"] ?? "N/A")." (Total generado: ".($datos["total_tamano_humano"] ?? "0 B").")");
        $this->info("Logs registrados en: storage/logs/backups.log");

        return 0;
    }
}
