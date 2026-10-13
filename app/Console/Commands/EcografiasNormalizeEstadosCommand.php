<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcografiasNormalizeEstadosCommand extends Command
{
    protected $signature = 'ecografias:normalize-estados
        {--dry-run : Solo muestra cuántos registros ajustaría (no escribe)}
        {--limit=5000 : Máximo de filas a procesar en una corrida}
        {--chunk=500 : Tamaño de chunk para procesar}
    ';

    protected $description = 'Normaliza estados inválidos en ecografias_productos según el estado del padre (ecografias).';

    private const VALID = ['EN PROCESO', 'ANULADO', 'LEIDO'];

    /**
     * Normaliza los estados inválidos o vacíos de ecografias_productos según el estado de su ecografía padre.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $chunk = max(50, (int) $this->option('chunk'));

        // Detecta valores inválidos o vacíos en enum (en ambientes con mysql non-strict pueden quedar '')
        $base = DB::table('ecografias_productos as ep')
            ->join('ecografias as e', 'e.id', '=', 'ep.ecografia_id')
            ->where(function ($q) {
                $q->whereNull('ep.estado')
                    ->orWhere('ep.estado', '=', '')
                    ->orWhereNotIn('ep.estado', self::VALID);
            })
            ->orderBy('ep.id', 'asc')
            ->limit($limit)
            ->select(['ep.id', 'ep.ecografia_id', 'ep.estado as ep_estado', 'e.estado as e_estado']);

        $rows = $base->get();
        $count = $rows->count();

        if ($count === 0) {
            $this->info('No hay estados inválidos en ecografias_productos.');

            return self::SUCCESS;
        }

        $this->info("Encontrados {$count} registros con estado inválido/vacío (limit={$limit}).");

        // Pre-cálculo de target por fila
        $targets = [];
        foreach ($rows as $r) {
            $parent = (string) ($r->e_estado ?? '');
            $target = in_array($parent, self::VALID, true) ? $parent : 'EN PROCESO';
            $targets[(int) $r->id] = $target;
        }

        if ($dryRun) {
            $byTarget = [];
            foreach ($targets as $t) {
                $byTarget[$t] = ($byTarget[$t] ?? 0) + 1;
            }
            foreach ($byTarget as $t => $c) {
                $this->line(" - {$t}: {$c}");
            }
            $this->warn('Dry-run activo: no se realizaron cambios. Ejecuta sin --dry-run para aplicar.');

            return self::SUCCESS;
        }

        $this->info('Aplicando normalización...');

        $updated = 0;
        $ids = array_keys($targets);
        $chunks = array_chunk($ids, $chunk);

        foreach ($chunks as $batch) {
            DB::transaction(function () use ($batch, $targets, &$updated) {
                foreach ($batch as $id) {
                    $target = $targets[$id];
                    $n = DB::table('ecografias_productos')
                        ->where('id', $id)
                        ->update(['estado' => $target]);
                    $updated += (int) $n;
                }
            });
        }

        Log::info('Normalización de estados ecografias_productos ejecutada.', [
            'updated' => $updated,
            'limit' => $limit,
        ]);

        $this->info("Listo. Filas actualizadas: {$updated}.");

        return self::SUCCESS;
    }
}
