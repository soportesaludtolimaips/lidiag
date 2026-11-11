<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigSedeWhatsappNumber extends Model
{
    protected $table = 'config_sede_whatsapp_numbers';

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
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
