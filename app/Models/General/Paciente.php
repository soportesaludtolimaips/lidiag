<?php

namespace App\Models\General;

use App\Models\Estudio\Estudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = ['num_docu', 'nombres', 'direccion', 'sexo', 'fec_naci', 'tel', 'email'];

    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }
}
