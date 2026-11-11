<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfigModulo extends Model
{
    use HasFactory;

    protected $table = 'config_modulos';

    protected $fillable = [
        'modulo',
    ];

    /**
     * Usuarios con el módulo asignado.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'users_modulos', 'modulo_id', 'user_id');
    }

    /**
     * Productos clínicos asociados a este módulo asistencial.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function productos(): HasMany
    {
        return $this->hasMany(ConfigProducto::class, 'modulo_id');
    }
}
