<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoDocumentoToken extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_documento_tokens';

    protected $fillable = [
        'documento_id',
        'token_hash',
        'codigo_qr',
        'usos_maximos',
        'usos_actuales',
        'expires_at',
        'used_at',
        'ip_generacion',
        'ip_uso',
        'activo',
        'generado_por_id',
    ];

    protected $casts = [
        'usos_maximos' => 'integer',
        'usos_actuales' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'activo' => 'boolean',
    ];

    protected $hidden = [
        'token_hash',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoDocumento::class, 'documento_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_id');
    }
}
