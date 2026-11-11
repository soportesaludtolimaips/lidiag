<?php

namespace App\Console\Commands;

use App\Models\Estudio\EntregaResultado;
use App\Services\Entrega\ClientePortalReportes;
use Illuminate\Console\Command;
use Throwable;

/**
 * Muestra a quién y cuándo se le envió el enlace de resultados de un estudio y cómo va en el portal.
 *
 *   php artisan entrega:estado 123
 *
 * Solo muestra destinos enmascarados: el enlace y los datos completos nunca se imprimen.
 */
class EntregaEstadoCommand extends Command
{
    protected $signature = 'entrega:estado {estudio_producto_id : Id del producto del estudio}';

    protected $description = 'Muestra los envíos del enlace de resultados al paciente y el estado del enlace en el portal';

    public function handle(ClientePortalReportes $portal): int
    {
        $entregas = EntregaResultado::where('estudio_producto_id', (int) $this->argument('estudio_producto_id'))
            ->orderBy('id')
            ->get();

        if ($entregas->isEmpty()) {
            $this->warn('Este estudio no tiene envíos registrados.');

            return self::SUCCESS;
        }

        $this->table(
            ['Canal', 'Estado', 'Destino', 'Detalle', 'Cuándo', 'Origen'],
            $entregas->map(fn (EntregaResultado $e) => [
                $e->canal,
                $e->estado,
                $e->destino ?? '—',
                $e->detalle ?? '—',
                $e->created_at?->format('Y-m-d H:i'),
                $e->automatico ? 'automático' : 'manual (usuario '.($e->usuario_id ?? '?').')',
            ])->all()
        );

        $lecturaId = (int) $entregas->last()->lectura_portal_id;
        if ($lecturaId === 0 || ! $portal->configurado()) {
            return self::SUCCESS;
        }

        try {
            $respuesta = $portal->enlace($lecturaId);
        } catch (Throwable $e) {
            $this->warn('No se pudo consultar el portal: '.class_basename($e));

            return self::SUCCESS;
        }

        if (! $respuesta->successful()) {
            $this->warn('No se pudo consultar el portal (HTTP '.$respuesta->status().').');

            return self::SUCCESS;
        }

        $enlace = $respuesta->json('enlace');
        $this->line('Portal (lectura '.$lecturaId.'): imágenes '.($respuesta->json('imagenes_estado') ?? '—'));
        $this->line($enlace
            ? 'Enlace vigente hasta '.$enlace['expira_en'].' · vistas: '.$enlace['vistas'].' · última vista: '.($enlace['ultima_vista_en'] ?? 'nunca')
            : 'Sin enlace vigente (venció o fue revocado).');

        return self::SUCCESS;
    }
}
