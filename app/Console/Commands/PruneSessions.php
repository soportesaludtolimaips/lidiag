<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneSessions extends Command
{
    protected $signature = 'sessions:prune
        {--hours=24 : Eliminar sesiones de invitados con más de N horas de inactividad}
        {--user-hours=168 : Eliminar sesiones de usuarios autenticados con más de N horas de inactividad (default 7 días)}
        {--dry-run : Solo contar, no eliminar}';

    protected $description = 'Limpia sesiones antiguas de la tabla sessions';

    /**
     * Elimina las sesiones antiguas de invitados y usuarios según los umbrales de inactividad.
     *
     * @returns int Código de salida del comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $userHours = (int) $this->option('user-hours');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours)->timestamp;
        $userCutoff = now()->subHours($userHours)->timestamp;

        $total = DB::table('sessions')->count();
        $guestCount = DB::table('sessions')
            ->whereNull('user_id')
            ->where('last_activity', '<', $cutoff)
            ->count();
        $userCount = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '<', $userCutoff)
            ->count();

        $this->info("Sesiones totales: {$total}");
        $this->info("Sesiones de invitados inactivas > {$hours}h: {$guestCount}");
        $this->info("Sesiones de usuarios inactivas > {$userHours}h: {$userCount}");

        if ($dryRun) {
            return 0;
        }

        $deletedGuest = DB::table('sessions')
            ->whereNull('user_id')
            ->where('last_activity', '<', $cutoff)
            ->delete();

        $deletedUser = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '<', $userCutoff)
            ->delete();

        $remaining = DB::table('sessions')->count();

        $this->info("Eliminadas {$deletedGuest} sesiones de invitados + {$deletedUser} de usuarios.");
        $this->info("Sesiones restantes: {$remaining}");

        return 0;
    }
}
