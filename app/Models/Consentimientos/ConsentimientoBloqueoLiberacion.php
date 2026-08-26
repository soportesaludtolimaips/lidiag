<?php

namespace App\Models\Consentimientos;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentimientoBloqueoLiberacion extends Model
{
    use HasFactory;

    protected $table = 'consentimiento_bloqueo_liberaciones';

    protected $fillable = [
        'medico_id',
        'liberado_por_id',
        'liberado_hasta',
        'motivo',
    ];

    protected $casts = [
        'liberado_hasta' => 'datetime',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function liberadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liberado_por_id');
    }
}
