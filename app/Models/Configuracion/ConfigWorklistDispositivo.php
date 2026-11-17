<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para la configuración de dispositivos de flujo de trabajo (Worklist / Modalidades).
 *
 * @property int $id
 * @property int $sede_id
 * @property int $modulo_id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $ae_title
 * @property string|null $modality
 * @property string|null $ip_address
 * @property int|null $puerto
 * @property string|null $ubicacion
 * @property string|null $descripcion
 * @property bool $estado
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-12
 */
class ConfigWorklistDispositivo extends Model
{
    use HasFactory;

    protected $table = 'config_worklist_dispositivos';

    protected $fillable = [
        'sede_id',
        'modulo_id',
        'codigo',
        'nombre',
        'ae_title',
        'modality',
        'ip_address',
        'puerto',
        'ubicacion',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'sede_id' => 'integer',
        'modulo_id' => 'integer',
        'puerto' => 'integer',
        'estado' => 'boolean',
    ];

    /**
     * Sede a la cual pertenece físicamente el dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Módulo del sistema asociado al flujo del dispositivo (config_modulos).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ConfigModulo::class, 'modulo_id');
    }

    /**
     * Scope para filtrar dinámicamente por la sede activa.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function scopePorSede(Builder $query, ?int $sedeId): Builder
    {
        if (! empty($sedeId)) {
            return $query->where('sede_id', $sedeId);
        }

        return $query;
    }

    /**
     * Scope para consultar únicamente dispositivos activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }
}
