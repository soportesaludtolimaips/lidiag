<?php

namespace App\Models\Oftalmologia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las incapacidades médicas generadas en Oftalmología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaIncapacidad extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_incapacidades";

    protected $fillable = [
        "consulta_id",
        "fec_inicio",
        "fec_fin",
        "dias_incapacidad",
        "tipo_incapacidad",
        "origen_incapacidad",
        "empresa",
        "ocupacion",
        "telefono",
        "es_prorroga",
        "observaciones",
        "sahi_incapacidad_id",
    ];

    protected $casts = [
        "fec_inicio" => "date",
        "fec_fin" => "date",
        "dias_incapacidad" => "integer",
        "es_prorroga" => "boolean",
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(OftalmologiaConsulta::class, "consulta_id");
    }
}
