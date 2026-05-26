<?php

namespace App\Models\Configuracion;

use App\Models\Citas\CitaPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigServicioCitas extends Model
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
        )->withPivot('estado', 'id')->wherePivot('estado', true);
    }

    public function pivots()
    {
        return $this->hasMany(CitaPivot::class, 'servicio_cita_id');
    }
}
