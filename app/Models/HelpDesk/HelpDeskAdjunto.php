<?php

namespace App\Models\HelpDesk;

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

    public function ticket()
    {
        return $this->belongsTo(HelpDeskTickets::class, 'ticket_id');
    }

    public function historial()
    {
        return $this->belongsTo(HelpDeskTicketHistorial::class, 'historial_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
