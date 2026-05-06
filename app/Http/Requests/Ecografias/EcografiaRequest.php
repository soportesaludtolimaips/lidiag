<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EcografiaRequest extends FormRequest
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
            'atencion_id' => 'required|string|max:20',
            'paciente_id' => 'required|exists:pacientes,id',
            'usua_regis_id' => 'required|exists:users,id',
            'admin_salud_id' => 'required|exists:config_admin_salud,id',
            'fec_cita_sahi' => 'nullable|date',
            'fec_regis_cita_sahi' => 'nullable|date',
            'contrato' => 'nullable|string|max:100',
            'plan' => 'nullable|string|max:100',
            'quien_ordena' => 'nullable|string|max:100',
            'estado' => 'required|boolean',
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'atencion_id.required' => 'El campo atención es obligatorio.',
            'atencion_id.max' => 'El campo atención no debe exceder los 20 caracteres.',
            'paciente_id.required' => 'El campo paciente es obligatorio.',
            'paciente_id.exists' => 'El paciente seleccionado no es válido.',
            'usua_regis_id.required' => 'El campo usuario registrador es obligatorio.',
            'usua_regis_id.exists' => 'El usuario registrador seleccionado no es válido.',
            'admin_salud_id.required' => 'El campo administrador de salud es obligatorio.',
            'admin_salud_id.exists' => 'El administrador de salud seleccionado no es válido.',
            'fec_cita_sahi.date' => 'La fecha de cita debe ser una fecha válida.',
            'fec_regis_cita_sahi.date' => 'La fecha de registro de cita debe ser una fecha válida.',
            'contrato.max' => 'El campo contrato no debe exceder los 100 caracteres.',
            'plan.max' => 'El campo plan no debe exceder los 100 caracteres.',
            'quien_ordena.max' => 'El campo quien ordena no debe exceder los 100 caracteres.',
            'estado.required' => 'El campo estado es obligatorio.',
            'estado.boolean' => 'El campo estado debe ser verdadero o falso.',
        ];
    }
}
