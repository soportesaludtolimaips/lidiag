<?php

namespace App\Models\Configuracion;

use App\Models\Dosimetria\EquipoEmisor;
use App\Models\Dosimetria\RegistroDosisRadiacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nom_sede
 * @property string|null $email_repor
 * @property string|null $ip_dcm
 * @property string|null $bd_dcm
 * @property int|string|null $puerto_dcm
 * @property string|null $usuario_dcm
 * @property string|null $password_dcm
 * @property string|null $usuario_oviyam
 * @property string|null $password_oviyam
 * @property string|null $url_oviyam
 * @property string|null $tap_oviyam
 * @property string|null $encabezado
 * @property string|null $pie_pagina
 * @property string|null $logo_1
 * @property string|null $logo_2
 * @property bool $estado
 * @property string|null $interface_software
 * @property string|null $interface_driver
 * @property string|null $interface_host
 * @property int|string|null $interface_port
 * @property string|null $interface_database
 * @property string|null $interface_username
 * @property string|null $interface_password
 * @property string|null $plantilla_rx
 * @property string|null $plantilla_ecografia
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ConfigServicio> $servicios
 * @property-read Collection<int, ConfigSedeWhatsappNumber> $whatsappNumbers
 */
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
    public function servicios(): HasMany
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
    public function whatsappNumbers(): HasMany
    {
        return $this->hasMany(ConfigSedeWhatsappNumber::class, 'sede_id');
    }

    /**
     * Equipos emisores de radiación ionizante vinculados a la sede.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function equiposEmisores(): HasMany
    {
        return $this->hasMany(EquipoEmisor::class, 'sede_id');
    }

    /**
     * Registros de dosis de radiación recibidos desde el agente Edge de la sede.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function registrosDosis(): HasMany
    {
        return $this->hasMany(RegistroDosisRadiacion::class, 'sede_id');
    }
}
