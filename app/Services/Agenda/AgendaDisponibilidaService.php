<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaDisponibilida;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Services\BaseService;
use Carbon\Carbon;

class AgendaDisponibilidaService extends BaseService
{
    /**
     * Lista las disponibilidades de agenda con filtros por sede, servicio y rango de fechas.
     *
     * @param  array  $filters  filtros: sede_id, servicio_id, bus_fech_ini, bus_fecha_fin.
     *
     * @returns array resultado con las disponibilidades encontradas (máx. 1000 registros).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function list(array $filters): array
    {
        $user = auth()->user();
        $sedesUsuario = $user ? $user->sedes()->pluck('config_sedes.id')->toArray() : [];

        $query = AgendaDisponibilida::query()->with(['sede', 'servicioCita'])->orderByDesc('id');

        if (! empty($sedesUsuario)) {
            $query->whereIn('sede_id', $sedesUsuario);
        }

        if (! empty($filters['sede_id'])) {
            $query->where('sede_id', $filters['sede_id']);
        }

        if (! empty($filters['servicio_id'])) {
            $query->where('servicio_id', $filters['servicio_id']);
        }

        if (! empty($filters['bus_fech_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $ini = Carbon::parse($filters['bus_fech_ini'])->startOfDay();
            $fin = Carbon::parse($filters['bus_fecha_fin'])->endOfDay();
            $query->whereBetween('fecha_hora_ini', [$ini, $fin]);
        }

        return $this->success($query->limit(1000)->get());
    }

    /**
     * Crea una nueva disponibilidad de agenda y genera sus slots de detalle automáticamente.
     *
     * @param  array  $data  datos de la disponibilidad: sede_id, servicio_id, fecha_hora_ini, total_citas, dura_cita.
     *
     * @returns array resultado con la disponibilidad creada y sus detalles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $user = auth()->user();
        if ($user) {
            $sedesUsuario = $user->sedes()->pluck('config_sedes.id')->toArray();
            if (! empty($sedesUsuario) && ! in_array((int) $data['sede_id'], $sedesUsuario, true)) {
                return $this->unauthorized('La sede seleccionada no está asignada a tu usuario.');
            }

            if (! empty($data['servicio_id'])) {
                $serviciosUsuario = $user->serviciosCita()->pluck('config_servicios.id')->toArray();
                if (! empty($serviciosUsuario) && ! in_array((int) $data['servicio_id'], $serviciosUsuario, true)) {
                    return $this->unauthorized('El servicio de cita seleccionado no está asignado a tu usuario.');
                }
            }
        }

        $inicio = Carbon::parse($data['fecha_hora_ini']);
        $totalCitas = (int) $data['total_citas'];
        $dura = (int) $data['dura_cita'];
        $fin = $inicio->copy()->addMinutes($totalCitas * $dura);

        $created = $this->transaction(function () use ($data, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad = AgendaDisponibilida::create([
                'quien_registro_id' => auth()->id(),
                'sede_id' => (int) $data['sede_id'],
                'servicio_id' => ! empty($data['servicio_id']) ? (int) $data['servicio_id'] : null,
                'fecha_hora_ini' => $inicio,
                'fecha_hora_fin' => $fin,
                'total_citas' => $totalCitas,
                'dura_cita' => $dura,
                'estado' => 'Activo',
            ]);

            $slotStart = $inicio->copy();
            for ($i = 0; $i < $totalCitas; $i++) {
                $slotEnd = $slotStart->copy()->addMinutes($dura);
                AgendaDisponibilidaDetalle::create([
                    'agenda_disponibilidad_id' => $disponibilidad->id,
                    'fecha_hora_inicio' => $slotStart,
                    'fecha_hora_fin' => $slotEnd,
                    'estado' => 'Disponible',
                ]);
                $slotStart = $slotEnd;
            }

            return $disponibilidad;
        });

        return $this->created([
            'message' => 'Disponibilidad creada correctamente.',
            'data' => $created->load(['sede', 'servicioCita']),
        ]);
    }

    /**
     * Busca una disponibilidad por su identificador con relaciones cargadas.
     *
     * @param  int  $id  identificador de la disponibilidad.
     *
     * @returns array resultado con la disponibilidad o error si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $disponibilidad = AgendaDisponibilida::with(['sede', 'servicioCita'])->find($id);

        if (! $disponibilidad) {
            return $this->notFound('Disponibilidad no encontrada.');
        }

        return $this->success($disponibilidad);
    }

    /**
     * Actualiza una disponibilidad existente, eliminando y regenerando sus slots de detalle.
     *
     * @param  int  $id  identificador de la disponibilidad a actualizar.
     * @param  array  $data  datos actualizados.
     *
     * @returns array resultado con la disponibilidad actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $disponibilidad = AgendaDisponibilida::find($id);

        if (! $disponibilidad) {
            return $this->notFound('Disponibilidad no encontrada.');
        }

        $inicio = Carbon::parse($data['fecha_hora_ini']);
        $totalCitas = (int) $data['total_citas'];
        $dura = (int) $data['dura_cita'];
        $fin = $inicio->copy()->addMinutes($totalCitas * $dura);

        $this->transaction(function () use ($disponibilidad, $data, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad->update([
                'sede_id' => (int) $data['sede_id'],
                'servicio_id' => ! empty($data['servicio_id']) ? (int) $data['servicio_id'] : null,
                'fecha_hora_ini' => $inicio,
                'fecha_hora_fin' => $fin,
                'total_citas' => $totalCitas,
                'dura_cita' => $dura,
                'estado' => $data['estado'] ?? $disponibilidad->estado,
            ]);

            AgendaDisponibilidaDetalle::where('agenda_disponibilidad_id', $disponibilidad->id)->delete();
            $slotStart = $inicio->copy();
            for ($i = 0; $i < $totalCitas; $i++) {
                $slotEnd = $slotStart->copy()->addMinutes($dura);
                AgendaDisponibilidaDetalle::create([
                    'agenda_disponibilidad_id' => $disponibilidad->id,
                    'fecha_hora_inicio' => $slotStart,
                    'fecha_hora_fin' => $slotEnd,
                    'estado' => 'Disponible',
                ]);
                $slotStart = $slotEnd;
            }
        });

        return $this->updated([
            'message' => 'Disponibilidad actualizada correctamente.',
            'data' => $disponibilidad->fresh()->load(['sede', 'servicioCita']),
        ]);
    }

    /**
     * Elimina una disponibilidad y sus detalles asociados, validando que no tenga slots reservados.
     *
     * @param  int  $id  identificador de la disponibilidad a eliminar.
     *
     * @returns array resultado de la eliminación o error si tiene slots reservados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $disponibilidad = AgendaDisponibilida::find($id);

        if (! $disponibilidad) {
            return $this->notFound('Disponibilidad no encontrada.');
        }

        $reservados = AgendaDisponibilidaDetalle::where('agenda_disponibilidad_id', $id)
            ->where('estado', 'Reservado')
            ->count();

        if ($reservados > 0) {
            return $this->error('No se puede eliminar la disponibilidad porque tiene slots reservados asociados a citas.', 400);
        }

        AgendaDisponibilidaDetalle::where('agenda_disponibilidad_id', $id)->delete();
        $disponibilidad->delete();

        return $this->deleted('Disponibilidad eliminada correctamente.');
    }
}
