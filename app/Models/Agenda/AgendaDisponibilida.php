<?php

namespace App\Models\Agenda;

use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent que representa una disponibilidad de agenda médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-01-21
 */
class AgendaDisponibilida extends Model
{
    use HasFactory;

    protected $table = 'agenda_disponibilidad';

    protected $fillable = [
        'quien_registro_id',
        'sede_id',
        'servicio_id',
        'total_citas',
        'dura_cita',
        'fecha_hora_ini',
        'fecha_hora_fin',
        'estado',
    ];

    /**
     * Relación con los detalles (slots) de esta disponibilidad.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany relación con AgendaDisponibilidaDetalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function agendaDisponibilidaDetalles()
    {
        return $this->hasMany(AgendaDisponibilidaDetalle::class, 'agenda_disponibilidad_id');
    }

    /**
     * Relación con la sede a la que pertenece la disponibilidad.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigSede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Relación con el servicio de cita asociado a la disponibilidad.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigServicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-21
     */
    public function servicioCita()
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }
}
