<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LimpiarFirmasMedicos extends Command
{
    protected $signature = 'firmas:limpiar
        {--dry-run : Solo contar y listar, no eliminar}
        {--force : Eliminar sin confirmación}';

    protected $description = 'Limpia archivos de firma huérfanos en storage/app/firmas_medicos';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! $force && ! $dryRun && ! $this->confirm('¿Eliminar archivos de firma huérfanos?')) {
            $this->info('Cancelado.');

            return 0;
        }

        $disk = Storage::disk('firmas_medicos');
        $archivosEnDisco = collect($disk->files());

        $this->line('Analizando registros en DB...');
        $pathEnDb = User::whereNotNull('firma')->pluck('firma');

        $nombresEnDb = $pathEnDb->map(fn ($p) => basename($p))->filter()->values();

        $files = $archivosEnDisco;
        $huérfanos = $files->reject(fn ($f) => $nombresEnDb->contains($f));

        $this->line("Archivos en disco: {$files->count()}");
        $this->line("Registros en DB con firma: {$pathEnDb->count()}");
        $this->line("Huérfanos a eliminar: {$huérfanos->count()}");

        if ($huérfanos->isEmpty()) {
            $this->info('No hay archivos huérfanos.');

            return 0;
        }

        $this->line('Calculando espacio...');
        $this->output->progressStart($huérfanos->count());
        $totalBytes = 0;
        foreach ($huérfanos as $f) {
            $totalBytes += $disk->size($f) ?? 0;
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();

        $this->line("Espacio a liberar: " . round($totalBytes / 1024, 2) . " KB");

        if ($dryRun) {
            $this->warn('--- DRY RUN ---');
            foreach ($huérfanos as $f) {
                $this->line("  [huérfano] {$f}");
            }

            return 0;
        }

        $eliminados = 0;
        $errores = 0;
        $this->output->progressStart($huérfanos->count());
        foreach ($huérfanos as $f) {
            if ($disk->delete($f)) {
                $eliminados++;
            } else {
                $errores++;
                $this->error("  Error al eliminar: {$f}");
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();

        $this->info("Eliminados: {$eliminados}");
        if ($errores > 0) {
            $this->warn("Errores: {$errores}");
        }

        return 0;
    }
}
