<?php

namespace App\Models\Agenda;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaDisponibilida extends Model
{
    use HasFactory;

    protected $table = 'agenda_disponibilida';

    protected $fillable = [
        'quien_registro_id',
        'servicio_id',
        'dura_cita',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado',
    ];

    public function agendaDisponibilidaDetalles()
    {
        return $this->hasMany(AgendaDisponibilidaDetalle::class, 'agenda_disponibilida_id');
    }
}
