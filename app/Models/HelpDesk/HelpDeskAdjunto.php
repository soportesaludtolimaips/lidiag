<?php

namespace App\Models\HelpDesk;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpDeskAdjunto extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'help_desk_adjuntos';

    protected $fillable = [
        'historial_id',
        'ticket_id',
        'user_id',
        'original_name',
        'stored_name',
        'path',
        'mime',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Ticket al que pertenece el adjunto.
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
     * Historial al que pertenece el adjunto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function historial()
    {
        return $this->belongsTo(HelpDeskTicketHistorial::class, 'historial_id');
    }

    /**
     * Usuario que subió el adjunto.
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
}
