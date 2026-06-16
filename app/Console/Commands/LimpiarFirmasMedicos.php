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

        $this->line('Calculando espacio (puede tomar unos segundos)...');
        $totalBytes = $this->getDirectorySize($disk->path(''));
        $avgBytes = $archivosEnDisco->count() > 0 ? intdiv($totalBytes, $archivosEnDisco->count()) : 0;
        $espacioEst = $huérfanos->count() * $avgBytes;

        $this->line("  Total directorio: " . $this->formatBytes($totalBytes));
        $this->line("  Tamaño promedio por archivo: " . $this->formatBytes($avgBytes));
        $this->line("  Espacio a liberar (estimado): " . $this->formatBytes($espacioEst));

        if ($dryRun) {
            $this->warn('--- DRY RUN ---');
            foreach ($huérfanos as $f) {
                $this->line("  [huérfano] {$f}");
            }

            return 0;
        }

        $eliminados = 0;
        $errores = 0;
        $this->output->write('  Eliminando: ');
        foreach ($huérfanos as $i => $f) {
            if ($disk->delete($f)) {
                $eliminados++;
            } else {
                $errores++;
                $this->error("Error al eliminar: {$f}");
            }
            if (($i + 1) % 1000 === 0 || $i === $huérfanos->count() - 1) {
                $this->output->write(($i + 1) . ' ');
            }
        }
        $this->line('');

        $this->info("Eliminados: {$eliminados}");
        if ($errores > 0) {
            $this->warn("Errores: {$errores}");
        }

        return 0;
    }

    private function getDirectorySize(string $path): int
    {
        $os = PHP_OS_FAMILY;
        if ($os === 'Windows') {
            $bytes = shell_exec("powershell -Command \"(Get-ChildItem -LiteralPath '$path' -Recurse -File | Measure-Object -Property Length -Sum).Sum\"");
        } else {
            $bytes = shell_exec("du -sb " . escapeshellarg($path) . " | cut -f1");
        }

        return $bytes ? (int) trim($bytes) : 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
