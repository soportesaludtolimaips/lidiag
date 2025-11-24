<?php

namespace App\Models\General;

use App\Models\Ecografias\Ecografia;
use App\Models\Estudio\Estudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';

    protected $fillable = ['tipo_documento_id', 'num_docu', 'nombres', 'seg_nom', 'pri_ape', 'seg_ape', 'direccion', 'sexo', 'fec_naci', 'tel', 'cel', 'email'];

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }

    public function ecografias()
    {
        return $this->hasMany(Ecografia::class);
    }
}
