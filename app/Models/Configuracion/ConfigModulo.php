<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigModulo extends Model
{
    use HasFactory;

    protected $table = 'config_modulos';

    protected $fillable = [
        'modulo',
    ];

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'users_modulos', 'modulo_id', 'user_id');
    }
}
