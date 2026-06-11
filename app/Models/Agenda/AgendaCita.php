<?php

namespace App\Models\Agenda;

use App\Models\Citas\ConfigCitaRechazoMotivo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaCita extends Model
{
    use HasFactory;

    protected $table = 'agenda_citas';

    protected $fillable = [
        'quien_registro_id',
        'quien_confirma_id',
        'paciente_id',
        'sede_id',
        'agenda_disponibilidad_detalle_id',
        'servicio_cita_id',
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
        'whatsapp_token',
        'whatsapp_mid',
        'whatsapp_estado',
        'revisada',
        'rechazo_motivo_id',
        'rechazo_observacion',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_ini' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'duracion_minutos' => 'integer',
        'total_citas' => 'integer',
        'dura_cita' => 'integer',
        'revisada' => 'boolean',
    ];

    protected $hidden = [
        'whatsapp_token',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ConfigServicioCita::class, 'servicio_cita_id');
    }

    public function servicioCita()
    {
        return $this->belongsTo(ConfigServicioCita::class, 'servicio_cita_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function quienRegistra()
    {
        return $this->belongsTo(User::class, 'quien_registro_id');
    }

    public function quienConfirma()
    {
        return $this->belongsTo(User::class, 'quien_confirma_id');
    }

    public function agendaDisponibilidadDetalle()
    {
        return $this->belongsTo(AgendaDisponibilidaDetalle::class, 'agenda_disponibilidad_detalle_id');
    }

    public function rechazoMotivo()
    {
        return $this->belongsTo(ConfigCitaRechazoMotivo::class, 'rechazo_motivo_id');
    }

    public function recomendaciones()
    {
        if (! $this->producto_id) {
            return collect();
        }

        return ConfigRecomendacionProducto::where('producto_id', $this->producto_id)
            ->where('estado', true)
            ->get();
    }

    public function scopeSolicitadas($query)
    {
        return $query->where('estado', 'solicitada');
    }

    public function scopeAsignadas($query)
    {
        return $query->where('estado', 'asignada');
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    public function scopePendientesNotificar($query)
    {
        return $query->whereIn('estado', ['asignada', 'confirmada'])
            ->whereDate('fecha_hora_inicio', now()->addDay()->toDateString());
    }
}
