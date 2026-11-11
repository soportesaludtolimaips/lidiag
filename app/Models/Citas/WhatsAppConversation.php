<?php

namespace App\Models\Citas;

use App\Models\Agenda\AgendaCita;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa una conversación activa de WhatsApp con un paciente.
 *
 * Almacena el estado del flujo conversacional, incluyendo el teléfono del paciente,
 * la cita asociada, la sede, el paso actual del flujo, los datos recopilados y
 * la marca de tiempo del último mensaje intercambiado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
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

    /**
     * Relación inversa con la cita de agenda asociada a esta conversación.
     *
     * @return BelongsTo Relación con AgendaCita.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function cita()
    {
        return $this->belongsTo(AgendaCita::class, 'cita_id');
    }
}
