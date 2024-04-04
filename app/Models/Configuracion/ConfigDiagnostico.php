<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigDiagnostico extends Model
{
    use HasFactory;

    protected $fillable = ['cod_diagnos', 'nom_diagnos'];
}
