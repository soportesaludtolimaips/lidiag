<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaDisponibilida;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgendaDisponibilidaService extends BaseService
{
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

        if (! empty($filters['servicio_cita_id'])) {
            $query->where('servicio_cita_id', $filters['servicio_cita_id']);
        }

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $ini = Carbon::parse($filters['bus_fehc_ini'])->startOfDay();
            $fin = Carbon::parse($filters['bus_fecha_fin'])->endOfDay();
            $query->whereBetween('fecha_hora_ini', [$ini, $fin]);
        }

        return $this->success($query->limit(1000)->get());
    }

    public function create(array $data): array
    {
        $user = auth()->user();
        if ($user) {
            $sedesUsuario = $user->sedes()->pluck('config_sedes.id')->toArray();
            if (! empty($sedesUsuario) && ! in_array((int) $data['sede_id'], $sedesUsuario, true)) {
                return $this->unauthorized('La sede seleccionada no está asignada a tu usuario.');
            }

            if (! empty($data['servicio_cita_id'])) {
                $serviciosUsuario = $user->serviciosCita()->pluck('config_servicio_citas.id')->toArray();
                if (! empty($serviciosUsuario) && ! in_array((int) $data['servicio_cita_id'], $serviciosUsuario, true)) {
                    return $this->unauthorized('El servicio de cita seleccionado no está asignado a tu usuario.');
                }
            }
        }

        $inicio = Carbon::parse($data['fecha_hora_ini']);
        $totalCitas = (int) $data['total_citas'];
        $dura = (int) $data['dura_cita'];
        $fin = $inicio->copy()->addMinutes($totalCitas * $dura);

        $created = DB::transaction(function () use ($data, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad = AgendaDisponibilida::create([
                'quien_registro_id' => auth()->id(),
                'sede_id' => (int) $data['sede_id'],
                'servicio_cita_id' => ! empty($data['servicio_cita_id']) ? (int) $data['servicio_cita_id'] : null,
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

    public function find(int $id): array
    {
        $disponibilidad = AgendaDisponibilida::with(['sede', 'servicioCita'])->find($id);

        if (! $disponibilidad) {
            return $this->notFound('Disponibilidad no encontrada.');
        }

        return $this->success($disponibilidad);
    }

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

        DB::transaction(function () use ($disponibilidad, $data, $inicio, $fin, $totalCitas, $dura) {
            $disponibilidad->update([
                'sede_id' => (int) $data['sede_id'],
                'servicio_cita_id' => ! empty($data['servicio_cita_id']) ? (int) $data['servicio_cita_id'] : null,
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
