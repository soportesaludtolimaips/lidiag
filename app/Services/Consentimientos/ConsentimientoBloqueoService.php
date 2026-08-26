<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoBloqueoLiberacion;
use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\User;
use App\Services\BaseService;

class ConsentimientoBloqueoService extends BaseService
{
    /**
     * ¿El médico está bloqueado para lecturas?
     * Bloqueado = tiene ≥1 consentimiento PENDIENTE_MEDICO vigente y no cuenta
     * con una liberación temporal aún vigente.
     */
    public function estaBloqueado(int $medicoId): bool
    {
        if ($this->contarPendientes($medicoId) === 0) {
            return false;
        }

        return ! $this->tieneLiberacionVigente($medicoId);
    }

    public function contarPendientes(int $medicoId): int
    {
        return ConsentimientoDocumento::query()
            ->where('medico_id', $medicoId)
            ->where('estado', 'PENDIENTE_MEDICO')
            ->where('vigente', true)
            ->count();
    }

    public function tieneLiberacionVigente(int $medicoId): bool
    {
        return ConsentimientoBloqueoLiberacion::query()
            ->where('medico_id', $medicoId)
            ->where('liberado_hasta', '>', now())
            ->exists();
    }

    public function estadoPara(User $medico): array
    {
        $pendientes = ConsentimientoDocumento::query()
            ->where('medico_id', $medico->id)
            ->where('estado', 'PENDIENTE_MEDICO')
            ->where('vigente', true)
            ->with('producto:id,cod_cups,nom_produc')
            ->orderByDesc('id')
            ->get(['id', 'producto_id', 'num_docu', 'nombres', 'apellidos', 'created_at']);

        $liberacion = ConsentimientoBloqueoLiberacion::query()
            ->where('medico_id', $medico->id)
            ->where('liberado_hasta', '>', now())
            ->orderByDesc('liberado_hasta')
            ->first();

        return $this->success([
            'bloqueado' => $pendientes->count() > 0 && ! $liberacion,
            'pendientes' => $pendientes->count(),
            'liberado_hasta' => $liberacion?->liberado_hasta,
            'documentos' => $pendientes,
        ]);
    }

    public function liberar(int $medicoId, array $data, User $admin): array
    {
        $medico = User::query()->find($medicoId);
        if (! $medico) {
            return $this->notFound('Médico no encontrado');
        }

        $horas = ! empty($data['horas']) ? (int) $data['horas'] : 24;

        $liberacion = ConsentimientoBloqueoLiberacion::create([
            'medico_id' => $medicoId,
            'liberado_por_id' => (int) $admin->id,
            'liberado_hasta' => now()->addHours($horas),
            'motivo' => $data['motivo'] ?? null,
        ]);

        return $this->created([
            'liberado_hasta' => $liberacion->liberado_hasta,
            'medico_id' => $medicoId,
        ], 'Bloqueo liberado temporalmente');
    }
}
