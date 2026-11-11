<?php

namespace App\Models\Estudio;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un intento de entrega del enlace de resultados al paciente por un canal.
 *
 * @property int $id
 * @property int $estudio_producto_id
 * @property string $canal whatsapp | correo
 * @property string|null $destino Destino enmascarado (nunca completo)
 * @property string $estado enviado | fallido | omitido
 * @property string|null $detalle Id del mensaje o motivo
 * @property bool $automatico
 */
class EntregaResultado extends Model
{
    public const CANAL_WHATSAPP = 'whatsapp';

    public const CANAL_CORREO = 'correo';

    public const ENVIADO = 'enviado';

    public const FALLIDO = 'fallido';

    public const OMITIDO = 'omitido';

    protected $table = 'entregas_resultado';

    protected $fillable = [
        'estudio_producto_id',
        'sede_id',
        'lectura_portal_id',
        'canal',
        'destino',
        'estado',
        'detalle',
        'automatico',
        'usuario_id',
    ];

    protected $casts = ['automatico' => 'boolean'];

    public function scopeEnviados(Builder $query): Builder
    {
        return $query->where('estado', self::ENVIADO);
    }
}
