<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class LidiagBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "lidiag:backup
        {--only-db : Realizar backup unicamente de la base de datos MySQL}
        {--only-files : Realizar backup unicamente de las carpetas de storage}
        {--destination= : Ruta personalizada del directorio de destino}
        {--retention= : Dias de retencion para purga de backups antiguos}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Genera el backup de la base de datos MySQL y las carpetas de storage en archivos .zip independientes";

    /**
     * Instancia del servicio de backup.
     */
    protected BackupService $backupService;

    /**
     * Constructor del comando.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Ejecuta el comando de backup de LIDiag.
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
        $this->info("    SISTEMA DE BACKUP AUTOMATICO - LIDiag (SEPARADO)     ");
        $this->info("=========================================================");

        $opciones = [];

        if ($this->option("only-db")) {
            $opciones["only_db"] = true;
        }

        if ($this->option("only-files")) {
            $opciones["only_files"] = true;
        }

        if ($this->option("destination")) {
            $opciones["destination_path"] = $this->option("destination");
        }

        if ($this->option("retention") !== null) {
            $opciones["retention_days"] = (int) $this->option("retention");
        }

        $callback = function (string $tipo, string $mensaje) {
            match ($tipo) {
                "success" => $this->info("  [OK] ".$mensaje),
                "error" => $this->error("  [ERROR] ".$mensaje),
                "comment" => $this->comment("  [INFO] ".$mensaje),
                default => $this->line("  [RUN] ".$mensaje),
            };
        };

        $resultado = $this->backupService->ejecutar($opciones, $callback);

        if (! $resultado["success"]) {
            $this->newLine();
            $this->error("Fallo durante el proceso de backup: ".($resultado["message"] ?? "Error desconocido"));

            return 1;
        }

        $datos = $resultado["data"] ?? [];

        $this->newLine();
        $this->info("---------------------------------------------------------");
        $this->info("               RESUMEN DE EJECUCION                      ");
        $this->info("---------------------------------------------------------");

        $filasTabla = [];

        if ($datos["bd_incluida"] ?? false) {
            $filasTabla[] = ["Backup Base de Datos", ($datos["archivo_db"] ?? "N/A")." (".($datos["tamano_db_humano"] ?? "N/A").")"];
        }

        if ($datos["storage_incluido"] ?? false) {
            $filasTabla[] = ["Backup Storage (".($datos["archivos_storage_count"] ?? 0)." archivos)", ($datos["archivo_storage"] ?? "N/A")." (".($datos["tamano_storage_humano"] ?? "N/A").")"];
        }

        $filasTabla[] = ["Tamaño Total Generado", $datos["tamano_total_humano"] ?? "N/A"];
        $filasTabla[] = ["Directorio Destino", config("backup.destination_path", "C:\\Backups_Lidiag")];
        $filasTabla[] = ["Backups Purgados (Retención)", count($datos["purgados"] ?? [])];
        $filasTabla[] = ["Tiempo Total de Ejecución", ($datos["duracion_segundos"] ?? 0)." segundos"];

        $this->table(["Métrica / Parámetro", "Valor"], $filasTabla);

        $this->info("Los archivos se encuentran listos en el directorio para sincronizacion hacia la QNAP.");
        $this->info("Logs registrados en: storage/logs/backups.log");

        return 0;
    }
}
