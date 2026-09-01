<?php

namespace App\Models\Citas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa un mensaje individual dentro de una conversación de WhatsApp.
 *
 * Almacena el contenido del mensaje, su tipo (texto, imagen, documento, etc.),
 * el rol (user/system), la URL de medios adjuntos y metadatos adicionales.
 * Cada mensaje pertenece a una conversación específica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-09
 */
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

    /**
     * Relación inversa con la conversación de WhatsApp a la que pertenece este mensaje.
     *
     * @return BelongsTo Relación con WhatsAppConversation.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-09
     */
    public function conversacion()
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }
}
