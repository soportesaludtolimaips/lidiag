<?php

namespace App\Models\HelpDesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpDeskTicketHistorial extends Model
{
    use HasFactory;

    protected $table = 'help_desk_ticket_historial';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'tipo',
        'estado_anterior_id',
        'estado_nuevo_id',
        'asignado_anterior_id',
        'asignado_nuevo_id',
        'categoria_anterior_id',
        'categoria_nueva_id',
        'prioridad_anterior_id',
        'prioridad_nueva_id',
        'comentario',
    ];

    public function ticket()
    {
        return $this->belongsTo(HelpDeskTickets::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function adjuntos()
    {
        return $this->hasMany(HelpDeskAdjunto::class, 'historial_id')->orderByDesc('id');
    }
}
