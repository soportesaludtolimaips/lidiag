<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigProducto;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcografiaProducto extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'ecografias_productos';

    protected $fillable = [
        'atencion_id',
        'ecografia_id',
        'usua_regis_id',
        'producto_id',
        'lectura',
        'opinion',
        'fechor_lectura',
        'estado',
        'estado_envio_sahi',
        'fechor_envio_sahi',
        'sahi_esquema_149',
        'sahi_esquema_150',
        'error_envio_sahi',
    ];

    /**
     * Relaciones
     */
    public function ecografia()
    {
        return $this->belongsTo(Ecografia::class, 'ecografia_id');
    }

    /**
     * Producto asociado a la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function productos()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    /**
     * Alias por compatibilidad: algunos controladores usan `producto`.
     */
    public function producto()
    {
        return $this->productos();
    }
}
