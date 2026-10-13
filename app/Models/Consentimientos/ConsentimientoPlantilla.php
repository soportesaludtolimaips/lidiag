<?php

namespace App\Models\Consentimientos;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsentimientoPlantilla extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_plantillas';

    protected $fillable = [
        'config_modulo_id',
        'producto_id',
        'usuario_crea_id',
        'nombre',
        'html',
        'version',
        'vigente',
        'vigente_key',
    ];

    /**
     * Módulo al que pertenece la plantilla.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function modulo()
    {
        return $this->belongsTo(ConfigModulo::class, 'config_modulo_id');
    }

    /**
     * Producto al que pertenece la plantilla.
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
     * Usuario que creó la plantilla.
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
