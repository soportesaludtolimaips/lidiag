<?php

namespace App\Models\Contratacion;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'sede_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'boolean',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ContratoDetalle::class, 'contrato_id');
    }
}
