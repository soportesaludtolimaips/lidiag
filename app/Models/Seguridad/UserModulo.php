<?php

namespace App\Models\Seguridad;

use App\Models\Configuracion\ConfigModulo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserModulo extends Model
{
    use HasFactory;

    protected $table = 'users_modulos';

    protected $fillable = [
        'user_id',
        'modulo_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modulo()
    {
        return $this->belongsTo(ConfigModulo::class);
    }
}
