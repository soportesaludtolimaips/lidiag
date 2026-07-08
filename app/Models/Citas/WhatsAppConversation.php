<?php

namespace App\Models\Citas;

use App\Models\Agenda\AgendaCita;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'telefono',
        'cita_id',
        'sede_id',
        'paso',
        'datos',
        'ultimo_mensaje',
    ];

    protected $casts = [
        'datos' => 'array',
        'ultimo_mensaje' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(AgendaCita::class, 'cita_id');
    }
}
