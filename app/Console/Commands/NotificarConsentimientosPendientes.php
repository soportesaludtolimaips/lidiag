<?php

namespace App\Console\Commands;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\User;
use App\Notifications\Consentimientos\ConsentimientosPendientesNotification;
use Illuminate\Console\Command;

/**
 * Notifica a cada médico que tiene consentimientos pendientes de su firma.
 * Pensado para ejecutarse a diario (recordatorio) desde el scheduler.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-08-29
 */
class NotificarConsentimientosPendientes extends Command
{
    protected $signature = 'consentimientos:notificar-medicos';

    protected $description = 'Notifica a los médicos con consentimientos pendientes de firma.';

    /**
     * Ejecuta el comando: agrupa los pendientes por médico y envía una
     * notificación (canal database) a cada uno.
     *
     * @return int Código de salida.
     */
    public function handle(): int
    {
        $porMedico = ConsentimientoDocumento::query()
            ->where('estado', 'PENDIENTE_MEDICO')
            ->where('vigente', true)
            ->whereNotNull('medico_id')
            ->selectRaw('medico_id, COUNT(*) as total')
            ->groupBy('medico_id')
            ->pluck('total', 'medico_id');

        if ($porMedico->isEmpty()) {
            $this->info('No hay consentimientos pendientes por notificar.');

            return self::SUCCESS;
        }

        $enviadas = 0;
        foreach ($porMedico as $medicoId => $total) {
            $medico = User::find($medicoId);
            if (! $medico) {
                continue;
            }
            $medico->notify(new ConsentimientosPendientesNotification((int) $total));
            $enviadas++;
        }

        $this->info("Notificaciones enviadas: {$enviadas}");

        return self::SUCCESS;
    }
}
