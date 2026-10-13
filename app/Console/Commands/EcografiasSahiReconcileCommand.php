<?php

namespace App\Console\Commands;

use App\Models\Ecografias\EcografiaProducto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcografiasSahiReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Uso:
     * - php artisan ecografias:sahi-reconcile --minutes=10 --limit=200
     */
    protected $signature = 'ecografias:sahi-reconcile {--minutes=10 : Minutos mínimos en estado ENVIANDO} {--limit=200 : Máximo de ecografías_productos a procesar por corrida}';

    /**
     * The console command description.
     */
    protected $description = 'Reconciliar envíos SAHI atascados (ENVIANDO) verificando si ya existen en SAHI y marcando ENVIADO/ERROR.';

    /**
     * Ejecuta la reconciliación de envíos SAHI atascados en estado ENVIANDO.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $limit = max(1, (int) $this->option('limit'));

        $cutoff = now()->subMinutes($minutes);

        $stuck = EcografiaProducto::query()
            ->select(['id', 'ecografia_id', 'producto_id', 'estado_envio_sahi', 'fechor_envio_sahi'])
            ->where('estado_envio_sahi', '=', 'ENVIANDO')
            ->whereNotNull('fechor_envio_sahi')
            ->where('fechor_envio_sahi', '<=', $cutoff)
            ->orderBy('fechor_envio_sahi', 'asc')
            ->limit($limit)
            ->get();

        if ($stuck->isEmpty()) {
            $this->info("No hay registros ENVIANDO con más de {$minutes} min.");

            return self::SUCCESS;
        }

        $this->info("Procesando {$stuck->count()} ecografias_productos ENVIANDO (cutoff: {$cutoff}).");

        $updatedSent = 0;
        $updatedError = 0;

        foreach ($stuck as $ep) {
            try {
                // Resolver atención desde ecografias (MySQL)
                $atencionId = DB::table('ecografias')->where('id', $ep->ecografia_id)->value('atencion_id');
                if (! $atencionId) {
                    $this->markError($ep->id, 'No se pudo resolver atencion_id para reconciliar SAHI.');
                    $updatedError++;

                    continue;
                }

                if (! $ep->producto_id) {
                    $this->markError($ep->id, 'No se pudo resolver producto_id para reconciliar SAHI.');
                    $updatedError++;

                    continue;
                }

                // Verificar si ya existe el esquema 150 para (atención, producto) en SAHI
                $existing = $this->findExistingEsquema150ForAtencionProducto((int) $atencionId, (int) $ep->producto_id);

                if ($existing) {
                    DB::transaction(function () use ($ep, $existing) {
                        EcografiaProducto::where('id', $ep->id)
                            ->where('estado_envio_sahi', 'ENVIANDO')
                            ->update([
                                'estado_envio_sahi' => 'ENVIADO',
                                // no sobreescribir si ya tiene timestamp
                                'fechor_envio_sahi' => DB::raw('COALESCE(fechor_envio_sahi, NOW())'),
                                'sahi_esquema_149' => $existing['IdEsquemaEncabezado149'],
                                'sahi_esquema_150' => $existing['IdEsquemaEncabezado150'],
                                'error_envio_sahi' => null,
                            ]);
                    });
                    $updatedSent++;
                } else {
                    $this->markError($ep->id, 'No se encontró evidencia en SAHI para (atención, producto) tras ENVIANDO.');
                    $updatedError++;
                }
            } catch (\Throwable $e) {
                Log::error('Error en ecografias:sahi-reconcile.', [
                    'ecografia_producto_id' => $ep->id,
                    'exception' => $e->getMessage(),
                ]);
                $this->markError($ep->id, 'Error reconciliando SAHI: '.$e->getMessage());
                $updatedError++;
            }
        }

        $this->info("Listo. Marcados ENVIADO: {$updatedSent}. Marcados ERROR: {$updatedError}.");

        return self::SUCCESS;
    }

    /**
     * Replica el check de idempotencia real: existe esquema 150 para (atención, producto).
     *
     * @return array{IdEsquemaEncabezado149:int,IdEsquemaEncabezado150:int}|null
     */
    private function findExistingEsquema150ForAtencionProducto(int $atencionId, int $productoId): ?array
    {
        $row = DB::connection('sqlsrv')
            ->table('hceEsquemaEncabeza as e150')
            ->join('hceEsquemaDetProc as d', 'd.IdEsquemaEncabezado', '=', 'e150.IdEsquemaEncabezado')
            ->where('e150.IdEsquema', 150)
            ->where('e150.idAtencion', $atencionId)
            ->where('d.IdDetalleValor', 1)
            ->where('d.ValNroRegistro', 1)
            ->where('d.ValVarChar1', (string) $productoId)
            ->orderByDesc('e150.IdEsquemaEncabezado')
            ->first([
                'e150.IdEsquemaEncabezado as IdEsquemaEncabezado150',
                'e150.idEncabezadoPadre as IdEsquemaEncabezado149',
            ]);

        if (! $row) {
            return null;
        }

        $id149 = (int) ($row->IdEsquemaEncabezado149 ?? 0);
        $id150 = (int) ($row->IdEsquemaEncabezado150 ?? 0);
        if ($id149 <= 0 || $id150 <= 0) {
            return null;
        }

        $parentOk = DB::connection('sqlsrv')
            ->table('hceEsquemaEncabeza')
            ->where('IdEsquemaEncabezado', $id149)
            ->where('IdEsquema', 149)
            ->exists();

        if (! $parentOk) {
            return null;
        }

        return [
            'IdEsquemaEncabezado149' => $id149,
            'IdEsquemaEncabezado150' => $id150,
        ];
    }

    /**
     * Marca un producto de ecografía como ERROR de envío a SAHI con el mensaje indicado.
     *
     * @param  int  $ecografiaProductoId  Identificador del producto de ecografía.
     * @param  string  $message  Mensaje de error a registrar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function markError(int $ecografiaProductoId, string $message): void
    {
        try {
            DB::transaction(function () use ($ecografiaProductoId, $message) {
                EcografiaProducto::where('id', $ecografiaProductoId)
                    ->where('estado_envio_sahi', 'ENVIANDO')
                    ->update([
                        'estado_envio_sahi' => 'ERROR',
                        'fechor_envio_sahi' => DB::raw('COALESCE(fechor_envio_sahi, NOW())'),
                        'error_envio_sahi' => $message,
                    ]);
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo marcar ERROR en reconcile SAHI.', [
                'ecografia_producto_id' => $ecografiaProductoId,
                'message' => $message,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
