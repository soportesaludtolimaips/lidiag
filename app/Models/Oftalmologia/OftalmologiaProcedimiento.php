<?php

namespace App\Models\Oftalmologia;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para las órdenes de procedimientos y exámenes CUPS de Oftalmología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaProcedimiento extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_procedimientos";

    protected $fillable = [
        "consulta_id",
        "producto_id",
        "cod_cups",
        "nombre_procedimiento",
        "cantidad",
        "justificacion_clinica",
        "observaciones",
        "sahi_proce_orden_id",
        "sahi_proce_detalle_id",
    ];

    protected $casts = [
        "cantidad" => "integer",
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
