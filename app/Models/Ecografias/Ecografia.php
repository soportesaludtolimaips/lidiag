<?php

namespace App\Models\Ecografias;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\General\Paciente;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ecografia extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'medico_id',
        'paciente_id',
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
}
