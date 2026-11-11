<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigServicio extends Model
{
    use HasFactory;

    protected $table = 'config_servicios';

    protected $fillable = [
        'nombre',
        'sede_id',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Sede a la que pertenece el servicio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Usuarios asignados al servicio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'user_servicios', 'servicio_id', 'user_id');
    }

    /**
     * Productos asociados al servicio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function productos()
    {
        return $this->belongsToMany(
            ConfigProducto::class,
            'config_whatsapp_producto_servicio',
            'servicio_id',
            'producto_id'
        )->withPivot('estado')->wherePivot('estado', true);
    }

    /**
     * Limita la consulta a los servicios de una sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function scopeDeSede($query, $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    /**
     * Limita la consulta a los servicios activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }
}
