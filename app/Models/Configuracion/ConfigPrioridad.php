<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigPrioridad extends Model
{
    use HasFactory;

    protected $fillable = ['nom_priori', 'nivel', 'tiempo', 'stado'];
}
