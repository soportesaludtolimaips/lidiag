<?php

namespace App\Models\Contratacion;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Contrato: contrato de prestación de servicios con una sede.
 *
 * Representa la tabla `contratos`. Cada contrato pertenece a una sede
 * (`config_sedes`), tiene vigencia por fechas y agrupa N productos
 * contratados (`contratos_productos`) y N médicos vinculados
 * (`contratos_medicos`).
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class Contrato extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'contratos';

    /**
     * Atributos asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sede_id',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
        'estado',
    ];

    /**
     * Conversiones de tipos para los atributos del modelo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'boolean',
    ];

    /**
     * Relación belongsTo con la sede del contrato (config_sedes).
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo<ConfigSede, Contrato>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Relación hasMany con los productos contratados del contrato.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany<ContratoProducto, Contrato>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function productos(): HasMany
    {
        return $this->hasMany(ContratoProducto::class, 'contrato_id');
    }

    /**
     * Relación hasMany con los médicos vinculados al contrato.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany<ContratoMedico, Contrato>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function medicos(): HasMany
    {
        return $this->hasMany(ContratoMedico::class, 'contrato_id');
    }
}
