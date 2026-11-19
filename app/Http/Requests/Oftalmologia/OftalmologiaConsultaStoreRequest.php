<?php

namespace App\Http\Requests\Oftalmologia;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para el guardado de la consulta clínica de Oftalmología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaConsultaStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'atencion_id' => 'required|integer|exists:oftalmologia_atenciones,id',
            'motivo_consulta' => 'nullable|string',
            'enfermedad_actual' => 'nullable|string',
            'antecedentes_medicos' => 'nullable|string',
            'antecedentes_quirurgicos_oft' => 'nullable|string',
            'antecedentes_alergicos' => 'nullable|string',
            'antecedentes_otros' => 'nullable|string',
            'revision_sistemas' => 'nullable|string',

            // Examen Oftalmológico
            'av_sc_od' => 'nullable|string|max:50',
            'av_sc_oi' => 'nullable|string|max:50',
            'av_cc_od' => 'nullable|string|max:50',
            'av_cc_oi' => 'nullable|string|max:50',
            'av_ph_od' => 'nullable|string|max:50',
            'av_ph_oi' => 'nullable|string|max:50',
            'motilidad_ocular' => 'nullable|string',
            'pupilas' => 'nullable|string',
            'biomicroscopia_od' => 'nullable|string',
            'biomicroscopia_oi' => 'nullable|string',
            'presion_intraocular_od' => 'nullable|string',
            'presion_intraocular_oi' => 'nullable|string',
            'fondo_ojo_od' => 'nullable|string',
            'fondo_ojo_oi' => 'nullable|string',
            'examen_fisico_general' => 'nullable|string',

            // Signos vitales
            'signos_fc' => 'nullable|string|max:20',
            'signos_pas' => 'nullable|string|max:20',
            'signos_pad' => 'nullable|string|max:20',
            'signos_fr' => 'nullable|string|max:20',
            'signos_spo2' => 'nullable|string|max:20',
            'signos_peso' => 'nullable|numeric',
            'signos_talla' => 'nullable|numeric',
            'signos_imc' => 'nullable|numeric',

            // Concepto y Plan
            'concepto_plan' => 'nullable|string',
            'destino_paciente' => 'nullable|string|max:100',
            'finalizar' => 'sometimes|boolean',

            // Diagnósticos CIE-10
            'diagnosticos' => 'nullable|array',
            'diagnosticos.*.diagnostico_id' => 'nullable|integer|exists:config_diagnosticos,id',
            'diagnosticos.*.cod_cie10' => 'nullable|string|max:20',
            'diagnosticos.*.es_principal' => 'nullable|boolean',
            'diagnosticos.*.tipo_diagnostico' => 'nullable|string|in:IMPRESION_DIAGNOSTICA,CONFIRMADO_NUEVO,CONFIRMADO_REPETIDO',
            'diagnosticos.*.lateralidad' => 'nullable|string|in:NO_APLICA,OJO_DERECHO,OJO_IZQUIERDO,BILATERAL',
            'diagnosticos.*.observaciones' => 'nullable|string|max:255',

            // Procedimientos CUPS
            'procedimientos' => 'nullable|array',
            'procedimientos.*.producto_id' => 'required|integer|exists:config_productos,id',
            'procedimientos.*.cod_cups' => 'nullable|string|max:30',
            'procedimientos.*.nombre_procedimiento' => 'nullable|string|max:255',
            'procedimientos.*.cantidad' => 'nullable|integer|min:1',
            'procedimientos.*.justificacion_clinica' => 'nullable|string',
            'procedimientos.*.observaciones' => 'nullable|string',

            // Medicamentos
            'medicamentos' => 'nullable|array',
            'medicamentos.*.producto_id' => 'required|integer|exists:config_productos,id',
            'medicamentos.*.codigo_medicamento' => 'nullable|string|max:50',
            'medicamentos.*.nombre_medicamento' => 'nullable|string|max:255',
            'medicamentos.*.forma_farmaceutica' => 'nullable|string|max:100',
            'medicamentos.*.dosis' => 'nullable|string|max:100',
            'medicamentos.*.via_administracion' => 'nullable|string|max:100',
            'medicamentos.*.frecuencia_horario' => 'nullable|string|max:150',
            'medicamentos.*.duracion_tratamiento' => 'nullable|string|max:100',
            'medicamentos.*.cantidad_entregar' => 'nullable|integer|min:1',
            'medicamentos.*.indicaciones' => 'nullable|string',

            // Remisión
            'remision' => 'nullable|array',
            'remision.institucion_receptora' => 'nullable|string|max:200',
            'remision.especialidad_solicitada' => 'nullable|string|max:150',
            'remision.motivo_remision' => 'nullable|string|max:255',
            'remision.resumen_clinico' => 'nullable|string',
            'remision.observaciones' => 'nullable|string',

            // Incapacidad
            'incapacidad' => 'nullable|array',
            'incapacidad.fec_inicio' => 'nullable|date',
            'incapacidad.fec_fin' => 'nullable|date',
            'incapacidad.dias_incapacidad' => 'nullable|integer|min:1',
            'incapacidad.tipo_incapacidad' => 'nullable|string|max:100',
            'incapacidad.origen_incapacidad' => 'nullable|string|max:100',
            'incapacidad.empresa' => 'nullable|string|max:150',
            'incapacidad.ocupacion' => 'nullable|string|max:150',
            'incapacidad.telefono' => 'nullable|string|max:50',
            'incapacidad.es_prorroga' => 'nullable|boolean',
            'incapacidad.observaciones' => 'nullable|string',
        ];
    }
}
