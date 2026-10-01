<?php

namespace App\Models\Liquidacion;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de liquidación congelada por lectura.
 *
 * Se crea automáticamente al registrar una lectura (leerEstudio) con la tarifa
 * vigente en ese momento: el valor queda congelado aunque el contrato cambie
 * después. Las lecturas no liquidables se guardan con valor null y su categoría
 * de exclusión, para que el reporte de productividad relacione toda la
 * producción del médico. Anular el estudio marca estado = 0 (no se borra).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-01
 */
class LiquidacionLinea extends Model
{
    protected $table = 'liquidacion_lineas';

    protected $fillable = [
        'estudio_producto_id',
        'estudio_id',
        'sede_id',
        'contrato_id',
        'medico_id',
        'producto_id',
        'clase',
        'valor',
        'exclusion',
        'fecha_lectura',
        'periodo',
        'estado',
    ];

    protected $casts = [
        'fecha_lectura' => 'date:Y-m-d',
        'valor' => 'decimal:2',
    ];

    /**
     * Médico lector al que se atribuye la producción.
     *
     * @return BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * Producto leído.
     *
     * @return BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
