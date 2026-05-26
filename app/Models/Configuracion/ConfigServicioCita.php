<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigServicioCita extends Model
{
    use HasFactory;

    protected $table = 'config_servicio_citas';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function productos()
    {
        return $this->belongsToMany(
            ConfigProducto::class,
            'config_servicio_cita_producto',
            'servicio_cita_id',
            'producto_id'
        )->withPivot('estado')->wherePivot('estado', true);
    }
}
