<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class ListarPendientesMedicoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para listar los estudios pendientes de un médico.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:users,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            'id.required' => 'El identificador del médico es obligatorio.',
            'id.exists' => 'El médico seleccionado no existe.',
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ];
    }
}
