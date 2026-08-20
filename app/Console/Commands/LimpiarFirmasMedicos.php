<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        $dir = $disk->path('');

        $this->line('Cargando firmas referenciadas en DB...');
        $nombresEnDb = User::whereNotNull('firma')
            ->pluck('firma')
            ->map(fn ($p) => basename($p))
            ->filter()
            ->flip();
        $this->line('  Referencias en DB: '.$nombresEnDb->count());

        $this->line('Escaneando archivos en disco...');
        $total = 0;
        $huerfanos = 0;
        $bytesHuerfanos = 0;
        $eliminados = 0;
        $errores = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $total++;

            if (! $nombresEnDb->has($file->getFilename())) {
                $huerfanos++;

                if ($dryRun) {
                    $bytesHuerfanos += $file->getSize();
                    $this->line("  [{$huerfanos}] {$file->getFilename()} (".$this->formatBytes($file->getSize()).")");
                } else {
                    $this->output->write("  [{$huerfanos}] {$file->getFilename()} ... ");
                    if (@unlink($file->getRealPath())) {
                        $eliminados++;
                        $this->output->writeln('OK');
                    } else {
                        $errores++;
                        $this->output->writeln('<error>ERROR</error>');
                    }
                }
            }

            if ($total % 1000 === 0) {
                $this->line("  --- Escaneados: {$total} ---");
            }
        }

        $this->line('');
        $this->line("  Total archivos en disco: {$total}");
        $this->line("  Huérfanos encontrados: {$huerfanos}");

        if ($dryRun) {
            $this->line("  Espacio a liberar: ".$this->formatBytes($bytesHuerfanos));
            $this->newLine();
            $this->warn('--- DRY RUN: no se eliminó ningún archivo ---');
        } else {
            $this->line("  Eliminados: {$eliminados}");
            if ($errores > 0) {
                $this->warn("  Errores: {$errores}");
            }
            if ($eliminados > 0) {
                $this->info('Espacio liberado. Ejecute firmas:limpiar --dry-run para ver el tamaño actual del directorio.');
            }
        }

        return 0;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
