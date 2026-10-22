<?php

namespace App\Http\Requests\Pacientes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para retirar/cancelar una orden de la Modality Worklist.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class WorklistCancelarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden_id' => 'required|integer|exists:worklist_ordenes,id',
            'motivo' => 'nullable|string|max:250',
        ];
    }

    public function messages(): array
    {
        return [
            'orden_id.required' => 'El identificador de la orden es obligatorio.',
            'orden_id.exists' => 'La orden de Worklist no existe.',
        ];
    }
}
