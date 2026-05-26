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

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sedes_id');
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'quien_registra_id');
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'quien_atiende_id');
    }

    public function solucionador()
    {
        return $this->belongsTo(User::class, 'quien_soluciona_id');
    }

    public function categoria()
    {
        return $this->belongsTo(HelpDeskCategoria::class, 'categoria_id');
    }

    public function prioridad()
    {
        return $this->belongsTo(HelpDeskPrioridad::class, 'prioridad_id');
    }

    public function estado()
    {
        return $this->belongsTo(HelpDeskEstado::class, 'estado_id');
    }

    public function historial()
    {
        return $this->hasMany(HelpDeskTicketHistorial::class, 'ticket_id')->orderByDesc('id');
    }

    public function adjuntos()
    {
        return $this->hasMany(HelpDeskAdjunto::class, 'ticket_id')->orderByDesc('id');
    }
}
