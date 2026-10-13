<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPlantilla extends Model
{
    use HasFactory;

    protected $table = 'config_plantillas';

    protected $fillable = [
        'plantilla_tipo_id',
        'medico_id',
        'nom_plantilla',
        'plantilla',
        'opinion',
        'estado',
    ];

    /**
     * Tipo de plantilla asociada.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function plantillaTipo()
    {
        return $this->belongsTo(ConfigPlantillaTipo::class, 'plantilla_tipo_id');
    }

    /**
     * Médico propietario de la plantilla.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }
}
