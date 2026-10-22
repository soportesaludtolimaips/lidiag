<?php

namespace App\Http\Requests\Pacientes;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para consulta y filtrado de pacientes en admisión.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class PacienteIndexRequest extends FormRequest
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
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_documento' => 'nullable|string|max:30',
            'bus_nombre' => 'nullable|string|max:100',
            'bus_fech_ini' => 'nullable|date|required_with:bus_fecha_fin',
            'bus_fecha_fin' => 'nullable|date|required_with:bus_fech_ini|after_or_equal:bus_fech_ini',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria para verificar órdenes activas.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'bus_fecha_fin.after_or_equal' => 'La fecha fin no puede ser anterior a la fecha de inicio.',
        ];
    }
}
