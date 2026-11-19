<?php

namespace App\Models\Oftalmologia;

use App\Models\Configuracion\ConfigDiagnostico;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para los diagnósticos CIE-10 de la consulta oftalmológica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaDiagnostico extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_diagnosticos";

    protected $fillable = [
        "consulta_id",
        "diagnostico_id",
        "cod_cie10",
        "nombre_diagnostico",
        "es_principal",
        "tipo_diagnostico",
        "lateralidad",
        "observaciones",
    ];

    protected $casts = [
        "es_principal" => "boolean",
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(OftalmologiaConsulta::class, "consulta_id");
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(ConfigDiagnostico::class, "diagnostico_id");
    }
}
