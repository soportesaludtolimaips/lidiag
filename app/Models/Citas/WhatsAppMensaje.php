<?php

namespace App\Models\Citas;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMensaje extends Model
{
    protected $table = 'whatsapp_mensajes';

    protected $fillable = [
        'whatsapp_conversation_id',
        'role',
        'tipo',
        'contenido',
        'media_url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversacion()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }
}
