<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Configuracion\ConfigPrioridad;
use App\Models\Configuracion\ConfigSucursale;
use App\Models\General\Paciente;
use App\Models\User;

class Estudio extends Model
{
    use HasFactory;

    protected $fillable = ['study_pk', 'study_iuid', 'study_id', 'fec_estudio', 'accession_no', 'study_desc', 'paciente_id', 'medico_id', 'quien_registro_id', 'sucursal_id', 'prioridad_id', 'observaciones', 'estado'];

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

    public function sucursal()
    {
        return $this->belongsTo(ConfigSucursale::class);
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
