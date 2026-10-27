<?php

namespace App\Models\Worklist;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para registro y trazabilidad de órdenes enviadas a la Modality Worklist DICOM.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class WorklistOrden extends Model
{
    use HasFactory;

    protected $table = 'worklist_ordenes';

    protected $fillable = [
        'paciente_id',
        'sede_id',
        'producto_id',
        'accession_no',
        'study_iuid',
        'modality',
        'station_aet',
        'mwl_item_pk',
        'study_pk',
        'estudio_id',
        'estado',
        'adquirido_at',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'paciente_id' => 'integer',
        'sede_id' => 'integer',
        'producto_id' => 'integer',
        'mwl_item_pk' => 'integer',
        'study_pk' => 'integer',
        'estudio_id' => 'integer',
        'usuario_id' => 'integer',
        'adquirido_at' => 'datetime',
    ];

    /**
     * Paciente asociado a la orden de Worklist.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Sede asistencial donde se atiende la orden.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Producto o procedimiento de Rayos X a realizar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    /**
     * Estudio formal registrado en LIDiag cuando la orden es asignada a un radiólogo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function estudio(): BelongsTo
    {
        return $this->belongsTo(Estudio::class, 'estudio_id');
    }

    /**
     * Usuario que registró y admitió la orden en el sistema.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
