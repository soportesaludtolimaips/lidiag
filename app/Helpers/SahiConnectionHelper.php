<?php

namespace App\Helpers;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Support\Facades\DB;

class SahiConnectionHelper
{
    /**
     * Configura dinámicamente la conexión 'sqlsrv' para los parámetros de la sede especificada.
     *
     * Flujo del proceso:
     * 1. Verifica si la sede cuenta con interfaz 'SAHI' y si define host o base de datos específica.
     * 2. Si tiene configuración específica, sobreescribe en tiempo de ejecución las claves de database.connections.sqlsrv.
     * 3. Si no tiene configuración específica, mantiene los valores predeterminados de .env.
     * 4. Purga la conexión 'sqlsrv' para forzar a Laravel a reconectar con los parámetros vigentes si cambiaron.
     *
     * @param  ConfigSede|null  $sede  Instancia de la sede a conectar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public static function configureForSede(?ConfigSede $sede): void
    {
        if (! $sede || $sede->interface_software !== 'SAHI') {
            return;
        }

        $currentHost = config('database.connections.sqlsrv.host');
        $currentDb = config('database.connections.sqlsrv.database');

        $targetHost = ! empty($sede->interface_host) ? $sede->interface_host : (string) config('database.connections.sqlsrv.host', 'localhost');
        $targetPort = ! empty($sede->interface_port) ? (int) $sede->interface_port : (int) config('database.connections.sqlsrv.port', 1433);
        $targetDb = ! empty($sede->interface_database) ? $sede->interface_database : (string) config('database.connections.sqlsrv.database', 'forge');
        $targetUser = ! empty($sede->interface_username) ? $sede->interface_username : (string) config('database.connections.sqlsrv.username', 'forge');
        $targetPass = ! empty($sede->interface_password) ? $sede->interface_password : (string) config('database.connections.sqlsrv.password', '');

        if ($currentHost !== $targetHost || $currentDb !== $targetDb) {
            config([
                'database.connections.sqlsrv.host' => $targetHost,
                'database.connections.sqlsrv.port' => $targetPort,
                'database.connections.sqlsrv.database' => $targetDb,
                'database.connections.sqlsrv.username' => $targetUser,
                'database.connections.sqlsrv.password' => $targetPass,
            ]);

            DB::purge('sqlsrv');
        }
    }
}
