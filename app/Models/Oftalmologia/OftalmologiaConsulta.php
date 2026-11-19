<?php

namespace App\Models\Oftalmologia;

use App\Models\Configuracion\ConfigSede;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo para las consultas clínicas e historias de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Almacena la anamnesis, signos vitales y examen físico especializado (AV, MOE, PIO, Biomicroscopía, Fondo de ojo).
 * 2. Guarda el concepto clínico, plan y destino del paciente.
 * 3. Relaciona diagnósticos CIE-10, órdenes de procedimientos, fórmulas, remisión e incapacidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaConsulta extends Model
{
    use HasFactory;

    protected $table = "oftalmologia_consultas";

    protected $fillable = [
        "atencion_id",
        "medico_id",
        "sede_id",
        "paciente_id",
        "motivo_consulta",
        "enfermedad_actual",
        "antecedentes_medicos",
        "antecedentes_quirurgicos_oft",
        "antecedentes_alergicos",
        "antecedentes_otros",
        "revision_sistemas",
        "av_sc_od",
        "av_sc_oi",
        "av_cc_od",
        "av_cc_oi",
        "av_ph_od",
        "av_ph_oi",
        "motilidad_ocular",
        "pupilas",
        "biomicroscopia_od",
        "biomicroscopia_oi",
        "presion_intraocular_od",
        "presion_intraocular_oi",
        "fondo_ojo_od",
        "fondo_ojo_oi",
        "examen_fisico_general",
        "signos_fc",
        "signos_pas",
        "signos_pad",
        "signos_fr",
        "signos_spo2",
        "signos_peso",
        "signos_talla",
        "signos_imc",
        "concepto_plan",
        "destino_paciente",
        "estado_consulta",
        "fechor_finalizacion",
        "sahi_consulta_id",
    ];

    protected $casts = [
        "signos_peso" => "decimal:2",
        "signos_talla" => "decimal:2",
        "signos_imc" => "decimal:2",
        "fechor_finalizacion" => "datetime",
    ];

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(OftalmologiaAtencion::class, "atencion_id");
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(User::class, "medico_id");
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(ConfigSede::class, "sede_id");
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, "paciente_id");
    }

    public function diagnosticos(): HasMany
    {
        return $this->hasMany(OftalmologiaDiagnostico::class, "consulta_id");
    }

    public function procedimientos(): HasMany
    {
        return $this->hasMany(OftalmologiaProcedimiento::class, "consulta_id");
    }

    public function medicamentos(): HasMany
    {
        return $this->hasMany(OftalmologiaMedicamento::class, "consulta_id");
    }

    public function remision(): HasOne
    {
        return $this->hasOne(OftalmologiaRemision::class, "consulta_id");
    }

    public function incapacidad(): HasOne
    {
        return $this->hasOne(OftalmologiaIncapacidad::class, "consulta_id");
    }
}
