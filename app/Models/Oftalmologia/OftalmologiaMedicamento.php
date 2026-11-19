<?php

namespace App\Models\Oftalmologia;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las fórmulas y prescripciones de medicamentos oftalmológicos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaMedicamento extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_medicamentos";

    protected $fillable = [
        "consulta_id",
        "producto_id",
        "codigo_medicamento",
        "nombre_medicamento",
        "forma_farmaceutica",
        "dosis",
        "via_administracion",
        "frecuencia_horario",
        "duracion_tratamiento",
        "cantidad_entregar",
        "indicaciones",
        "sahi_formula_id",
        "sahi_formula_det_id",
    ];

    protected $casts = [
        "cantidad_entregar" => "integer",
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(OftalmologiaConsulta::class, "consulta_id");
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, "producto_id");
    }
}
