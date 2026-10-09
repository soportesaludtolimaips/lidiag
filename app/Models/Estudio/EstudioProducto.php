<?php

namespace App\Models\Estudio;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function estudio()
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    /**
     * Usuario que transcribió el producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function transcriptor()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Adendums de lectura del producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function adendums()
    {
        return $this->hasMany(EstudioProductoAdendum::class);
    }

    /**
     * Configuración del producto (catálogo CUPS).
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
