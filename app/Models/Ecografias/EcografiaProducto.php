<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use App\Models\Worklist\WorklistOrden;
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
        "atencion_id",
        "ecografia_id",
        "usua_regis_id",
        "medico_id",
        "producto_id",
        "lectura",
        "opinion",
        "fechor_lectura",
        "estado",
        "estado_envio_sahi",
        "fechor_envio_sahi",
        "sahi_esquema_149",
        "sahi_esquema_150",
        "error_envio_sahi",
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

    /**
     * Alias por convención: configuración del producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function configProducto()
    {
        return $this->productos();
    }

    /**
     * Médico asignado a la lectura del producto de ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function medico()
    {
        return $this->belongsTo(User::class, "medico_id");
    }

    /**
     * Orden de Worklist activa o asociada al producto ecográfico.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function worklistOrden()
    {
        return $this->hasOne(WorklistOrden::class, 'ecografia_producto_id');
    }
}
