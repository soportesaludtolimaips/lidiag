<?php

namespace App\Models\Dosimetria;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroDosisRadiacion extends Model
{
    use HasFactory;

    protected $table = 'registros_dosis_radiacion';

    protected $fillable = [
        'sede_id',
        'equipo_emisor_id',
        'study_uid',
        'patient_id_hashed',
        'tipo_metrica',
        'valor_dosis',
        'unidad_medida',
        'fecha_estudio',
        'accession_no',
        'study_desc',
    ];

    protected $casts = [
        'valor_dosis' => 'float',
        'fecha_estudio' => 'datetime',
    ];

    /**
     * Sede en la cual se adquirió el estudio radiológico.
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
     * Equipo emisor de radiación que adquirió el estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function equipoEmisor(): BelongsTo
    {
        return $this->belongsTo(EquipoEmisor::class, 'equipo_emisor_id');
    }
}
