<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigRecomendacionProducto extends Model
{
    use HasFactory;

    protected $table = 'config_recomendacion_productos';

    protected $fillable = [
        'producto_id',
        'recomendacion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
