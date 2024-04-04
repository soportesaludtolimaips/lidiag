<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigAdminSalud extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'config_admin_salud';

    protected $fillable = ['cod_admin_salud', 'nit_admin_salud', 'nom_admin_salud', 'estado'];
}
