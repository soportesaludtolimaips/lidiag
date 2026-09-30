<?php

namespace App\Models\Contratacion;

use App\Models\Configuracion\ConfigProducto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Modelo ContratoMedico: médico vinculado a un contrato con un producto contratado.
 *
 * Representa la tabla `contratos_medicos`. Cada registro asocia un médico
 * (users) con un contrato y con un producto del contrato
 * (`contratos_productos`), definiendo los valores por lectura de estudios
 * de imágenes: contrato, consulta externa y urgencias.
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoMedico extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'contratos_medicos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contrato_id',
        'medico_id',
        'contratos_productos_id',
        'valor_contrato',
        'valor_consulta',
        'valor_urgencias',
        'estado',
        'fecha_ingreso',
        'fecha_retiro',
    ];

    /**
     * Conversiones de tipos para los atributos del modelo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_contrato' => 'decimal:2',
        'valor_consulta' => 'decimal:2',
        'valor_urgencias' => 'decimal:2',
        'estado' => 'boolean',
        'fecha_ingreso' => 'date:Y-m-d',
        'fecha_retiro' => 'date:Y-m-d',
    ];

    /**
     * Relación belongsTo con el contrato al que pertenece el registro.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<Contrato, ContratoMedico>
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
     * Relación belongsTo con el usuario médico vinculado.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ContratoMedico>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * Relación belongsTo con el registro de producto del contrato.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<ContratoProducto, ContratoMedico>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function contratoProducto(): BelongsTo
    {
        return $this->belongsTo(ContratoProducto::class, 'contratos_productos_id');
    }

    /**
     * Relación hasOneThrough para obtener directamente el producto del catálogo,
     * atravesando la tabla `contratos_productos`.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasOneThrough<ConfigProducto, ContratoProducto, ContratoMedico>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function producto(): HasOneThrough
    {
        return $this->hasOneThrough(
            ConfigProducto::class,
            ContratoProducto::class,
            'id',
            'id',
            'contratos_productos_id',
            'producto_id'
        );
    }
}
