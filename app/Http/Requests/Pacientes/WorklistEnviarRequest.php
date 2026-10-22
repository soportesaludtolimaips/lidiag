<?php

namespace App\Http\Requests\Pacientes;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para enviar un paciente admitido a la Modality Worklist.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class WorklistEnviarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar los datos antes de la validación asegurando que la sede exista si no vino en el request.
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->sede_id)) {
            $sedeId = session('sede_id')
                ?? auth()->user()?->sedes()?->first()?->id
                ?? ConfigSede::where('estado', true)->value('id');

            if ($sedeId) {
                $this->merge(['sede_id' => (int) $sedeId]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'paciente_id' => 'required|integer|exists:pacientes,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'producto_id' => 'required|integer|exists:config_productos,id',
            'accession_no' => 'required|string|max:50',
            'modality' => 'nullable|string|in:CR,DX',
            'station_aet' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El paciente es obligatorio.',
            'paciente_id.exists' => 'El paciente no existe en el sistema.',
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'producto_id.required' => 'Debe seleccionar el procedimiento de Rayos X a realizar.',
            'producto_id.exists' => 'El procedimiento seleccionado no es válido.',
            'accession_no.required' => 'El Accession Number es obligatorio.',
            'modality.in' => 'La modalidad debe ser CR o DX.',
        ];
    }
}
