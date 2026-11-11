<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPlantillaTipo extends Model
{
    use HasFactory;

    protected $table = 'config_plantillas_tipos'; // Nombre de la tabla

    protected $fillable = [
        'producto_id',
        'nom_tipo',
        'estado',
    ];

    /**
     * Producto al que pertenece el tipo de plantilla.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
