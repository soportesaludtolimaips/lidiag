<?php

namespace App\Models\Citas;

use Illuminate\Database\Eloquent\Model;

class ConfigCitaRechazoMotivo extends Model
{
    protected $table = 'config_cita_rechazo_motivos';

    protected $fillable = [
        'motivo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
