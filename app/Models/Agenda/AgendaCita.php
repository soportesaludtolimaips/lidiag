<?php

namespace App\Models\Agenda;

use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicio;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent que representa una cita de agenda médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2024-04-04
 */
class AgendaCita extends Model
{
    use HasFactory;

    protected $table = 'agenda_citas';

    protected $fillable = [
        'quien_confirma_id',
        'paciente_id',
        'sede_id',
        'agenda_disponibilidad_detalle_id',
        'servicio_id',
        'producto_id',
        'fecha_hora_inicio',
        'fecha_hora_ini',
        'fecha_hora_fin',
        'duracion_minutos',
        'total_citas',
        'dura_cita',
        'estado',
        'numero_orden',
        'numero_autorizacion',
        'ruta_orden_media',
        'ruta_cedula_frente_media',
        'ruta_cedula_dorso_media',
        'ruta_autorizacion_media',
        'observaciones',
        'whatsapp_mid',
        'whatsapp_estado',
        'revisada',
        'rechazo_motivo_id',
        'rechazo_observacion',
        'recordatorio_24h_enviado',
        'recordatorio_2h_enviado',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_ini' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'duracion_minutos' => 'integer',
        'total_citas' => 'integer',
        'dura_cita' => 'integer',
        'revisada' => 'boolean',
        'recordatorio_24h_enviado' => 'boolean',
        'recordatorio_2h_enviado' => 'boolean',
    ];

    protected $hidden = [
        'whatsapp_token',
    ];

    /**
     * Relación con el paciente asociado a la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con Paciente.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    /**
     * Relación con la sede donde se realiza la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigSede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    /**
     * Relación con el servicio de cita asociado.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigServicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function servicio()
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }

    /**
     * Relación alternativa con el servicio de cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigServicio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function servicioCita()
    {
        return $this->belongsTo(ConfigServicio::class, 'servicio_id');
    }

    /**
     * Relación con el producto médico asociado a la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigProducto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    /**
     * Relación con el usuario que registró la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con User.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function quienRegistra()
    {
        return $this->belongsTo(User::class, 'quien_registro_id');
    }

    /**
     * Relación con el usuario que confirmó la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con User.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function quienConfirma()
    {
        return $this->belongsTo(User::class, 'quien_confirma_id');
    }

    /**
     * Relación con el detalle de disponibilidad al que está asignada la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con AgendaDisponibilidaDetalle.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function agendaDisponibilidadDetalle()
    {
        return $this->belongsTo(AgendaDisponibilidaDetalle::class, 'agenda_disponibilidad_detalle_id');
    }

    /**
     * Relación con el motivo de rechazo asignado a la cita.
     *
     * @returns \Illuminate\Database\Eloquent\Relations\BelongsTo relación con ConfigCitaRechazoMotivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function rechazoMotivo()
    {
        return $this->belongsTo(ConfigCitaRechazoMotivo::class, 'rechazo_motivo_id');
    }

    /**
     * Obtiene las recomendaciones activas asociadas al producto de la cita.
     *
     * @returns \Illuminate\Support\Collection colección de recomendaciones o colección vacía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function recomendaciones()
    {
        if (! $this->producto_id) {
            return collect();
        }

        return ConfigRecomendacionProducto::where('producto_id', $this->producto_id)
            ->where('estado', true)
            ->get();
    }

    /**
     * Scope que filtra las citas con estado solicitada.
     *
     * @param  Builder  $query  builder de consulta.
     *
     * @returns \Illuminate\Database\Eloquent\Builder builder con el filtro aplicado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function scopeSolicitadas($query)
    {
        return $query->where('estado', 'solicitada');
    }

    /**
     * Scope que filtra las citas con estado asignada.
     *
     * @param  Builder  $query  builder de consulta.
     *
     * @returns \Illuminate\Database\Eloquent\Builder builder con el filtro aplicado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function scopeAsignadas($query)
    {
        return $query->where('estado', 'asignada');
    }

    /**
     * Scope que filtra las citas con estado confirmada.
     *
     * @param  Builder  $query  builder de consulta.
     *
     * @returns \Illuminate\Database\Eloquent\Builder builder con el filtro aplicado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    /**
     * Scope que filtra citas asignadas o confirmadas cuya fecha de inicio es mañana (para recordatorios).
     *
     * @param  Builder  $query  builder de consulta.
     *
     * @returns \Illuminate\Database\Eloquent\Builder builder con el filtro aplicado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-04-04
     */
    public function scopePendientesNotificar($query)
    {
        return $query->whereIn('estado', ['asignada', 'confirmada'])
            ->whereDate('fecha_hora_inicio', now()->addDay()->toDateString());
    }
}
