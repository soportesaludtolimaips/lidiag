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

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function medico()
    {
        return $this->belongsTo(User::class);
    }

    public function quienRegistro()
    {
        return $this->belongsTo(User::class);
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class);
    }

    public function prioridad()
    {
        return $this->belongsTo(ConfigPrioridad::class);
    }

    public function productos()
    {
        return $this->hasMany(EstudioProducto::class);
    }

    public function diagnosticos()
    {
        return $this->hasMany(EstudioDiagnostico::class);
    }
}
