<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigWhatsappNumber extends Model
{
    protected $table = 'config_whatsapp_numbers';

    protected $fillable = [
        'sede_id',
        'phone_number_id',
        'numero',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Sede a la que pertenece el número de WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
