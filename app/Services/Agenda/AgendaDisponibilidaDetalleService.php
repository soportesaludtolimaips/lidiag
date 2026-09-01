<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Services\BaseService;
use Carbon\Carbon;

class AgendaDisponibilidaDetalleService extends BaseService
{
    /**
     * Obtiene todos los detalles de disponibilidad registrados.
     *
     * @returns array resultado con la colección de detalles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(AgendaDisponibilidaDetalle::all());
    }

    /**
     * Busca un detalle de disponibilidad por su identificador.
     *
     * @param  int  $id  identificador del detalle.
     *
     * @returns array resultado con el detalle o error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = AgendaDisponibilidaDetalle::find($id);

        if (! $item) {
            return $this->notFound('Detalle de disponibilidad no encontrado.');
        }

        return $this->success($item);
    }

    /**
     * Crea uno o más detalles de disponibilidad de forma secuencial según total_citas y dura_cita.
     *
     * @param  array  $data  datos: agenda_disponibilidad_id, fecha_hora_inicio, total_citas, dura_cita.
     *
     * @returns array resultado con los detalles creados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Elimina un detalle de disponibilidad específico.
     *
     * @param  int  $id  identificador del detalle a eliminar.
     *
     * @returns array resultado de la eliminación o error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
