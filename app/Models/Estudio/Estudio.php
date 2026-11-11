<?php

namespace App\Models\Estudio;

use App\Models\Configuracion\ConfigPrioridad;
use App\Models\Configuracion\ConfigSede;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudio extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_pk',
        'study_iuid',
        'study_id',
        'fec_estudio',
        'accession_no',
        'accession_no',
        'study_desc',
        'mods_in_study',
        'email_reportar',
        'paciente_id',
        'medico_id',
        'quien_registro_id',
        'sede_id',
        'prioridad_id',
        'observaciones',
        'motivo_examen',
        'atencion',
        'servicio',
        'eps',
        'contrato',
        'plan',
        'estado',
        'destino_estudio',
        'destino_estudio_id',
    ];

    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Paciente al que pertenece el estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    /**
     * Médico asignado para la lectura.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function medico()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario que registró el estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function quienRegistro()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sede donde se realizó el estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class);
    }

    /**
     * Prioridad del estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function prioridad()
    {
        return $this->belongsTo(ConfigPrioridad::class);
    }

    /**
     * Productos (partes) del estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function productos()
    {
        return $this->hasMany(EstudioProducto::class);
    }

    /**
     * Diagnósticos asociados al estudio.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function diagnosticos()
    {
        return $this->hasMany(EstudioDiagnostico::class);
    }
}
