<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSede extends Model
{
    use HasFactory;

    protected $fillable = [' nom_sedes', 'ip_dcm', 'bd_dcm', 'usuario_dcm', 'password_dcm', 'usuario_oviyam', 'passwor_oviyam', 'url_oviyam', 'estado'];
}
