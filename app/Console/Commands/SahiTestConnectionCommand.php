<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando Artisan para diagnosticar y auditar la conectividad con el HIS / SAHI (SQL Server).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class SahiTestConnectionCommand extends Command
{
    /**
     * El nombre y la firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'sahi:test {--timeout=5 : Tiempo límite de espera en segundos}';

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Verifica la conectividad, latencia y tablas maestras de la interfaz HIS / SAHI (SQL Server).';

    /**
     * Ejecuta el diagnóstico integral de conectividad con SQL Server.
     *
     * Flujo del proceso:
     * 1. Lee la configuración de conexión 'sqlsrv' de config/database.php.
     * 2. Muestra los parámetros de red (host, puerto, base de datos).
     * 3. Realiza prueba a nivel socket TCP para descartar bloqueos de firewall o caída de red física.
     * 4. Establece conexión PDO y mide el tiempo de respuesta (handshake).
     * 5. Ejecuta consulta de metadatos (SELECT @@VERSION, @@SERVERNAME, GETDATE()).
     * 6. Comprueba acceso de lectura a tablas críticas (admAtencion, admCliente, proProducto, HceEsquema, sisTabla).
     * 7. Imprime reporte visual y retorna código de salida acorde.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  DIAGNÓSTICO DE INTEROPERABILIDAD HIS / SAHI       ');
        $this->info('====================================================');

        $config = config('database.connections.sqlsrv');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 1433);
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');

        $this->table(
            ['Parámetro', 'Valor Configurado'],
            [
                ['Host', $host],
                ['Puerto', (string) $port],
                ['Base de Datos', $database],
                ['Usuario', $username],
                ['Connect Timeout', ($config['connect_timeout'] ?? 5).'s'],
            ]
        );

        // 1. Prueba de Socket TCP
        $this->line('');
        $this->info('1. Probando socket TCP a '.$host.':'.$port.'...');
        $socketStart = microtime(true);
        $socket = @fsockopen($host, $port, $errno, $errstr, 3);
        if (! $socket) {
            $this->error("❌ Fallo en conexión TCP: [{$errno}] {$errstr}");
            $this->warn('Verifique que el servidor 172.16.5.6 esté encendido y que el puerto 1433 no esté bloqueado por firewall.');

            return self::FAILURE;
        }
        fclose($socket);
        $socketMs = round((microtime(true) - $socketStart) * 1000, 2);
        $this->info("✔ Socket TCP abierto exitosamente en {$socketMs} ms.");

        // 2. Handshake PDO SQL Server
        $this->line('');
        $this->info('2. Iniciando autenticación y handshake PDO con SQL Server...');
        try {
            $pdoStart = microtime(true);
            $pdo = DB::connection('sqlsrv')->getPdo();
            $pdoMs = round((microtime(true) - $pdoStart) * 1000, 2);
            $this->info("✔ Autenticación PDO completada en {$pdoMs} ms.");
        } catch (\Throwable $e) {
            $this->error('❌ Error de autenticación/conexión PDO: '.$e->getMessage());

            return self::FAILURE;
        }

        // 3. Consulta de Metadatos
        $this->line('');
        $this->info('3. Consultando metadatos del servidor...');
        try {
            $meta = DB::connection('sqlsrv')->selectOne('SELECT @@SERVERNAME as server_name, @@VERSION as server_version, GETDATE() as server_date');
            if ($meta) {
                $this->table(
                    ['Propiedad', 'Valor'],
                    [
                        ['Nombre del Servidor', (string) ($meta->server_name ?? 'N/A')],
                        ['Fecha del Servidor', (string) ($meta->server_date ?? 'N/A')],
                        ['Versión SQL Server', substr((string) ($meta->server_version ?? 'N/A'), 0, 60).'...'],
                    ]
                );
            }
        } catch (\Throwable $e) {
            $this->warn('⚠ No se pudieron consultar metadatos: '.$e->getMessage());
        }

        // 4. Verificación de Tablas Críticas
        $this->line('');
        $this->info('4. Verificando tablas críticas de interoperabilidad...');
        $tablas = [
            'admAtencion' => 'Atenciones de pacientes',
            'admCliente' => 'Maestro de clientes/pacientes',
            'proProducto' => 'Catálogo de productos y CUPS',
            'HceEsquema' => 'Esquemas de historia clínica',
            'sisTabla' => 'Consecutivos de esquemas y tablas',
        ];

        $filasTablas = [];
        $todasOk = true;

        foreach ($tablas as $tabla => $descripcion) {
            $tQuery = microtime(true);
            try {
                $count = DB::connection('sqlsrv')->table($tabla)->count();
                $msQuery = round((microtime(true) - $tQuery) * 1000, 2);
                $filasTablas[] = [$tabla, $descripcion, number_format($count), "✔ OK ({$msQuery} ms)"];
            } catch (\Throwable $e) {
                $todasOk = false;
                $filasTablas[] = [$tabla, $descripcion, 'N/A', '❌ Falló: '.substr($e->getMessage(), 0, 30)];
            }
        }

        $this->table(['Tabla', 'Propósito', 'Total Registros', 'Estado'], $filasTablas);

        $this->line('');
        if ($todasOk) {
            $this->info('✔ ¡INTEROPERABILIDAD HIS / SAHI OPERATIVA AL 100%!');

            return self::SUCCESS;
        } else {
            $this->warn('⚠ Conexión establecida pero algunas tablas tuvieron problemas de acceso.');

            return self::FAILURE;
        }
    }
}
