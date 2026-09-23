<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsappRecomendaciones extends Model
{
    protected $table = 'config_whatsapp_recomendaciones';

    protected $fillable = [
        'sede_id',
        'servicio_id',
        'producto_id',
        'recomendacion',
        'activo',
    ];

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
