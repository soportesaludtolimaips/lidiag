<?php

namespace App\Console\Commands;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoDocumentoAuditoria;
use App\Models\Consentimientos\ConsentimientoDocumentoToken;
use Illuminate\Console\Command;

class ExpirarConsentimientos extends Command
{
    protected $signature = 'consentimientos:expirar
                            {--limit=500 : Máximo de documentos a procesar por corrida}';

    protected $description = 'Marca como EXPIRADO los consentimientos vigentes cuyo reloj (fecha_expiracion) venció sin completarse.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $docs = ConsentimientoDocumento::query()
            ->where('vigente', true)
            ->whereNotNull('fecha_expiracion')
            ->where('fecha_expiracion', '<', now())
            ->whereIn('estado', ['GENERADO', 'PENDIENTE_PACIENTE', 'PENDIENTE_MEDICO'])
            ->limit($limit)
            ->get();

        if ($docs->isEmpty()) {
            $this->info('Sin consentimientos por expirar.');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($docs as $doc) {
            $doc->estado = 'EXPIRADO';
            $doc->save();

            // Invalida tokens de firma aún activos
            ConsentimientoDocumentoToken::query()
                ->where('documento_id', $doc->id)
                ->where('activo', true)
                ->update(['activo' => false]);

            ConsentimientoDocumentoAuditoria::create([
                'documento_id' => $doc->id,
                'usuario_id' => null,
                'accion' => 'expirar',
                'descripcion' => 'Expirado automáticamente por vencimiento de fecha_expiracion',
                'fecha_accion' => now(),
            ]);

            $count++;
        }

        $this->info("Consentimientos expirados: {$count}");

        return self::SUCCESS;
    }
}
