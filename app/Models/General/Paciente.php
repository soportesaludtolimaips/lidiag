<?php

namespace App\Models\General;

use App\Models\Ecografias\Ecografia;
use App\Models\Estudio\Estudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = ['num_docu', 'nombres', 'seg_nom', 'pri_ape', 'seg_ape', 'direccion', 'sexo', 'fec_naci', 'tel', 'cel', 'email'];

    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }

    public function ecografias()
    {
        return $this->hasMany(Ecografia::class);
    }
}
