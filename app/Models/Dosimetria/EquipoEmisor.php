<?php

namespace App\Models\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipoEmisor extends Model
{
    use HasFactory;

    protected $table = 'medicion_dosis_equipos_emisores';

    protected $fillable = [
        'sede_id',
        'nombre',
        'modalidad',
        'marca',
        'modelo',
        'aet_title',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Sede asistencial a la que pertenece el equipo emisor de radiación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Registros de dosis de radiación generados por este equipo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function registrosDosis(): HasMany
    {
        return $this->hasMany(RegistroDosisRadiacion::class, 'equipo_emisor_id');
    }
}
