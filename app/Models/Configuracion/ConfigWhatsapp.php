<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsapp extends Model
{
    protected $table = 'config_whatsapp';

    protected $fillable = [
        'sede_id',
        'servicio_id',
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
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDeSede($query, $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    public function scopeDeServicio($query, $servicioId)
    {
        return $query->where('servicio_id', $servicioId);
    }

    public function scopeParaSedeYservicio($query, $sedeId, $servicioId)
    {
        return $query->where('activo', true)
            ->where(function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId)->orWhereNull('sede_id');
            })
            ->where(function ($q) use ($servicioId) {
                $q->where('servicio_id', $servicioId)->orWhereNull('servicio_id');
            })
            ->orderByRaw('CASE
                WHEN sede_id IS NOT NULL AND servicio_id IS NOT NULL THEN 0
                WHEN sede_id IS NOT NULL AND servicio_id IS NULL THEN 1
                WHEN sede_id IS NULL AND servicio_id IS NOT NULL THEN 2
                ELSE 3 END')
            ->orderBy('id');
    }
}
