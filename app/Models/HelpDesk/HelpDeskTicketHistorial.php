<?php

namespace App\Models\HelpDesk;

use App\Models\User;
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

    /**
     * Ticket al que pertenece el historial.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function ticket()
    {
        return $this->belongsTo(HelpDeskTickets::class, 'ticket_id');
    }

    /**
     * Usuario que generó la entrada del historial.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Adjuntos del historial.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function adjuntos()
    {
        return $this->hasMany(HelpDeskAdjunto::class, 'historial_id')->orderByDesc('id');
    }
}
