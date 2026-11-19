<?php

namespace App\Models\Oftalmologia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las remisiones clínicas de Oftalmología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaRemision extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_remisiones";

    protected $fillable = [
        "consulta_id",
        "institucion_receptora",
        "especialidad_solicitada",
        "motivo_remision",
        "resumen_clinico",
        "observaciones",
        "sahi_remision_id",
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(OftalmologiaConsulta::class, "consulta_id");
    }
}
