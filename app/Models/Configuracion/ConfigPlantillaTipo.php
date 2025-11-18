<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPlantillaTipo extends Model
{
    use HasFactory;

    protected $table = 'config_plantillas_tipos'; // Nombre de la tabla

    protected $fillable = [
        //'producto_id',
        'nom_tipo',
        'estado',
    ];

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
