<?php

namespace App\Models\Contratacion;

use App\Models\Configuracion\ConfigProducto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo ContratoProducto: producto vinculado a un contrato con su valor contratado.
 *
 * Representa la tabla `contratos_productos`. Cada registro asocia un producto
 * del catálogo (`config_productos`) con un contrato y define el valor
 * contratado global para ese producto en la sede del contrato.
 *
 * @property int $id
 * @property int $contrato_id
 * @property int $producto_id
 * @property float|string $valor_contrato
 * @property int $estado
 * @property-read ConfigProducto|null $producto
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoProducto extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'contratos_productos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contrato_id',
        'producto_id',
        'valor_contrato',
        'estado',
    ];

    /**
     * Conversiones de tipos para los atributos del modelo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_contrato' => 'decimal:2',
        'estado' => 'boolean',
    ];

    /**
     * Relación belongsTo con el contrato al que pertenece el producto.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<Contrato, ContratoProducto>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    /**
     * Relación belongsTo con el producto del catálogo (config_productos).
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<ConfigProducto, ContratoProducto>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    /**
     * Relación hasMany con los registros de médicos vinculados a este producto del contrato.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany<ContratoMedico, ContratoProducto>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function medicos(): HasMany
    {
        return $this->hasMany(ContratoMedico::class, 'contratos_productos_id');
    }
}
