<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantillaTipo extends Model
{
    use HasFactory;

    protected $table = 'plantillas_tipos'; // Nombre de la tabla

    protected $fillable = [
        'producto_id',
        'nom_tipo',
        'estado',
    ];

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
