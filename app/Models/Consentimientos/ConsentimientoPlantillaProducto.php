<?php

namespace App\Models\Consentimientos;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoPlantillaProducto extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_plantilla_productos';

    protected $fillable = [
        'plantilla_id',
        'producto_id',
        'sede_id',
        'requiere_firma_medico',
    ];

    protected $casts = [
        'requiere_firma_medico' => 'boolean',
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }
}
