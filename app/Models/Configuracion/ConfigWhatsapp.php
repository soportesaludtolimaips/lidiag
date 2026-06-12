<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsapp extends Model
{
    protected $table = 'config_whatsapp';

    protected $fillable = [
        'sede_id',
        'servicio_cita_id',
        'mensaje_bienvenida',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ConfigServicioCita::class, 'servicio_cita_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeSede($query, $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    public function scopeDeServicio($query, $servicioCitaId)
    {
        return $query->where('servicio_cita_id', $servicioCitaId);
    }
}
