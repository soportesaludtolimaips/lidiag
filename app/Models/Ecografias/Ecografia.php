<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigTipoRecaudo;
use App\Models\General\Paciente;
use App\Models\User;
use App\Models\Worklist\WorklistOrden;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $medico_id
 * @property int $paciente_id
 * @property int $usua_regis_id
 * @property int $admin_salud_id
 * @property int $atencion_id
 * @property string $fec_cita_sahi
 * @property string $fec_regis_cita_sahi
 * @property string $contrato
 * @property string $plan
 * @property string $quien_ordena
 * @property string $estado
 */
class Ecografia extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'medico_id',
        'paciente_id',
        'tipo_recaudo_id',
        'usua_regis_id',
        'admin_salud_id',
        'atencion_id',
        'fec_cita_sahi',
        'fec_regis_cita_sahi',
        'contrato',
        'plan',
        'quien_ordena',
        'estado',
    ];

    /**
     * Relaciones
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Usuario que registró la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function usuarioRegistrador()
    {
        return $this->belongsTo(User::class, 'usua_regis_id');
    }

    /**
     * Administradora de salud de la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function adminSalud()
    {
        return $this->belongsTo(ConfigAdminSalud::class, 'admin_salud_id');
    }

    /**
     * Médico asignado para la lectura.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    /**
     * Tipo de recaudo asociado (Particular, Cortesía, Por Atención).
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function tipoRecaudo()
    {
        return $this->belongsTo(ConfigTipoRecaudo::class, 'tipo_recaudo_id');
    }

    /**
     * Productos de la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function productosEcografias()
    {
        return $this->hasMany(EcografiaProducto::class, 'ecografia_id');
    }

    /**
     * Alias por convención: productos de la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function ecografiaProductos()
    {
        return $this->productosEcografias();
    }

    /**
     * Órdenes de Worklist asociadas a la ecografía.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function worklistOrdenes()
    {
        return $this->hasMany(WorklistOrden::class, 'ecografia_id');
    }
}
