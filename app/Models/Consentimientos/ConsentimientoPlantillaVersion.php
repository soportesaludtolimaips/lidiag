<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoPlantillaVersion extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_plantilla_versiones';

    protected $fillable = [
        'plantilla_id',
        'version_numero',
        'html',
        'usuario_crea_id',
        'cambios',
        'fecha_version',
    ];

    protected $casts = [
        'version_numero' => 'integer',
        'fecha_version' => 'datetime',
    ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }

    public function usuarioCrea(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_crea_id');
    }
}
