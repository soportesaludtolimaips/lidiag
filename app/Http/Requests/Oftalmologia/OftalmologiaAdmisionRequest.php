<?php

namespace App\Http\Requests\Oftalmologia;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para el registro de admisión en Oftalmología.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaAdmisionRequest extends FormRequest
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
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'tipo_recaudo_id' => 'nullable|integer|exists:config_tipos_recaudo,id',
            'atencion_id' => 'nullable|string|max:50',
            'paciente_id' => 'nullable|integer|exists:pacientes,id',
            'documento' => 'nullable|string|max:30',
            'num_docu' => 'nullable|string|max:30',
            'tipo_documento_id' => 'nullable|integer',
            'paciente_nombre' => 'nullable|string|max:200',
            'nombres' => 'nullable|string|max:100',
            'seg_nom' => 'nullable|string|max:100',
            'pri_ape' => 'nullable|string|max:100',
            'seg_ape' => 'nullable|string|max:100',
            'sexo' => 'nullable|string|in:M,F,O',
            'fecha_nacimiento' => 'nullable|date',
            'fec_naci' => 'nullable|date',
            'telefono' => 'nullable|string|max:50',
            'tel' => 'nullable|string|max:50',
            'cel' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:200',
            'medico_id' => 'nullable|integer|exists:users,id',
            'admin_salud_id' => 'nullable|integer|exists:config_admin_salud,id',
            'admin_salud' => 'nullable|string|max:150',
            'contrato' => 'nullable|string|max:150',
            'plan' => 'nullable|string|max:150',
            'ubicacion' => 'nullable|string|max:150',
            'cama' => 'nullable|string|max:80',
            'quien_ordena' => 'nullable|string|max:150',
            'quien_ordeno' => 'nullable|string|max:150',
            'fec_cita_sahi' => 'nullable|date',
            'fec_ingreso' => 'nullable|date',
            'productosAgenda' => 'sometimes|array',
        ];
    }

    /**
     * Nombres legibles para los atributos validados.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sede_id' => 'sede',
            'tipo_recaudo_id' => 'tipo de recaudo',
            'atencion_id' => 'número de atención',
            'paciente_id' => 'paciente',
            'documento' => 'documento del paciente',
            'paciente_nombre' => 'nombre del paciente',
        ];
    }
}
