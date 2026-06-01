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

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
