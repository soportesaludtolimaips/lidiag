<?php

namespace App\Models\Estudio;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $estudio_id
 * @property int $producto_id
 * @property int|null $lector_id
 * @property int|null $prioridad_id
 * @property int|null $transcriptor_id
 * @property string|null $cod_cups
 * @property string|null $nom_produc
 * @property string|null $fechor_lectura
 * @property string|null $lectura
 * @property int $activo
 * @property-read Estudio|null $estudio
 * @property-read ConfigProducto|null $producto
 */
class EstudioProducto extends Model
{
    use HasFactory;

    protected $table = 'estudios_productos';

    protected $fillable = ['estudio_id', 'producto_id', 'transcriptor_id', 'cod_cups', 'nom_produc', 'fechor_lectura', 'lectura', 'fechor_trascrito', 'medico_ordena', 'visible'];

    protected $guarded = ['*'];

    protected $casts = [
        'fechor_lectura' => 'datetime',
        'fechor_trascrito' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Estudio al que pertenece el producto.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function estudio(): BelongsTo
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    /**
     * Usuario que transcribió el producto.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function transcriptor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Adendums de lectura del producto.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function adendums(): HasMany
    {
        return $this->hasMany(EstudioProductoAdendum::class);
    }

    /**
     * Configuración del producto (catálogo CUPS).
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
