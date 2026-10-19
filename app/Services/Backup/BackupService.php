<?php

namespace App\Services\Backup;

use App\Services\BaseService;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

class BackupService extends BaseService
{
    /**
     * Ejecuta el proceso integral de backup con archivos separados (DB en un .zip y Storage en otro .zip).
     *
     * Flujo del proceso:
     * 1. Inicializa variables de entorno, rutas y callback de notificación en tiempo real.
     * 2. Prepara y limpia el directorio de trabajo temporal.
     * 3. Si aplica BD: realiza el dump MySQL con mysqldump y lo empaqueta en backup_lidiag_db_*.zip.
     * 4. Si aplica Storage: comprime las 16 carpetas seleccionadas en backup_lidiag_storage_*.zip.
     * 5. Aplica la política de retención eliminando archivos de backup antiguos (> N días).
     * 6. Registra el resultado detallado en storage/logs/backups.log y retorna el resumen.
     *
     * @param  array  $opciones  Opciones de ejecución ('only_db', 'only_files', 'retention_days', 'destination_path')
     * @param  callable|null  $callback  Callback opcional para emitir mensajes de progreso a la consola
     * @return array Resultado estructurado de la operación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function ejecutar(array $opciones = [], ?callable $callback = null): array
    {
        $inicioTiempo = microtime(true);
        $onlyDb = (bool) ($opciones["only_db"] ?? false);
        $onlyFiles = (bool) ($opciones["only_files"] ?? false);
        $retencionDias = (int) ($opciones["retention_days"] ?? config("backup.retention_days", 30));
        $destino = (string) ($opciones["destination_path"] ?? config("backup.destination_path", "C:\\Backups_Lidiag"));
        $tempPath = (string) config("backup.temp_path", storage_path("app/backup-temp"));

        $this->notificar($callback, "info", "Iniciando proceso de backup independiente (DB y Storage por separado)...");
        Log::channel("backups")->info("=== INICIO DE BACKUP AUTOMÁTICO LIDiag (SEPARADO) ===", [
            "only_db" => $onlyDb,
            "only_files" => $onlyFiles,
            "retencion_dias" => $retencionDias,
            "destino" => $destino,
        ]);

        // Asegurar que el directorio de destino existe
        if (! File::exists($destino)) {
            File::makeDirectory($destino, 0755, true, true);
        }

        // Asegurar que el directorio temporal existe y está limpio
        if (! File::exists($tempPath)) {
            File::makeDirectory($tempPath, 0755, true, true);
        }

        $timestamp = date("Y-m-d_His");
        $rutaSqlDump = null;
        $rutaZipDb = null;
        $rutaZipStorage = null;
        $nombreZipDb = null;
        $nombreZipStorage = null;
        $tamanoZipDbBytes = 0;
        $tamanoZipStorageBytes = 0;
        $archivosStorageCount = 0;
        $archivosPurgados = [];

        try {
            // 1. BACKUP DE BASE DE DATOS (si no se indicó only_files)
            if (! $onlyFiles) {
                $nombreZipDb = "backup_lidiag_db_{$timestamp}.zip";
                $rutaZipDb = rtrim($destino, "\\/").DIRECTORY_SEPARATOR.$nombreZipDb;

                $this->notificar($callback, "info", "Generando volcado de base de datos MySQL...");
                $rutaSqlDump = $this->generarDumpDatabase($tempPath, $timestamp, $callback);
                $tamanoDumpBytes = filesize($rutaSqlDump);
                $this->notificar($callback, "success", "Dump de BD generado exitosamente: ".$this->formatearBytes($tamanoDumpBytes));

                $this->notificar($callback, "info", "Comprimiendo base de datos en: {$nombreZipDb}");
                $this->empaquetarDb($rutaZipDb, $rutaSqlDump);

                $tamanoZipDbBytes = file_exists($rutaZipDb) ? filesize($rutaZipDb) : 0;
                $this->notificar($callback, "success", "Archivo DB generado: {$nombreZipDb} (".$this->formatearBytes($tamanoZipDbBytes).")");
            }

            // 2. BACKUP DE STORAGE (si no se indicó only_db)
            if (! $onlyDb) {
                $nombreZipStorage = "backup_lidiag_storage_{$timestamp}.zip";
                $rutaZipStorage = rtrim($destino, "\\/").DIRECTORY_SEPARATOR.$nombreZipStorage;

                $carpetasStorage = $this->obtenerCarpetasStorage();
                $this->notificar($callback, "info", "Empaquetando ".count($carpetasStorage)." carpetas de storage en: {$nombreZipStorage}");

                $resumenStorage = $this->empaquetarStorage($rutaZipStorage, $carpetasStorage, $callback);
                $archivosStorageCount = $resumenStorage["total_archivos"];
                $tamanoZipStorageBytes = file_exists($rutaZipStorage) ? filesize($rutaZipStorage) : 0;

                $this->notificar($callback, "success", "Archivo Storage generado: {$nombreZipStorage} (".$this->formatearBytes($tamanoZipStorageBytes).", {$archivosStorageCount} archivos)");
            }

            // 3. POLÍTICA DE RETENCIÓN EN EL DESTINO
            if ($retencionDias > 0) {
                $this->notificar($callback, "info", "Evaluando política de retención ({$retencionDias} días) en: {$destino}");
                $archivosPurgados = $this->aplicarPoliticaRetencion($destino, $retencionDias, $callback);
            }

            $duracionSegundos = round(microtime(true) - $inicioTiempo, 2);
            $totalTamanoBytes = $tamanoZipDbBytes + $tamanoZipStorageBytes;

            $resultado = [
                "archivo_db" => $nombreZipDb,
                "ruta_db" => $rutaZipDb,
                "tamano_db_bytes" => $tamanoZipDbBytes,
                "tamano_db_humano" => $this->formatearBytes($tamanoZipDbBytes),
                "archivo_storage" => $nombreZipStorage,
                "ruta_storage" => $rutaZipStorage,
                "tamano_storage_bytes" => $tamanoZipStorageBytes,
                "tamano_storage_humano" => $this->formatearBytes($tamanoZipStorageBytes),
                "archivos_storage_count" => $archivosStorageCount,
                "tamano_total_humano" => $this->formatearBytes($totalTamanoBytes),
                "bd_incluida" => ! $onlyFiles,
                "storage_incluido" => ! $onlyDb,
                "purgados" => $archivosPurgados,
                "duracion_segundos" => $duracionSegundos,
            ];

            Log::channel("backups")->info("Backup completado exitosamente", $resultado);
            $this->notificar($callback, "success", "Proceso de backups finalizado exitosamente en {$duracionSegundos}s.");

            return $this->success($resultado, "Backups generados correctamente por separado.");
        } catch (Throwable $e) {
            Log::channel("backups")->error("Error durante la ejecución del backup: ".$e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);

            $this->notificar($callback, "error", "Fallo crítico en el backup: ".$e->getMessage());

            // Eliminar archivos incompletos si falló
            if ($rutaZipDb && file_exists($rutaZipDb) && filesize($rutaZipDb) === 0) {
                @unlink($rutaZipDb);
            }
            if ($rutaZipStorage && file_exists($rutaZipStorage) && filesize($rutaZipStorage) === 0) {
                @unlink($rutaZipStorage);
            }

            return $this->error("Error al generar el backup: ".$e->getMessage(), 500);
        } finally {
            // Limpieza del volcado SQL temporal
            if ($rutaSqlDump && file_exists($rutaSqlDump)) {
                @unlink($rutaSqlDump);
            }
        }
    }

    /**
     * Genera el volcado de la base de datos MySQL hacia un archivo .sql en el directorio temporal.
     *
     * Flujo del proceso:
     * 1. Resuelve los parámetros de conexión de la BD MySQL configurada.
     * 2. Localiza el binario ejecutable de mysqldump (auto-detección en Laragon o variable).
     * 3. Ejecuta el comando mysqldump protegiendo credenciales y extrayendo esquemas, vistas, triggers y rutinas.
     * 4. Valida la integridad del archivo resultante y su tamaño.
     *
     * @param  string  $tempPath  Directorio temporal donde se escribirá el .sql
     * @param  string  $timestamp  Marca de tiempo para el nombre de archivo
     * @param  callable|null  $callback  Callback opcional de progreso
     * @return string Ruta absoluta del archivo .sql generado
     *
     * @throws Exception Si el ejecutable no se encuentra o el proceso falla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function generarDumpDatabase(string $tempPath, string $timestamp, ?callable $callback = null): string
    {
        $defaultConn = config("database.default", "mysql");
        $dbConfig = config("database.connections.{$defaultConn}", []);

        $host = env("DB_HOST", $dbConfig["host"] ?? "127.0.0.1");
        $port = env("DB_PORT", $dbConfig["port"] ?? "3306");
        $database = env("DB_DATABASE", $dbConfig["database"] ?? "");
        $username = env("DB_USERNAME", $dbConfig["username"] ?? "");
        $password = env("DB_PASSWORD", $dbConfig["password"] ?? "");

        $mysqldumpBin = $this->resolverRutaMysqldump();

        $sqlFilename = "database_{$database}_{$timestamp}.sql";
        $sqlPath = rtrim($tempPath, "\\/").DIRECTORY_SEPARATOR.$sqlFilename;

        // Construir argumentos de mysqldump
        $cmdArgs = [
            escapeshellarg($mysqldumpBin),
            "--host=".escapeshellarg($host),
            "--protocol=tcp",
            "--port=".escapeshellarg((string) $port),
            "--user=".escapeshellarg($username),
            "--single-transaction",
            "--quick",
            "--routines",
            "--triggers",
            "--default-character-set=utf8mb4",
            "--result-file=".escapeshellarg($sqlPath),
            escapeshellarg($database),
        ];

        $command = implode(" ", $cmdArgs);

        // Si hay contraseña, se suministra mediante entorno MYSQL_PWD para no exponerla en proceso
        $env = [];
        foreach ($_SERVER as $k => $v) {
            if (is_scalar($v)) {
                $env[(string) $k] = (string) $v;
            }
        }
        if (! empty($password)) {
            $env["MYSQL_PWD"] = $password;
        }

        $descriptors = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, null, $env);

        if (! is_resource($process)) {
            throw new Exception("No fue posible inicializar el proceso mysqldump.");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $errorMsg = trim($stderr) ?: "Código de salida: {$exitCode}";
            throw new Exception("mysqldump falló al exportar la base de datos: {$errorMsg}");
        }

        if (! file_exists($sqlPath) || filesize($sqlPath) === 0) {
            throw new Exception("El archivo dump SQL generado está vacío o no se creó.");
        }

        return $sqlPath;
    }

    /**
     * Empaqueta el archivo SQL de la base de datos en su propio archivo ZIP independiente.
     *
     * @param  string  $zipPath  Ruta final del archivo .zip de la BD
     * @param  string  $sqlDumpPath  Ruta del archivo .sql generado
     *
     * @throws Exception Si el archivo zip no puede crearse o cerrarse
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function empaquetarDb(string $zipPath, string $sqlDumpPath): void
    {
        $zip = new ZipArchive;
        $abierto = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($abierto !== true) {
            throw new Exception("No se pudo crear el archivo ZIP de la BD en {$zipPath} (Error: {$abierto})");
        }

        $zip->addFile($sqlDumpPath, basename($sqlDumpPath));

        $cerrado = $zip->close();
        if (! $cerrado) {
            throw new Exception("Error al cerrar el archivo ZIP de la BD en {$zipPath}.");
        }

        clearstatcache(true, $zipPath);
    }

    /**
     * Empaqueta las carpetas de storage seleccionadas en un archivo ZIP independiente.
     *
     * Flujo del proceso:
     * 1. Abre el archivo ZipArchive de storage.
     * 2. Itera recursivamente por cada carpeta de storage y añade cada archivo preservando su ruta relativa.
     * 3. Cierra y consolida el archivo ZIP registrando métricas de archivos procesados.
     *
     * @param  string  $zipPath  Ruta final del archivo .zip de storage
     * @param  array  $carpetasStorage  Lista de carpetas de storage
     * @param  callable|null  $callback  Callback opcional de progreso
     * @return array Resumen del empaquetado (total_archivos)
     *
     * @throws Exception Si el archivo zip no puede crearse o cerrarse
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function empaquetarStorage(string $zipPath, array $carpetasStorage, ?callable $callback = null): array
    {
        $zip = new ZipArchive;
        $abierto = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($abierto !== true) {
            throw new Exception("No se pudo crear el archivo ZIP de Storage en {$zipPath} (Error: {$abierto})");
        }

        $totalArchivos = 0;
        $storageBase = storage_path("app");

        foreach ($carpetasStorage as $nombreCarpeta) {
            $carpetaRuta = rtrim($storageBase, "\\/").DIRECTORY_SEPARATOR.$nombreCarpeta;

            if (! is_dir($carpetaRuta)) {
                continue;
            }

            $archivos = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($carpetaRuta, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $archivosCarpeta = 0;
            foreach ($archivos as $archivo) {
                if ($archivo->isFile()) {
                    $rutaAbsoluta = $archivo->getRealPath();
                    $rutaRelativa = $nombreCarpeta."/".substr($rutaAbsoluta, strlen($carpetaRuta) + 1);
                    $rutaRelativaNormalizada = str_replace("\\", "/", $rutaRelativa);

                    $zip->addFile($rutaAbsoluta, $rutaRelativaNormalizada);
                    $totalArchivos++;
                    $archivosCarpeta++;
                }
            }

            $this->notificar($callback, "info", "  - [storage/{$nombreCarpeta}]: {$archivosCarpeta} archivos añadidos.");
        }

        $this->notificar($callback, "info", "Finalizando compresión de storage en disco (Total: {$totalArchivos} archivos)...");

        $cerrado = $zip->close();
        if (! $cerrado) {
            throw new Exception("Error al consolidar y cerrar el archivo ZIP de Storage en {$zipPath}.");
        }

        clearstatcache(true, $zipPath);

        return [
            "total_archivos" => $totalArchivos,
        ];
    }

    /**
     * Aplica la política de retención eliminando los backups más antiguos en el directorio de destino.
     *
     * Flujo del proceso:
     * 1. Escanea el directorio de destino buscando archivos con patrón 'backup_lidiag_*.zip'.
     * 2. Calcula la fecha límite restando N días a la fecha actual.
     * 3. Compara la fecha de modificación de cada archivo contra la fecha límite.
     * 4. Elimina los archivos que sobrepasan el tiempo de retención y registra la acción.
     *
     * @param  string  $directorioDestino  Directorio donde residen los backups
     * @param  int  $diasRetencion  Días máximos de antigüedad permitidos
     * @param  callable|null  $callback  Callback opcional de progreso
     * @return array Lista de nombres de archivos eliminados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function aplicarPoliticaRetencion(string $directorioDestino, int $diasRetencion, ?callable $callback = null): array
    {
        $eliminados = [];
        $tiempoLimite = now()->subDays($diasRetencion)->timestamp;

        $patron = rtrim($directorioDestino, "\\/").DIRECTORY_SEPARATOR."backup_lidiag_*.zip";
        $archivos = glob($patron);

        if (! is_array($archivos)) {
            return $eliminados;
        }

        foreach ($archivos as $archivo) {
            if (! is_file($archivo)) {
                continue;
            }

            $mtime = filemtime($archivo);
            if ($mtime !== false && $mtime < $tiempoLimite) {
                $nombreArchivo = basename($archivo);
                if (@unlink($archivo)) {
                    $eliminados[] = $nombreArchivo;
                    $this->notificar($callback, "comment", "  - Purgado por retención (> {$diasRetencion} días): {$nombreArchivo}");
                    Log::channel("backups")->info("Archivo de backup purgado por política de retención: {$nombreArchivo}");
                }
            }
        }

        return $eliminados;
    }

    /**
     * Localiza la ruta absoluta al ejecutable mysqldump.exe en el entorno de ejecución.
     *
     * @return string Ruta ejecutable de mysqldump
     *
     * @throws Exception Si no se puede localizar el ejecutable
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function resolverRutaMysqldump(): string
    {
        $configPath = config("backup.mysqldump_path");
        if (! empty($configPath) && file_exists($configPath)) {
            return $configPath;
        }

        // Búsqueda en instalaciones de Laragon
        $laragonGlob = glob("C:/laragon/bin/mysql/*/bin/mysqldump.exe");
        if (! empty($laragonGlob)) {
            rsort($laragonGlob);

            return str_replace("/", DIRECTORY_SEPARATOR, $laragonGlob[0]);
        }

        // Búsqueda en PATH del sistema
        $donde = shell_exec("where mysqldump 2>nul");
        if ($donde) {
            $lineas = array_filter(array_map("trim", explode("\n", $donde)));
            if (! empty($lineas)) {
                return $lineas[0];
            }
        }

        throw new Exception("No se encontró el ejecutable mysqldump.exe en Laragon ni en el PATH del sistema. Configura MYSQLDUMP_PATH en tu archivo .env.");
    }

    /**
     * Retorna la lista de nombres de subcarpetas de storage que deben ser respaldadas.
     *
     * @return array Lista de carpetas declaradas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function obtenerCarpetasStorage(): array
    {
        $carpetas = config("backup.storage_folders", []);
        if (empty($carpetas)) {
            $carpetas = [
                "audios",
                "avatars",
                "citas_soportes",
                "files_consentimientos",
                "files_help_desk",
                "firmas_consntimientos",
                "firmas_medicos",
                "img_dicom",
                "lo-debug-profile",
                "logos",
                "plantillas",
                "plantillas_lecturas",
                "public",
                "reporte_lecturas",
                "reporte_lecturas_ecografias",
                "soportes_hc",
            ];
        }

        return $carpetas;
    }

    /**
     * Convierte una cantidad de bytes a una representación legible (B, KB, MB, GB).
     *
     * @param  int  $bytes  Cantidad de bytes
     * @param  int  $precision  Número de decimales
     * @return string Cadena formateada
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function formatearBytes(int $bytes, int $precision = 2): string
    {
        $unidades = ["B", "KB", "MB", "GB", "TB"];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($unidades) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision)." ".$unidades[$pow];
    }

    /**
     * Emite un mensaje de estado al callback si fue provisto.
     *
     * @param  callable|null  $callback  Callback de notificación
     * @param  string  $tipo  Tipo de mensaje ('info', 'success', 'comment', 'error')
     * @param  string  $mensaje  Contenido del mensaje
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function notificar(?callable $callback, string $tipo, string $mensaje): void
    {
        if (is_callable($callback)) {
            $callback($tipo, $mensaje);
        }
    }
}
