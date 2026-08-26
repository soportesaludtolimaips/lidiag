<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoDocumentoAuditoria extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_documento_auditoria';

    // Registro append-only: no se usan updated_at semánticamente, pero se
    // mantienen timestamps por consistencia con el resto del esquema.
    protected $fillable = [
        'documento_id',
        'usuario_id',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent',
        'descripcion',
        'fecha_accion',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha_accion' => 'datetime',
    ];

    public function documento(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoDocumento::class, 'documento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
