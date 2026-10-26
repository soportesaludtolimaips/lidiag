<?php

namespace App\Models\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmbralNrd extends Model
{
    use HasFactory;

    protected $table = 'medicion_dosis_umbrales_nrd';

    protected $fillable = [
        'sede_id',
        'modalidad',
        'tipo_metrica',
        'valor_alerta',
        'valor_limite_nrd',
        'unidad_medida',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'valor_alerta' => 'float',
        'valor_limite_nrd' => 'float',
        'estado' => 'boolean',
    ];

    /**
     * Sede específica asociada al umbral (opcional, null si es estándar nacional).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
