<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstudioDiagnostico extends Model
{
    use HasFactory;

    protected $table = 'estudios_diagnosticos';

    protected $fillable  = ['estudio_id', 'cod_diagnos', 'nom_diagnos'];
}
