<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneSessions extends Command
{
    protected $signature = 'sessions:prune
        {--hours=24 : Eliminar sesiones de invitados con más de N horas de inactividad}
        {--dry-run : Solo contar, no eliminar}';

    protected $description = 'Limpia sesiones de invitados antiguas de la tabla sessions';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subHours($hours)->timestamp;

        if ($dryRun) {
            $count = DB::table('sessions')
                ->whereNull('user_id')
                ->where('last_activity', '<', $cutoff)
                ->count();

            $total = DB::table('sessions')->count();

            $this->info("Sesiones totales: {$total}");
            $this->info("Sesiones de invitados > {$hours}h: {$count}");

            return 0;
        }

        $deleted = DB::table('sessions')
            ->whereNull('user_id')
            ->where('last_activity', '<', $cutoff)
            ->delete();

        $remaining = DB::table('sessions')->count();

        $this->info("Eliminadas {$deleted} sesiones de invitados (inactivas > {$hours}h).");
        $this->info("Sesiones restantes: {$remaining}");

        return 0;
    }
}
