<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoDocumentoMarca extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_documento_marcas';

    protected $fillable = [
        'documento_id',
        'imagen_id',
        'usuario_marca_id',
        'tipo_marca',
        'posicion_x',
        'posicion_y',
        'ancho',
        'alto',
        'descripcion',
        'datos_dibujo',
        'fecha_marca',
    ];

    protected $casts = [
        'posicion_x' => 'float',
        'posicion_y' => 'float',
        'ancho' => 'float',
        'alto' => 'float',
        'datos_dibujo' => 'array',
        'fecha_marca' => 'datetime',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoDocumento::class, 'documento_id');
    }

    public function imagen(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantillaImagen::class, 'imagen_id');
    }

    public function usuarioMarca(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_marca_id');
    }
}
