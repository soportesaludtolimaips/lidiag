<?php

namespace App\Models\Configuracion;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentimientoInformado extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'sede_id',
        'usuario_crea_id',
        'nombre',
        'archivo',
        'estado',
    ];

    /**
     * Producto al que aplica el consentimiento.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    /**
     * Sede a la que pertenece el consentimiento.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Usuario que creó el consentimiento.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function usuarioCrea()
    {
        return $this->belongsTo(User::class, 'usuario_crea_id');
    }
}
