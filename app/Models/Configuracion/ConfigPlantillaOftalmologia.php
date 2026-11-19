<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las plantillas clínicas de Oftalmología en el módulo de Configuración.
 *
 * Flujo del proceso:
 * 1. Permite crear plantillas por médico especialista o institucionales (medico_id = null).
 * 2. Segmenta las plantillas por sección clínica (EXAMEN_FISICO, CONCEPTO_PLAN, PROCEDIMIENTOS, MEDICAMENTOS, ANAMNESIS).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class ConfigPlantillaOftalmologia extends Model
{
    use HasFactory;

    protected $table = "config_plantillas_oftalmologia";

    protected $fillable = [
        "medico_id",
        "sede_id",
        "seccion",
        "titulo",
        "contenido",
        "activo",
    ];

    protected $casts = [
        "activo" => "boolean",
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, "medico_id");
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, "sede_id");
    }
}
