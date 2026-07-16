<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicioCitas;
use App\Models\Estudio\Estudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'num_docu',
        'reg_med',
        'name',
        'email',
        'password',
        'imagen_perfil',
        'interface_id',
        'transcrip_remoto',
        'password_changed_at',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'estado' => 'boolean',
        'transcrip_remoto' => 'boolean',
    ];

    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    public function paciente()
    {
        return $this->hasMany(User::class, 'paciente_id');
    }

    public function medico()
    {
        return $this->hasMany(User::class, 'medico_id');
    }

    public function quienRegistro()
    {
        return $this->hasMany(User::class, 'quien_registro_id');
    }

    public function estudios()
    {
        return $this->hasMany(Estudio::class, 'medico_id');
    }

    public function modulos()
    {
        return $this->belongsToMany(ConfigModulo::class, 'users_modulos', 'user_id', 'modulo_id');
    }

    public function sedes()
    {
        return $this->belongsToMany(ConfigSede::class, 'user_sedes', 'user_id', 'sede_id');
    }

    public function serviciosCita()
    {
        return $this->belongsToMany(ConfigServicioCitas::class, 'user_servicio_citas', 'user_id', 'servicio_cita_id');
    }
}
