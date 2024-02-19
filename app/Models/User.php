<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Estudio\Estudio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
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
        'imagen_perfil'
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
    ];

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
}
