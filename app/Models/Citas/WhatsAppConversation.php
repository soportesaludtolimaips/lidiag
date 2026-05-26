<?php

namespace App\Models\Citas;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'telefono',
        'cita_id',
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
        return $this->belongsTo(Cita::class, 'cita_id');
    }
}
