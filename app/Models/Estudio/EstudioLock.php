<?php

namespace App\Models\Estudio;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reserva (lock) activa de lectura o asignación de un estudio.
 *
 * Un registro por (tipo, clave) indica quién tiene abierto el registro
 * y hasta cuándo (expiración automática).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-08
 */
class EstudioLock extends Model
{
    protected $table = 'estudio_locks';

    protected $fillable = [
        'tipo',
        'clave',
        'user_id',
        'hasta',
    ];

    protected $casts = [
        'hasta' => 'datetime',
    ];

    /**
     * Usuario que mantiene la reserva activa.
     *
     * @returns BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
