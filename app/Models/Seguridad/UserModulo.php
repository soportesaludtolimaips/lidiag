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

    /**
     * Usuario asociado al módulo.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Módulo asignado al usuario.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function modulo()
    {
        return $this->belongsTo(ConfigModulo::class);
    }
}
