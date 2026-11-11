<?php

namespace App\Models\HelpDesk;

use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpDeskTickets extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'help_desk_tickets';

    protected $fillable = [
        'folio',
        'titulo',
        'descripcion',
        'categoria_id',
        'prioridad_id',
        'estado_id',
        'quien_registra_id',
        'sedes_id',
        'quien_atiende_id',
        'quien_soluciona_id',
        'descripcion_insidencia',
        'descripcion_solucion',
        'fechor_sol_quien_registr',
        'fechor_sol_quien_soluciona',
        'resolved_at',
        'closed_at',
        'asignado_visto_at',
        'pendiente_de',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'asignado_visto_at' => 'datetime',
        'fechor_sol_quien_registr' => 'datetime',
        'fechor_sol_quien_soluciona' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sede del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sedes_id');
    }

    /**
     * Usuario que registró el ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function solicitante()
    {
        return $this->belongsTo(User::class, 'quien_registra_id');
    }

    /**
     * Usuario asignado al ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function asignado()
    {
        return $this->belongsTo(User::class, 'quien_atiende_id');
    }

    /**
     * Usuario que solucionó el ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function solucionador()
    {
        return $this->belongsTo(User::class, 'quien_soluciona_id');
    }

    /**
     * Categoría del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function categoria()
    {
        return $this->belongsTo(HelpDeskCategoria::class, 'categoria_id');
    }

    /**
     * Prioridad del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function prioridad()
    {
        return $this->belongsTo(HelpDeskPrioridad::class, 'prioridad_id');
    }

    /**
     * Estado del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function estado()
    {
        return $this->belongsTo(HelpDeskEstado::class, 'estado_id');
    }

    /**
     * Historial del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function historial()
    {
        return $this->hasMany(HelpDeskTicketHistorial::class, 'ticket_id')->orderByDesc('id');
    }

    /**
     * Adjuntos del ticket.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function adjuntos()
    {
        return $this->hasMany(HelpDeskAdjunto::class, 'ticket_id')->orderByDesc('id');
    }
}
