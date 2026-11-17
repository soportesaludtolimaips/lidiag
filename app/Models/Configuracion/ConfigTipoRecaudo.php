<?php

namespace App\Models\Configuracion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la configuración de tipos de recaudo y canal de envío.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $aplica_recaudo
 * @property bool $envia_sahi
 * @property string|null $descripcion
 * @property bool $estado
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-17
 */
class ConfigTipoRecaudo extends Model
{
    use HasFactory;

    protected $table = 'config_tipos_recaudo';

    protected $fillable = [
        'codigo',
        'nombre',
        'aplica_recaudo',
        'envia_sahi',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'aplica_recaudo' => 'boolean',
        'envia_sahi' => 'boolean',
        'estado' => 'boolean',
    ];

    /**
     * Scope para consultar únicamente tipos de recaudo activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }
}
