<?php

namespace App\Models\Contratacion;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoDetalle extends Model
{
    use HasFactory;

    protected $table = 'contrato_detalle';

    protected $fillable = [
        'contrato_id',
        'medico_id',
        'producto_id',
        'valor_contrato',
        'valor_consulta',
        'valor_urgencias',
        'estado',
    ];

    protected $casts = [
        'valor_contrato' => 'decimal:2',
        'valor_consulta' => 'decimal:2',
        'valor_urgencias' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }
}
