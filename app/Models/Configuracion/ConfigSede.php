<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSede extends Model
{
    use HasFactory;

    protected $table = 'config_sedes';
    protected $fillable = [
        'nom_sede', 'email_repor', 'ip_dcm', 'bd_dcm', 'puerto_dcm', 'usuario_dcm', 'password_dcm', 'usuario_oviyam', 'password_oviyam', 'url_oviyam', 'tap_oviyam', 'encabezado',
        'pie_pagina', 'logo_1', 'logo_2', 'estado', 'interface_software', 'interface_driver', 'interface_host', 'interface_port', 'interface_database', 'interface_username', 'interface_password'
    ];
}
