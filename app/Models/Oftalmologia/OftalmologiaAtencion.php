<?php

namespace App\Models\Oftalmologia;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigTipoRecaudo;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo para las atenciones y admisiones del módulo de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Registra atenciones importadas de SAHI o creadas directamente (Particular / Cortesía).
 * 2. Asocia la sede, paciente, aseguradora, tipo de recaudo y médico asignado.
 * 3. Mantiene el estado de atención y sincronización con SAHI.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaAtencion extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_atenciones";

    protected $fillable = [
        "atencion_id",
        "sede_id",
        "paciente_id",
        "medico_id",
        "usua_regis_id",
        "admin_salud_id",
        "tipo_recaudo_id",
        "contrato",
        "plan",
        "ubicacion",
        "cama",
        "quien_ordena",
        "fec_cita_sahi",
        "fec_ingreso",
        "estado",
        "usua_anula_id",
        "fechor_anula",
        "motivo_anula",
        "estado_sahi",
        "fechor_envio_sahi",
        "error_envio_sahi",
    ];

    protected $casts = [
        "fec_cita_sahi" => "date",
        "fec_ingreso" => "datetime",
        "fechor_anula" => "datetime",
        "fechor_envio_sahi" => "datetime",
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, "sede_id");
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, "paciente_id");
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, "medico_id");
    }

    public function usuarioRegistrador(): BelongsTo
    {
        return $this->belongsTo(User::class, "usua_regis_id");
    }

    public function usuarioAnulador(): BelongsTo
    {
        return $this->belongsTo(User::class, "usua_anula_id");
    }

    public function adminSalud(): BelongsTo
    {
        return $this->belongsTo(ConfigAdminSalud::class, "admin_salud_id");
    }

    public function tipoRecaudo(): BelongsTo
    {
        return $this->belongsTo(ConfigTipoRecaudo::class, "tipo_recaudo_id");
    }

    public function consulta(): HasOne
    {
        return $this->hasOne(OftalmologiaConsulta::class, "atencion_id");
    }
}
