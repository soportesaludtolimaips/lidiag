<?php

namespace App\Models\Agenda;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function agendaDisponibilida()
    {
        return $this->belongsTo(AgendaDisponibilida::class, 'agenda_disponibilidad_id');
    }
}
