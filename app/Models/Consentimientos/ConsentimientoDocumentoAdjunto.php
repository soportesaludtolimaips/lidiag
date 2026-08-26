<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoDocumentoAdjunto extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_documento_adjuntos';

    protected $fillable = [
        'documento_id',
        'usuario_sube_id',
        'nombre_original',
        'archivo',
        'tipo_mime',
        'tamano',
        'categoria',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoDocumento::class, 'documento_id');
    }

    public function usuarioSube(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_sube_id');
    }
}
