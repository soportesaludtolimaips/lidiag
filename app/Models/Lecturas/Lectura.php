<?php

namespace App\Models\Lecturas;

use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lectura extends Model
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

    public function quien_registro()
    {
        return $this->belongsTo(User::class);
    }
}
