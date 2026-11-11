<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigServicioCita extends Model
{
    use HasFactory;

    protected $table = 'config_servicio_citas';

    protected $fillable = [
        'nombre',
        'sede_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'user_servicio_citas', 'servicio_cita_id', 'user_id');
    }

    public function productos()
    {
        return $this->belongsToMany(
            ConfigProducto::class,
            'config_servicio_cita_producto',
            'servicio_cita_id',
            'producto_id'
        )->withPivot('estado')->wherePivot('estado', true);
    }

    public function scopeDeSede($query, $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }
}
