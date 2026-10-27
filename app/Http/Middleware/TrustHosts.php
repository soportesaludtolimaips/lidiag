<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * Flujo del proceso:
     * 1. Confía en subdominios de la URL principal de la aplicación.
     * 2. Confía en orígenes locales (localhost, 127.0.0.1).
     * 3. Confía en dominios de entornos virtuales locales de Laragon (.test, ej. lidiag-update.test, lidiag.test).
     * 4. Confía en direcciones IP privadas de intranet hospitalaria (redes 10.x, 192.168.x, 172.16-31.x).
     * 5. Permite extender hosts confiables dinámicamente mediante variable TRUSTED_HOSTS en .env.
     *
     * @return array<int, string|null>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function hosts()
    {
        $customHosts = array_filter(array_map("trim", explode(",", (string) env("TRUSTED_HOSTS", ""))));

        return array_merge([
            $this->allSubdomainsOfApplicationUrl(),
            "localhost",
            "127\\.0\\.0\\.1",
            "^.*\\.test$",
            "^10\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$",
            "^192\\.168\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$",
            "^172\\.(1[6-9]|2\\d|3[0-1])\\.\\d{1,3}\\.\\d{1,3}(:\\d+)?$",
        ], $customHosts);
    }
}
