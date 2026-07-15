<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune
        {--days=90 : Eliminar notificaciones leídas con más de N días de antigüedad}
        {--dry-run : Solo contar, no eliminar}';

    protected $description = 'Limpia notificaciones leídas antiguas de la tabla notifications';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $total = DB::table('notifications')->count();
        $prunable = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("Notificaciones totales: {$total}");
        $this->info("Notificaciones leídas y antiguas (> {$days}d): {$prunable}");

        if ($dryRun || $prunable === 0) {
            return 0;
        }

        $deleted = DB::table('notifications')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $remaining = DB::table('notifications')->count();

        $this->info("Eliminadas: {$deleted}");
        $this->info("Restantes: {$remaining}");

        return 0;
    }
}
