<?php

namespace App\Models\Agenda;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent que representa un detalle (slot) de disponibilidad de agenda.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-01-21
 */
class AgendaDisponibilidaDetalle extends Model
{
    use HasFactory;

    protected $table = 'agenda_disponibilidad_detalle';

    protected $fillable = [
        'agenda_disponibilidad_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
    ];

    /**
     * Relación con la disponibilidad padre a la que pertenece este detalle.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con AgendaDisponibilida.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function agendaDisponibilida()
    {
        return $this->belongsTo(AgendaDisponibilida::class, 'agenda_disponibilidad_id');
    }
}
