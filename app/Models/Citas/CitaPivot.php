<?php

namespace App\Models\Citas;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitaPivot extends Model
{
    use HasFactory;

    protected $table = 'config_servicio_cita_producto';

    protected $fillable = [
        'servicio_cita_id',
        'producto_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function servicio()
    {
        return $this->belongsTo(ConfigServicioCita::class, 'servicio_cita_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
