<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para registro de pacientes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class PacienteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento_id' => 'nullable|integer|exists:config_tipos_documentos,id',
            'num_docu' => 'required|string|max:15|unique:pacientes,num_docu',
            'nombres' => 'required|string|max:150',
            'seg_nom' => 'nullable|string|max:70',
            'pri_ape' => 'nullable|string|max:70',
            'seg_ape' => 'nullable|string|max:70',
            'sexo' => 'nullable|string|in:M,F,O',
            'fec_naci' => 'nullable|date',
            'tel' => 'nullable|string|max:15',
            'cel' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'direccion' => 'nullable|string|max:70',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento_id.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento_id.exists' => 'El tipo de documento no es válido.',
            'num_docu.required' => 'El número de documento es obligatorio.',
            'num_docu.unique' => 'Este número de documento ya se encuentra registrado.',
            'nombres.required' => 'El primer nombre es obligatorio.',
            'pri_ape.required' => 'El primer apellido es obligatorio.',
            'sexo.required' => 'El sexo es obligatorio.',
            'sexo.in' => 'El sexo debe ser M (Masculino) o F (Femenino).',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
        ];
    }
}
