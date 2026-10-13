<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsappRequerimeinto extends Model
{
    protected $table = 'config_whatsapp_requerimeintos';

    protected $fillable = [
        'sede_id',
        'servicio_id',
        'producto_id',
        'recomendacion',
        'activo',
    ];

    /**
     * Producto del requerimiento de WhatsApp.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
