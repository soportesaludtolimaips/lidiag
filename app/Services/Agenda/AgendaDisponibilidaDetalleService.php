<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Services\BaseService;
use Carbon\Carbon;

class AgendaDisponibilidaDetalleService extends BaseService
{
    public function all(): array
    {
        return $this->success(AgendaDisponibilidaDetalle::all());
    }

    public function find(int $id): array
    {
        $item = AgendaDisponibilidaDetalle::find($id);

        if (! $item) {
            return $this->notFound('Detalle de disponibilidad no encontrado.');
        }

        return $this->success($item);
    }

    public function create(array $data): array
    {
        $registros = [];
        $inicio = Carbon::parse($data['fecha_hora_inicio']);

        for ($i = 0; $i < $data['total_citas']; $i++) {
            $fin = $inicio->copy()->addMinutes($data['dura_cita']);

            $registro = AgendaDisponibilidaDetalle::create([
                'agenda_disponibilidad_id' => (int) $data['agenda_disponibilidad_id'],
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $fin,
                'estado' => 'Disponible',
            ]);

            $registros[] = $registro;
            $inicio = $fin;
        }

        return $this->created($registros);
    }

    public function delete(int $id): array
    {
        $item = AgendaDisponibilidaDetalle::find($id);

        if (! $item) {
            return $this->notFound('Detalle de disponibilidad no encontrado.');
        }

        $item->delete();

        return $this->deleted('Detalle de disponibilidad eliminado correctamente.');
    }
}
