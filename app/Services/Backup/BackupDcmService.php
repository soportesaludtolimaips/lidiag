<?php

namespace App\Services\Backup;

use App\Models\Configuracion\ConfigSede;
use App\Services\BaseService;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class BackupDcmService extends BaseService
{
    /**
     * Ejecuta el proceso de backup de bases de datos DCM/PACS de cada sede configurada.
     *
     * Flujo del proceso:
     * 1. Prepara las rutas de destino (C:\Backups_Lidiag\dcm_sedes\) y temporal de trabajo.
     * 2. Consulta todas las sedes activas que tengan configurada IP y Base de Datos DCM (o la sede filtrada).
     * 3. Itera de forma tolerante a fallos: prueba conectividad rápida al puerto MySQL y ejecuta mysqldump remoto.
     * 4. Empaqueta el dump SQL de cada sede en su propio archivo .zip independiente.
     * 5. Aplica la política de retención en la subcarpeta dcm_sedes/ purgando archivos > N días.
     * 6. Registra métricas detalladas en storage/logs/backups.log y retorna el balance general.
     *
     * @param  array  $opciones  Opciones de ejecución ('sede_id', 'destination_path', 'retention_days', 'timeout')
     * @param  callable|null  $callback  Callback opcional de progreso a la consola
     * @return array Resultado estructurado de la operación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function ejecutar(array $opciones = [], ?callable $callback = null): array
    {
        $inicioTiempo = microtime(true);
        $sedeIdFiltro = ! empty($opciones["sede_id"]) ? (int) $opciones["sede_id"] : null;
        $retencionDias = (int) ($opciones["retention_days"] ?? config("backup.dcm_retention_days", 30));
        $destino = (string) ($opciones["destination_path"] ?? config("backup.dcm_destination_path", "C:\\Backups_Lidiag\\dcm_sedes"));
        $timeout = (int) ($opciones["timeout"] ?? config("backup.dcm_timeout_seconds", 5));
        $tempPath = (string) config("backup.temp_path", storage_path("app/backup-temp"));

        $this->notificar($callback, "info", "Iniciando proceso de backup de bases de datos DCM por sede...");
        Log::channel("backups")->info("=== INICIO DE BACKUP DCM POR SEDES ===", [
            "sede_id_filtro" => $sedeIdFiltro,
            "destino" => $destino,
            "retencion_dias" => $retencionDias,
            "timeout_segundos" => $timeout,
        ]);

        // Asegurar que el directorio de destino y temporal existen
        if (! File::exists($destino)) {
            File::makeDirectory($destino, 0755, true, true);
        }
        if (! File::exists($tempPath)) {
            File::makeDirectory($tempPath, 0755, true, true);
        }

        $query = ConfigSede::query()
            ->where("estado", 1)
            ->whereNotNull("ip_dcm")
            ->where("ip_dcm", "!=", "")
            ->whereNotNull("bd_dcm")
            ->where("bd_dcm", "!=", "");

        if ($sedeIdFiltro) {
            $query->where("id", $sedeIdFiltro);
        }

        $sedes = $query->orderBy("id")->get();

        if ($sedes->isEmpty()) {
            $msg = $sedeIdFiltro
                ? "No se encontró la sede activa #{$sedeIdFiltro} con configuración DCM."
                : "No hay sedes activas con configuración de base de datos DCM.";

            $this->notificar($callback, "comment", $msg);

            return $this->error($msg, 404);
        }

        $this->notificar($callback, "info", "Sedes encontradas para respaldo DCM: ".$sedes->count());

        $timestamp = date("Y-m-d_His");
        $detalles = [];
        $exitosas = 0;
        $fallidas = 0;
        $totalBytesGenerados = 0;

        foreach ($sedes as $sede) {
            $nomSede = $sede->nom_sede ?? "Sede-{$sede->id}";
            $this->notificar($callback, "info", "Procesando Sede #{$sede->id} - {$nomSede} ({$sede->ip_dcm}:{$sede->puerto_dcm})...");

            try {
                $resultadoSede = $this->respaldarSede($sede, $destino, $tempPath, $timestamp, $timeout, $callback);
                $detalles[] = $resultadoSede;
                $exitosas++;
                $totalBytesGenerados += $resultadoSede["tamano_bytes"];

                $this->notificar($callback, "success", "  [OK] Sede #{$sede->id} respaldada: {$resultadoSede['archivo']} ({$resultadoSede['tamano_humano']}) en {$resultadoSede['duracion_segundos']}s");
                Log::channel("backups")->info("Sede DCM respaldada con éxito", $resultadoSede);
            } catch (Throwable $e) {
                $fallidas++;
                $errorMsg = $e->getMessage();
                $detalles[] = [
                    "sede_id" => $sede->id,
                    "nom_sede" => $nomSede,
                    "host" => $sede->ip_dcm.":".$sede->puerto_dcm,
                    "bd" => $sede->bd_dcm,
                    "archivo" => "N/A",
                    "tamano_bytes" => 0,
                    "tamano_humano" => "0 B",
                    "duracion_segundos" => 0,
                    "estado" => "FALLIDO",
                    "error" => $errorMsg,
                ];

                $this->notificar($callback, "error", "  [FALLO] Sede #{$sede->id} ({$nomSede}): {$errorMsg}");
                Log::channel("backups")->warning("Fallo al respaldar base de datos DCM de sede #{$sede->id} ({$nomSede}): {$errorMsg}");
            }
        }

        // Aplicar política de retención
        $archivosPurgados = [];
        if ($retencionDias > 0) {
            $this->notificar($callback, "info", "Evaluando política de retención ({$retencionDias} días) en: {$destino}");
            $archivosPurgados = $this->aplicarPoliticaRetencion($destino, $retencionDias, $callback);
        }

        $duracionTotal = round(microtime(true) - $inicioTiempo, 2);

        $resumen = [
            "total_sedes" => $sedes->count(),
            "exitosas" => $exitosas,
            "fallidas" => $fallidas,
            "total_bytes" => $totalBytesGenerados,
            "total_tamano_humano" => $this->formatearBytes($totalBytesGenerados),
            "destino" => $destino,
            "detalles" => $detalles,
            "purgados" => $archivosPurgados,
            "duracion_segundos" => $duracionTotal,
        ];

        Log::channel("backups")->info("=== FIN DE BACKUP DCM POR SEDES ===", $resumen);
        $this->notificar($callback, "success", "Proceso de backups DCM finalizado en {$duracionTotal}s. Exitosas: {$exitosas}, Fallidas: {$fallidas}");

        return $this->success($resumen, "Proceso de backups DCM completado.");
    }

    /**
     * Realiza el volcado y empaquetado de una sede individual.
     *
     * @param  ConfigSede  $sede  Modelo de la sede a respaldar
     * @param  string  $destino  Directorio final de almacenamiento
     * @param  string  $tempPath  Directorio temporal
     * @param  string  $timestamp  Marca de tiempo para el archivo
     * @param  int  $timeout  Timeout de socket para probar conectividad
     * @param  callable|null  $callback  Callback opcional de progreso
     * @return array Metadatos del backup generado
     *
     * @throws Exception Si la sede no responde o mysqldump falla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function respaldarSede(ConfigSede $sede, string $destino, string $tempPath, string $timestamp, int $timeout, ?callable $callback = null): array
    {
        $inicioSede = microtime(true);
        $host = trim($sede->ip_dcm);
        $port = (int) ($sede->puerto_dcm ?: 3306);
        $bd = trim($sede->bd_dcm);
        $user = trim($sede->usuario_dcm ?: "root");
        $pass = (string) $sede->password_dcm;
        $slug = Str::slug($sede->nom_sede ?? "sede_{$sede->id}", "_");

        // 1. Probar conectividad rápida al puerto TCP
        if (! $this->probarConexion($host, $port, $timeout)) {
            throw new Exception("No es posible conectar al puerto TCP {$port} en el host {$host} (Tiempo de espera agotado: {$timeout}s). Posible corte de VPN o sede offline.");
        }

        // 2. Ejecutar mysqldump
        $sqlPath = $this->generarDumpSede($host, $port, $bd, $user, $pass, $sede->id, $tempPath, $timestamp);

        // 3. Empaquetar en archivo .zip independiente
        $nombreZip = "backup_dcm_sede_{$sede->id}_{$slug}_{$timestamp}.zip";
        $rutaZip = rtrim($destino, "\\/").DIRECTORY_SEPARATOR.$nombreZip;

        $this->empaquetarSede($rutaZip, $sqlPath);

        $duracion = round(microtime(true) - $inicioSede, 2);
        $tamanoBytes = file_exists($rutaZip) ? filesize($rutaZip) : 0;

        return [
            "sede_id" => $sede->id,
            "nom_sede" => $sede->nom_sede,
            "host" => "{$host}:{$port}",
            "bd" => $bd,
            "archivo" => $nombreZip,
            "ruta_completa" => $rutaZip,
            "tamano_bytes" => $tamanoBytes,
            "tamano_humano" => $this->formatearBytes($tamanoBytes),
            "duracion_segundos" => $duracion,
            "estado" => "OK",
            "error" => null,
        ];
    }

    /**
     * Prueba si el puerto de una dirección remota responde antes de invocar mysqldump.
     *
     * @param  string  $host  Dirección IP o dominio
     * @param  int  $port  Puerto TCP
     * @param  int  $timeout  Timeout en segundos
     * @return bool True si el puerto está abierto, false en caso contrario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function probarConexion(string $host, int $port, int $timeout = 5): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, (float) $timeout);
        if (is_resource($socket)) {
            fclose($socket);

            return true;
        }

        return false;
    }

    /**
     * Ejecuta mysqldump contra la base de datos remota de la sede.
     *
     * @param  string  $host  Host remoto
     * @param  int  $port  Puerto
     * @param  string  $database  Nombre de la BD
     * @param  string  $username  Usuario
     * @param  string  $password  Contraseña
     * @param  int  $sedeId  ID de la sede
     * @param  string  $tempPath  Directorio temporal
     * @param  string  $timestamp  Marca de tiempo
     * @return string Ruta absoluta del archivo SQL generado
     *
     * @throws Exception Si mysqldump falla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function generarDumpSede(string $host, int $port, string $database, string $username, string $password, int $sedeId, string $tempPath, string $timestamp): string
    {
        $mysqldumpBin = $this->resolverRutaMysqldump();

        $sqlFilename = "database_dcm_sede_{$sedeId}_{$database}_{$timestamp}.sql";
        $sqlPath = rtrim($tempPath, "\\/").DIRECTORY_SEPARATOR.$sqlFilename;

        $cmdArgs = [
            escapeshellarg($mysqldumpBin),
            "--protocol=tcp",
            "--host=".escapeshellarg($host),
            "--port=".escapeshellarg((string) $port),
            "--user=".escapeshellarg($username),
            "--single-transaction",
            "--quick",
            "--routines",
            "--triggers",
            "--column-statistics=0",
            "--result-file=".escapeshellarg($sqlPath),
            escapeshellarg($database),
        ];

        $command = implode(" ", $cmdArgs);

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
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];

        $process = proc_open($command, $descriptors, $pipes, null, $env);

        if (! is_resource($process)) {
            throw new Exception("No fue posible inicializar el proceso mysqldump para la sede #{$sedeId}.");
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $errorMsg = trim($stderr) ?: "Código de salida: {$exitCode}";
            throw new Exception("mysqldump falló al exportar la BD de la sede #{$sedeId}: {$errorMsg}");
        }

        if (! file_exists($sqlPath) || filesize($sqlPath) === 0) {
            throw new Exception("El archivo dump de la sede #{$sedeId} está vacío o no se generó.");
        }

        return $sqlPath;
    }

    /**
     * Empaqueta el archivo SQL en un archivo .zip individual y elimina el .sql temporal.
     *
     * @param  string  $zipPath  Ruta final del archivo .zip
     * @param  string  $sqlDumpPath  Ruta del dump .sql
     *
     * @throws Exception Si el archivo zip no puede crearse o cerrarse
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function empaquetarSede(string $zipPath, string $sqlDumpPath): void
    {
        try {
            $zip = new ZipArchive;
            $abierto = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($abierto !== true) {
                throw new Exception("No se pudo crear el archivo ZIP en {$zipPath} (Error: {$abierto})");
            }

            $zip->addFile($sqlDumpPath, basename($sqlDumpPath));

            $cerrado = $zip->close();
            if (! $cerrado) {
                throw new Exception("Error al cerrar el archivo ZIP en {$zipPath}.");
            }

            clearstatcache(true, $zipPath);
        } finally {
            if (file_exists($sqlDumpPath)) {
                @unlink($sqlDumpPath);
            }
        }
    }

    /**
     * Aplica la política de retención eliminando backups antiguos en el directorio de destino.
     *
     * @param  string  $directorioDestino  Directorio donde residen los backups de sedes
     * @param  int  $diasRetencion  Días máximos permitidos
     * @param  callable|null  $callback  Callback opcional de progreso
     * @return array Nombres de archivos eliminados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function aplicarPoliticaRetencion(string $directorioDestino, int $diasRetencion, ?callable $callback = null): array
    {
        $eliminados = [];
        $tiempoLimite = now()->subDays($diasRetencion)->timestamp;

        $patron = rtrim($directorioDestino, "\\/").DIRECTORY_SEPARATOR."backup_dcm_sede_*.zip";
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
                    $this->notificar($callback, "comment", "  - Purgado DCM por retención (> {$diasRetencion} días): {$nombreArchivo}");
                    Log::channel("backups")->info("Backup DCM de sede purgado por retención: {$nombreArchivo}");
                }
            }
        }

        return $eliminados;
    }

    /**
     * Localiza el binario de mysqldump.
     *
     * @return string Ruta absoluta al ejecutable
     *
     * @throws Exception Si no se encuentra
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

        $laragonGlob = glob("C:/laragon/bin/mysql/*/bin/mysqldump.exe");
        if (! empty($laragonGlob)) {
            rsort($laragonGlob);

            return str_replace("/", DIRECTORY_SEPARATOR, $laragonGlob[0]);
        }

        $donde = shell_exec("where mysqldump 2>nul");
        if ($donde) {
            $lineas = array_filter(array_map("trim", explode("\n", $donde)));
            if (! empty($lineas)) {
                return $lineas[0];
            }
        }

        throw new Exception("No se encontró el ejecutable mysqldump.exe en Laragon ni en el PATH del sistema.");
    }

    /**
     * Formatea bytes a cadena legible.
     *
     * @param  int  $bytes  Cantidad de bytes
     * @param  int  $precision  Decimales
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
