<?php

namespace App\Models\Citas;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa la tabla pivote entre servicios de cita y productos.
 *
 * Almacena la relación N:N entre los servicios de cita configurados y los productos
 * asociados, incluyendo el estado (activo/inactivo) de cada vinculación.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
class CitaPivot extends Model
{
    use HasFactory;

    protected $table = 'config_servicio_producto';

    protected $fillable = [
        'servicio_id',
        'producto_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Relación inversa con el servicio de cita al que pertenece este registro pivote.
     *
     * @return BelongsTo Relación con ConfigServicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function servicio()
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }

    /**
     * Relación inversa con el producto asociado a este registro pivote.
     *
     * @return BelongsTo Relación con ConfigProducto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
