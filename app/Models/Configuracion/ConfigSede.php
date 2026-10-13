<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSede extends Model
{
    use HasFactory;

    protected $table = 'config_sedes';

    protected $fillable = [
        'nom_sede',
        'email_repor',
        'ip_dcm',
        'bd_dcm',
        'puerto_dcm',
        'usuario_dcm',
        'password_dcm',
        'usuario_oviyam',
        'password_oviyam',
        'url_oviyam',
        'tap_oviyam',
        'encabezado',
        'pie_pagina',
        'logo_1',
        'logo_2',
        'estado',
        'interface_software',
        'interface_driver',
        'interface_host',
        'interface_port',
        'interface_database',
        'interface_username',
        'interface_password',
        'plantilla_rx',
        'plantilla_ecografia',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    protected $hidden = [
        'password_dcm',
        'password_oviyam',
        'interface_password',
    ];

    /**
     * Servicios de la sede.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function servicios()
    {
        return $this->hasMany(ConfigServicio::class, 'sede_id');
    }

    /**
     * Números de WhatsApp de la sede.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function whatsappNumbers()
    {
        return $this->hasMany(ConfigSedeWhatsappNumber::class, 'sede_id');
    }
}
