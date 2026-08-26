<?php

namespace App\Models\Consentimientos;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentimientoDocumento extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'consentimiento_documentos';

    protected $fillable = [
        'origen_tipo',
        'origen_id',
        'producto_id',
        'sede_id',
        'config_modulo_id',
        'plantilla_id',
        'plantilla_version',
        'plantilla_html',
        'contenido_renderizado',
        'paciente_id',
        'num_docu',
        'nombres',
        'apellidos',
        'vigente',
        'vigente_key',
        'estado',
        'requiere_firma_medico',
        'fecha_expiracion',
        'firmante_tipo',
        'acompanante_cedula',
        'acompanante_nombres',
        'acompanante_email',
        'acompanante_direccion',
        'acompanante_telefono',
        'firma_paciente_path',
        'firma_paciente_at',
        'ip_firma_paciente',
        'medico_id',
        'firma_medico_path',
        'firma_medico_at',
        'ip_firma_medico',
        'generado_por_id',
        'ip_generado',
        'anulada_por_id',
        'anulada_at',
        'anulacion_observacion',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'vigente' => 'boolean',
        'vigente_key' => 'integer',
        'requiere_firma_medico' => 'boolean',
        'fecha_expiracion' => 'datetime',
        'firma_paciente_at' => 'datetime',
        'firma_medico_at' => 'datetime',
        'anulada_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ConfigProducto::class, 'producto_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, 'sede_id');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ConfigModulo::class, 'config_modulo_id');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(ConsentimientoPlantilla::class, 'plantilla_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'medico_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_id');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ConsentimientoDocumentoToken::class, 'documento_id');
    }

    public function marcas(): HasMany
    {
        return $this->hasMany(ConsentimientoDocumentoMarca::class, 'documento_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(ConsentimientoDocumentoAdjunto::class, 'documento_id');
    }

    public function auditoria(): HasMany
    {
        return $this->hasMany(ConsentimientoDocumentoAuditoria::class, 'documento_id');
    }
}
