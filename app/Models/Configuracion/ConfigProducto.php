<?php

namespace App\Models\Configuracion;

use App\Models\Estudio\EstudioProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigProducto extends Model
{
    use HasFactory;

    protected $fillable = ['cod_cups', 'nom_produc', 'requi_lectura', 'tipo_oral', 'tipo_venoso', 'factura', 'estado', 'servicio_id', 'modulo_id'];

    protected $casts = [
        'requi_lectura' => 'boolean',
        'tipo_oral' => 'boolean',
        'tipo_venoso' => 'boolean',
        'factura' => 'boolean',
        'estado' => 'boolean',
    ];

    /**
     * Consentimientos informados del producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function consentimientoInformado()
    {
        return $this->hasMany(ConsentimientoInformado::class);
    }

    /**
     * Registros de estudio asociados al producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function estudioProductos()
    {
        return $this->hasMany(EstudioProducto::class, 'producto_id');
    }

    /**
     * Recomendación asociada al producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function recomendacionProducto()
    {
        return $this->hasOne(ConfigRecomendacionProducto::class, 'producto_id');
    }

    /**
     * Módulo asistencial al que pertenece el producto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ConfigModulo::class, 'modulo_id');
    }

    /**
     * Servicio asistencial de citas al que pertenece el producto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }
}
