<?php

namespace App\Models\Agenda;

use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicioCitas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaDisponibilida extends Model
{
    use HasFactory;

    protected $table = 'agenda_disponibilidad';

    protected $fillable = [
        'quien_registro_id',
        'sede_id',
        'servicio_cita_id',
        'total_citas',
        'dura_cita',
        'fecha_hora_ini',
        'fecha_hora_fin',
        'estado',
    ];

    public function agendaDisponibilidaDetalles()
    {
        return $this->hasMany(AgendaDisponibilidaDetalle::class, 'agenda_disponibilidad_id');
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function servicioCita()
    {
        return $this->belongsTo(ConfigServicioCitas::class, 'servicio_cita_id');
    }
}
