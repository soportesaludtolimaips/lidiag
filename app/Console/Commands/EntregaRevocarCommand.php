<?php

namespace App\Console\Commands;

use App\Services\Entrega\ClientePortalReportes;
use Illuminate\Console\Command;
use Throwable;

/**
 * Revoca los enlaces vigentes de una lectura en el portal (p. ej. se envió a un número equivocado).
 * Revoca TODOS los vigentes, no solo el último. Con --regenerar crea además un enlace nuevo, que se entrega
 * con un reenvío manual.
 *
 * Ojo: un reenvío manual posterior crea un enlace nuevo para el MISMO contacto; si el número o el correo
 * estaba mal, corríjalo antes de reenviar.
 *
 *   php artisan entrega:revocar 2
 *   php artisan entrega:revocar 2 --regenerar
 *
 * El id de la lectura es el que muestra `entrega:estado` ("Portal (lectura N)").
 */
class EntregaRevocarCommand extends Command
{
    /** Tope de vueltas por si el portal siguiera devolviendo enlaces vigentes. */
    private const MAX_ENLACES = 20;

    protected $signature = 'entrega:revocar {lectura_id : Id de la lectura en el portal} {--regenerar : Crea un enlace nuevo y revoca los anteriores}';

    protected $description = 'Revoca el enlace de resultados de una lectura en el portal de pacientes';

    public function handle(ClientePortalReportes $portal): int
    {
        if (! $portal->configurado()) {
            $this->error('El portal no está configurado (URL_API_REPORTES / API_HMAC_SECRET).');

            return self::FAILURE;
        }

        $lecturaId = (int) $this->argument('lectura_id');

        try {
            if ($this->option('regenerar')) {
                $respuesta = $portal->regenerarEnlace($lecturaId, true);
                if (! $respuesta->successful()) {
                    return $this->falla($respuesta->status());
                }
                $this->info('Los enlaces anteriores quedaron revocados y se creó uno nuevo. Use un reenvío manual para entregarlo.');

                return self::SUCCESS;
            }

            $revocados = $this->revocarTodos($portal, $lecturaId);
            if ($revocados === null) {
                return self::FAILURE;
            }

            if ($revocados === 0) {
                $this->warn('La lectura no tiene un enlace vigente: no hay nada que revocar.');
            } else {
                $this->info($revocados === 1
                    ? 'Enlace revocado: el paciente ya no puede abrirlo.'
                    : "Se revocaron {$revocados} enlaces: el paciente ya no puede abrir ninguno.");
            }
        } catch (Throwable $e) {
            $this->error('No se pudo contactar al portal: '.class_basename($e));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Revoca un enlace vigente tras otro hasta que no quede ninguno.
     *
     * @return int|null cuántos revocó; null si el portal respondió con error (ya se mostró)
     */
    private function revocarTodos(ClientePortalReportes $portal, int $lecturaId): ?int
    {
        for ($revocados = 0; $revocados < self::MAX_ENLACES; $revocados++) {
            $estado = $portal->enlace($lecturaId);
            if (! $estado->successful()) {
                $this->falla($estado->status());

                return null;
            }

            $enlace = $estado->json('enlace');
            if (! $enlace) {
                return $revocados;
            }

            $revocado = $portal->revocarEnlace((int) $enlace['id']);
            if (! $revocado->successful()) {
                $this->falla($revocado->status());

                return null;
            }
        }

        $this->warn('Quedan enlaces vigentes: ejecute el comando otra vez.');

        return $revocados;
    }

    private function falla(int $http): int
    {
        $this->error("El portal respondió HTTP {$http}.");

        return self::FAILURE;
    }
}
