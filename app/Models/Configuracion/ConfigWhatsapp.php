<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Model;

class ConfigWhatsapp extends Model
{
    protected $table = 'config_whatsapp';

    protected $fillable = [
        'sede_id',
        'servicio_id',
        'mensaje_bienvenida',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Sede a la que pertenece la configuración.
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
     * Servicio al que pertenece la configuración.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function servicio()
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }

    /**
     * Limita la consulta a las configuraciones activas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Limita la consulta a las configuraciones de una sede.
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
     * Limita la consulta a las configuraciones de un servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function scopeDeServicio($query, $servicioId)
    {
        return $query->where('servicio_id', $servicioId);
    }

    /**
     * Limita la consulta a las configuraciones activas de una sede y servicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function scopeParaSedeYservicio($query, $sedeId, $servicioId)
    {
        return $query->where('activo', true)
            ->where(function ($q) use ($sedeId) {
                $q->where('sede_id', $sedeId)->orWhereNull('sede_id');
            })
            ->where(function ($q) use ($servicioId) {
                $q->where('servicio_id', $servicioId)->orWhereNull('servicio_id');
            })
            ->orderByRaw('CASE
                WHEN sede_id IS NOT NULL AND servicio_id IS NOT NULL THEN 0
                WHEN sede_id IS NOT NULL AND servicio_id IS NULL THEN 1
                WHEN sede_id IS NULL AND servicio_id IS NOT NULL THEN 2
                ELSE 3 END')
            ->orderBy('id');
    }
}
