<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de compatibilidad para números de WhatsApp de sedes.
 *
 * Mapea a la tabla canónica config_whatsapp_numbers.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-11
 */
class ConfigSedeWhatsappNumber extends ConfigWhatsappNumber
{
    protected $table = 'config_whatsapp_numbers';

    /**
     * Sede a la que pertenece el número de WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
