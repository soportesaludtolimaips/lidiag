<?php

namespace App\Models\Citas;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'servicio_cita_id',
        'producto_id',
        'sede_id',
        'quien_registra_id',
        'quien_confirma_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'duracion_minutos',
        'estado',
        'numero_orden',
        'numero_autorizacion',
        'observaciones',
        'whatsapp_token',
        'whatsapp_mid',
        'whatsapp_estado',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'duracion_minutos' => 'integer',
        'estado' => 'string',
        'whatsapp_estado' => 'string',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function servicio()
    {
        return $this->belongsTo(ConfigServicioCita::class, 'servicio_cita_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function quienRegistra()
    {
        return $this->belongsTo(User::class, 'quien_registra_id');
    }

    public function quienConfirma()
    {
        return $this->belongsTo(User::class, 'quien_confirma_id');
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
