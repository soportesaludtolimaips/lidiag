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
            'orden_id' => 'required_without:accession_no|nullable|integer|exists:worklist_ordenes,id',
            'accession_no' => 'required_without:orden_id|nullable|string|max:64',
            'motivo' => 'nullable|string|max:250',
        ];
    }

    public function messages(): array
    {
        return [
            'orden_id.required_without' => 'Debe indicar el identificador de la orden o el Accession Number.',
            'orden_id.exists' => 'La orden de Worklist no existe en el sistema.',
            'accession_no.required_without' => 'Debe indicar el Accession Number o el ID de la orden.',
            'motivo.max' => 'El motivo de anulación no debe superar los 250 caracteres.',
        ];
    }
}
