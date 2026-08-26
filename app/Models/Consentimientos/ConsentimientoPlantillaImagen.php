<?php

namespace App\Models\Consentimientos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoPlantillaImagen extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_plantilla_imagenes';

    protected $fillable = [
        'plantilla_id',
        'archivo',
        'nombre',
        'orden',
        'es_para_marcar',
    ];

    protected $casts = [
        'orden' => 'integer',
        'es_para_marcar' => 'boolean',
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }
}
