<?php

namespace App\Models\Consentimientos;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcografiaConsentimiento extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'ecografias_consentimientos';

    protected $fillable = [
        'ecografia_producto_id',
        'producto_id',
        'sede_id',
        'config_modulo_id',
        'plantilla_id',
        'plantilla_version',
        'plantilla_html',
        'vigente',
        'vigente_key',
        'estado',
        'firmante_tipo',
        'acompanante_cedula',
        'acompanante_nombres',
        'acompanante_email',
        'acompanante_direccion',
        'acompanante_telefono',
        'token_hash',
        'token_expires_at',
        'token_used_at',
        'ip_token',
        'firma_paciente_path',
        'firma_paciente_at',
        'ip_firma_paciente',
        'medico_id',
        'firma_medico_path',
        'firma_medico_at',
        'ip_firma_medico',
        'transcriptor_id',
        'ip_transcriptor',
        'anulada_por_id',
        'anulada_at',
        'anulacion_observacion',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'vigente_key' => 'integer',
        'token_expires_at' => 'datetime',
        'token_used_at' => 'datetime',
        'firma_paciente_at' => 'datetime',
        'firma_medico_at' => 'datetime',
        'anulada_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    public function ecografiaProducto()
    {
        return $this->belongsTo(EcografiaProducto::class, 'ecografia_producto_id');
    }

    public function producto()
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function sede()
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function modulo()
    {
        return $this->belongsTo(ConfigModulo::class, 'config_modulo_id');
    }

    public function plantilla()
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }

    public function transcriptor()
    {
        return $this->belongsTo(User::class, 'transcriptor_id');
    }

    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function anuladaPor()
    {
        return $this->belongsTo(User::class, 'anulada_por_id');
    }
}
